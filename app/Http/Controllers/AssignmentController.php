<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $assignments = Assignment::query()
            ->where('is_active', true)
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('submissions')
            ->latest('is_custom')
            ->orderBy('name')
            ->get();

        return view('assignments.index', compact('assignments', 'filters'));
    }

    public function show(Assignment $assignment): View
    {
        $assignment->loadCount('submissions');

        return view('assignments.show', compact('assignment'));
    }
}
