<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DeputyController;
use App\Http\Controllers\DeputyVoteController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\PoliticalGroupController;

Route::get('/', function () {
    // Récupérer les 5 derniers scrutins
    $recentVotes = \App\Models\Vote::orderBy('date_scrutin', 'desc')
        ->take(5)
        ->get(['id', 'numero', 'titre', 'date_scrutin', 'resultat', 'pour', 'contre', 'abstention', 'demandeur']);
    
    // Récupérer les partis politiques avec le nombre de députés
    $politicalGroups = \App\Models\PoliticalGroup::withCount('deputies')
        ->orderBy('deputies_count', 'desc')
        ->take(10)
        ->get(['id', 'libelle_abrege', 'libelle', 'couleur_associee']);

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login') && false,
        'canRegister' => Route::has('register') && false,
        'deputiesCount' => \App\Models\Deputy::count(),
        'votesCount' => \App\Models\Vote::count(),
        'politicalGroupsCount' => \App\Models\PoliticalGroup::count(),
        'recentVotes' => $recentVotes,
        'politicalGroups' => $politicalGroups,
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

// Routes pour les partis politiques
Route::prefix('parties')->name('parties.')->group(function () {
    Route::get('/', [PoliticalGroupController::class, 'index'])->name('index');
    Route::get('/{politicalGroup}', [PoliticalGroupController::class, 'show'])->name('show');
    Route::get('/{politicalGroup}/votes', [PoliticalGroupController::class, 'votes'])->name('votes');
});

// Routes pour les votes des députés
Route::prefix('deputies/{deputy}')->name('deputies.')->group(function () {
    Route::get('/votes', [DeputyVoteController::class, 'show'])->name('votes.show');
    Route::get('/votes/api', [DeputyVoteController::class, 'api'])->name('votes.api');
});

// Routes pour les pages légales
Route::get('/privacy-policy', function () {
    $policy = file_get_contents(resource_path('markdown/policy.md'));
    return Inertia::render('PrivacyPolicy', [
        'policy' => \Illuminate\Support\Str::markdown($policy),
    ]);
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    $terms = file_get_contents(resource_path('markdown/terms.md'));
    return Inertia::render('TermsOfService', [
        'terms' => \Illuminate\Support\Str::markdown($terms),
    ]);
})->name('terms-of-service');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
