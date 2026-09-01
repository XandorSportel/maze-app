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
        $this->get(route('glades.create'))
            ->assertOk()
            ->assertSee('Tegelpalet')
            ->assertSee('Muur (O2)')
            ->assertSee('Kostenkaart instellen')
            ->assertSee('data-tile="E1"', false)
            ->assertSee('data-tile="E9"', false)
            ->assertSee('data-tile="B0"', false)
            ->assertSee('data-tile="B8"', false)
            ->assertSee('data-tile="R0"', false)
            ->assertSee('data-tile="R3"', false)
            ->assertSee('data-tile="D1"', false)
            ->assertSee('data-tile="D9"', false);
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

    public function test_the_same_goal_cannot_be_added_more_than_once(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'D1';
        $tiles[23] = 'D1';

        $this->post(route('glades.store'), [
            'name' => 'Dubbel doel',
            'start_capital' => 2024,
            'map_definition' => implode(' ', $tiles),
            'costs' => config('glade.default_costs'),
        ])->assertSessionHasErrors('map_definition');

        $this->assertDatabaseMissing('assignments', ['name' => 'Dubbel doel']);
    }

    public function test_a_custom_glade_can_be_edited(): void
    {
        $assignment = $this->assignment();
        $assignment->update(['is_custom' => true]);
        $tiles = $assignment->tiles();
        $tiles[22] = 'C3';
        $tiles[23] = 'D2';
        $costs = config('glade.default_costs');
        $costs['stapVooruit'] = 99;

        $this->get(route('glades.edit', $assignment))
            ->assertOk()
            ->assertSee('Pas je glade aan.')
            ->assertSee($assignment->name);

        $this->put(route('glades.update', $assignment), [
            'name' => 'Gewijzigde Glade',
            'description' => 'Nieuwe uitleg.',
            'start_capital' => 5000,
            'map_definition' => implode(' ', $tiles),
            'costs' => $costs,
        ])->assertRedirect(route('assignments.show', $assignment));

        $assignment->refresh();
        $this->assertSame('Gewijzigde Glade', $assignment->name);
        $this->assertSame('Nieuwe uitleg.', $assignment->description);
        $this->assertSame(5000, $assignment->start_capital);
        $this->assertSame('D2', $assignment->tiles()[23]);
        $this->assertSame(99, $assignment->costs['stapVooruit']);
    }

    public function test_a_sandbox_assignment_can_also_be_edited_as_a_glade(): void
    {
        $assignment = $this->assignment();

        $this->get(route('glades.edit', $assignment))
            ->assertOk()
            ->assertSee('Pas je glade aan.');
        $this->get(route('assignments.show', $assignment))->assertSee('Glade bewerken');
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
        $this->assertSame(264, $submission->total_cost);
        $this->assertSame(8, $submission->final_state['variables']['i']);
    }

    public function test_official_coordinates_collision_and_leaving_the_glade_are_reported(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[197] = 'S0';
        $tiles[159] = 'O2';
        $tiles[0] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);
        $commands = [
            'stapVooruit',
            'stapVooruit',
            'draaiRechts',
            'stapVooruit',
            'stapVooruit',
            'draaiRechts',
            'stapVooruit',
            'stapVooruit',
            'draaiLinks',
            'stapVooruit',
            'stapVooruit',
            ...array_fill(0, 15, 'stapVooruit'),
        ];

        $this->post(route('submissions.store', $assignment), ['code' => implode("\n", $commands)])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Uit de Glade gelopen', $submission->status);
        $this->assertSame(0, $submission->remaining_budget);
        $this->assertSame(2024, $submission->total_cost);
        $this->assertContains('kosten opdracht: 26 * 20', $submission->execution_log);
        $this->assertContains('kosten voor stap vooruit: 1 naar: [8,17]', $submission->execution_log);
        $this->assertContains('steen verplaatsing naar [7,20] geblokkeerd', $submission->execution_log);
        $this->assertContains('uitzondering: Uit the Glade gelopen! [9,20]', $submission->execution_log);
        $this->assertContains('The rule says: "Don\'t enter the maze"', $submission->execution_log);
    }

    public function test_compass_hardware_can_be_declared_read_and_assigned(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[390] = 'S0';
        $tiles[370] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);
        $code = <<<'CODE'
gebruik kompas
gebruik k
k = kompas
stapVooruit
CODE;

        $this->post(route('submissions.store', $assignment), ['code' => $code])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertSame(178, $submission->total_cost);
        $this->assertSame(1846, $submission->remaining_budget);
        $this->assertSame(0, $submission->final_state['variables']['k']);
        $this->assertContains("kosten voor declaratie 'kompas': 100", $submission->execution_log);
        $this->assertContains("kosten voor declaratie variabele 'k': 30", $submission->execution_log);
        $this->assertContains('kosten voor gebruik kompas: 15', $submission->execution_log);
        $this->assertContains('kosten voor het toewijzen van een waarde: 0 aan k: 2', $submission->execution_log);
        $this->assertContains('kosten voor stap vooruit: 1 naar: [18,10]', $submission->execution_log);
    }

    public function test_color_eye_and_else_follow_language_20_syntax(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S0';
        $tiles[22] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);
        $code = <<<'CODE'
