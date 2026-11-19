<?php

namespace App\Services;

use App\Enums\StatutProjet;
use App\Enums\TypeStructure;
use App\Models\Projet;
use App\Models\Structure;
use App\Services\PerimetreVisibilite;
use Illuminate\Support\Collection;

/** Indicateurs du tableau de bord, calculés sur les projets non archivés correspondant aux filtres. */
class TableauDeBordService
{
    /** Les indicateurs ne portent que sur ce que le compte peut voir (sa branche de l'organigramme). */
    public function calculer(array $filtres, ?PerimetreVisibilite $perimetre = null): array
    {
        $requete = Projet::query();
        $perimetre?->restreindreProjets($requete);

        $projets = $requete
            ->nonArchives()
            ->filtrer(array_filter([
                'structure_id' => $filtres['section'] ?? $filtres['division'] ?? null,
                'agent_id' => $filtres['agent_id'] ?? null,
                'statut' => $filtres['statut'] ?? null,
            ]))
            ->avecSommesPoids()
            ->with(['structure', 'responsables'])
            ->get();

        $structures = Structure::query()
            ->when($perimetre, fn ($q) => $perimetre->restreindreStructures($q))
            ->ordonne()->get();
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
            'divisions' => $divisions->map(fn (Structure $d) => $this->indicateurs($d, $projets))->values(),
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
