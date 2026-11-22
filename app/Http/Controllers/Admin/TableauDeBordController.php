<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutProjet;
use App\Enums\TypeStructure;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Structure;
use App\Services\TableauDeBordService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableauDeBordController extends Controller
{
    public function __invoke(Request $request, TableauDeBordService $service): View
    {
        $filtres = $request->validate([
            'division' => ['nullable', 'integer', 'exists:structures,id'],
            'section' => ['nullable', 'integer', 'exists:structures,id'],
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'statut' => ['nullable', Rule::enum(StatutProjet::class)],
        ]);

        $user = $request->user();

        return view('admin.tableau-de-bord', $service->calculer($filtres, $user) + [
            'filtres' => $filtres,
            'perimetre' => $user->voitTousLesProjets() ? null : ($user->structuresGerees() !== [] ? $user->agent->structure->nom : 'vos projets'),
            'listeDivisions' => Structure::deType(TypeStructure::Division)->visiblesPar($user)->ordonne()->get(),
            'listeSections' => Structure::deType(TypeStructure::Section)->visiblesPar($user)->ordonne()->get(),
            'listeAgents' => Agent::actifs()->visiblesPar($user)->orderBy('nom')->get(),
            'mesProjets' => $request->user()->agent?->projets()->nonArchives()->avecSommesPoids()->get() ?? collect(),
        ]);
    }
}
