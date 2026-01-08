<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use StellarSkirmish\GameConfig;
use StellarSkirmish\GameEngine;

it('creates a new game via the Api', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['games:write']);

    $response = $this->postJson('/api/games', []);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.id')
            ->where('data.status', 'waiting')
            ->where('data.player_count', 2)
            ->missing('data.state')
            ->etc()
        );

    $gameId = $response->json('data.id');

    $game = Game::findOrFail($gameId);

    expect($game->creator->is($user))->toBeTrue()
        ->and($game->status)->toBe('waiting')
        ->and($game->player_count)->toBe(2);
});

it('allows a player to play a card via the actions endpoint', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['games:write']);

    $engine = new GameEngine;
    $config = GameConfig::standardTwoPlayer(seed: 123);
    $state = $engine->startNewGame($config);

    $game = Game::create([
        'user_id' => $user->id,
        'status' => 'active',
        'player_count' => 2,
        'seed' => 123,
        'state' => $state->toArray(),
    ]);

    $game->addPlayer($user, 1);

    expect($state->hands[1])->toContain(7);

    $response = $this->postJson("/api/games/{$game->id}/actions", [
        'type' => 'play_card',
        'card_value' => 7,
    ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.id')
            ->where('data.id', $game->id)
            ->has('data.state.hands.1')
            ->etc()
        );

    $updated = Game::findOrFail($game->id);
    $updatedState = $updated->toGameState();

    expect($updatedState->hands[1])->not()->toContain(7);
    expect($updatedState->currentPlays[1])->toBe(7);
});

it('rejects invalid moves with a 422 status', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['games:write']);

    $engine = new GameEngine;
    $config = GameConfig::standardTwoPlayer();
    $state = $engine->startNewGame($config);

    $game = Game::create([
        'user_id' => $user->id,
        'status' => 'active',
        'player_count' => 2,
        'seed' => null,
        'state' => $state->toArray(),
    ]);

    $game->addPlayer($user, 1);

    $this->postJson("/api/games/{$game->id}/actions", [
        'type' => 'play_card',
        'card_value' => 67,
    ])
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('message')
            ->has('error')
        );
});

it('requires authentication to list games', function () {
    $this->getJson('/api/games')->assertUnauthorized();
});
