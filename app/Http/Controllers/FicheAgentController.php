<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PieceJointe;
use App\Models\Projet;
use Illuminate\Http\JsonResponse;

/**
 * Données du panneau latéral ouvert au clic sur une photo.
 *
 * Fichiers joints : détaillés (nom, aperçu, téléchargement) pour les comptes qui
 * ont accès à l'administration ; pour les visiteurs, seulement leur nombre, avec
 * une invitation à se connecter, car ils peuvent contenir des documents internes.
 */
class FicheAgentController extends Controller
{
    private bool $voitFichiers = false;

    public function show(Agent $agent): JsonResponse
    {
        abort_unless($agent->actif, 404);

        $this->voitFichiers = (bool) auth()->user()?->can('acceder_admin');
        $relations = $this->voitFichiers
            ? ['etapes.piecesJointes', 'documents', 'structure']
            : ['etapes' => fn ($q) => $q->withCount('piecesJointes'), 'structure', 'piecesJointes:id,projet_id'];

        $agent->load(['structure.parent', 'projets' => fn ($q) => $q->nonArchives()->with($relations)->orderBy('date_fin_prevue')]);

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
            'voitFichiers' => $this->voitFichiers,
            'urlConnexion' => route('login'),
        ];

        // Chef de division ou de section : projets de toute sa structure, avec un taux consolidé.
        if ($agent->estChefDeStructure()) {
            $projets = $agent->structure->projetsConsolides()
                ->with([...$relations, 'responsables'])
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
                'fichiers' => $this->voitFichiers ? $e->piecesJointes->map(fn ($pj) => $this->fichier($p, $pj))->values() : [],
                'nbFichiers' => $this->voitFichiers ? $e->piecesJointes->count() : (int) $e->pieces_jointes_count,
            ])->values(),
            'documents' => $this->voitFichiers ? $p->documents->map(fn ($pj) => $this->fichier($p, $pj))->values() : [],
            // Visiteur : nombre total de fichiers du projet (documents et justificatifs d'étapes).
            'nbFichiers' => $this->voitFichiers
                ? $p->documents->count() + $p->etapes->sum(fn ($e) => $e->piecesJointes->count())
                : $p->piecesJointes->count(),
        ];
    }

    /** Données d'un fichier pour la visionneuse (mêmes clés que le composant liste-fichiers). */
    private function fichier(Projet $p, PieceJointe $pj): array
    {
        return [
            'id' => $pj->id,
            'nom' => $pj->nom,
            'icone' => $pj->icone,
            'taille' => $pj->taille_lisible,
            'visualisable' => $pj->est_visualisable,
            'image' => $pj->est_image,
            'url' => route('admin.projets.pieces-jointes.apercu', [$p, $pj]),
            'telechargement' => route('admin.projets.pieces-jointes.show', [$p, $pj]),
        ];
    }
}
