<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StellarSkirmish\GameConfig;
use StellarSkirmish\GameEngine;

class GameController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_count'  => ['sometimes', 'integer', 'min:2'],
            'seed'          => ['sometimes', 'integer'],
        ]);

        $playerCount    = $validated['player_count'] ?? 2;
        $seed           = $validated['seed'] ?? null;

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

        $engine = new GameEngine();
        $gameState = $engine->startNewGame($config);

        $game = Game::create([
            'user_id'   => $request->user()->id,
            'status'    => 'active',
            'player_count' => $playerCount,
            'seed'      => $seed,
            'state'     => $gameState->toArray(),
        ]);

        return response()->json([
            'data' => [
                'id'        => $game->id,
                'status'    => $game->status,
                'player_count' => $game->player_count,
                'seed'      => $game->seed,
                'state'     => $gameState->toArray(),
            ],
        ], 201);
    }

    public function show(Game $game) : JsonResponse
    {
        return response()->json([
            'data' => [
                'id'        => $game->id,
                'status'    => $game->status,
                'player_count' => $game->player_count,
                'seed'      => $game->seed,
                'state'     => $game->state,
            ],
        ]);
    }
}
