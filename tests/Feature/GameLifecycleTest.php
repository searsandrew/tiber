<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

function apiActingAs(User $user, array $abilities = ['games:read', 'games:write']): void
{
    Sanctum::actingAs($user, $abilities);
}

it('creates a waiting game and returns invite_code for private visibility', function () {
    $user = User::factory()->create();

    apiActingAs($user, ['games:write']);

    $response = $this->postJson('/api/games', [
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
    apiActingAs($owner, ['games:write']);

    $create = $this->postJson('/api/games', [
        'visibility' => 'public',
        'player_count' => 2,
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // Join as second player
    apiActingAs($joiner, ['games:write']);

    $this->postJson("/api/games/{$gameId}/join", [])
        ->assertOk();

    // Game now full; third user cannot join
    apiActingAs($extra, ['games:write']);

    $this->postJson("/api/games/{$gameId}/join", [])
        ->assertForbidden();
});

it('requires invite_code to join a private waiting game', function () {
    [$owner, $friend] = User::factory()->count(2)->create();

    // Create a private game (owner auto-joined as player 1)
    apiActingAs($owner, ['games:write']);

    $create = $this->postJson('/api/games', [
        'visibility' => 'private',
    ])->assertCreated();

    $gameId = $create->json('data.id');
    $code = $create->json('data.invite_code');

    // Missing code -> forbidden
    apiActingAs($friend, ['games:write']);

    $this->postJson("/api/games/{$gameId}/join", [])
        ->assertForbidden();

    // Wrong code -> forbidden
    $this->postJson("/api/games/{$gameId}/join", ['invite_code' => Str::upper(Str::random(6))])
        ->assertForbidden();

    // Correct code -> join ok
    $this->postJson("/api/games/{$gameId}/join", ['invite_code' => $code])
        ->assertOk();
});

it('starts a game only when exactly two players have joined', function () {
    [$owner, $friend] = User::factory()->count(2)->create();

    apiActingAs($owner, ['games:write']);

    $create = $this->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // Cannot start with less than two
    $this->postJson("/api/games/{$gameId}/start")
        ->assertStatus(422);

    // Join second player
    apiActingAs($friend, ['games:write']);

    $this->postJson("/api/games/{$gameId}/join")
        ->assertOk();

    // Only owner can start (should be enforced by policy/authorization)
    $this->postJson("/api/games/{$gameId}/start")
        ->assertForbidden();

    // Start now works
    apiActingAs($owner, ['games:write']);

    $this->postJson("/api/games/{$gameId}/start")
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

    apiActingAs($owner, ['games:write']);

    $create = $this->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    $gameId = $create->json('data.id');

    // While waiting, state is hidden
    apiActingAs($owner, ['games:read']);

    $this->getJson("/api/games/{$gameId}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->missing('data.state')
            ->etc()
        );

    // Join and start
    apiActingAs($friend, ['games:write']);
    $this->postJson("/api/games/{$gameId}/join")->assertOk();

    apiActingAs($owner, ['games:write']);
    $this->postJson("/api/games/{$gameId}/start")->assertOk();

    // After active, state present
    apiActingAs($owner, ['games:read']);

    $this->getJson("/api/games/{$gameId}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.state')
        );
});

it('indexes my games and public joinable games', function () {
    [$me, $other, $joiner] = User::factory()->count(3)->create();

    // My private waiting game
    apiActingAs($me, ['games:write']);
    $this->postJson('/api/games', [
        'visibility' => 'private',
    ])->assertCreated();

    // Someone else's public waiting game
    apiActingAs($other, ['games:write']);
    $this->postJson('/api/games', [
        'visibility' => 'public',
    ])->assertCreated();

    // Index as third user
    apiActingAs($joiner, ['games:read']);

    $this->getJson('/api/games?joinable=1')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
        );

    // Index my games
    apiActingAs($me, ['games:read']);

    $this->getJson('/api/games?mine=1')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
        );
});
