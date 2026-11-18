<?php

use App\Http\Controllers\FicheAgentController;
use App\Http\Controllers\OrganigrammeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Partie publique : organigramme et fiches des agents.
Route::get('/', [OrganigrammeController::class, 'index'])->name('organigramme');
Route::get('/organigramme/donnees', [OrganigrammeController::class, 'donnees'])->name('organigramme.donnees');
Route::get('/agents/{agent}/fiche', [FicheAgentController::class, 'show'])->name('agents.fiche');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
