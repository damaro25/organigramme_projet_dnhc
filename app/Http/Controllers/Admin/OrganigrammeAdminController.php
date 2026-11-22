<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\Structure;
use App\Services\OrganigrammeBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Organigramme en mode administrateur : icône ✏️ sur les nœuds modifiables.
 * Un compte n'y voit que ce qu'il peut modifier : l'arbre entier pour
 * l'Administrateur, sa branche pour un chef, sa propre fiche pour un chargé d'études.
 */
class OrganigrammeAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->peutModifierDesAgents(), 403);

        return view('admin.organigramme', [
            'divisions' => Structure::query()->where('type', 'division')->ordonne()->get(['id', 'nom', 'couleur']),
        ]);
    }

    public function donnees(Request $request, OrganigrammeBuilder $builder): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->peutModifierDesAgents(), 403);
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

        if (! $user->voitTousLesAgents()) {
            $noeuds = $this->restreindre($noeuds);
        }

        return response()->json(['noeuds' => $noeuds->values()]);
    }

    /**
     * Ne garde que les agents modifiables, rattachés à leur plus proche ancêtre gardé.
     * Les agents des services rattachés (dessinés sur le côté du DNH) deviennent des
     * nœuds ordinaires s'ils sont modifiables. S'il reste plusieurs racines, elles sont
     * réunies sous un nœud « Votre périmètre ».
     */
    private function restreindre(Collection $noeuds): Collection
    {
        $parIdentifiant = $noeuds->keyBy('id');
        $modifiable = fn (array $n) => ($n['droits']['photo'] ?? false) || ($n['droits']['poste'] ?? false);

        $gardes = $noeuds->filter(fn ($n) => ($n['type'] ?? null) === 'agent' && $modifiable($n))
            ->map(function (array $n) {
                unset($n['cotes'], $n['estRacine']);

                return $n;
            })
            ->keyBy('id');

        // Agents des services rattachés (clé « cotes » du nœud racine).
        foreach ($noeuds->pluck('cotes')->filter() as $groupes) {
            foreach ($groupes as $services) {
                foreach ($services as $service) {
                    foreach ($service['agents'] as $i => $a) {
                        if (! $modifiable($a)) {
                            continue;
                        }
                        $titulaire = $service['agents'][0]['agentId'];
                        $gardes->put('a'.$a['agentId'], [
                            'id' => 'a'.$a['agentId'], 'type' => 'agent', 'agentId' => $a['agentId'],
                            'nom' => $a['nom'], 'poste' => $a['poste'], 'photo' => $a['photo'],
                            'structureId' => $service['structureId'], 'structure' => $service['nom'], 'sigle' => $service['sigle'],
                            'typeStructure' => 'service_rattache', 'division' => null, 'couleur' => Structure::COULEUR_DEFAUT,
                            'estResponsable' => $i === 0, 'lien' => 'hierarchique', 'taux' => $a['taux'],
                            'couleurTaux' => Projet::couleurPourTaux($a['taux']), 'nbProjets' => 0, '_expanded' => true,
                            'parentId' => $i === 0 ? null : 'a'.$titulaire, 'droits' => $a['droits'], 'admin' => true,
                        ]);
                    }
                }
            }
        }

        // Rattacher chaque nœud gardé à son plus proche ancêtre gardé.
        $gardes = $gardes->map(function (array $n) use ($gardes, $parIdentifiant) {
            $parent = $n['parentId'] ?? null;
            while ($parent !== null && ! $gardes->has($parent)) {
                $parent = $parIdentifiant->get($parent)['parentId'] ?? null;
            }
            $n['parentId'] = $parent;

            return $n;
        });

        $racines = $gardes->whereNull('parentId');
        if ($racines->count() > 1) {
            $gardes = $gardes->map(fn ($n) => $n['parentId'] === null ? ['parentId' => 'perimetre'] + $n : $n);
            $gardes->prepend([
                'id' => 'perimetre', 'parentId' => null, 'type' => 'structure', 'nom' => 'Votre périmètre', 'sigle' => '',
                'typeStructure' => 'direction', 'couleur' => Structure::COULEUR_DEFAUT, 'lien' => 'hierarchique', '_expanded' => true,
            ], 'perimetre');
        } else {
            $gardes = $gardes->map(fn ($n) => $n['parentId'] === null ? ['_expanded' => true] + $n : $n);
        }

        return $gardes;
    }
}
