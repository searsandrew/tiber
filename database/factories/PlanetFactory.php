<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use StellarSkirmish\PlanetAbilityType;
use StellarSkirmish\PlanetClass;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Planet>
 */
class PlanetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'flavor' => $this->faker->sentence(),
            'type' => $this->faker->randomElement([null, ...PlanetAbilityType::cases()]),
            'class' => $this->faker->randomElement(PlanetClass::cases()),
            'victory_point_value' => $this->faker->numberBetween(1, 10),
            'filename' => $this->faker->word().'.png',
            'is_standard' => false,
            'is_purchasable' => false,
            'is_promotional' => false,
            'is_custom' => false,
        ];
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_standard' => true,
        ]);
    }

    public function purchasable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_purchasable' => true,
        ]);
    }

    public function promotional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_promotional' => true,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom' => true,
        ]);
    }
}
