<?php

namespace App\Http\Controllers;

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

        if ($playerCount === 2 && method_exists(GameConfig::class, 'standardTwoPlayer')) {
            $config = GameConfig::standardTwoPlayer(seed: $seed);
        } else {
            $config = new GameConfig(
                playerCount: $playerCount,
                planets: \StellarSkirmish\Planet::defaultDeck($seed),
                fleetValues: range(1, 15),
                seed: $seed,
            );
        }

        $engine = new GameEngine;
        $gameState = $engine->startNewGame($config);

        $game = Game::create([
            'user_id' => $request->user()->id,
            'status' => 'active',
            'player_count' => $playerCount,
            'seed' => $seed,
            'visibility' => $validated['visibility'] ?? 'private',
            'config' => [
                'seed' => $config->seed,
                'use_corporations' => $useCorporations,
                'use_mercenaries' => $useMercenaries,
                'use_planet_abilities' => $usePlanetAbilities,
                'use_admirals' => $useAdmirals,
            ],
            'state' => $gameState->toArray(),
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
                'state' => $gameState->toArray(),
            ],
        ], 201);
    }

    public function show(Game $game): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $game->id,
                'status' => $game->status,
                'player_count' => $game->player_count,
                'seed' => $game->seed,
                'visibility' => $game->visibility,
                'config' => $game->config,
                'state' => $game->state,
            ],
        ]);
    }
}
