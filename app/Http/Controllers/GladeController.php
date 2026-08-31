<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GladeController extends Controller
{
    public function create(): View
    {
        return view('glades.create', [
            'defaultMap' => $this->defaultMap(),
            'defaultCosts' => config('glade.default_costs'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('assignments', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'map_definition' => ['required', 'string'],
            'start_capital' => ['required', 'integer', 'min:1', 'max:1000000'],
            'costs' => ['required', 'array'],
        ];

        foreach (array_keys(config('glade.default_costs')) as $costName) {
            $rules["costs.{$costName}"] = ['required', 'integer', 'min:0', 'max:1000000'];
        }

        $validated = $request->validate($rules);

        $tiles = preg_split('/\s+/', trim($validated['map_definition'])) ?: [];
        $validTile = fn (string $tile): bool => preg_match('/^(C[0-8]|B[0-8]|D[1-9]|E[1-9]|O[0-3]|R[0-3]|S[0-3])$/', $tile) === 1;

        if (count($tiles) !== 400 || ! collect($tiles)->every($validTile)) {
            throw ValidationException::withMessages(['map_definition' => 'De glade moet uit precies 400 geldige tegels bestaan.']);
        }

        if (collect($tiles)->filter(fn (string $tile): bool => str_starts_with($tile, 'S'))->count() !== 1) {
            throw ValidationException::withMessages(['map_definition' => 'Plaats precies één starttegel.']);
        }

        if (! collect($tiles)->contains(fn (string $tile): bool => str_starts_with($tile, 'D'))) {
            throw ValidationException::withMessages(['map_definition' => 'Plaats minimaal één doeltegel.']);
        }

        $assignment = Assignment::create([
            ...$validated,
            'map_definition' => implode(' ', $tiles),
            'costs' => array_map('intval', $validated['costs']),
            'is_custom' => true,
            'is_active' => true,
        ]);

        return redirect()->route('assignments.show', $assignment)->with('success', 'De glade is aangemaakt.');
    }

    private function defaultMap(): string
    {
        $tiles = array_fill(0, 400, 'C3');
        foreach (range(0, 19) as $index) {
            $tiles[$index] = $tiles[380 + $index] = 'O2';
            $tiles[$index * 20] = $tiles[$index * 20 + 19] = 'O2';
        }
        $tiles[21] = 'S1';
        $tiles[378] = 'D1';

        return implode(' ', $tiles);
    }
}
