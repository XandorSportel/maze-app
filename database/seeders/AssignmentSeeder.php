<?php

namespace Database\Seeders;

use App\Models\Assignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $costOverrides = $this->costOverrides();

        foreach ($this->maps() as $number => $map) {
            Assignment::updateOrCreate(
                ['name' => sprintf('Sandbox #%03d', $number)],
                [
                    'description' => 'Bereik alle doelen in de juiste volgorde en houd zoveel mogelijk budget over.',
                    'map_definition' => $this->normalizeMap($map),
                    'costs' => [
                        ...config('glade.default_costs'),
                        ...($costOverrides[$number] ?? []),
                    ],
                    'start_capital' => 2024,
                    'is_custom' => false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function maps(): array
    {
        $maze03 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 C3 C3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C4 C4 C3 C3 C3 C3 C3 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C5 S1 C3 C3 C3 C3 C3 C3 C3 C3 C3 D1 C5 C5 C5 O2
O2 C5 C5 C5 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 C3 C3 C3 C3 C3 C4 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5
C5 C5 C3 C3 C4 C4 C4 C4 C4 C3 C3 C3 C3 C3 C3 O1 C3 C4 C4 C5
O2 C5 C3 C3 C4 C4 C4 C2 C3 C3 C3 C3 C3 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 C2 C3 O1 C3 C3 C3 C3 C3 C3 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C5 C4 O2
O2 C5 C3 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 O2
O2 C5 C5 R1 E8 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze09 = <<<'MAP'
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
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze11 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 D3 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 C3 C3 R0 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C4 C4 C3 C3 C3 C3 C3 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C2 C3 C3 C3 O3 C3 O3 C3 C4 C5 C5 O1 C5 O1 O2
O2 C5 O3 C2 C2 C2 C2 C2 C3 C3 R0 C3 C3 C4 C4 C5 C5 C5 C5 O2
O2 C5 C5 C5 C2 C3 C3 C3 C3 O3 D2 O3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 C3 C3 C3 C3 C3 C4 O3 C3 O3 C3 C3 C3 C3 C3 C5 C5 C5
C5 C5 C3 C3 C4 C4 C4 C4 C4 O3 E8 O3 C3 C3 C3 O1 C3 C4 C4 C5
O2 C5 C3 C3 C4 C4 C4 C2 C3 O1 C3 O1 C3 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 C2 C3 C3 R0 C3 C3 C4 C3 C3 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C3 C3 C3 O1 D1 O1 O3 O1 C4 C3 O3 C5 C4 O2
O2 C5 C3 R1 R1 R1 C3 C3 C3 O3 C3 C3 C3 C4 C4 O3 C3 O1 C5 O2
O2 C5 C5 R1 R1 R1 C3 C3 C3 O1 C3 O1 O3 O3 O3 C3 C3 C5 C5 O2
O2 O1 C5 R1 R1 R1 O1 C2 C3 C3 R0 C3 C3 C4 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 O1 O1 O1 O1 C3 O1 O1 O1 O1 O1 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 S0 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze12 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 D6 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 O1 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 C3 O1 C5 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 C3 C3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C8 R3 C8 C0 C8 C0 C8 C0 C8 C0 O1 O1 O1 C5 O1 O2
O2 C5 O3 C2 C0 C8 C0 D1 C0 C8 C0 C8 C0 C8 C5 C5 O1 C5 O1 O2
O2 C5 O3 C2 E5 C0 C8 C0 C8 R3 C8 C0 C8 C0 C3 C5 C5 C5 C5 O2
O2 C5 C5 C5 C0 C8 C0 C8 C0 C8 C0 C8 R1 C8 C3 C3 C3 C3 C5 O2
C5 C5 C5 D2 C8 C0 C8 C0 S0 C0 C8 C0 E7 C0 C3 E6 O1 C5 C5 C5
C5 C5 C3 C3 C0 C8 C0 C8 D3 C8 C0 C8 C0 C8 C3 C3 C3 C4 C4 C5
O2 C5 R3 E8 C8 C0 C8 C0 C8 C0 C8 C0 C8 C0 C3 O1 D5 C5 C4 O2
O2 C5 C3 C3 C0 C8 C0 C8 C0 C8 O1 C8 C0 C8 D4 C3 C3 C5 C4 O2
O2 C5 C3 C3 C8 C0 C8 C0 O1 C0 C8 C0 C8 C0 C3 C3 C3 R3 C4 O2
O2 C5 C3 R1 C0 R1 C0 C8 C0 C8 C0 C8 C0 C8 C3 R3 E9 O1 C5 O2
O2 C5 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze13 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 R0 D3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C4 O2 R0 E9 R0 O2 O2 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 O3 R1 C2 C3 C3 R0 R0 R0 B4 R0 O2 C3 C5 C5 O1 C5 O1 O2
O2 C5 O3 C2 C5 C5 C3 O2 O2 O2 O2 O2 C3 C3 C3 C5 C5 C5 C5 O2
O2 C5 C5 D2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 C3 R3 C3 C3 C3 C4 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 S3
C5 C5 C3 C4 C4 R1 C4 C4 C4 C3 O1 O1 C3 C3 C3 O1 C3 C4 C4 C5
O2 C5 C3 E2 C4 C4 R2 C2 C3 O1 C3 E6 O1 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C4 C4 C4 C4 C2 C3 O1 C3 C3 O1 C3 C3 C3 C3 C5 C4 O2
O2 C5 C3 C4 C4 C4 C3 C3 C3 O1 C3 C3 C3 O1 C3 C3 C3 C5 C4 O2
O2 C5 C3 R1 R3 R2 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 O2
O2 C5 C5 R2 D1 R3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R3 R2 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze14 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 D9 C5 C5 C5 C5 C5 C5 C5 C5 S1 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 C2 C2 C2 C4 O2 O1 C3 C3 C3 B2 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 C2 C2 C4 O2 C3 C3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 O3 C2 C2 C4 C4 C3 C3 C3 C3 C3 C3 B0 C5 C5 O1 C5 O1 O2
O2 C5 O3 C2 C2 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 C5 C5 O1 O2
O2 C5 O3 C2 C5 C3 C3 O3 O3 O3 O3 O3 O3 O3 C3 O1 C5 C5 C5 O2
O2 C5 C5 C5 C5 C3 C3 O3 C3 O3 C3 C3 C3 O3 C3 C3 C3 C3 D2 O2
C5 C5 C5 C3 C3 C3 C3 O3 C3 O3 O3 O3 C3 O3 C3 C3 C3 C5 C5 C0
C5 C5 C3 C3 C4 C4 C4 O3 C3 O3 O3 O3 C3 O3 C3 O1 C3 C4 C4 C0
O2 C5 C3 C3 C4 C4 C4 O3 C3 C3 C3 O3 C3 O3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 O3 O3 O1 O3 O3 O3 O3 C3 R0 C3 C5 C4 O2
O2 C5 C3 C3 D6 C4 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C5 C4 O2
O2 C5 C3 R1 C4 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 C5 O2
O2 C5 C5 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 O1 E7 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 D4 C3 C3 O1 O1 O1 C5 O2
O2 O1 E8 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C0 C0 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze15 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 C2 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 C5 O1 O1 C2 O3 O3 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C5 R0 R0 R0 R0 E5 O1 C3 C3 C3 C3 C5 D3 O1 O1 C5 O1 O2
O2 C5 O3 R0 R0 S1 R0 R0 C3 C3 C3 C3 C3 C3 D1 O1 O1 C5 O1 O2
O2 C5 O3 E4 R0 R0 R0 R0 O1 C3 C3 C3 C3 C3 C5 C5 O1 C5 O1 O2
O2 C5 O3 R0 R0 R0 R0 R0 O1 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5 O2
O2 C5 O3 R0 E3 R0 R0 E9 O2 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 O3 C4 O2 O2 O2 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5
C5 C5 C3 C3 C4 C4 C4 C4 C4 C3 C3 C3 C3 C3 C3 O1 C3 C4 C4 C5
O2 C5 C3 C3 C4 C4 C4 C2 C3 C3 C3 C3 C3 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 C2 C3 O1 C3 C3 C3 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C3 C3 C3 C3 C3 C3 C3 O1 D2 O1 C3 C5 C4 O2
O2 C5 C3 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 O2
O2 C5 C5 R1 E8 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze16 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O2
O2 E9 C5 O1 C2 C4 C4 O1 O2 C3 C3 C3 C5 C5 C5 C5 C5 O1 O1 O2
O2 D9 O1 C2 C2 C2 O2 O2 O1 C3 C3 C3 C3 C5 O1 O1 C5 C5 O1 O2
O2 C5 C2 C2 E4 C2 C4 C3 D7 C3 C3 C3 C3 C5 C5 O1 O1 C5 O1 O2
O2 C5 E5 D4 C2 C4 C4 C3 C3 C3 C3 C3 C3 C3 C5 O1 O1 C5 O1 O2
O2 C5 C2 C2 C2 C3 C3 D2 D5 C3 C3 C3 C3 C3 C5 C5 O1 C5 O1 O2
O2 C5 C5 C2 E7 S1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5 C5 O2
O2 C5 C5 E2 C5 C3 D1 E8 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O2
C5 C5 C5 C3 C3 C3 D3 C3 C4 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5
C5 C5 C3 C3 C4 C4 D6 C4 C4 C3 C3 C3 C3 C3 C3 O1 C3 C4 C4 C5
O2 C5 C3 C3 C4 C4 D8 C2 C3 C3 C3 C3 C3 C3 C3 O1 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C4 C2 C3 O1 C3 C3 C3 C3 C3 C3 C3 C5 C4 O2
O2 C5 C3 C3 C4 C4 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C5 C4 O2
O2 C5 C3 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 O2
O2 C5 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 C5 O2
O2 O1 C5 R1 R1 R1 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 O1 O1 C5 O2
O2 O1 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 O1 C5 C5 O2
O2 O1 O1 O1 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 C5 C5 E6 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze47 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O2
O2 C5 C5 C5 C5 C5 C5 B0 C3 C3 C5 C3 C5 C5 C5 C5 C5 C5 C5 O2
O2 C5 C5 C5 C5 C5 B0 D2 B0 O1 O1 C3 C3 C5 C5 C5 C5 C5 C5 O2
O2 C5 C5 C5 C5 C5 O0 D3 C4 E6 O1 C5 C5 C5 C5 C5 C5 C5 C5 O2
O2 C5 C5 C2 C2 C4 O0 C4 O0 C4 B0 D7 C3 C3 C5 C5 C5 C5 C5 O2
O2 C5 C5 C2 C2 C3 O0 B1 O0 B1 O3 E9 B0 O3 O3 O3 O3 B0 O2 O2
O2 C5 C5 C5 C5 C3 O0 C4 C4 C4 C4 S3 C4 C4 C4 C4 C4 E3 O2 O2
O2 C5 C5 C5 C5 C3 O0 C4 O0 D1 B0 O1 O1 O3 O3 C4 C4 D6 O2 O2
C5 C5 C5 C3 C3 C3 O0 C4 O0 B0 O0 C3 C3 O1 C4 C4 R0 C4 C4 C5
C5 C5 C3 C3 C4 C4 O0 C4 O0 O0 O0 C3 O1 C4 C4 R2 R1 C4 C4 C5
O2 C5 C3 C3 C4 C4 O1 C4 O1 C3 C3 O1 C4 C4 R3 R3 R0 C4 C4 O2
O2 C5 C3 C3 C4 C4 O1 C4 O1 C3 O1 C4 C4 R3 R0 E5 R0 C4 C4 O2
O2 C5 C3 C3 C4 C4 O1 C4 O1 O1 C4 C4 R1 R1 R2 R0 R3 C4 C4 O2
O2 C5 C3 C3 C4 C4 C4 C4 C4 C4 C4 C4 B0 C4 C4 C4 C4 C4 C4 O2
O2 C5 C5 C3 C3 C3 O1 D4 C4 C4 C4 C4 C4 C4 C4 D5 C4 E7 C4 O2
O2 C5 C5 C3 C3 C3 O1 B1 O1 O1 O1 O1 O1 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O1 O1 O2
O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 O1 O1 O1 O1 O1 O1 O1 O1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze48 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C7 C7 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 C0 C0 C0 C0 C0 C7 C7 C7 C7 C7 C7 C7 C7 C0 C0 C4 C0 C0 O2
O2 C0 C0 C0 C0 C0 C7 C7 C7 C7 C7 C7 C7 C7 C0 E7 C4 C4 C0 O2
O2 C0 C0 C0 C0 C7 C7 C7 C7 C7 C7 C7 C7 C7 C0 C4 C4 C4 C0 O2
O2 C0 C0 C0 C0 C5 C4 C5 C5 C5 C5 C5 C5 C5 C5 R3 C4 C4 C4 O2
O2 C5 C5 C5 D1 C5 C4 C4 C5 C5 C5 C5 C5 C5 C5 C4 C4 C4 C4 O2
O2 C5 C5 C5 C5 C5 C4 C4 C5 C5 C5 C5 C5 C5 C5 C4 C4 C4 C4 O2
O2 C5 C5 C5 R0 C4 C4 C4 C5 C5 C5 C5 C2 C2 C2 C4 C4 C4 C4 O2
O2 C5 C5 O0 C4 O3 O1 C4 C5 C5 C5 C5 C2 C2 C2 C4 C4 C1 C4 O2
C5 C5 O1 O0 C4 O1 C8 O1 C8 C5 C5 C5 C2 C2 C2 C4 C1 C1 C1 C1
C5 O1 C4 C4 R0 C4 C8 C8 O1 O1 O1 C5 C2 C2 C2 C7 C7 C1 C1 C1
O2 C4 C4 O2 C4 O1 C8 C8 C8 C8 C8 O1 C2 C2 C7 C7 C1 C1 C1 O2
O2 C4 C4 O1 C8 O1 C8 C8 C8 C8 C8 C2 O1 C3 C7 C1 C1 C3 C3 O2
O2 C4 C4 C8 R0 C8 C8 C8 C8 C8 C2 C2 O1 C3 C7 C1 C1 C1 C3 O2
O2 C3 C3 O1 C3 O0 O1 C3 C3 C3 C3 C3 O1 C3 C3 C3 C3 C3 C3 O2
O2 C3 C3 O1 C3 O3 C3 O1 C3 C3 C3 C3 C3 O1 C3 C3 C3 C3 C0 O2
O2 C3 C3 C3 S0 C3 C3 C3 O1 C3 C3 C3 C3 C3 O1 D2 C3 C3 C0 O2
O2 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C3 C3 O1 C3 C3 C3 C0 O2
O2 C3 C3 C3 C3 C3 C3 C3 C3 O1 C3 C3 C3 C3 O1 C0 C3 C0 C0 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C3 C3 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze49 = <<<'MAP'
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
O2 O2 C5 C5 C5 C5 C5 C5 C5 C5 C5 C5 C6 O1 O1 R1 R1 O1 O1 O2
O2 C5 C5 R2 B6 C3 C3 C3 C3 C3 C3 C5 D7 C5 B7 D8 B2 O1 O1 O2
O2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C6 C5 C5 O1 O2
O2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O1 O2
O2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O1 O2
O2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 O1 O2
O2 C5 C3 C3 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C5 C5 C5 O2
O2 R3 C5 C5 C5 C3 C3 C3 C3 C3 C3 C3 C3 E9 C3 C3 R1 C3 C5 O2
C5 C5 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C6 D3 B2 D2 C6 C5 C5
C5 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 C3 S1 D1 C6 C4 C5
O2 C5 C3 C3 C3 C3 C3 C3 C3 C3 C3 C6 D6 C3 C3 C3 C3 C3 C3 R3
O2 C5 C3 C3 C3 C3 C3 C3 C3 R2 C3 C3 C3 R1 D4 C3 C3 C3 C3 D5
O2 C0 C0 C0 C3 C3 C3 C3 C3 C3 C3 C3 R2 C3 C6 C3 C3 C3 C3 R2
O2 C0 C3 C0 C3 C3 C3 C3 C3 C0 C0 C0 C0 C0 R2 C3 R1 C5 C3 R2
O2 C0 C0 C0 C3 C0 C3 C0 C3 C0 C3 C0 C3 C0 C3 C3 C3 O1 C5 O2
O2 C0 C5 C0 C3 C3 C0 C3 C3 C0 C3 C0 C3 C0 C3 C3 C3 O1 C5 O2
O2 C3 C5 C5 C5 E8 C5 C5 C5 C5 C5 C5 C5 C5 C5 C3 C3 C5 C5 O2
O2 D9 C3 C3 C5 C5 C5 C3 C3 C5 C5 C3 C3 C3 C5 C5 R1 C5 R1 O2
O2 O2 O2 O2 O2 O2 O2 O2 O2 C5 C5 O2 O2 O2 O2 O2 O2 O2 O2 O2
MAP;

        $maze02 = $this->withTiles($maze03, [
            [8, 7, 'E1'], [8, 8, 'E2'], [8, 9, 'E3'], [8, 10, 'E4'],
            [8, 11, 'E5'], [8, 12, 'E6'], [8, 13, 'E7'], [8, 15, 'E9'],
        ]);
        $maze07 = $this->withTiles($maze03, [[16, 5, 'R1'], [18, 6, 'E8']]);
        $maze08 = $this->withTiles($maze07, [[4, 11, 'D2'], [8, 11, 'D1'], [8, 16, 'C5']]);
        $maze10 = $this->withTiles($maze07, [
            [3, 15, 'B0'], [4, 18, 'D2'], [7, 18, 'B8'], [8, 6, 'C3'],
            [10, 18, 'S0'], [15, 18, 'D3'],
        ]);
        $maze56 = $this->withTiles($maze07, [[2, 10, 'O0'], [2, 11, 'O2'], [2, 12, 'O1'], [2, 13, 'O3']]);
        $maze57 = $this->withTiles($maze03, [[19, 19, 'R0']]);

        return [
            2 => $maze02,
            3 => $maze03,
            4 => $maze03,
            5 => $maze03,
            6 => $maze03,
            7 => $maze07,
            8 => $maze08,
            9 => $maze09,
            10 => $maze10,
            11 => $maze11,
            12 => $maze12,
            13 => $maze13,
            14 => $maze14,
            15 => $maze15,
            16 => $maze16,
            17 => $maze07,
            18 => $maze08,
            19 => $maze09,
            20 => $maze10,
            21 => $maze11,
            22 => $maze12,
            23 => $maze13,
            24 => $maze14,
            25 => $maze15,
            26 => $maze16,
            27 => $maze07,
            28 => $maze08,
            29 => $maze09,
            30 => $maze10,
            31 => $maze11,
            32 => $maze12,
            33 => $maze13,
            34 => $maze14,
            35 => $maze15,
            36 => $maze16,
            37 => $maze07,
            38 => $maze08,
            39 => $maze09,
            40 => $maze10,
            41 => $maze11,
            42 => $maze12,
            43 => $maze13,
            44 => $maze14,
            45 => $maze15,
            46 => $maze16,
            47 => $maze47,
            48 => $maze48,
            49 => $maze49,
            50 => $maze47,
            51 => $maze48,
            52 => $maze49,
            53 => $maze47,
            54 => $maze48,
            55 => $maze49,
            56 => $maze56,
            57 => $maze57,
            58 => $maze57,
            59 => $maze57,
            60 => $maze57,
            61 => $maze57,
            62 => $maze57,
        ];
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function costOverrides(): array
    {
        $vision = ['kleurOogHardware' => 100, 'zwOog' => 3, 'kleurOog' => 5];
        $backwards = ['stapAchteruit' => 4];
        $pushing = ['duwen' => 50];
        $logic = ['stapAchteruit' => 3, 'zwOog' => 5, 'opdracht' => 30];
        $advanced = [
            'kompas' => 200,
            'variabele' => 50,
            'stapVooruit' => 3,
            'stapAchteruit' => 3,
            'draaiRechts' => 9,
            'kompasVerbruik' => 20,
            'duwen' => 80,
            'toewijzing' => 1,
            'operatie' => 1,
            'vergelijking' => 1,
            'zolang' => 25,
            'als' => 25,
            'opdracht' => 80,
        ];
        $quickTurns = ['stapVooruit' => 2, 'draaiLinks' => 2, 'draaiRechts' => 2];
        $expensiveTurns = ['draaiLinks' => 10, 'draaiRechts' => 10, 'opdracht' => 5];
        $minimal = [
            'kompas' => 1,
            'zwOogHardware' => 5,
            'kleurOogHardware' => 2,
            'variabele' => 3,
            'draaiLinks' => 1,
            'draaiRechts' => 1,
            'zwOog' => 1,
            'zolang' => 1,
            'als' => 1,
            'opdracht' => 1,
            'toekenning' => 1,
        ];

        return [
            9 => $vision,
            10 => $backwards,
            13 => $pushing,
            14 => $logic,
            19 => $vision,
            20 => $backwards,
            23 => $pushing,
            24 => $logic,
            29 => $vision,
            30 => $backwards,
            33 => $pushing,
            34 => $logic,
            39 => $vision,
            40 => $backwards,
            43 => $pushing,
            44 => $logic,
            47 => $advanced,
            48 => $quickTurns,
            49 => $expensiveTurns,
            50 => $advanced,
            51 => $quickTurns,
            52 => $expensiveTurns,
            53 => $advanced,
            54 => $quickTurns,
            55 => $expensiveTurns,
            57 => $minimal,
        ];
    }

    /**
     * @param  array<int, array{int, int, string}>  $replacements
     */
    private function withTiles(string $map, array $replacements): string
    {
        $tiles = preg_split('/\s+/', trim($map)) ?: [];

        foreach ($replacements as [$row, $column, $code]) {
            $tiles[(($row - 1) * 20) + $column - 1] = $code;
        }

        return implode(' ', $tiles);
    }

    private function normalizeMap(string $map): string
    {
        return implode(' ', preg_split('/\s+/', trim($map)) ?: []);
    }
}
