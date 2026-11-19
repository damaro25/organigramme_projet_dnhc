<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Projet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Données du panneau latéral ouvert au clic sur une photo. */
class FicheAgentController extends Controller
{
    public function show(Request $request, Agent $agent): JsonResponse
    {
        abort_unless($agent->actif, 404);
        // Une fiche hors de la branche du compte est introuvable, sans révéler qu'elle existe.
        abort_unless($request->user()->perimetre()->voitAgent($agent), 404);

        $agent->load(['structure.parent', 'projets' => fn ($q) => $q->nonArchives()->with('etapes', 'structure')->orderBy('date_fin_prevue')]);

        $donnees = [
            'agent' => [
                'id' => $agent->id,
                'nom' => $agent->nom_complet,
                'poste' => $agent->poste,
                'structure' => $agent->structure?->nom,
                'structureParente' => $agent->structure?->parent?->nom,
                'email' => $agent->email,
                'telephone' => $agent->telephone,
                'photo' => $agent->photo_url,
            ],
            'taux' => Projet::moyenneTaux($agent->projets),
            'projets' => $agent->projets->map(fn (Projet $p) => $this->projet($p, $p->pivot->role))->values(),
            'structure' => null,
        ];

        // Chef de division ou de section : projets de toute sa structure, avec un taux consolidé.
        if ($agent->estChefDeStructure()) {
            $projets = $agent->structure->projetsConsolides()
                ->with(['etapes', 'structure', 'responsables'])
                ->orderBy('date_fin_prevue')
                ->get();

            $donnees['structure'] = [
                'nom' => $agent->structure->nom,
                'taux' => Projet::moyenneTaux($projets),
                'projets' => $projets->map(fn (Projet $p) => $this->projet($p))->values(),
            ];
        }

        return response()->json($donnees);
    }

    private function projet(Projet $p, ?string $role = null): array
    {
        return [
            'id' => $p->id,
            'titre' => $p->titre,
            'structure' => $p->structure?->sigle ?: $p->structure?->nom,
            'role' => $role,
            'responsables' => $p->relationLoaded('responsables') ? $p->responsables->pluck('nom_complet')->join(', ') : null,
            'statut' => $p->statut->value,
            'statutLibelle' => $p->statut->libelle(),
            'dateDebut' => $p->date_debut?->format('d/m/Y'),
            'dateFin' => $p->date_fin_prevue?->format('d/m/Y'),
            'taux' => $p->taux_execution,
            'couleur' => $p->couleur_taux,
            'enRetard' => $p->est_en_retard,
            'etapes' => $p->etapes->map(fn ($e) => [
                'libelle' => $e->libelle,
                'terminee' => $e->est_terminee,
                'date' => $e->date_realisation?->format('d/m/Y'),
                'commentaire' => $e->commentaire,
            ])->values(),
        ];
    }
}
