<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_pages_are_available(): void
    {
        $assignment = $this->assignment();

        $this->get(route('assignments.index'))->assertOk()->assertSee($assignment->name);
        $this->get(route('assignments.show', $assignment))->assertOk()->assertSee('Jouw programma');
        $this->get(route('submissions.index'))->assertOk()->assertSee('Alle pogingen');
        $this->get(route('glades.create'))->assertOk()->assertSee('Tegelpalet')->assertSee('Muur (O2)')->assertSee('Kostenkaart instellen');
    }

    public function test_each_run_is_stored_as_a_new_submission_with_calculated_costs(): void
    {
        $assignment = $this->assignment();

        $this->post(route('submissions.store', $assignment), ['code' => 'stapVooruit'])
            ->assertRedirect();
        $this->post(route('submissions.store', $assignment), ['code' => 'stapVooruit'])
            ->assertRedirect();

        $this->assertDatabaseCount('submissions', 2);
        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertSame(21, $submission->total_cost);
        $this->assertSame(2003, $submission->remaining_budget);
        $this->get(route('submissions.show', $submission))->assertOk()->assertSee('simulation-result', false);
    }

    public function test_a_valid_custom_glade_can_be_created(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'D1';

        $this->post(route('glades.store'), [
            'name' => 'Mijn Glade',
            'description' => 'Een testglade.',
            'start_capital' => 2024,
            'map_definition' => implode(' ', $tiles),
            'costs' => config('glade.default_costs'),
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', ['name' => 'Mijn Glade', 'is_custom' => true]);
    }

    public function test_custom_costs_and_start_capital_are_saved_used_and_highlighted(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'D1';
        $costs = config('glade.default_costs');
        $costs['opdracht'] = 7;
        $costs['stapVooruit'] = 3;

        $this->post(route('glades.store'), [
            'name' => 'Goedkope Glade',
            'start_capital' => 100,
            'map_definition' => implode(' ', $tiles),
            'costs' => $costs,
        ])->assertRedirect();

        $assignment = Assignment::firstOrFail();
        $this->assertSame(100, $assignment->start_capital);
        $this->assertSame(7, $assignment->costs['opdracht']);
        $this->get(route('assignments.show', $assignment))->assertOk()->assertSee('class="changed"', false);

        $this->post(route('submissions.store', $assignment), ['code' => 'stapVooruit'])->assertRedirect();
        $submission = Submission::firstOrFail();
        $this->assertSame(10, $submission->total_cost);
        $this->assertSame(90, $submission->remaining_budget);
    }

    public function test_variables_conditions_and_loops_are_executed_with_costs(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[210] = 'S1';
        $tiles[135] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);
        $code = <<<'CODE'
gebruik i
i = 0

zolang i < 9 {
als i == 5 {
draaiLinks
}
stapVooruit
i = i + 1
}
CODE;

        $this->post(route('submissions.store', $assignment), ['code' => $code])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertSame(270, $submission->total_cost);
        $this->assertSame(9, $submission->final_state['variables']['i']);
    }

    private function assignment(): Assignment
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'D1';

        return $this->assignmentWithTiles($tiles);
    }

    private function assignmentWithTiles(array $tiles): Assignment
    {
        return Assignment::create([
            'name' => 'Zandbak #001',
            'description' => 'Testopdracht',
            'map_definition' => implode(' ', $tiles),
            'costs' => config('glade.default_costs'),
            'start_capital' => 2024,
            'is_active' => true,
        ]);
    }
}
