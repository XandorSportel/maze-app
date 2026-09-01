<?php

namespace App\Http\Controllers;

use App\Services\GladeScreenshotImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GladeImportController extends Controller
{
    public function create(): View
    {
        return view('glades.import');
    }

    public function preview(Request $request, GladeScreenshotImporter $importer): View
    {
        $validated = $request->validate([
            'screenshot' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120', 'dimensions:min_width=300,min_height=300,max_width=4000,max_height=4000'],
        ]);

        try {
            $result = $importer->import($validated['screenshot']->getRealPath());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['screenshot' => $exception->getMessage()]);
        }

        return view('glades.create', [
            'defaultMap' => $result['map_definition'],
            'defaultCosts' => config('glade.default_costs'),
            'importWarnings' => $result['warnings'],
        ]);
    }
}
