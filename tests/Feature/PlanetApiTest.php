<?php

use App\Models\Planet;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns a list of planets belonging to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userPlanets = Planet::factory()->count(3)->create();
    $otherUserPlanets = Planet::factory()->count(2)->create();

    $user->planets()->attach($userPlanets);
    $otherUser->planets()->attach($otherUserPlanets);

    Sanctum::actingAs($user, ['games:read']);

    $response = $this->getJson('/api/planets');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'flavor',
                    'type',
                    'class',
                    'victory_point_value',
                    'filename',
                    'is_standard',
                    'is_purchasable',
                    'is_promotional',
                    'is_custom',
                ],
            ],
        ]);

    foreach ($userPlanets as $planet) {
        $response->assertJsonFragment(['id' => $planet->id]);
    }

    foreach ($otherUserPlanets as $planet) {
        $response->assertJsonMissing(['id' => $planet->id]);
    }
});

it('requires authentication to access planets', function () {
    $response = $this->getJson('/api/planets');

    $response->assertUnauthorized();
});
