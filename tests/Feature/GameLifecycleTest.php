<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

it('creates a waiting game and returns invite_code for private visibility', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/games', [
            'visibility' => 'private',
        ]);

    $response
        ->assertCreated()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'waiting')
            ->where('data.visibility', 'private')
            ->missing('data.state')
            ->has('data.invite_code')
        );

    $game = Game::findOrFail($response->json('data.id'));
    expect($game->status)->toBe('waiting');
    // DB keeps an empty array for JSON column until start; API hides it.
    expect($game->visibility)->toBe('private');
});

it('allows joining a public waiting game and prevents joining when full', function () {
    [$owner, $joiner, $extra] = User::factory()->count(3)->create();

    // Create a public game
    $create = $this->actingAs($owner)->postJson('/api/games', [
        'visibility' => 'public',
        'player_count' => 2,
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // Join as second player
    $this->actingAs($joiner)
        ->postJson("/api/games/{$gameId}/join", [])
        ->assertOk();

    // Game now full; third user cannot join
    $this->actingAs($extra)
        ->postJson("/api/games/{$gameId}/join", [])
        ->assertForbidden();
});

it('requires invite_code to join a private waiting game', function () {
    [$owner, $friend] = User::factory()->count(2)->create();

    // Create a private game (owner auto-joined as player 1)
    $create = $this->actingAs($owner)->postJson('/api/games', [
        'visibility' => 'private',
    ])->assertCreated();

    $gameId = $create->json('data.id');
    $code = $create->json('data.invite_code');

    // Missing code -> forbidden
    $this->actingAs($friend)
        ->postJson("/api/games/{$gameId}/join", [])
        ->assertForbidden();

    // Wrong code -> forbidden
    $this->actingAs($friend)
        ->postJson("/api/games/{$gameId}/join", ['invite_code' => Str::upper(Str::random(6))])
        ->assertForbidden();

    // Correct code -> join ok
    $this->actingAs($friend)
        ->postJson("/api/games/{$gameId}/join", ['invite_code' => $code])
        ->assertOk();
});

it('starts a game only when exactly two players have joined', function () {
    [$owner, $friend] = User::factory()->count(2)->create();

    $create = $this->actingAs($owner)->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // Cannot start with less than two
    $this->actingAs($owner)
        ->postJson("/api/games/{$gameId}/start")
        ->assertStatus(422);

    // Join second player
    $this->actingAs($friend)
        ->postJson("/api/games/{$gameId}/join")
        ->assertOk();

    // Only owner can start
    $this->actingAs($friend)
        ->postJson("/api/games/{$gameId}/start")
        ->assertForbidden();

    // Start now works
    $started = $this->actingAs($owner)
        ->postJson("/api/games/{$gameId}/start")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('data.status', 'active')
            ->has('data.state')
        );

    $game = Game::findOrFail($gameId);
    expect($game->status)->toBe('active');
    expect($game->state)->not()->toBeNull();
});

it('hides state while waiting and exposes state after active', function () {
    [$owner, $friend] = User::factory()->count(2)->create();

    $create = $this->actingAs($owner)->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // While waiting, state is hidden
    $this->actingAs($owner)
        ->getJson("/api/games/{$gameId}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->missing('data.state')
            ->etc()
        );

    // Join and start
    $this->actingAs($friend)->postJson("/api/games/{$gameId}/join")->assertOk();
    $this->actingAs($owner)->postJson("/api/games/{$gameId}/start")->assertOk();

    // After active, state present
    $this->actingAs($owner)
        ->getJson("/api/games/{$gameId}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.state')
        );
});

it('indexes my games and public joinable games', function () {
    [$me, $other, $joiner] = User::factory()->count(3)->create();

    // My private waiting game
    $mine = $this->actingAs($me)->postJson('/api/games', [
        'visibility' => 'private',
    ])->assertCreated();

    // Someone else's public waiting game
    $public = $this->actingAs($other)->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    // Index as third user
    $this->actingAs($joiner)
        ->getJson('/api/games?joinable=1')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
        );

    // Index my games
    $this->actingAs($me)
        ->getJson('/api/games?mine=1')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
        );
});
