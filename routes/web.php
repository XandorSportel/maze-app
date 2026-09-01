<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\GladeController;
use App\Http\Controllers\GladeImportController;
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
Route::get('/glades/importeren', [GladeImportController::class, 'create'])->name('glades.import.create');
Route::post('/glades/importeren', [GladeImportController::class, 'preview'])->name('glades.import.preview');
Route::get('/glades/{assignment}/bewerken', [GladeController::class, 'edit'])->name('glades.edit');
Route::put('/glades/{assignment}', [GladeController::class, 'update'])->name('glades.update');
