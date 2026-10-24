<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\OverviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', OverviewController::class)->name('overview');

Route::get('/decisions', [DecisionController::class, 'index'])->name('decisions.index');
Route::get('/decisions/create', [DecisionController::class, 'create'])->name('decisions.create');
Route::post('/decisions', [DecisionController::class, 'store'])->name('decisions.store');
Route::get('/decisions/{decision}', [DecisionController::class, 'show'])->name('decisions.show');
Route::get('/decisions/{decision}/edit', [DecisionController::class, 'edit'])->name('decisions.edit');
Route::put('/decisions/{decision}', [DecisionController::class, 'update'])->name('decisions.update');

Route::get('/checks', [CheckController::class, 'show'])->name('checks.show');
Route::post('/checks', [CheckController::class, 'store'])->name('checks.store');
Route::post('/findings/{finding}/review', [CheckController::class, 'review'])->name('findings.review');
