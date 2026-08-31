<?php

namespace App\Services;

use App\Models\Assignment;
use RuntimeException;

class GladeSimulator
{
    private const MAX_EXECUTIONS = 10000;

    private array $tiles;

    private array $costs;

    private int $position;

    private int $direction;

    private int $budget;

    private int $spent = 0;

    private int $goal = 1;

    private int $executions = 0;

    private array $collected = [];

    private array $variables = [];

    private array $log = [];

    private ?string $haltStatus = null;

    /**
     * Execute language 20 and return a persistable result.
     *
     * @return array{status:string,total_cost:int,remaining_budget:int,execution_log:array<int,string>,final_state:array<string,mixed>}
     */
    public function run(Assignment $assignment, string $code): array
    {
        $this->reset($assignment);

        try {
            $program = $this->parse($code);
            $this->charge($this->compileCost($program), 'Compile- en aanschafkosten');
            $this->executeBlock($program);
        } catch (RuntimeException $exception) {
            $this->log[] = $exception->getMessage();

            return $this->result($this->haltStatus ?? 'Syntaxfout');
        }

        $goalCount = count(array_filter($this->tiles, fn (string $tile): bool => str_starts_with($tile, 'D')));
        $status = $this->haltStatus ?? ($this->goal > $goalCount ? 'Doel bereikt' : 'Programma afgerond, doel niet bereikt');
        $this->log[] = "Eindkapitaal: €{$this->budget}.";

        return $this->result($status);
    }

    private function reset(Assignment $assignment): void
    {
        $this->tiles = $assignment->tiles();
        $this->costs = $assignment->costs;
        $start = collect($this->tiles)->search(fn (string $tile): bool => str_starts_with($tile, 'S'));

        if ($start === false) {
            throw new RuntimeException('Geen starttegel gevonden.');
        }

        $this->position = (int) $start;
        $this->direction = (int) substr($this->tiles[$this->position], 1, 1);
        $this->budget = $assignment->start_capital;
        $this->spent = 0;
        $this->goal = 1;
        $this->executions = 0;
        $this->collected = [];
        $this->variables = [];
        $this->log = ["Startkapitaal: €{$this->budget}", 'Start op ['.($this->position % 20 + 1).', '.(intdiv($this->position, 20) + 1).'].'];
        $this->haltStatus = null;
    }

