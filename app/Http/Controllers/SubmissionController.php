<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\GladeSimulator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = Submission::query()->with('assignment')->latest()->paginate(15);

        return view('submissions.index', compact('submissions'));
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
