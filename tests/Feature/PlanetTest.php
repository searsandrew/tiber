<?php

use App\Models\Planet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use StellarSkirmish\Planet as StellarPlanet;
use StellarSkirmish\PlanetAbility;
use StellarSkirmish\PlanetAbilityType;
use StellarSkirmish\PlanetClass;

uses(RefreshDatabase::class);

it('can create a planet', function () {
    $planet = Planet::factory()->create([
        'name' => 'Tatooine',
        'flavor' => 'A desert world.',
        'type' => PlanetAbilityType::DoubleNextPlanetNoCombat,
        'class' => PlanetClass::TradePostColony,
        'victory_point_value' => 5,
        'filename' => 'tatooine.png',
        'is_standard' => true,
    ]);

    expect($planet->name)->toBe('Tatooine')
        ->and($planet->flavor)->toBe('A desert world.')
        ->and($planet->type)->toBe(PlanetAbilityType::DoubleNextPlanetNoCombat)
        ->and($planet->class)->toBe(PlanetClass::TradePostColony)
        ->and($planet->victory_point_value)->toBe(5)
        ->and($planet->filename)->toBe('tatooine.png')
        ->and($planet->is_standard)->toBeTrue()
        ->and($planet->is_purchasable)->toBeFalse()
        ->and($planet->is_promotional)->toBeFalse()
        ->and($planet->is_custom)->toBeFalse()
        ->and($planet->id)->toBeString()->toHaveLength(26);
});

it('can convert to stellar skirmish planet', function () {
    $planet = Planet::factory()->create([
        'name' => 'Tatooine',
        'victory_point_value' => 5,
        'class' => PlanetClass::TradePostColony,
        'type' => PlanetAbilityType::DoubleNextPlanetNoCombat,
    ]);

    $stellarPlanet = $planet->toStellarSkirmishPlanet();

    expect($stellarPlanet)->toBeInstanceOf(StellarPlanet::class)
        ->and($stellarPlanet->id)->toBe($planet->id)
        ->and($stellarPlanet->victoryPoints)->toBe(5)
        ->and($stellarPlanet->name)->toBe('Tatooine')
        ->and($stellarPlanet->planetClass)->toBe(PlanetClass::TradePostColony)
        ->and($stellarPlanet->abilities)->toHaveCount(1)
        ->and($stellarPlanet->abilities[0])->toBeInstanceOf(PlanetAbility::class)
        ->and($stellarPlanet->abilities[0]->type)->toBe(PlanetAbilityType::DoubleNextPlanetNoCombat);
});

it('can associate planets with users', function () {
    $user = User::factory()->create();
    $planet = Planet::factory()->purchasable()->create();

    $user->planets()->attach($planet);

    expect($user->planets)->toHaveCount(1)
        ->and($user->planets->first()->id)->toBe($planet->id);

    expect($planet->users)->toHaveCount(1)
        ->and($planet->users->first()->id)->toBe($user->id);
});

it('supports different planet sources', function () {
    $standard = Planet::factory()->standard()->create();
    $purchasable = Planet::factory()->purchasable()->create();
    $promotional = Planet::factory()->promotional()->create();
    $custom = Planet::factory()->custom()->create();

    expect($standard->is_standard)->toBeTrue()
        ->and($purchasable->is_purchasable)->toBeTrue()
        ->and($promotional->is_promotional)->toBeTrue()
        ->and($custom->is_custom)->toBeTrue();
});
