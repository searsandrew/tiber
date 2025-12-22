<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameActionRequest;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use StellarSkirmish\Exceptions\GameOver;
use StellarSkirmish\Exceptions\InvalidMove;
use StellarSkirmish\GameEngine;

class GameActionController extends Controller
{
    public function store(StoreGameActionRequest $request, Game $game): JsonResponse
    {
        $validated = $request->validated();

        $playerIndex = $game->playerIndexFor($request->user());

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
        } catch (GameOver $e) {
            return response()->json([
                'message' => 'The game is over.',
                'error' => $e->getMessage(),
            ], 422);
        } catch (InvalidMove $e) {
            return response()->json([
                'message' => 'That move is not valid.',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
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
