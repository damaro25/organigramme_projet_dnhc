<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\FicheAgentController;
use App\Http\Controllers\OrganigrammeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Organigramme et fiches des agents : réservés aux comptes connectés. Chacun n'y voit
// que sa fiche et la branche placée sous lui (voir App\Services\PerimetreVisibilite).
Route::middleware('auth')->group(function () {
    Route::get('/', [OrganigrammeController::class, 'index'])->name('organigramme');
    Route::get('/organigramme/donnees', [OrganigrammeController::class, 'donnees'])->name('organigramme.donnees');
    Route::get('/agents/{agent}/fiche', [FicheAgentController::class, 'show'])->name('agents.fiche');

    // Exports PDF : les mêmes informations que l'organigramme affiché au compte.
    Route::get('/organigramme.pdf', [ExportPdfController::class, 'organigramme'])->name('organigramme.pdf');
    Route::get('/agents/{agent}/fiche.pdf', [ExportPdfController::class, 'ficheAgent'])->name('agents.fiche.pdf');
});

// Après connexion, Breeze redirige vers « dashboard » : c'est le tableau de bord de l'administration.
Route::redirect('/dashboard', '/admin')->name('dashboard');

// Espace d'administration.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:acceder_admin'])->group(function () {
    Route::get('/', Admin\TableauDeBordController::class)->name('tableau-de-bord');

    Route::get('organigramme', [Admin\OrganigrammeAdminController::class, 'index'])->name('organigramme');
    Route::get('organigramme/donnees', [Admin\OrganigrammeAdminController::class, 'donnees'])->name('organigramme.donnees');

    Route::resource('agents', Admin\AgentController::class)->except(['show', 'destroy']);
    Route::patch('agents/{agent}/poste', [Admin\AgentController::class, 'modifierPoste'])->name('agents.poste');
    Route::patch('agents/{agent}/actif', [Admin\AgentController::class, 'basculerActif'])->name('agents.actif');
    Route::post('agents/{agent}/photo', [Admin\AgentPhotoController::class, 'store'])->name('agents.photo.store');
    Route::delete('agents/{agent}/photo', [Admin\AgentPhotoController::class, 'destroy'])->name('agents.photo.destroy');

    Route::resource('projets', Admin\ProjetController::class)->except(['show']);
    Route::patch('projets/{projet}/archiver', [Admin\ProjetController::class, 'archiver'])->name('projets.archiver');
    Route::post('projets/{projet}/dupliquer', [Admin\ProjetController::class, 'dupliquer'])->name('projets.dupliquer');
    Route::patch('projets/{projet}/valider', [Admin\ProjetController::class, 'valider'])->name('projets.valider');
    Route::get('projets/{projet}/pieces-jointes/{pieceJointe}', [Admin\ProjetController::class, 'telechargerPieceJointe'])->name('projets.pieces-jointes.show');
    Route::delete('projets/{projet}/pieces-jointes/{pieceJointe}', [Admin\ProjetController::class, 'supprimerPieceJointe'])->name('projets.pieces-jointes.destroy');

    Route::resource('structures', Admin\StructureController::class)->except(['show', 'destroy']);
    Route::post('structures/reordonner', [Admin\StructureController::class, 'reordonner'])->name('structures.reordonner');

    Route::resource('utilisateurs', Admin\UserController::class)->except(['show', 'destroy'])
        ->parameters(['utilisateurs' => 'utilisateur']);

    Route::get('notifications', Admin\NotificationController::class)->name('notifications');
    Route::get('journal', Admin\JournalController::class)->middleware('can:voir_journal')->name('journal');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
