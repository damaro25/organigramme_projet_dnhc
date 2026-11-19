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

        $perimetre = $request->user()->perimetre();

        return view('admin.tableau-de-bord', $service->calculer($filtres, $perimetre) + [
            'filtres' => $filtres,
            'listeDivisions' => $perimetre->restreindreStructures(Structure::deType(TypeStructure::Division))->ordonne()->get(),
            'listeSections' => $perimetre->restreindreStructures(Structure::deType(TypeStructure::Section))->ordonne()->get(),
            'listeAgents' => $perimetre->restreindreAgents(Agent::actifs())->orderBy('nom')->get(),
            'peutExporterOrganigramme' => $perimetre->estGlobal(),
            'mesProjets' => $request->user()->agent?->projets()->nonArchives()->avecSommesPoids()->get() ?? collect(),
        ]);
    }
}
