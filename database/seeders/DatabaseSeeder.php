<?php

namespace Database\Seeders;

use App\Models\Assignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $map = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 C3 C3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C4 C4 C3 C3 C3 C3 C3 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 D1 O1 C5 O1 O2
O2 C5 O3 C2 C5 S1 C3 C3 C3 C5 C3 C3 C3 C3 C3 C5 C5 C5 C5 O2
O2 C5 C5 C5 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 C3 C3 C3 C3 C3 C3 E4 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5
C5 C5 C3 C3 C4 C4 C4 C4 C3 C3 C3 C3 C3 C3 E8 O1 C3 C4 C4 C5
O2 C5 C3 C3 C4 C4 C4 C2 C3 C1 C3 C3 E3 C3 C1 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 C2 C3 O1 C3 C3 C3 C3 C3 C3 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C5 C4 O2
O2 C5 C3 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 O2
O2 C5 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $costs = [
            'kompas' => 100, 'zwOogHardware' => 50, 'kleurOogHardware' => 200, 'variabele' => 30,
            'stapVooruit' => 1, 'stapAchteruit' => 1, 'draaien' => 5, 'zwOog' => 10,
            'kleurOog' => 20, 'duwen' => 100, 'toewijzing' => 2, 'operatie' => 2,
            'vergelijking' => 2, 'zolang' => 50, 'als' => 40, 'opdracht' => 20, 'toekenning' => 10,
        ];

        foreach (range(11, 18) as $number) {
            Assignment::firstOrCreate(
                ['name' => sprintf('Zandbak #%03d', $number)],
                [
                    'description' => 'Bereik alle doelen in de juiste volgorde en houd zoveel mogelijk budget over.',
                    'map_definition' => $map,
                    'costs' => $costs,
                    'start_capital' => 2024,
                    'is_active' => true,
                ],
            );
        }
    }
}
