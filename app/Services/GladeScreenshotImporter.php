<?php

namespace App\Services;

use GdImage;
use RuntimeException;

class GladeScreenshotImporter
{
    private const SIZE = 20;

    /** @var array<string, string> */
    private const FLOOR_COLORS = [
        '40,46,43' => 'C0',
        '117,58,148' => 'C1',
        '42,100,170' => 'C2',
        '48,162,76' => 'C3',
        '216,214,42' => 'C4',
        '237,172,80' => 'C5',
        '200,73,61' => 'C6',
        '144,147,144' => 'C7',
        '245,242,231' => 'C8',
    ];

    /**
     * @return array{map_definition:string,warnings:array<int, string>}
     */
    public function import(string $path): array
    {
        $contents = file_get_contents($path);
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('De afbeelding kon niet worden gelezen. Gebruik een PNG-, JPG- of WebP-bestand.');
        }

        [$left, $top, $gridWidth, $gridHeight] = $this->detectGrid($image);
        $cells = $this->extractCells($image, $left, $top, $gridWidth, $gridHeight);
        [$floors, $digitSamples] = $this->recognizeFloors($cells);
        $warnings = [];
        $tiles = [];

        foreach ($cells as $index => $cell) {
            if (isset($floors[$index])) {
                $tiles[] = $floors[$index];

                continue;
            }

            [$tile, $warning] = $this->recognizeObject($cell, $digitSamples, $index);
            $tiles[] = $tile;

            if ($warning !== null) {
                $warnings[] = $warning;
            }
        }

        $this->addMapWarnings($tiles, $warnings);

