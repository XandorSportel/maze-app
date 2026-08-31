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
        $this->get(route('glades.create'))->assertOk()->assertSee('Tegelpalet');
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
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', ['name' => 'Mijn Glade', 'is_custom' => true]);
    }

    private function assignment(): Assignment
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'D1';

        return Assignment::create([
            'name' => 'Zandbak #001',
            'description' => 'Testopdracht',
            'map_definition' => implode(' ', $tiles),
            'costs' => [
                'kompas' => 100, 'zwOogHardware' => 50, 'kleurOogHardware' => 200, 'variabele' => 30,
                'stapVooruit' => 1, 'stapAchteruit' => 1, 'draaien' => 5, 'zwOog' => 10,
                'kleurOog' => 20, 'duwen' => 100, 'toewijzing' => 2, 'operatie' => 2,
                'vergelijking' => 2, 'zolang' => 50, 'als' => 40, 'opdracht' => 20, 'toekenning' => 10,
            ],
            'start_capital' => 2024,
            'is_active' => true,
        ]);
    }
}
