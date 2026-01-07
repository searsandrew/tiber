<?php

use App\Models\Planet;
use Database\Seeders\PlanetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use StellarSkirmish\PlanetAbilityType;
use StellarSkirmish\PlanetClass;

uses(RefreshDatabase::class);

it('seeds default planets', function () {
    $this->seed(PlanetSeeder::class);

    expect(Planet::count())->toBe(15)
        ->and(Planet::where('is_standard', true)->count())->toBe(15);

    $levo = Planet::where('name', 'Levo')->first();
    expect($levo->type)->toBeNull()
        ->and($levo->class)->toBe(PlanetClass::TradePostColony);
});

it('can add a planet via command', function () {
    $this->artisan('app:add-planet', [
        'name' => 'Mustafar',
        'flavor' => 'A tiny volcanic planet.',
        'type' => 'double_next_planet_no_combat',
        'class' => 'mining_colony',
        'vp' => 7,
        'filename' => 'mustafar.png',
        '--standard' => true,
    ])
        ->expectsOutputToContain("Planet 'Mustafar' added successfully")
        ->assertExitCode(0);

    $planet = Planet::where('name', 'Mustafar')->first();

    expect($planet)->not->toBeNull()
        ->and($planet->flavor)->toBe('A tiny volcanic planet.')
        ->and($planet->type)->toBe(PlanetAbilityType::DoubleNextPlanetNoCombat)
        ->and($planet->class)->toBe(PlanetClass::MiningColony)
        ->and($planet->victory_point_value)->toBe(7)
        ->and($planet->is_standard)->toBeTrue();
});

it('fails to add a planet without source flags', function () {
    $this->artisan('app:add-planet', [
        'name' => 'Nowhere',
        'flavor' => 'Void.',
        'type' => 'none',
        'class' => 'mining_colony',
        'vp' => 0,
        'filename' => 'nowhere.png',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('You must specify at least one source flag: --standard, --purchasable, --promotional, or --custom')
        ->assertExitCode(1);

    expect(Planet::where('name', 'Nowhere')->exists())->toBeFalse();
});

it('can add a planet via interactive prompts', function () {
    $typeOptions = [
        'none' => 'None',
        ...collect(PlanetAbilityType::cases())->mapWithKeys(fn (PlanetAbilityType $t) => [$t->value => str($t->value)->replace('_', ' ')->title()])->toArray(),
    ];
    $classOptions = collect(PlanetClass::cases())->mapWithKeys(fn (PlanetClass $c) => [$c->value => str($c->value)->replace('_', ' ')->title()])->toArray();

    $this->artisan('app:add-planet')
        ->expectsQuestion('What is the name of the planet?', 'Mustafar')
        ->expectsQuestion('What is the flavor text for the planet?', 'A tiny volcanic planet.')
        ->expectsChoice('What type of ability does the planet have?', 'double_next_planet_no_combat', $typeOptions)
        ->expectsChoice('What is the class of the planet?', 'mining_colony', $classOptions)
        ->expectsQuestion('What is the victory point value?', '7')
        ->expectsQuestion('What is the filename for the planet image?', 'mustafar.png')
        ->expectsChoice('What are the sources for this planet?', ['standard'], [
            'standard' => 'Standard',
            'purchasable' => 'Purchasable',
            'promotional' => 'Promotional',
            'custom' => 'Custom',
        ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('planets', [
        'name' => 'Mustafar',
        'type' => 'double_next_planet_no_combat',
        'class' => 'mining_colony',
        'is_standard' => true,
        'is_purchasable' => false,
        'is_promotional' => false,
        'is_custom' => false,
    ]);
});

it('can add a promotional planet via command', function () {
    $this->artisan('app:add-planet', [
        'name' => 'Promo Planet',
        'flavor' => 'Limited edition.',
        'type' => 'none',
        'class' => 'station',
        'vp' => 10,
        'filename' => 'promo.png',
        '--promotional' => true,
    ])
        ->expectsOutputToContain("Planet 'Promo Planet' added successfully")
        ->assertExitCode(0);

    $planet = Planet::where('name', 'Promo Planet')->first();

    expect($planet)->not->toBeNull()
        ->and($planet->is_promotional)->toBeTrue()
        ->and($planet->is_purchasable)->toBeFalse();
});