        return [
            'map_definition' => implode(' ', $tiles),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @return array{int, int, int, int}
     */
    private function detectGrid(GdImage $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $backgrounds = [
            $this->rgbAt($image, 0, 0),
            $this->rgbAt($image, $width - 1, 0),
            $this->rgbAt($image, 0, $height - 1),
            $this->rgbAt($image, $width - 1, $height - 1),
        ];
        $best = ['length' => 0, 'x' => 0, 'start' => 0, 'color' => [0, 0, 0]];

        for ($x = 0; $x < $width; $x++) {
            $start = 0;
            $color = $this->rgbAt($image, $x, 0);

            for ($y = 1; $y <= $height; $y++) {
                $next = $y < $height ? $this->rgbAt($image, $x, $y) : null;

                if ($next !== null && $this->colorDistance($color, $next) <= 6) {
                    continue;
                }

                $length = $y - $start;
                $isBackground = collect($backgrounds)->contains(
                    fn (array $background): bool => $this->colorDistance($background, $color) <= 12,
                );

                if (! $isBackground && $length > $best['length']) {
                    $best = compact('length', 'x', 'start', 'color');
                }

                $start = $y;
                $color = $next ?? $color;
            }
        }

        if ($best['length'] < min($width, $height) * .55) {
            throw new RuntimeException('Het 20×20-raster kon niet worden gevonden. Upload een screenshot waarop de volledige kaart zichtbaar is.');
        }

        $lineColor = $best['color'];
        $runTop = $best['start'];
        $runBottom = $runTop + $best['length'] - 1;
        $verticalLines = [];

        for ($x = 0; $x < $width; $x++) {
            $matches = 0;

            for ($y = $runTop; $y <= $runBottom; $y++) {
                $matches += $this->colorDistance($this->rgbAt($image, $x, $y), $lineColor) <= 10 ? 1 : 0;
            }

            if ($matches >= $best['length'] * .85) {
                $verticalLines[] = $x;
            }
        }

        if (count($verticalLines) < 2) {
            throw new RuntimeException('De linker- en rechterrand van het raster konden niet worden gevonden.');
        }

        $left = min($verticalLines);
        $right = max($verticalLines);
        $horizontalLines = [];

        for ($y = 0; $y < $height; $y++) {
            $matches = 0;

            for ($x = $left; $x <= $right; $x++) {
                $matches += $this->colorDistance($this->rgbAt($image, $x, $y), $lineColor) <= 10 ? 1 : 0;
            }

            if ($matches >= ($right - $left + 1) * .85) {
                $horizontalLines[] = $y;
            }
        }

        if (count($horizontalLines) < 2) {
            throw new RuntimeException('De boven- en onderrand van het raster konden niet worden gevonden.');
        }

        $top = min($horizontalLines);
        $bottom = max($horizontalLines);
        $gridWidth = $right - $left + 1;
        $gridHeight = $bottom - $top + 1;

        if ($gridWidth < 300 || $gridHeight < 300 || abs($gridWidth - $gridHeight) > max($gridWidth, $gridHeight) * .03) {
            throw new RuntimeException('Het gevonden raster is niet groot of vierkant genoeg voor een 20×20-glade.');
        }

        return [$left, $top, $gridWidth, $gridHeight];
    }

    /**
     * @return array<int, GdImage>
     */
    private function extractCells(GdImage $image, int $left, int $top, int $gridWidth, int $gridHeight): array
    {
        $cells = [];

        for ($row = 0; $row < self::SIZE; $row++) {
            for ($column = 0; $column < self::SIZE; $column++) {
                $x1 = (int) round($left + ($column * $gridWidth / self::SIZE));
                $x2 = (int) round($left + (($column + 1) * $gridWidth / self::SIZE));
                $y1 = (int) round($top + ($row * $gridHeight / self::SIZE));
                $y2 = (int) round($top + (($row + 1) * $gridHeight / self::SIZE));
                $cell = imagecreatetruecolor(37, 37);
                imagecopyresized($cell, $image, 0, 0, $x1 + 1, $y1 + 1, 37, 37, max(1, $x2 - $x1 - 1), max(1, $y2 - $y1 - 1));
                $cells[] = $cell;
            }
        }

        return $cells;
    }

    /**
     * @param  array<int, GdImage>  $cells
     * @return array{array<int, string>, array<string, array<int, array<int, array<int, float>>>>}
     */
    private function recognizeFloors(array $cells): array
    {
        $floors = [];
        $samples = [];

        foreach ($cells as $index => $cell) {
            $counts = array_fill_keys(array_keys(self::FLOOR_COLORS), 0);

            for ($y = 1; $y < 36; $y++) {
                for ($x = 1; $x < 36; $x++) {
                    $pixel = $this->rgbAt($cell, $x, $y);

                    foreach (self::FLOOR_COLORS as $key => $code) {
                        $color = array_map('intval', explode(',', $key));

                        if ($this->colorDistance($pixel, $color) <= 24) {
                            $counts[$key]++;
                        }
                    }
                }
            }

            $key = array_search(max($counts), $counts, true);

            if ($key === false || $counts[$key] < 400) {
                continue;
            }

            $code = self::FLOOR_COLORS[$key];
            $floors[$index] = $code;
            $digit = substr($code, 1);

            if (count($samples[$digit] ?? []) < 12) {
                $samples[$digit][] = $this->digitFeature(
                    $cell,
                    array_map('intval', explode(',', $key)),
                    $digit === '8' ? -1 : 1,
                );
            }
        }

        return [$floors, $samples];
    }

    /**
     * @param  array<string, array<int, array<int, array<int, float>>>>  $digitSamples
     * @return array{string, ?string}
     */
    private function recognizeObject(GdImage $cell, array $digitSamples, int $index): array
    {
        $features = $this->objectFeatures($cell);
        $type = match (true) {
            $features['red'] > 25 => 'S',
            $features['green'] > 280 && $features['black'] > 80 => 'O1',
            $features['lime'] > 190 && $features['brown'] < 60 && $features['black'] < 40 => 'R',
            $features['green'] > 80 && $features['yellow'] > 70 => 'E',
            $features['brown'] > 220 => 'O3',
            $features['darkGray'] > 220 && $features['black'] > 70 => 'O2',
            $features['black'] > 80 && $features['darkGray'] < 100 => 'D',
            $features['darkGray'] > 80 && $features['black'] < 60 => 'B',
            $features['midGray'] > 120 => 'O0',
            default => null,
        };
        [$row, $column] = [intdiv($index, self::SIZE) + 1, ($index % self::SIZE) + 1];

        if ($type === null) {
            return ['C8', "Tegel op rij {$row}, kolom {$column} kon niet zeker worden herkend en is als C8 ingevuld."];
        }

        if (str_starts_with($type, 'O')) {
            return [$type, null];
        }

        if ($type === 'S') {
            return ['S'.$this->startDirection($cell), null];
        }

        [$digit, $score] = $this->recognizeDigit($cell, $digitSamples, $type);
        $warning = $score > 1.25
            ? "Controleer {$type}{$digit} op rij {$row}, kolom {$column}; het cijfer was minder scherp."
            : null;

        return [$type.$digit, $warning];
    }

    private function startDirection(GdImage $cell): int
    {
        $points = [];

        for ($y = 3; $y < 35; $y++) {
            for ($x = 3; $x < 35; $x++) {
                [$red, $green, $blue] = $this->rgbAt($cell, $x, $y);

                if ($red > 160 && $red > $green * 1.5 && $red > $blue * 1.4) {
                    $points[] = [$x, $y];
                }
            }
        }

        if ($points === []) {
            return 0;
        }

        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $centerX = (min($xs) + max($xs)) / 2;
        $centerY = (min($ys) + max($ys)) / 2;
        $centroidX = array_sum($xs) / count($xs);
        $centroidY = array_sum($ys) / count($ys);
        $horizontalOffset = $centroidX - $centerX;
        $verticalOffset = $centroidY - $centerY;

        if (abs($horizontalOffset) > abs($verticalOffset)) {
            return $horizontalOffset < 0 ? 1 : 3;
        }

        return $verticalOffset > 0 ? 0 : 2;
    }

    /**
     * @return array{red:int,brown:int,green:int,yellow:int,lime:int,darkGray:int,midGray:int,black:int}
     */
    private function objectFeatures(GdImage $cell): array
    {
        $features = array_fill_keys(['red', 'brown', 'green', 'yellow', 'lime', 'darkGray', 'midGray', 'black'], 0);

        for ($y = 3; $y < 35; $y++) {
            for ($x = 3; $x < 35; $x++) {
                if ($x < 11 && $y < 11) {
                    continue;
                }

                [$red, $green, $blue] = $this->rgbAt($cell, $x, $y);
                $maximum = max($red, $green, $blue);
                $minimum = min($red, $green, $blue);
                $average = ($red + $green + $blue) / 3;
                $features['red'] += $red > 160 && $red > $green * 1.5 && $red > $blue * 1.4 ? 1 : 0;
                $features['brown'] += $red > 60 && $red > $green * 1.25 && $green > $blue * 1.05 && $blue < 110 ? 1 : 0;
                $features['green'] += $green > 40 && $green > $red * 1.18 && $green > $blue * 1.1 && $green < 180 ? 1 : 0;
                $features['yellow'] += $red > 110 && $green > 75 && $red > $green * 1.05 && $blue < 90 ? 1 : 0;
                $features['lime'] += $green > 100 && $green > $red * 1.15 && $blue < 100 ? 1 : 0;
                $features['darkGray'] += $maximum - $minimum < 18 && $average > 40 && $average < 115 ? 1 : 0;
                $features['midGray'] += $maximum - $minimum < 25 && $average >= 115 && $average < 225 ? 1 : 0;
                $features['black'] += $maximum < 45 ? 1 : 0;
            }
        }

        return $features;
    }

    /**
     * @param  array<string, array<int, array<int, array<int, float>>>>  $samples
     * @return array{string, float}
     */
    private function recognizeDigit(GdImage $cell, array $samples, string $type): array
    {
        $feature = $this->digitFeature($cell, [255, 255, 255], -1);
        $bestDigit = match ($type) {
            'S', 'B' => '0',
            default => '1',
        };
        $bestScore = INF;

        foreach ($samples as $digit => $digitSamples) {
            foreach ($digitSamples as $sample) {
                $score = $this->digitDistance($feature, $sample);

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestDigit = $digit;
                }
            }
        }

        return [$bestDigit, is_finite($bestScore) ? $bestScore : 99.0];
    }

