<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutProjet;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Projet;
use Illuminate\View\View;

/** Tableau de bord provisoire : les graphiques et filtres arrivent à l'étape 6. */
class TableauDeBordController extends Controller
{
    public function __invoke(): View
    {
        $projets = Projet::nonArchives()->avecSommesPoids()->get();

        return view('admin.tableau-de-bord', [
            'parStatut' => collect(StatutProjet::cases())->mapWithKeys(fn ($s) => [$s->libelle() => $projets->where('statut', $s)->count()]),
            'enRetard' => Projet::nonArchives()->enRetard()->count(),
            'tauxGlobal' => Projet::moyenneTaux($projets),
            'nbAgents' => Agent::actifs()->count(),
            'mesProjets' => auth()->user()->agent?->projets()->nonArchives()->avecSommesPoids()->get() ?? collect(),
        ]);
    }
}