    private function parse(string $code): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $code) ?: []), fn (string $line): bool => $line !== ''));
        $index = 0;
        $program = $this->parseBlock($lines, $index, false);

        if ($index < count($lines)) {
            throw new RuntimeException('Onverwachte afsluitende accolade op regel '.($index + 1).'.');
        }

        return $program;
    }

    private function parseBlock(array $lines, int &$index, bool $expectsClosingBrace): array
    {
        $statements = [];

        while ($index < count($lines)) {
            $line = $lines[$index];
            $lineNumber = $index + 1;

            if ($line === '}') {
                if (! $expectsClosingBrace) {
                    return $statements;
                }
                $index++;

                return $statements;
            }

            if (preg_match('/^gebruik ([a-z])$/', $line, $match)) {
                $statements[] = ['type' => 'declaration', 'name' => $match[1], 'line' => $lineNumber];
                $index++;

                continue;
            }

            if (preg_match('/^(zolang|als) (.+) \{$/', $line, $match)) {
                $index++;
                $statements[] = [
                    'type' => $match[1] === 'zolang' ? 'while' : 'if',
                    'condition' => $match[2],
                    'body' => $this->parseBlock($lines, $index, true),
                    'line' => $lineNumber,
                ];

                continue;
            }

            if (preg_match('/^([a-z])\s*=\s*(.+)$/', $line, $match)) {
                $statements[] = ['type' => 'assignment', 'name' => $match[1], 'expression' => $match[2], 'line' => $lineNumber];
                $index++;

                continue;
            }

            if (in_array($line, ['stapVooruit', 'stapAchteruit', 'draaiLinks', 'draaiRechts'], true)) {
                $statements[] = ['type' => 'command', 'command' => $line, 'line' => $lineNumber];
                $index++;

                continue;
            }

            throw new RuntimeException("Regel {$lineNumber}: onbekende of ongeldige instructie '{$line}'.");
        }

        if ($expectsClosingBrace) {
            throw new RuntimeException('Een blok is niet afgesloten met }.');
        }

        return $statements;
    }

    private function compileCost(array $statements): int
    {
        $total = 0;

        foreach ($statements as $statement) {
            $total += match ($statement['type']) {
                'declaration' => (int) ($this->costs['variabele'] ?? 30),
                'assignment' => (int) ($this->costs['toekenning'] ?? 10),
                'command' => (int) ($this->costs['opdracht'] ?? 20),
                'while' => (int) ($this->costs['zolang'] ?? 50) + $this->compileCost($statement['body']),
                'if' => (int) ($this->costs['als'] ?? 40) + $this->compileCost($statement['body']),
                default => 0,
            };
        }

        return $total;
    }

    private function executeBlock(array $statements): void
    {
        foreach ($statements as $statement) {
            if ($this->haltStatus !== null) {
                return;
            }

            $this->guardExecutionLimit();

            match ($statement['type']) {
                'declaration' => $this->declare($statement),
                'assignment' => $this->assign($statement),
                'command' => $this->command($statement['command']),
                'if' => $this->conditional($statement),
                'while' => $this->loop($statement),
                default => null,
            };
        }
    }

    private function declare(array $statement): void
    {
        if (array_key_exists($statement['name'], $this->variables)) {
            throw new RuntimeException("Regel {$statement['line']}: variabele {$statement['name']} is al gedeclareerd.");
        }

        $this->variables[$statement['name']] = 0;
        $this->log[] = "Variabele {$statement['name']} geïnitialiseerd.";
    }

    private function assign(array $statement): void
    {
        $this->requireVariable($statement['name'], $statement['line']);
        [$value, $operations] = $this->evaluateArithmetic($statement['expression'], $statement['line']);
        $runtimeCost = (int) ($this->costs['toewijzing'] ?? 2) + ($operations * (int) ($this->costs['operatie'] ?? 2));
        $this->charge($runtimeCost, "Toekenning {$statement['name']}");
        $this->variables[$statement['name']] = $value;
        $this->log[] = "{$statement['name']} = {$value}.";
    }

    private function conditional(array $statement): void
    {
        if ($this->evaluateCondition($statement['condition'], $statement['line'])) {
            $this->executeBlock($statement['body']);
        }
    }

    private function loop(array $statement): void
    {
        while ($this->haltStatus === null && $this->evaluateCondition($statement['condition'], $statement['line'])) {
            $this->guardExecutionLimit();
            $this->executeBlock($statement['body']);
        }
    }

    private function evaluateCondition(string $condition, int $line): bool
    {
        if (! preg_match('/^(.+?)\s*(==|!=|<=|>=|<|>)\s*(.+)$/', $condition, $match)) {
            throw new RuntimeException("Regel {$line}: ongeldige vergelijking '{$condition}'.");
        }

        [$left, $leftOperations] = $this->evaluateArithmetic($match[1], $line);
        [$right, $rightOperations] = $this->evaluateArithmetic($match[3], $line);
        $cost = (int) ($this->costs['vergelijking'] ?? 2) + (($leftOperations + $rightOperations) * (int) ($this->costs['operatie'] ?? 2));
        $this->charge($cost, "Vergelijking {$condition}");

        return match ($match[2]) {
            '==' => $left === $right,
            '!=' => $left !== $right,
            '<' => $left < $right,
            '>' => $left > $right,
            '<=' => $left <= $right,
            '>=' => $left >= $right,
        };
    }

    /** @return array{0:int,1:int} */
    private function evaluateArithmetic(string $expression, int $line): array
    {
        preg_match_all('/\d+|[a-z]|[+\-*\/%]/', str_replace(' ', '', $expression), $matches);
        $tokens = $matches[0];

        if ($tokens === [] || implode('', $tokens) !== str_replace(' ', '', $expression)) {
            throw new RuntimeException("Regel {$line}: ongeldige expressie '{$expression}'.");
        }

        $values = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 2];

        foreach ($tokens as $index => $token) {
            if ($index % 2 === 0) {
                if (! preg_match('/^(\d+|[a-z])$/', $token)) {
                    throw new RuntimeException("Regel {$line}: ongeldige expressie '{$expression}'.");
                }
                $values[] = ctype_digit($token) ? (int) $token : $this->variableValue($token, $line);

                continue;
            }

            if (! isset($precedence[$token])) {
                throw new RuntimeException("Regel {$line}: ongeldige operator '{$token}'.");
            }

            while ($operators !== [] && $precedence[end($operators)] >= $precedence[$token]) {
                $this->applyOperator($values, array_pop($operators), $line);
            }
            $operators[] = $token;
        }

        if (count($tokens) % 2 === 0) {
            throw new RuntimeException("Regel {$line}: onvolledige expressie '{$expression}'.");
        }

        while ($operators !== []) {
            $this->applyOperator($values, array_pop($operators), $line);
        }

        return [$values[0], intdiv(count($tokens), 2)];
    }

    private function applyOperator(array &$values, string $operator, int $line): void
    {
        $right = array_pop($values);
        $left = array_pop($values);

        if (($operator === '/' || $operator === '%') && $right === 0) {
            throw new RuntimeException("Regel {$line}: delen door nul is niet toegestaan.");
        }

        $values[] = match ($operator) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => intdiv($left, $right),
            '%' => $left % $right,
        };
    }

    private function variableValue(string $name, int $line): int
    {
        $this->requireVariable($name, $line);

        return $this->variables[$name];
    }

    private function requireVariable(string $name, int $line): void
    {
        if (! array_key_exists($name, $this->variables)) {
            throw new RuntimeException("Regel {$line}: variabele {$name} is niet geïnitialiseerd met 'gebruik {$name}'.");
        }
    }

    private function command(string $command): void
    {
        $runtimeCost = (int) ($this->costs[$command] ?? $this->costs['draaien'] ?? 1);
        $this->charge($runtimeCost, $command);

        if ($command === 'draaiLinks') {
            $this->direction = ($this->direction + 3) % 4;
            $this->log[] = 'Draai links.';

            return;
        }

        if ($command === 'draaiRechts') {
            $this->direction = ($this->direction + 1) % 4;
            $this->log[] = 'Draai rechts.';

            return;
        }

        $stepDirection = $command === 'stapAchteruit' ? ($this->direction + 2) % 4 : $this->direction;
        $next = $this->position + [-20, 1, 20, -1][$stepDirection];
        $wraps = $next >= 0 && $next < 400 && abs(($next % 20) - ($this->position % 20)) > 1;

        if ($next < 0 || $next >= 400 || $wraps || str_starts_with($this->tiles[$next], 'O')) {
            $this->log[] = 'Botsing vanaf ['.($this->position % 20 + 1).', '.(intdiv($this->position, 20) + 1).'].';
            $this->haltStatus = 'Gebotst op een obstakel';

            return;
        }

        $this->position = $next;
        $tile = $this->tiles[$this->position];
        $this->log[] = 'Stap naar ['.($this->position % 20 + 1).', '.(intdiv($this->position, 20) + 1)."] ({$tile}).";

        if (str_starts_with($tile, 'B')) {
            $this->haltStatus = 'Bom geraakt';

            return;
        }

        if (str_starts_with($tile, 'R')) {
            $this->direction = ($this->direction + (int) substr($tile, 1, 1)) % 4;
            $this->log[] = "Draaischijf {$tile} activeert.";
        }

        if (str_starts_with($tile, 'E') && ! in_array($tile, $this->collected, true)) {
            $bonus = 2 ** (int) substr($tile, 1, 1);
            $this->budget += $bonus;
            $this->collected[] = $tile;
            $this->log[] = "Bonus {$tile}: +€{$bonus}.";
        }

        if ($tile === "D{$this->goal}") {
            $this->log[] = "Doel {$this->goal} bereikt.";
            $this->goal++;
        }
    }

    private function charge(int $amount, string $reason): void
    {
        $this->budget -= $amount;
        $this->spent += $amount;

        if ($amount > 0) {
            $this->log[] = "{$reason}: -€{$amount}.";
        }

        if ($this->budget < 0) {
            $this->haltStatus = 'Budget overschreden';
            throw new RuntimeException('Het beschikbare budget is overschreden.');
        }
    }

    private function guardExecutionLimit(): void
    {
        $this->executions++;

        if ($this->executions > self::MAX_EXECUTIONS) {
            $this->haltStatus = 'Programma gestopt';
            throw new RuntimeException('Uitvoering gestopt na 10.000 stappen; controleer op een oneindige lus.');
        }
    }

    private function result(string $status): array
    {
        return [
            'status' => $status,
            'total_cost' => $this->spent,
            'remaining_budget' => $this->budget,
            'execution_log' => $this->log,
            'final_state' => [
                'position' => $this->position,
                'direction' => $this->direction,
                'goal' => $this->goal,
                'collected' => $this->collected,
                'variables' => $this->variables,
            ],
        ];
    }
}
