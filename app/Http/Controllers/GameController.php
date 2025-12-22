<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinGameRequest;
use App\Http\Requests\StartGameRequest;
use App\Http\Requests\StoreGameRequest;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use StellarSkirmish\GameConfig;
use StellarSkirmish\GameEngine;

class GameController extends Controller
{
    public function store(StoreGameRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $playerCount = $validated['player_count'] ?? 2;
        $seed = $validated['seed'] ?? null;
        $useCorporations = $validated['use_corporations'] ?? true;
        $useMercenaries = $validated['use_mercenaries'] ?? false;
        $usePlanetAbilities = $validated['use_planet_abilities'] ?? false;
        $useAdmirals = $validated['use_admirals'] ?? false;
        $visibility = $validated['visibility'] ?? 'private';
        if ($visibility === 'friends') {
            // MVP: treat friends as private
            $visibility = 'private';
        }

        // Do not start engine yet; create waiting game/lobby.
        // Generate invite code for private visibility.
        $inviteCode = $visibility === 'private' ? strtoupper(str()->random(6)) : null;

        $game = Game::create([
            'user_id' => $request->user()->id,
            'status' => 'waiting',
            'player_count' => $playerCount,
            'seed' => $seed,
            'visibility' => $visibility,
            'invite_code' => $inviteCode,
            'config' => [
                'seed' => $seed,
                'use_corporations' => $useCorporations,
                'use_mercenaries' => $useMercenaries,
                'use_planet_abilities' => $usePlanetAbilities,
                'use_admirals' => $useAdmirals,
            ],
            // Keep state as an empty array until start to satisfy non-null DB JSON column.
            'state' => [],
        ]);

        // Attach creator as player 1 in the pivot
        $game->addPlayer($request->user(), 1);

        return response()->json([
            'data' => [
                'id' => $game->id,
                'status' => $game->status,
                'player_count' => $game->player_count,
                'seed' => $game->seed,
                'visibility' => $game->visibility,
                'config' => $game->config,
                'invite_code' => $inviteCode,
            ],
        ], 201);
    }

    public function show(Game $game): JsonResponse
    {
        $data = [
            'id' => $game->id,
            'status' => $game->status,
            'player_count' => $game->player_count,
            'seed' => $game->seed,
            'visibility' => $game->visibility,
            'config' => $game->config,
        ];

        if (in_array($game->status, ['active', 'completed'], true)) {
            $data['state'] = $game->state;
        }

        return response()->json(['data' => $data]);
    }

    public function index(): JsonResponse
    {
        $user = request()->user();
        $query = Game::query()->with('players');

        if (request()->boolean('mine')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('players', fn ($p) => $p->where('users.id', $user->id));
            });
        }

        if (request()->boolean('joinable')) {
            $query->where('status', 'waiting')
                ->where('visibility', 'public')
                ->whereDoesntHave('players', function ($p) {
                    // ensure room for second player
                })
                ->whereRaw('(select count(*) from game_user where game_user.game_id = games.id) < 2');
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $games = $query->latest()->limit(50)->get()->map(function (Game $g) {
            return [
                'id' => $g->id,
                'status' => $g->status,
                'player_count' => $g->player_count,
                'seed' => $g->seed,
                'visibility' => $g->visibility,
                'config' => $g->config,
                'state' => in_array($g->status, ['active', 'completed'], true) ? $g->state : null,
            ];
        });

        return response()->json(['data' => $games]);
    }

    public function join(JoinGameRequest $request, Game $game): JsonResponse
    {
        $user = $request->user();

        if ($game->visibility === 'private') {
            $code = $request->validated()['invite_code'] ?? null;
            if ($code === null || ! hash_equals($game->invite_code ?? '', $code)) {
                return response()->json(['message' => 'Invite code required.'], 403);
            }
        }

        // Join as next index (2 for MVP)
        $game->addPlayer($user, 2);

        return response()->json(['data' => [
            'id' => $game->id,
            'status' => $game->status,
            'player_count' => $game->player_count,
            'seed' => $game->seed,
            'visibility' => $game->visibility,
            'config' => $game->config,
        ]]);
    }

    public function leave(Game $game): JsonResponse
    {
        $user = request()->user();

        $this->authorize('leave', $game);

        $index = $game->playerIndexFor($user);
        if ($index === null) {
            return response()->json(['message' => 'You are not in this game.'], 403);
        }

        if ($game->status === 'waiting') {
            // Detach the player
            $game->players()->detach($user->id);

            // If creator leaves and no other players remain, delete the game
            if ($game->creator->is($user) && $game->players()->count() === 0) {
                $game->delete();

                return response()->json(['data' => null]);
            }

            return response()->json(['data' => [
                'id' => $game->id,
                'status' => $game->status,
            ]]);
        }

        // Active game: resignation/forfeit
        if ($game->status === 'active') {
            $config = $game->config ?? [];
            $config['resignation'] = [
                'user_id' => $user->id,
                'at' => now()->toISOString(),
            ];
            $game->config = $config;
            $game->status = 'completed';
            $game->save();

            return response()->json(['data' => [
                'id' => $game->id,
                'status' => $game->status,
            ]]);
        }

        return response()->json(['message' => 'Cannot leave this game now.'], 409);
    }

    public function start(StartGameRequest $request, Game $game): JsonResponse
    {
        if ($game->playersCount() !== 2) {
            return response()->json(['message' => 'Exactly two players are required to start.'], 422);
        }

        // Build engine config
        if ($game->player_count === 2 && method_exists(GameConfig::class, 'standardTwoPlayer')) {
            $config = GameConfig::standardTwoPlayer(seed: $game->seed);
        } else {
            $config = new GameConfig(
                playerCount: $game->player_count,
                planets: \StellarSkirmish\Planet::defaultDeck($game->seed),
                fleetValues: range(1, 15),
                seed: $game->seed,
            );
        }

        $engine = new GameEngine;
        $state = $engine->startNewGame($config);

        $game->status = 'active';
        $game->state = $state->toArray();
        $game->save();

        return response()->json([
            'data' => [
                'id' => $game->id,
                'status' => $game->status,
                'player_count' => $game->player_count,
                'seed' => $game->seed,
                'visibility' => $game->visibility,
                'config' => $game->config,
                'state' => $state->toArray(),
            ],
        ]);
    }
}