    /**
     * @return array<int, array<int, float>>
     */
    private function digitFeature(GdImage $cell, array $background, int $sign): array
    {
        $feature = [];
        $maximum = 0.0;
        $backgroundLuminance = $this->luminance($background);

        for ($y = 0; $y < 9; $y++) {
            $row = [];

            for ($x = 1; $x < 11; $x++) {
                $contrast = max(0, ($this->luminance($this->rgbAt($cell, $x, $y)) - $backgroundLuminance) * $sign);
                $maximum = max($maximum, $contrast);
                $row[] = $contrast;
            }

            $feature[] = $row;
        }

        $maximum = max(1.0, $maximum);

        return array_map(
            fn (array $row): array => array_map(fn (float $value): float => $value / $maximum, $row),
            $feature,
        );
    }

    /**
     * @param  array<int, array<int, float>>  $first
     * @param  array<int, array<int, float>>  $second
     */
    private function digitDistance(array $first, array $second): float
    {
        $best = INF;

        foreach ([-1, 0, 1] as $verticalShift) {
            foreach ([-1, 0, 1] as $horizontalShift) {
                $score = 0.0;

                for ($y = 0; $y < 9; $y++) {
                    for ($x = 0; $x < 10; $x++) {
                        $comparisonY = $y + $verticalShift;
                        $comparisonX = $x + $horizontalShift;
                        $comparison = $comparisonY >= 0 && $comparisonY < 9 && $comparisonX >= 0 && $comparisonX < 10
                            ? $second[$comparisonY][$comparisonX]
                            : 0.0;
                        $score += ($first[$y][$x] - $comparison) ** 2;
                    }
                }

                $best = min($best, $score);
            }
        }

        return $best;
    }

