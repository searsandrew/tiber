<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameActionRequest;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use StellarSkirmish\GameEngine;

class GameActionController extends Controller
{
    public function store(StoreGameActionRequest $request, Game $game): JsonResponse
    {
        if ($game->status !== 'active') {
            return response()->json([
                'message' => 'Game is not active.',
            ], 409);
        }

        $validated = $request->validated();

        $playerIndex = $game->playerIndexFor($request->user());
        if ($playerIndex === null) {
            return response()->json([
                'message' => 'You are not a participant in this game.',
            ], 403);
        }

        $engine = new GameEngine;
        $state = $game->toGameState();

        if ($state->gameOver) {
            return response()->json([
                'message' => 'Game is already over.',
            ], 422);
        }

        try {
            switch ($validated['type']) {
                case 'play_card':
                    $cardValue = (int) $validated['card_value'];
                    $state = $engine->playCard($state, $playerIndex, $cardValue);
                    break;
                case 'play_mercenary':
                    $mercenaryId = $validated['mercenary_id'];
                    $state = $engine->playMercenary($state, $playerIndex, $mercenaryId);
                    break;
            }
        } catch (\Throwable $e) {
            // @todo: refactor to capture specific engine exceptions
            return response()->json([
                'message' => 'Invalid action.',
                'error' => $e->getMessage(),
            ], 422);
        }

        $game->updateFromGameState($state);

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
