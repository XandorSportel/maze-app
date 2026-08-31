<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\GladeController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AssignmentController::class, 'index']);

Route::get('/opdrachten', [AssignmentController::class, 'index'])->name('assignments.index');
Route::get('/opdrachten/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
Route::post('/opdrachten/{assignment}/pogingen', [SubmissionController::class, 'store'])->name('submissions.store');

Route::get('/gemaakte-opdrachten', [SubmissionController::class, 'index'])->name('submissions.index');
Route::get('/gemaakte-opdrachten/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

Route::get('/glades/maken', [GladeController::class, 'create'])->name('glades.create');
Route::post('/glades', [GladeController::class, 'store'])->name('glades.store');
