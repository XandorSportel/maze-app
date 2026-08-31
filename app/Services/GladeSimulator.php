<?php

namespace App\Services;

use App\Models\Assignment;

class GladeSimulator
{
    /**
     * Execute the supported straight-line subset of language 20.
     *
     * @return array{status:string,total_cost:int,remaining_budget:int,execution_log:array<int,string>,final_state:array<string,mixed>}
     */
    public function run(Assignment $assignment, string $code): array
    {
        $tiles = $assignment->tiles();
        $costs = $assignment->costs;
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $code) ?: [])));
        $start = collect($tiles)->search(fn (string $tile): bool => str_starts_with($tile, 'S'));

        if ($start === false) {
            return $this->result('Ongeldige glade: start ontbreekt', 0, $assignment->start_capital, ['Geen starttegel gevonden.'], 0, 0, 0, []);
        }

        $position = (int) $start;
        $direction = (int) substr($tiles[$position], 1, 1);
        $budget = $assignment->start_capital;
        $spent = 0;
        $goal = 1;
        $collected = [];
        $log = ["Startkapitaal: €{$budget}", 'Start op ['.($position % 20 + 1).', '.(intdiv($position, 20) + 1).']'];

        $softwareCost = count($lines) * (int) ($costs['opdracht'] ?? 20);
        $budget -= $softwareCost;
        $spent += $softwareCost;
        $log[] = 'Software: '.count($lines).' opdrachten × €'.($costs['opdracht'] ?? 20)." = €{$softwareCost}";

        foreach ($lines as $lineNumber => $line) {
            if ($budget < 0) {
                return $this->result('Budget overschreden', $spent, $budget, $log, $position, $direction, $goal, $collected);
            }

            $runtimeCost = match ($line) {
                'stapVooruit' => (int) ($costs['stapVooruit'] ?? 1),
                'stapAchteruit' => (int) ($costs['stapAchteruit'] ?? 1),
                'draaiLinks', 'draaiRechts' => (int) ($costs['draaien'] ?? 5),
                default => null,
            };

            if ($runtimeCost === null) {
                $log[] = 'Regel '.($lineNumber + 1).": onbekende opdracht '{$line}'.";

                return $this->result('Syntaxfout', $spent, $budget, $log, $position, $direction, $goal, $collected);
            }

            $budget -= $runtimeCost;
            $spent += $runtimeCost;

            if ($line === 'draaiLinks') {
                $direction = ($direction + 3) % 4;
                $log[] = 'Draai links.';

                continue;
            }

            if ($line === 'draaiRechts') {
                $direction = ($direction + 1) % 4;
                $log[] = 'Draai rechts.';

                continue;
            }

            $stepDirection = $line === 'stapAchteruit' ? ($direction + 2) % 4 : $direction;
            $next = $position + [-20, 1, 20, -1][$stepDirection];
            $wraps = $next >= 0 && $next < 400 && abs(($next % 20) - ($position % 20)) > 1;

            if ($next < 0 || $next >= 400 || $wraps || str_starts_with($tiles[$next], 'O')) {
                $log[] = 'Botsing vanaf ['.($position % 20 + 1).', '.(intdiv($position, 20) + 1).'].';

                return $this->result('Gebotst op een obstakel', $spent, $budget, $log, $position, $direction, $goal, $collected);
            }

            $position = $next;
            $tile = $tiles[$position];
            $log[] = 'Stap naar ['.($position % 20 + 1).', '.(intdiv($position, 20) + 1)."] ({$tile}).";

            if (str_starts_with($tile, 'B')) {
                return $this->result('Bom geraakt', $spent, $budget, [...$log, 'De griever is uitgeschakeld.'], $position, $direction, $goal, $collected);
            }

            if (str_starts_with($tile, 'R')) {
                $direction = ($direction + (int) substr($tile, 1, 1)) % 4;
                $log[] = "Draaischijf {$tile} activeert.";
            }

            if (str_starts_with($tile, 'E') && ! in_array($tile, $collected, true)) {
                $bonus = 2 ** (int) substr($tile, 1, 1);
                $budget += $bonus;
                $collected[] = $tile;
                $log[] = "Bonus {$tile}: +€{$bonus}.";
            }

            if ($tile === "D{$goal}") {
                $log[] = "Doel {$goal} bereikt.";
                $goal++;
            }
        }

        $goalCount = count(array_filter($tiles, fn (string $tile): bool => str_starts_with($tile, 'D')));
        $status = $goal > $goalCount ? 'Doel bereikt' : 'Programma afgerond, doel niet bereikt';
        $log[] = "Eindkapitaal: €{$budget}.";

        return $this->result($status, $spent, $budget, $log, $position, $direction, $goal, $collected);
    }

    private function result(string $status, int $spent, int $budget, array $log, int $position, int $direction, int $goal, array $collected): array
    {
        return [
            'status' => $status,
            'total_cost' => $spent,
            'remaining_budget' => $budget,
            'execution_log' => $log,
            'final_state' => compact('position', 'direction', 'goal', 'collected'),
        ];
    }
}
