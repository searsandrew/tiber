<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use StellarSkirmish\Planet as StellarPlanet;
use StellarSkirmish\PlanetAbility;
use StellarSkirmish\PlanetAbilityType;
use StellarSkirmish\PlanetClass;

class Planet extends Model
{
    /** @use HasFactory<\Database\Factories\PlanetFactory> */
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'type' => PlanetAbilityType::class,
            'class' => PlanetClass::class,
            'is_standard' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_promotional' => 'boolean',
            'is_custom' => 'boolean',
            'victory_point_value' => 'integer',
        ];
    }

    public function toStellarSkirmishPlanet(): StellarPlanet
    {
        $abilities = [];

        if ($this->type) {
            $abilities[] = new PlanetAbility($this->type, []);
        }

        return new StellarPlanet(
            id: $this->id,
            victoryPoints: $this->victory_point_value,
            name: $this->name,
            planetClass: $this->class,
            abilities: $abilities,
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
