<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Structure;
use App\Services\OrganigrammeBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Organigramme en mode administrateur : icône ✏️ sur les nœuds que l'utilisateur peut modifier. */
class OrganigrammeAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.organigramme', [
            'divisions' => Structure::query()->where('type', 'division')->ordonne()->get(['id', 'nom', 'couleur']),
        ]);
    }

    public function donnees(Request $request, OrganigrammeBuilder $builder): JsonResponse
    {
        $user = $request->user();
        $agents = Agent::query()->actifs()->get()->keyBy('id');

        $droits = fn (?int $id) => ($a = $agents->get($id)) ? [
            'photo' => $user->can('updatePhoto', $a),
            'poste' => $user->can('update', $a),
            'url' => $user->can('update', $a) ? route('admin.agents.edit', $a) : null,
        ] : null;

        $noeuds = collect($builder->construire())->map(function (array $n) use ($droits) {
            if (($n['type'] ?? null) === 'agent') {
                $n['droits'] = $droits($n['agentId']);
                $n['admin'] = $n['droits']['photo'] || $n['droits']['poste'];
            }
            if (isset($n['cotes'])) {
                foreach ($n['cotes'] as $g => $services) {
                    foreach ($services as $i => $s) {
                        foreach ($s['agents'] as $j => $a) {
                            $n['cotes'][$g][$i]['agents'][$j]['droits'] = $droits($a['agentId']);
                        }
                    }
                }
            }

            return $n;
        });

        return response()->json(['noeuds' => $noeuds->values()]);
    }
}
