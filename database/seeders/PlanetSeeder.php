<?php

namespace Database\Seeders;

use App\Models\Planet;
use Illuminate\Database\Seeder;
use StellarSkirmish\PlanetClass;

class PlanetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Levo',
                'flavor' => 'An independent world that resisted joining the Rhyno Confederacy. Anybody is welcome at the tiny, but neutral, spaceport.',
                'type' => null,
                'class' => 'trade_post_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/levo.png',
                'is_standard' => true,
            ], [
                'name' => 'Lethe Prime',
                'flavor' => 'In a generational war with New Cydonia over water rights. Large fields and forests feed most of the sector and provides export revenue.',
                'type' => null,
                'class' => 'research_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/lethe-prime.png',
                'is_standard' => true,
            ], [
                'name' => 'New Cydonia',
                'flavor' => 'In a generational war with Lethe Prime over water rights, Astex Mining Corp has mined large fissures in the planet, poisoning the water.',
                'type' => null,
                'class' => 'mining_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/new-cydonia.png',
                'is_standard' => true,
            ], [
                'name' => 'Uppskeruenda',
                'flavor' => 'Pushed to the edge of viability, this planet was mined of a rare mineral until there was nothing left. They now eke out a trade post existence.',
                'type' => null,
                'class' => 'trade_post_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/uppskeruenda.png',
                'is_standard' => true,
            ], [
                'name' => 'Scrandy',
                'flavor' => 'A rich hydrogen/helium gas dwarf with a liquid ocean below its thick methane envelope. The Low pressure gas makes a unique biosphere.',
                'type' => null,
                'class' => 'research_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/scrandy.png',
                'is_standard' => true,
            ], [
                'name' => 'Infernus',
                'flavor' => 'An inhospitable, fiery planet, covered in lava as far as the eye can see. Contains vast mineral deposits under a thick, molten layer of lava.',
                'type' => null,
                'class' => 'mining_colony',
                'victory_point_value' => 1,
                'filename' => 'skirmish/infernus.png',
                'is_standard' => true,
            ], [
                'name' => 'Vanta',
                'flavor' => 'The surface absorbs all light, warming the subterranean colonies. Home to merchants, mercenaries, and marauders of all creeds.',
                'type' => null,
                'class' => 'trade_post_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/vanta.png',
                'is_standard' => true,
            ], [
                'name' => 'Teslaron',
                'flavor' => 'The unique blend of fluidic chemicals found in vast rivers offer the most efficient energy storage medium in the known universe.',
                'type' => null,
                'class' => 'research_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/teslaron.png',
                'is_standard' => true,
            ], [
                'name' => 'Khatri',
                'flavor' => 'Named for the renowned physicist; Or. Gian Khatri, the mineral enriched oceans provide an enhanced catalyst for the Khatri Engine.',
                'type' => null,
                'class' => 'mining_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/khatri.png',
                'is_standard' => true,
            ], [
                'name' => 'Tallon 7',
                'flavor' => 'Continuously ravaged by strong solar storms. The inhabitants have learned to use the storms to both power and defence.',
                'type' => null,
                'class' => 'trade_post_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/tallon-7.png',
                'is_standard' => true,
            ], [
                'name' => 'Raiu',
                'flavor' => 'Rich veins of a bioluminescent crystal are unique to the planet. The crystal is the largest Intergalactic Research Co project.',
                'type' => null,
                'class' => 'research_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/raiu.png',
                'is_standard' => true,
            ], [
                'name' => 'Lyyrah',
                'flavor' => 'Mined for the liquid metals that wind around the surface. The refined metals are crucial for the construction of starships.',
                'type' => null,
                'class' => 'mining_colony',
                'victory_point_value' => 2,
                'filename' => 'skirmish/lyyrah.png',
                'is_standard' => true,
            ], [
                'name' => 'Nu\'rexia',
                'flavor' => 'A gas giant with a surprisingly breathable atmosphere. Famous for its floating cities, luxury megaplexes, and premium casinos.',
                'type' => null,
                'class' => 'trade_post_colony',
                'victory_point_value' => 3,
                'filename' => 'skirmish/nu-rexia.png',
                'is_standard' => true,
            ], [
                'name' => 'Auea Prime',
                'flavor' => 'Headquarters of Intergalactic Research Co. Legend says this planet was named for the fair haired princess of the stars.',
                'type' => null,
                'class' => 'research_colony',
                'victory_point_value' => 3,
                'filename' => 'skirmish/auea-prime.png',
                'is_standard' => true,
            ], [
                'name' => 'Asher A',
                'flavor' => 'Named for a stellar prince. Deep veins of rare and exotic minerals keep prospectors busy. The planet maintains a temperate climate.',
                'type' => null,
                'class' => 'mining_colony',
                'victory_point_value' => 3,
                'filename' => 'skirmish/asher-a.png',
                'is_standard' => true,
            ]
        ];

        foreach ($rows as $row) {
            Planet::firstOrCreate(
                ['name' => $row['name']],
                $row
            );
        }
    }
}