gebruik kleurOog
als kleurOog == 1 {
draaiLinks
} anders {
draaiRechts
}
stapVooruit
CODE;

        $this->post(route('submissions.store', $assignment), ['code' => $code])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertContains('kosten voor gebruik kleur-oog: 20', $submission->execution_log);
        $this->assertContains('kosten voor het draaien naar rechts: 5', $submission->execution_log);
        $this->assertNotContains('kosten voor het draaien naar links: 5', $submission->execution_log);
    }

    public function test_a_collision_costs_money_but_does_not_stop_the_program(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'O2';
        $tiles[41] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);
        $code = "stapVooruit\ndraaiRechts\nstapVooruit";

        $this->post(route('submissions.store', $assignment), ['code' => $code])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertSame(166, $submission->total_cost);
        $this->assertContains('kosten voor botsen / duwen: 100', $submission->execution_log);
        $this->assertContains('kosten voor stap vooruit: 1 naar: [2,1]', $submission->execution_log);
    }

    public function test_a_b1_bomb_can_be_crossed_in_the_next_second(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'B1';
        $tiles[23] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);

        $this->post(route('submissions.store', $assignment), [
            'code' => "stapVooruit\nstapVooruit",
        ])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Doel bereikt', $submission->status);
        $this->assertSame(23, $submission->final_state['position']);
        $this->assertSame('O0', $submission->final_state['tiles'][22]);
        $this->assertContains('bom op [1,2] ontploft.', $submission->execution_log);
    }

    public function test_turning_on_a_b1_bomb_destroys_the_runner(): void
    {
        $tiles = array_fill(0, 400, 'C3');
        $tiles[21] = 'S1';
        $tiles[22] = 'B1';
        $tiles[23] = 'D1';
        $assignment = $this->assignmentWithTiles($tiles);

        $this->post(route('submissions.store', $assignment), [
            'code' => "stapVooruit\ndraaiRechts",
        ])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertSame('Bom geraakt', $submission->status);
        $this->assertSame(0, $submission->remaining_budget);
        $this->assertSame(22, $submission->final_state['position']);
        $this->assertSame('O0', $submission->final_state['tiles'][22]);
    }

    public function test_assignments_can_be_searched_by_name_or_description(): void
    {
        $alpha = $this->assignment();
        $alpha->update(['name' => 'Alfa doolhof', 'description' => 'Een groene route']);
        $beta = $this->assignment();
        $beta->update(['name' => 'Beta doolhof', 'description' => 'Een blauwe route']);

        $this->get(route('assignments.index', ['q' => 'groene']))
            ->assertOk()
            ->assertSee($alpha->name)
            ->assertDontSee($beta->name);
    }

    public function test_submissions_can_be_searched_and_sorted_from_table_columns(): void
    {
        $alpha = $this->assignment();
        $alpha->update(['name' => 'Alfa opdracht']);
        $beta = $this->assignment();
        $beta->update(['name' => 'Beta opdracht']);

        $cheap = $this->storedSubmission($alpha, 100, 1900, '2026-01-10 10:00:00');
        $expensive = $this->storedSubmission($beta, 500, 1200, '2026-08-10 10:00:00');

        $this->get(route('submissions.index', ['q' => 'Alfa']))
            ->assertOk()
            ->assertSee($alpha->name)
            ->assertDontSee($beta->name)
            ->assertSee('sort=cost_asc', false);

        $this->get(route('submissions.index', ['sort' => 'cost_desc']))
            ->assertOk()
            ->assertSeeInOrder([$expensive->assignment->name, $cheap->assignment->name]);

        $this->get(route('submissions.index', ['sort' => 'oldest']))
            ->assertOk()
            ->assertSeeInOrder([$cheap->assignment->name, $expensive->assignment->name]);
    }

    public function test_submission_pagination_uses_the_sized_navigation_container(): void
    {
        $assignment = $this->assignment();

        foreach (range(1, 16) as $index) {
            $this->storedSubmission($assignment, $index, 2024 - $index, "2026-08-01 10:00:{$index}");
        }

        $this->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertSee('page=2', false);
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
            'name' => 'Sandbox #001',
            'description' => 'Testopdracht',
            'map_definition' => implode(' ', $tiles),
            'costs' => config('glade.default_costs'),
            'start_capital' => 2024,
            'is_active' => true,
        ]);
    }

    private function storedSubmission(Assignment $assignment, int $cost, int $remaining, string $createdAt): Submission
    {
        $submission = $assignment->submissions()->create([
            'code' => 'stapVooruit',
            'status' => 'Doel bereikt',
            'total_cost' => $cost,
            'remaining_budget' => $remaining,
            'execution_log' => ['Test'],
            'final_state' => ['position' => 22, 'direction' => 1, 'goal' => 2, 'collected' => [], 'variables' => []],
        ]);
        $submission->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $submission->fresh('assignment');
    }
}
