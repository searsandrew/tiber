<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StellarSkirmish\GameEngine;

class GameActionController extends Controller
{
    public function store(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'player_id'     => ['required', 'exists:users,id'],
            'type'          => ['required', 'string', 'in:play_card,play_mercenary'],
            'card_value'    => ['required_if:type,play_card', 'integer', 'min:-5', 'max:15'],
            'mercenary_id'  => ['required_if:type,play_mercenary', 'exists:mercenaries,id'],
        ]);

        $playerId = (int) $validated['player_id'];

        $engine = new GameEngine();
        $state = $game->toGameState();

        if ($state->gameOver) {
            return response()->json([
                'message' => 'Game is already over.',
            ], 422);
        }

        try {
            switch ($validated['type']) {
                case 'play_card':
                    $cardValue  = (int) $validated['card_value'];
                    $state      = $engine->playCard($state, $playerId, $cardValue);
                    break;
                case 'play_mercenary':
                    $mercenaryId = $validated['mercenary_id'];
                    $state       = $engine->playMercenary($state, $playerId, $mercenaryId);
                    break;
            }
        } catch (\Throwable $e) {
            // @todo: refactor to capture specific engine exceptions
            return response()->json([
                'message'   => 'Invalid action.',
                'error'     => $e->getMessage(),
            ], 422);
        }

        $game->updateFromGameState($state);

        return response()->json([
            'data' => [
                'id'        => $game->id,
                'status'    => $game->status,
                'player_count' => $game->player_count,
                'seed'      => $game->seed,
                'state'     => $state->toArray(),
            ],
        ]);
    }
}
