<?php

namespace Tests\Feature;

use App\Models\Assignment;
use Database\Seeders\AssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_official_screenshot_assignments_with_valid_maps(): void
    {
        $this->seed(AssignmentSeeder::class);

        $this->assertDatabaseCount('assignments', 61);

        foreach (range(2, 62) as $number) {
            $assignment = Assignment::where('name', sprintf('Sandbox #%03d', $number))->firstOrFail();
            $tiles = $assignment->tiles();

            $this->assertCount(400, $tiles);
            $this->assertCount(1, array_filter($tiles, fn (string $tile): bool => str_starts_with($tile, 'S')));
            $this->assertNotEmpty(array_filter($tiles, fn (string $tile): bool => str_starts_with($tile, 'D')));
            $this->assertTrue(collect($tiles)->every(
                fn (string $tile): bool => preg_match('/^(C[0-8]|B[0-8]|D[1-9]|E[1-9]|O[0-3]|R[0-3]|S[0-3])$/', $tile) === 1,
            ));
        }
    }

    public function test_the_screenshot_specific_objects_are_in_the_expected_tiles(): void
    {
        $this->seed(AssignmentSeeder::class);

        $maze02 = Assignment::where('name', 'Sandbox #002')->firstOrFail()->tiles();
        $this->assertSame(['E1', 'E2', 'E3', 'E4', 'E5', 'E6', 'E7'], array_slice($maze02, 146, 7));
        $this->assertSame('C3', $maze02[153]);
        $this->assertSame('E9', $maze02[154]);
        $this->assertSame('E8', $maze02[304]);

        $maze10 = Assignment::where('name', 'Sandbox #010')->firstOrFail()->tiles();
        $this->assertSame('B0', $maze10[54]);
        $this->assertSame('D2', $maze10[77]);
        $this->assertSame('B8', $maze10[137]);
        $this->assertSame('S0', $maze10[197]);
        $this->assertSame('D3', $maze10[297]);
    }

    public function test_repeated_official_maps_are_reused_and_special_tiles_are_preserved(): void
    {
        $this->seed(AssignmentSeeder::class);

        $this->assertSame($this->map(11), $this->map(21));
        $this->assertSame($this->map(11), $this->map(31));
        $this->assertSame($this->map(11), $this->map(41));
        $this->assertSame($this->map(47), $this->map(50));
        $this->assertSame($this->map(47), $this->map(53));
        $this->assertSame($this->map(57), $this->map(62));

        $this->assertContains('C0', $this->map(12));
        $this->assertContains('C8', $this->map(12));
        $this->assertContains('O0', $this->map(47));
        $this->assertSame('O0', $this->map(56)[29]);
        $this->assertSame('R0', $this->map(57)[378]);
    }

    public function test_config_screenshots_override_only_their_changed_costs(): void
    {
        $this->seed(AssignmentSeeder::class);

        $defaults = config('glade.default_costs');
        $this->assertSame($defaults, Assignment::where('name', 'Sandbox #011')->firstOrFail()->costs);

        $maze09 = Assignment::where('name', 'Sandbox #009')->firstOrFail();
        $this->assertSame(100, $maze09->costs['kleurOogHardware']);
        $this->assertSame(3, $maze09->costs['zwOog']);
        $this->assertSame(5, $maze09->costs['kleurOog']);

        $maze47 = Assignment::where('name', 'Sandbox #047')->firstOrFail();
        $this->assertSame(200, $maze47->costs['kompas']);
        $this->assertSame(3, $maze47->costs['stapVooruit']);
        $this->assertSame(9, $maze47->costs['draaiRechts']);
        $this->assertSame(25, $maze47->costs['zolang']);
        $this->assertSame(80, $maze47->costs['opdracht']);

        $maze57 = Assignment::where('name', 'Sandbox #057')->firstOrFail();
        $this->assertSame(1, $maze57->costs['kompas']);
        $this->assertSame(5, $maze57->costs['zwOogHardware']);
        $this->assertSame(1, $maze57->costs['draaiLinks']);
        $this->assertSame(1, $maze57->costs['toekenning']);
        $this->assertSame(20, $maze57->costs['kleurOog']);
    }

    /**
     * @return array<int, string>
     */
    private function map(int $number): array
    {
        return Assignment::where('name', sprintf('Sandbox #%03d', $number))->firstOrFail()->tiles();
    }
}
