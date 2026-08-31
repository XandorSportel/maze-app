<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Contracts\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = Assignment::query()
            ->where('is_active', true)
            ->withCount('submissions')
            ->latest('is_custom')
            ->orderBy('name')
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function show(Assignment $assignment): View
    {
        $assignment->loadCount('submissions');

        return view('assignments.show', compact('assignment'));
    }
}
