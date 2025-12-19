<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use StellarSkirmish\GameConfig;
use StellarSkirmish\GameEngine;
use StellarSkirmish\GameState;

it('creates a new game via the Api', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/api/games', []);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.status', 'waiting')
                ->where('data.player_count', 2)
                ->missing('data.state')
                ->etc()
        );

    $gameId = $response->json('data.id');

    $game = Game::findOrFail($gameId);

    expect($game->creator->is($user))->toBeTrue();
    expect($game->status)->toBe('waiting');
    expect($game->player_count)->toBe(2);
    // State is not initialized until start; API hides it while waiting.
});

it('allows a player to play a card via the actions endpoint', function () {
    $user = User::factory()->create();

    $engine = new GameEngine();
    $config = GameConfig::standardTwoPlayer(seed: 123);
    $state  = $engine->startNewGame($config);

    $game = Game::create([
        'user_id'      => $user->id,
        'status'       => 'active',
        'player_count' => 2,
        'seed'         => 123,
        'state'        => $state->toArray(),
    ]);

    // Attach creator as participant player 1
    $game->addPlayer($user, 1);

    // Player 1 should have card 7 in hand before we play it
    expect($state->hands[1])->toContain(7);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->id}/actions", [
            'type'       => 'play_card',
            'card_value' => 7,
        ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) =>
        $json
            ->has('data.id')
            ->where('data.id', $game->id)
            ->has('data.state.hands.1') // player 1 hand exists
            ->etc()
        );

    $updated = Game::findOrFail($game->id);
    $updatedState = $updated->toGameState();

    // Card 7 should be gone from player 1's hand
    expect($updatedState->hands[1])->not()->toContain(7);

    // And currentPlays should reflect that player 1 has played this round
    // (Player 2 hasn't played yet, so the battle isn't resolved yet.)
    expect($updatedState->currentPlays[1])->toBe(7);
});

it('rejects invalid moves with a 422 status', function () {
    $user = User::factory()->create();

    $engine = new GameEngine();
    $config = GameConfig::standardTwoPlayer();
    $state  = $engine->startNewGame($config);

    $game = Game::create([
        'user_id'      => $user->id,
        'status'       => 'active',
        'player_count' => 2,
        'seed'         => null,
        'state'        => $state->toArray(),
    ]);

    // Attach creator as participant player 1
    $game->addPlayer($user, 1);

    // Player 1 definitely does not have card 67
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/games/{$game->id}/actions", [
            'type'       => 'play_card',
            'card_value' => 67,
        ])
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) =>
        $json
            ->has('message')
            ->has('error')
        );
});