    /**
     * @param  array<int, string>  $tiles
     * @param  array<int, string>  $warnings
     */
    private function addMapWarnings(array $tiles, array &$warnings): void
    {
        $starts = collect($tiles)->filter(fn (string $tile): bool => str_starts_with($tile, 'S'));
        $goals = collect($tiles)->filter(fn (string $tile): bool => str_starts_with($tile, 'D'));

        if ($starts->count() !== 1) {
            $warnings[] = "Er zijn {$starts->count()} starttegels herkend; plaats in de editor precies één starttegel.";
        }

        if ($goals->isEmpty()) {
            $warnings[] = 'Er is geen doel herkend; plaats minimaal D1 in de editor.';
        } elseif ($goals->duplicates()->isNotEmpty()) {
            $warnings[] = 'Sommige doelnummers zijn dubbel herkend; controleer de doelen in de editor.';
        }

        $bonuses = collect($tiles)->filter(fn (string $tile): bool => str_starts_with($tile, 'E'));

        if ($bonuses->duplicates()->isNotEmpty()) {
            $warnings[] = 'Sommige bonusnummers zijn dubbel herkend; controleer de bonustegels in de editor.';
        }
    }

    /** @return array{int, int, int} */
    private function rgbAt(GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    private function colorDistance(array $first, array $second): float
    {
        return sqrt(
            ($first[0] - $second[0]) ** 2
            + ($first[1] - $second[1]) ** 2
            + ($first[2] - $second[2]) ** 2,
        );
    }

    private function luminance(array $color): float
    {
        return .299 * $color[0] + .587 * $color[1] + .114 * $color[2];
    }
}
