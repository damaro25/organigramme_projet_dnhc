<?php

namespace App\Services;

use App\Enums\StatutProjet;
use App\Enums\TypeStructure;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Indicateurs du tableau de bord, calculés sur les projets non archivés correspondant
 * aux filtres, dans le périmètre de l'utilisateur (toute la DNH, sa branche, ou ses projets).
 */
class TableauDeBordService
{
    public function calculer(array $filtres, ?User $user = null): array
    {
        $projets = Projet::query()
            ->nonArchives()
            ->when($user, fn ($q) => $q->visiblesPar($user))
            ->filtrer(array_filter([
                'structure_id' => $filtres['section'] ?? $filtres['division'] ?? null,
                'agent_id' => $filtres['agent_id'] ?? null,
                'statut' => $filtres['statut'] ?? null,
            ]))
            ->avecSommesPoids()
            ->with(['structure', 'responsables'])
            ->get();

        $structures = Structure::query()->ordonne()->get();
        $divisions = $structures->where('type', TypeStructure::Division)->values();
        $sections = $structures->where('type', TypeStructure::Section)->values();

        return [
            'total' => $projets->count(),
            'parStatut' => collect(StatutProjet::cases())->map(fn (StatutProjet $s) => [
                'statut' => $s,
                'nombre' => $projets->where('statut', $s)->count(),
            ]),
            'enRetard' => $projets->filter->est_en_retard->sortBy('date_fin_prevue')->values(),
            'tauxGlobal' => Projet::moyenneTaux($projets),
            // Hors périmètre complet, on ne compare que les divisions où l'utilisateur a des projets.
            'divisions' => $divisions->map(fn (Structure $d) => $this->indicateurs($d, $projets))
                ->when($user && ! $user->voitTousLesProjets(), fn ($c) => $c->filter(fn ($l) => $l['nombre'] > 0))
                ->values(),
            'sections' => $sections
                ->map(fn (Structure $s) => $this->indicateurs($s, $projets) + ['division' => $divisions->firstWhere('id', $s->parent_id)])
                ->filter(fn ($l) => $l['nombre'] > 0)
                ->sortByDesc('taux')
                ->values(),
        ];
    }

    private function indicateurs(Structure $structure, Collection $projets): array
    {
        $ids = $structure->descendantIds();
        $siens = $projets->whereIn('structure_id', $ids);

        return [
            'structure' => $structure,
            'couleur' => $structure->couleur_effective,
            'nombre' => $siens->count(),
            'taux' => Projet::moyenneTaux($siens),
            'retards' => $siens->filter->est_en_retard->count(),
            'termines' => $siens->where('statut', StatutProjet::Termine)->count(),
        ];
    }
}
