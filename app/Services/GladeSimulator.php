<?php

namespace App\Services;

use App\Models\Assignment;
use RuntimeException;

class GladeSimulator
{
    private const MAX_EXECUTIONS = 10000;

    private const HARDWARE = ['kompas', 'zwOog', 'kleurOog'];

    private array $tiles;

    private array $costs;

    private int $position;

    private int $direction;

    private int $budget;

    private int $spent = 0;

    private int $goal = 1;

    private int $goalCount = 0;

    private int $executions = 0;

    private array $collected = [];

    private array $variables = [];

    private array $hardware = [];

    private array $armedBombs = [];

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
            $this->purchaseProgram($program);
            $this->executeBlock($program);
        } catch (RuntimeException $exception) {
            $this->log[] = $exception->getMessage();

            if ($this->haltStatus === 'Budget overschreden') {
                $this->log[] = 'eind kapitaal: 0';
            }

            return $this->result($this->haltStatus ?? 'Syntaxfout');
        }

        $status = $this->haltStatus ?? ($this->goal > $this->goalCount ? 'Doel bereikt' : 'Programma afgerond, doel niet bereikt');
        $this->log[] = "eind kapitaal: {$this->budget}";

        if ($status === 'Doel bereikt') {
            $this->log[] = 'Doel bereikt binnen budget';
        }

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
        $this->goalCount = count(array_filter($this->tiles, fn (string $tile): bool => str_starts_with($tile, 'D')));
        $this->executions = 0;
        $this->collected = [];
        $this->variables = [];
        $this->hardware = [];
        $this->armedBombs = [];
        $this->log = ["start kapitaal: {$this->budget}"];
        $this->haltStatus = null;
    }

    private function parse(string $code): array
    {
        $lines = [];

        foreach (preg_split('/\R/', $code) ?: [] as $offset => $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^}\s*anders\s*{$/', $line)) {
                $lines[] = ['text' => '}', 'line' => $offset + 1];
                $lines[] = ['text' => 'anders {', 'line' => $offset + 1];

                continue;
            }

            $lines[] = ['text' => $line, 'line' => $offset + 1];
        }

        $index = 0;
        $program = $this->parseBlock($lines, $index, false);

        if ($index < count($lines)) {
            throw new RuntimeException('Onverwachte afsluitende accolade op regel '.$lines[$index]['line'].'.');
        }

        return $program;
    }

    private function parseBlock(array $lines, int &$index, bool $expectsClosingBrace): array
    {
        $statements = [];

        while ($index < count($lines)) {
            $line = $lines[$index]['text'];
            $lineNumber = $lines[$index]['line'];

            if ($line === '}') {
                if (! $expectsClosingBrace) {
                    return $statements;
                }

                $index++;

                return $statements;
            }

            if (preg_match('/^gebruik\s+([a-z]|kompas|zwOog|kleurOog)$/', $line, $match)) {
                $statements[] = ['type' => 'declaration', 'name' => $match[1], 'line' => $lineNumber];
                $index++;

                continue;
            }

            if (preg_match('/^(zolang|als)\s+(.+)\s+{$/', $line, $match)) {
                $index++;
                $type = $match[1] === 'zolang' ? 'while' : 'if';
                $statement = [
                    'type' => $type,
                    'condition' => trim($match[2]),
                    'body' => $this->parseBlock($lines, $index, true),
                    'line' => $lineNumber,
                ];

                if ($type === 'if' && ($lines[$index]['text'] ?? null) === 'anders {') {
                    $index++;
                    $statement['else'] = $this->parseBlock($lines, $index, true);
                }

                $statements[] = $statement;

                continue;
            }

            if (preg_match('/^([a-z])\s*=\s*(.+)$/', $line, $match)) {
                $statements[] = ['type' => 'assignment', 'name' => $match[1], 'expression' => trim($match[2]), 'line' => $lineNumber];
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

    private function purchaseProgram(array $program): void
    {
        $analysis = [
            'while' => 0,
            'if' => 0,
            'command' => 0,
            'assignment' => 0,
            'declarations' => [],
        ];
        $this->analyseProgram($program, $analysis);

        foreach ([
            ['while', 'zolang', 'zolang'],
            ['if', 'als', 'als'],
            ['command', 'opdracht', 'opdracht'],
            ['assignment', 'toekenning', 'toekenning'],
        ] as [$countKey, $costKey, $label]) {
            $count = $analysis[$countKey];
            $cost = (int) ($this->costs[$costKey] ?? 0);
            $this->recordCost("kosten {$label}: {$count} * {$cost}", $count * $cost);
        }

        foreach ($analysis['declarations'] as $declaration) {
            $name = $declaration['name'];

            if (in_array($name, self::HARDWARE, true)) {
                if (in_array($name, $this->hardware, true)) {
                    throw new RuntimeException("Regel {$declaration['line']}: hardware {$name} is al gedeclareerd.");
                }

                $costKey = match ($name) {
                    'kompas' => 'kompas',
                    'zwOog' => 'zwOogHardware',
                    'kleurOog' => 'kleurOogHardware',
                };
                $cost = (int) ($this->costs[$costKey] ?? 0);
                $this->recordCost("kosten voor declaratie '{$name}': {$cost}", $cost);
                $this->hardware[] = $name;

                continue;
            }

            if (array_key_exists($name, $this->variables)) {
                throw new RuntimeException("Regel {$declaration['line']}: variabele {$name} is al gedeclareerd.");
            }

            $cost = (int) ($this->costs['variabele'] ?? 30);
            $this->recordCost("kosten voor declaratie variabele '{$name}': {$cost}", $cost);
            $this->variables[$name] = 0;
        }
    }

    private function analyseProgram(array $statements, array &$analysis): void
    {
        foreach ($statements as $statement) {
            match ($statement['type']) {
                'declaration' => $analysis['declarations'][] = $statement,
                'assignment' => $analysis['assignment']++,
                'command' => $analysis['command']++,
                'while' => $analysis['while']++,
                'if' => $analysis['if']++,
                default => null,
            };

            if (isset($statement['body'])) {
                $this->analyseProgram($statement['body'], $analysis);
            }

            if (isset($statement['else'])) {
                $this->analyseProgram($statement['else'], $analysis);
            }
        }
    }

    private function executeBlock(array $statements): void
    {
        foreach ($statements as $statement) {
            if ($this->haltStatus !== null) {
                return;
            }

            $this->guardExecutionLimit();

            match ($statement['type']) {
                'declaration' => null,
                'assignment' => $this->assign($statement),
                'command' => $this->command($statement['command']),
                'if' => $this->conditional($statement),
                'while' => $this->loop($statement),
                default => null,
            };
        }
    }

    private function assign(array $statement): void
    {
        $this->requireVariable($statement['name'], $statement['line']);
        [$value, $operations] = $this->evaluateArithmetic($statement['expression'], $statement['line']);
        $this->chargeOperations($operations);
        $cost = (int) ($this->costs['toewijzing'] ?? 2);
        $this->recordCost("kosten voor het toewijzen van een waarde: {$value} aan {$statement['name']}: {$cost}", $cost);
        $this->variables[$statement['name']] = $value;
    }

    private function conditional(array $statement): void
    {
        if ($this->evaluateCondition($statement['condition'], $statement['line'])) {
            $this->executeBlock($statement['body']);

            return;
        }

        $this->executeBlock($statement['else'] ?? []);
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
        if (! preg_match('/^(.+?)\s*(==|!=|<|>)\s*(.+)$/', $condition, $match)) {
            throw new RuntimeException("Regel {$line}: ongeldige vergelijking '{$condition}'.");
        }

        [$left, $leftOperations] = $this->evaluateArithmetic($match[1], $line);
        [$right, $rightOperations] = $this->evaluateArithmetic($match[3], $line);
        $this->chargeOperations($leftOperations + $rightOperations);
        $cost = (int) ($this->costs['vergelijking'] ?? 2);
        $this->recordCost("kosten voor het vergelijken: {$cost}", $cost);

        return match ($match[2]) {
            '==' => $left === $right,
            '!=' => $left !== $right,
            '<' => $left < $right,
            '>' => $left > $right,
        };
    }

    /** @return array{0:int,1:int} */
    private function evaluateArithmetic(string $expression, int $line): array
    {
        $compactExpression = preg_replace('/\s+/', '', $expression) ?? '';
        preg_match_all('/kleurOog|zwOog|kompas|\d+|[a-z]|[+\-*\/%]/', $compactExpression, $matches);
        $tokens = $matches[0];

        if ($tokens === [] || implode('', $tokens) !== $compactExpression) {
            throw new RuntimeException("Regel {$line}: ongeldige expressie '{$expression}'.");
        }

        $values = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '%' => 2];

        foreach ($tokens as $index => $token) {
            if ($index % 2 === 0) {
                if (! preg_match('/^(\d+|[a-z]|kleurOog|zwOog|kompas)$/', $token)) {
                    throw new RuntimeException("Regel {$line}: ongeldige expressie '{$expression}'.");
                }

                $values[] = $this->operandValue($token, $line);

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

    private function operandValue(string $operand, int $line): int
    {
        if (ctype_digit($operand)) {
            return (int) $operand;
        }

        if (in_array($operand, self::HARDWARE, true)) {
            return $this->readHardware($operand, $line);
        }

        return $this->variableValue($operand, $line);
    }

    private function readHardware(string $name, int $line): int
    {
        if (! in_array($name, $this->hardware, true)) {
            throw new RuntimeException("Regel {$line}: hardware {$name} is niet geïnitialiseerd met 'gebruik {$name}'.");
        }

        $costKey = $name === 'kompas' ? 'kompasVerbruik' : $name;
        $cost = (int) ($this->costs[$costKey] ?? 0);
        $label = match ($name) {
            'kompas' => 'kompas',
            'zwOog' => 'zwart-wit-oog',
            'kleurOog' => 'kleur-oog',
        };
        $this->recordCost("kosten voor gebruik {$label}: {$cost}", $cost);

        if ($name === 'kompas') {
            return $this->direction;
        }

        $color = $this->tileColor($this->tiles[$this->position]);

        return $name === 'zwOog' ? (int) ($color !== 0) : $color;
    }

    private function tileColor(string $tile): int
    {
        return match ($tile[0]) {
            'C' => (int) $tile[1],
            'D', 'E' => 4,
            'R' => 2,
            'S', 'B' => 0,
            default => 3,
        };
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

    private function chargeOperations(int $operations): void
    {
        $cost = (int) ($this->costs['operatie'] ?? 2);

        for ($operation = 0; $operation < $operations; $operation++) {
            $this->recordCost("kosten voor het rekenen: {$cost}", $cost);
        }
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
        $armedAtStart = array_keys($this->armedBombs);

        if ($command === 'draaiLinks' || $command === 'draaiRechts') {
            $cost = (int) ($this->costs[$command] ?? $this->costs['draaien'] ?? 1);
            $label = $command === 'draaiLinks' ? 'links' : 'rechts';
            $this->recordCost("kosten voor het draaien naar {$label}: {$cost}", $cost);
            $this->direction = ($this->direction + ($command === 'draaiLinks' ? 3 : 1)) % 4;

            if (str_starts_with($this->tiles[$this->position], 'R')) {
                $this->applyRotationTile($this->tiles[$this->position]);
            }

            $this->advanceBombs($armedAtStart);

            return;
        }

        $stepDirection = $command === 'stapAchteruit' ? ($this->direction + 2) % 4 : $this->direction;
        [$row, $column] = $this->coordinates($this->position);
        [$rowDelta, $columnDelta] = [[-1, 0], [0, 1], [1, 0], [0, -1]][$stepDirection];
        $nextRow = $row + $rowDelta;
        $nextColumn = $column + $columnDelta;

        if (! $this->inside($nextRow, $nextColumn)) {
            $this->leaveGlade($nextRow, $nextColumn);

            return;
        }

        $next = $nextRow * 20 + $nextColumn;

        if (str_starts_with($this->tiles[$next], 'O')) {
            $this->collideWithObstacle($next, $stepDirection, $command);
            $this->advanceBombs($armedAtStart);

            return;
        }

        $this->completeStep($next, $command);
        $this->advanceBombs($armedAtStart);
    }

    private function collideWithObstacle(int $obstaclePosition, int $direction, string $command): void
    {
        $obstacle = $this->tiles[$obstaclePosition];
        $collisionCost = (int) ($this->costs['duwen'] ?? 100);
        $this->recordCost("kosten voor botsen / duwen: {$collisionCost}", $collisionCost);

        [$row, $column] = $this->coordinates($obstaclePosition);
        [$rowDelta, $columnDelta] = [[-1, 0], [0, 1], [1, 0], [0, -1]][$direction];
        $targetRow = $row + $rowDelta;
        $targetColumn = $column + $columnDelta;
        $name = ['puin', 'heg', 'steen', 'hout'][(int) $obstacle[1]] ?? 'obstakel';

        if ($obstacle !== 'O3') {
            $this->log[] = "{$name} verplaatsing naar [{$targetRow},{$targetColumn}] geblokkeerd";

            return;
        }

        if (! $this->inside($targetRow, $targetColumn)) {
            $this->tiles[$obstaclePosition] = 'C3';
            $this->log[] = 'hout uit the Glade geduwd.';
            $this->completeStep($obstaclePosition, $command);

            return;
        }

        $target = $targetRow * 20 + $targetColumn;

        if (! in_array($this->tiles[$target][0], ['C', 'S', 'D', 'E'], true)) {
            $this->log[] = "hout verplaatsing naar [{$targetRow},{$targetColumn}] geblokkeerd";

            return;
        }

        $this->tiles[$target] = 'O3';
        $this->tiles[$obstaclePosition] = 'C3';
        $this->log[] = "hout verplaatst naar [{$targetRow},{$targetColumn}]";
        $this->completeStep($obstaclePosition, $command);
    }

    private function completeStep(int $next, string $command): void
    {
        $cost = (int) ($this->costs[$command] ?? 1);
        [$row, $column] = $this->coordinates($next);
        $label = $command === 'stapAchteruit' ? 'achteruit' : 'vooruit';
        $this->recordCost("kosten voor stap {$label}: {$cost} naar: [{$row},{$column}]", $cost);
        $this->position = $next;
        $this->applyTile($this->tiles[$this->position]);
    }

    private function applyTile(string $tile): void
    {
        if (str_starts_with($tile, 'B')) {
            $delay = (int) $tile[1];

            if ($delay === 0) {
                $this->explodeBomb($this->position);

                return;
            }

            $this->armedBombs[$this->position] = $delay;
        }

        if (str_starts_with($tile, 'R')) {
            $this->applyRotationTile($tile);
        }

        if (str_starts_with($tile, 'E') && ! in_array($tile, $this->collected, true)) {
            $bonus = 2 ** (int) $tile[1];
            $this->budget += $bonus;
            $this->collected[] = $tile;
            $this->tiles[$this->position] = 'C4';
            $this->log[] = "bonus van {$bonus} gepakt.";
        }

        if ($tile === "D{$this->goal}") {
            [$row, $column] = $this->coordinates($this->position);
            $this->log[] = "doel [{$row}, {$column}] bereikt.";
            $this->goal++;

            if ($this->goal > $this->goalCount) {
                $this->haltStatus = 'Doel bereikt';
            }
        }
    }

    private function applyRotationTile(string $tile): void
    {
        $rotation = (int) $tile[1];

        if ($rotation === 0) {
            $this->direction = random_int(0, 3);
            $this->log[] = "nieuwe willekeurige richting = {$this->direction}";

            return;
        }

        $this->direction = ($this->direction + $rotation) % 4;
    }

    private function advanceBombs(array $armedAtStart): void
    {
        foreach ($armedAtStart as $position) {
            if (! isset($this->armedBombs[$position])) {
                continue;
            }

            $this->armedBombs[$position]--;

            if ($this->armedBombs[$position] <= 0) {
                $this->explodeBomb($position);
            }
        }
    }

    private function explodeBomb(int $position): void
    {
        unset($this->armedBombs[$position]);
        $this->tiles[$position] = 'O0';
        [$row, $column] = $this->coordinates($position);
        $this->log[] = "bom op [{$row},{$column}] ontploft.";

        if ($this->position === $position) {
            $this->forfeitBudget();
            $this->haltStatus = 'Bom geraakt';
        }
    }

    private function leaveGlade(int $row, int $column): void
    {
        $this->log[] = "uitzondering: Uit the Glade gelopen! [{$row},{$column}]";
        $this->log[] = 'The rule says: "Don\'t enter the maze"';
        $this->forfeitBudget();
        $this->haltStatus = 'Uit de Glade gelopen';
    }

    private function forfeitBudget(): void
    {
        if ($this->budget > 0) {
            $this->spent += $this->budget;
        }

        $this->budget = 0;
    }

    private function recordCost(string $message, int $amount): void
    {
        $this->log[] = $message;
        $this->charge($amount);
    }

    private function charge(int $amount): void
    {
        if ($amount <= $this->budget) {
            $this->budget -= $amount;
            $this->spent += $amount;

            return;
        }

        $this->spent += max(0, $this->budget);
        $this->budget = 0;
        $this->haltStatus = 'Budget overschreden';

        throw new RuntimeException('Het beschikbare budget is overschreden.');
    }

    /** @return array{0:int,1:int} */
    private function coordinates(int $position): array
    {
        return [intdiv($position, 20), $position % 20];
    }

    private function inside(int $row, int $column): bool
    {
        return $row >= 0 && $row < 20 && $column >= 0 && $column < 20;
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
                'hardware' => $this->hardware,
                'tiles' => $this->tiles,
            ],
        ];
    }
}
