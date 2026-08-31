<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\GladeSimulator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'cost_asc', 'cost_desc', 'remaining_asc', 'remaining_desc'])],
        ]);

        $submissions = Submission::query()
            ->with('assignment')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('status', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('assignment', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            });

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $submissions->oldest(),
            'cost_asc' => $submissions->orderBy('total_cost'),
            'cost_desc' => $submissions->orderByDesc('total_cost'),
            'remaining_asc' => $submissions->orderBy('remaining_budget'),
            'remaining_desc' => $submissions->orderByDesc('remaining_budget'),
            default => $submissions->latest(),
        };

        $submissions = $submissions->paginate(15)->withQueryString();

        return view('submissions.index', compact('submissions', 'filters'));
    }

    public function show(Submission $submission): View
    {
        $submission->load('assignment');

        return view('submissions.show', compact('submission'));
    }

    public function store(Request $request, Assignment $assignment, GladeSimulator $simulator): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20000'],
        ]);

        $result = $simulator->run($assignment, $validated['code']);
        $submission = $assignment->submissions()->create([
            'code' => $validated['code'],
            ...$result,
        ]);

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'De poging is uitgevoerd en opgeslagen.');
    }
}
