<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Deputy;
use App\Http\Controllers\PostalCodeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/deputies/foreign', function () {
    return Deputy::where('departement', '099')
        ->where('is_active', true)
        ->orderBy('circonscription')
        ->select(['id', 'prenom', 'nom', 'circonscription', 'groupe_politique', 'photo'])
        ->get();
});

Route::get('/postal-code/search', [PostalCodeController::class, 'search']);
