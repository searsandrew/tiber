<?php

namespace App\Console\Commands;

use App\Models\Planet;
use Illuminate\Console\Command;
use StellarSkirmish\PlanetAbilityType;
use StellarSkirmish\PlanetClass;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddPlanetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-planet {name?} {flavor?} {type?} {class?} {vp?} {filename?} {--standard} {--purchasable} {--promotional} {--custom}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new planet to the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name') ?? text(
            label: 'What is the name of the planet?',
            placeholder: 'e.g. Mustafar',
            required: true
        );

        $flavor = $this->argument('flavor') ?? text(
            label: 'What is the flavor text for the planet?',
            placeholder: 'e.g. A volcanic world.',
            required: true
        );

        $type = $this->argument('type') ?? select(
            label: 'What type of ability does the planet have?',
            options: [
                'none' => 'None',
                ...collect(PlanetAbilityType::cases())->mapWithKeys(fn (PlanetAbilityType $t) => [$t->value => str($t->value)->replace('_', ' ')->title()])->toArray(),
            ],
            default: 'none'
        );

        $type = $type === 'none' ? null : $type;

        $class = $this->argument('class') ?? select(
            label: 'What is the class of the planet?',
            options: collect(PlanetClass::cases())->mapWithKeys(fn (PlanetClass $c) => [$c->value => str($c->value)->replace('_', ' ')->title()])->toArray(),
            default: PlanetClass::TradePostColony->value
        );

        $vp = $this->argument('vp') ?? text(
            label: 'What is the victory point value?',
            placeholder: 'e.g. 5',
            validate: fn (string $value) => match (true) {
                ! is_numeric($value) => 'The victory point value must be a number.',
                (int) $value < 0 => 'The victory point value must be at least 0.',
                default => null,
            },
            required: true
        );

        $filename = $this->argument('filename') ?? text(
            label: 'What is the filename for the planet image?',
            placeholder: 'e.g. mustafar.png',
            required: true
        );

        $isStandard = $this->option('standard');
        $isPurchasable = $this->option('purchasable');
        $isPromotional = $this->option('promotional');
        $isCustom = $this->option('custom');

        if (! $isStandard && ! $isPurchasable && ! $isPromotional && ! $isCustom) {
            if ($this->option('no-interaction')) {
                $this->error('You must specify at least one source flag: --standard, --purchasable, --promotional, or --custom');

                return 1;
            }

            $sources = multiselect(
                label: 'What are the sources for this planet?',
                options: [
                    'standard' => 'Standard',
                    'purchasable' => 'Purchasable',
                    'promotional' => 'Promotional',
                    'custom' => 'Custom',
                ],
                required: true
            );

            $isStandard = in_array('standard', $sources);
            $isPurchasable = in_array('purchasable', $sources);
            $isPromotional = in_array('promotional', $sources);
            $isCustom = in_array('custom', $sources);
        }

        $planet = Planet::query()->create([
            'name' => $name,
            'flavor' => $flavor,
            'type' => $type,
            'class' => $class,
            'victory_point_value' => (int) $vp,
            'filename' => $filename,
            'is_standard' => $isStandard,
            'is_purchasable' => $isPurchasable,
            'is_promotional' => $isPromotional,
            'is_custom' => $isCustom,
        ]);

        info("Planet '{$planet->name}' added successfully with ID: {$planet->id}");

        return 0;
    }
}
