<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GladeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_screenshot_import_page_is_available(): void
    {
        $this->get(route('glades.import.create'))
            ->assertOk()
            ->assertSee('Importeer een bestaande glade.')
            ->assertSee('name="screenshot"', false);
    }

    public function test_a_screenshot_is_recognized_and_opened_as_an_editable_preview(): void
    {
        $this->post(route('glades.import.preview'), [
            'screenshot' => UploadedFile::fake()->createWithContent('glade.png', $this->screenshot()),
        ])
            ->assertOk()
            ->assertSee('Controleer de import.')
            ->assertSee('data-code="S1"', false)
            ->assertSee('data-code="D1"', false)
            ->assertSee('name="start_capital"', false)
            ->assertSee('name="costs[stapVooruit]"', false);

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_an_image_without_a_complete_grid_is_rejected(): void
    {
        $this->post(route('glades.import.preview'), [
            'screenshot' => UploadedFile::fake()->image('geen-grid.png', 500, 500),
        ])->assertSessionHasErrors('screenshot');
    }

    private function screenshot(): string
    {
        $image = imagecreatetruecolor(622, 622);
        $background = imagecolorallocate($image, 255, 253, 248);
        $border = imagecolorallocate($image, 173, 181, 171);
        $green = imagecolorallocate($image, 48, 162, 76);
        $purple = imagecolorallocate($image, 117, 58, 148);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 82, 71);
        imagefill($image, 0, 0, $background);

        for ($row = 0; $row < 20; $row++) {
            for ($column = 0; $column < 20; $column++) {
                $left = 10 + ($column * 30);
                $top = 10 + ($row * 30);
                imagefilledrectangle($image, $left + 1, $top + 1, $left + 29, $top + 29, $green);
                imagestring($image, 1, $left + 3, $top + 1, '3', $white);
            }
        }

        imagefilledrectangle($image, 11, 11, 39, 39, $purple);
        imagestring($image, 1, 13, 11, '1', $white);
        $this->drawStart($image, 1, 1, $white, $black, $red);
        $this->drawGoal($image, 1, 2, $white, $black);
        imagerectangle($image, 10, 10, 609, 609, $border);

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function drawStart(\GdImage $image, int $row, int $column, int $white, int $black, int $red): void
    {
        $left = 10 + ($column * 30);
        $top = 10 + ($row * 30);
        imagefilledrectangle($image, $left + 1, $top + 1, $left + 29, $top + 29, $white);
        imagestring($image, 1, $left + 3, $top + 1, '1', $black);
        imagefilledpolygon($image, [
            $left + 9, $top + 8,
            $left + 23, $top + 15,
            $left + 9, $top + 22,
        ], $red);
    }

    private function drawGoal(\GdImage $image, int $row, int $column, int $white, int $black): void
    {
        $left = 10 + ($column * 30);
        $top = 10 + ($row * 30);
        imagefilledrectangle($image, $left + 1, $top + 1, $left + 29, $top + 29, $white);
        imagestring($image, 1, $left + 3, $top + 1, '1', $black);
        imagesetthickness($image, 3);
        imageellipse($image, $left + 16, $top + 16, 14, 14, $black);
        imageline($image, $left + 6, $top + 16, $left + 26, $top + 16, $black);
        imageline($image, $left + 16, $top + 6, $left + 16, $top + 26, $black);
        imagesetthickness($image, 1);
    }
}
