<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DeputyController;
use App\Http\Controllers\DeputyVoteController;
use App\Http\Controllers\VoteController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'deputiesCount' => \App\Models\Deputy::count(),
        'votesCount' => \App\Models\Vote::count(),
        'politicalGroupsCount' => \App\Models\PoliticalGroup::count(),
    ]);
});

// Routes publiques pour les députés
Route::prefix('deputies')->name('deputies.')->group(function () {
    Route::get('/', [DeputyController::class, 'index'])->name('index');

    // Routes API
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/departments', [DeputyController::class, 'getDepartments'])->name('departments');
        Route::get('/departements/{code}/deputes', [DeputyController::class, 'byDepartment'])->name('by-department');
        Route::get('/departements/{code}/stats', [DeputyController::class, 'getDepartmentStats'])->name('department-stats');
        Route::get('/deputes/{slug}', [DeputyController::class, 'show'])->name('show');
        Route::get('/daily-participation/{id}', [DeputyController::class, 'getDailyParticipation'])->name('daily-participation');
    });
});

// Routes pour les votes
Route::prefix('votes')->name('votes.')->group(function () {
    Route::get('/', [VoteController::class, 'index'])->name('index');
    Route::get('/{vote}', [VoteController::class, 'show'])->name('show');
});

// Routes pour les votes des députés
Route::prefix('deputies/{deputy}')->name('deputies.')->group(function () {
    Route::get('/votes', [DeputyVoteController::class, 'show'])->name('votes.show');
    Route::get('/votes/api', [DeputyVoteController::class, 'api'])->name('votes.api');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
