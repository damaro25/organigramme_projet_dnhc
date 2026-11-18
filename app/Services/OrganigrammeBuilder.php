<?php

namespace App\Services;

use App\Enums\TypeStructure;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\Structure;
use Illuminate\Support\Collection;

/**
 * Construit la liste plate de nœuds attendue par d3-org-chart.
 *
 * - Les agents actifs sont des nœuds « personne » (id « a{id} »).
 * - Les structures sans titulaire (Services Déconcentrés, I.R, D.P) sont des nœuds « structure » (id « s{id} »).
 * - Les services rattachés ne sont pas dans la hiérarchie : ils sont joints au nœud racine (clé « cotes »)
 *   pour être dessinés sur le côté du DNH.
 */
class OrganigrammeBuilder
{
    /** @var Collection<int, Structure> */
    private Collection $structures;

    /** @var Collection<int, Agent> responsable actif par structure_id */
    private Collection $responsables;

    public function construire(): array
    {
        $this->structures = Structure::query()->ordonne()->get()->keyBy('id');

        // Ordre d'affichage : celui des structures, puis le titulaire, puis l'ordre des agents.
        $rang = $this->structures->keys()->flip();
        $agents = Agent::query()
            ->actifs()
            ->with(['projets' => fn ($q) => $q->nonArchives()->avecSommesPoids()])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($rang[$a->structure_id] ?? 0) <=> ($rang[$b->structure_id] ?? 0),
                fn ($a, $b) => $b->est_responsable <=> $a->est_responsable,
                fn ($a, $b) => [$a->ordre, $a->nom] <=> [$b->ordre, $b->nom],
            ])
            ->keyBy('id');

        $this->responsables = $agents->where('est_responsable', true)->keyBy('structure_id');

        $racine = $this->trouverRacine($agents);
        if (! $racine) {
            return [];
        }

        $noeuds = [];
        $cotes = ['appui_technique' => [], 'personnel_appui' => []];

        // Services rattachés : dessinés sur le côté du nœud racine.
        foreach ($this->structures->where('type', TypeStructure::ServiceRattache) as $service) {
            $titulaires = $agents->where('structure_id', $service->id)->values();
            $cotes[$service->groupe?->value ?? 'appui_technique'][] = [
                'structureId' => $service->id,
                'nom' => $service->nom,
                'sigle' => $service->libelle_court,
                'agents' => $titulaires->map(fn (Agent $a) => [
                    'agentId' => $a->id,
                    'nom' => $a->nom_complet,
                    'poste' => $a->poste,
                    'photo' => $a->miniature_url,
                    'taux' => $a->taux_moyen,
                ])->all(),
            ];
        }

        foreach ($agents as $agent) {
            $structure = $this->structures->get($agent->structure_id);
            if (! $structure || $structure->type === TypeStructure::ServiceRattache) {
                continue;
            }

            $noeud = $this->noeudAgent($agent, $structure);
            $noeud['parentId'] = $agent->id === $racine->id ? null : $this->parentAgent($agent, $agents, $racine);
            if ($agent->id === $racine->id) {
                $noeud['cotes'] = $cotes;
                $noeud['estRacine'] = true;
            }
            $noeuds[] = $noeud;
        }

        // Structures sans titulaire (hors services rattachés et hors racine).
        foreach ($this->structures as $structure) {
            if ($structure->type === TypeStructure::ServiceRattache
                || $this->responsables->has($structure->id)
                || $structure->parent_id === null) {
                continue;
            }
            $noeuds[] = [
                'id' => 's'.$structure->id,
                'parentId' => $this->noeudDeStructure($structure->parent_id) ?? 'a'.$racine->id,
                'type' => 'structure',
                'structureId' => $structure->id,
                'nom' => $structure->nom,
                'sigle' => $structure->libelle_court,
                'typeStructure' => $structure->type->value,
                'couleur' => $this->couleur($structure),
                'lien' => $structure->type_lien->value,
                '_expanded' => true,
            ];
        }

        return $noeuds;
    }

    private function noeudAgent(Agent $agent, Structure $structure): array
    {
        $division = $this->division($structure);

        return [
            'id' => 'a'.$agent->id,
            'type' => 'agent',
            'agentId' => $agent->id,
            'nom' => $agent->nom_complet,
            'poste' => $agent->poste,
            'photo' => $agent->photo_url,
            'structureId' => $structure->id,
            'structure' => $structure->nom,
            'sigle' => $structure->libelle_court,
            'typeStructure' => $structure->type->value,
            'division' => $division?->nom,
            'couleur' => $this->couleur($structure),
            'estResponsable' => $agent->est_responsable,
            'lien' => $agent->est_responsable ? $structure->type_lien->value : 'hierarchique',
            'taux' => $agent->taux_moyen,
            'couleurTaux' => Projet::couleurPourTaux($agent->taux_moyen),
            'nbProjets' => $agent->projets->count(),
            // Arbre replié au niveau des sections : les chargés d'études apparaissent au clic sur leur chef.
            // Pour d3-org-chart, « _expanded » rend le nœud visible au premier affichage.
            '_expanded' => ! ($structure->type === TypeStructure::Section && ! $agent->est_responsable),
        ];
    }

    /** Parent d'un agent : son supérieur s'il est affiché, sinon le titulaire de sa structure ou d'une structure parente. */
    private function parentAgent(Agent $agent, Collection $agents, Agent $racine): string
    {
        $superieur = $agent->superieur_id ? $agents->get($agent->superieur_id) : null;
        if ($superieur && $this->structures->get($superieur->structure_id)?->type !== TypeStructure::ServiceRattache) {
            return 'a'.$superieur->id;
        }

        $structure = $this->structures->get($agent->structure_id);
        $responsable = $this->responsables->get($structure->id);
        if ($responsable && $responsable->id !== $agent->id) {
            return 'a'.$responsable->id;
        }

        return $this->noeudDeStructure($structure->parent_id) ?? 'a'.$racine->id;
    }

    /** Nœud qui représente une structure : son titulaire, sinon la structure elle-même. */
    private function noeudDeStructure(?int $structureId): ?string
    {
        if ($structureId === null || ! $this->structures->has($structureId)) {
            return null;
        }
        if ($r = $this->responsables->get($structureId)) {
            return 'a'.$r->id;
        }

        return $this->structures->get($structureId)->parent_id === null ? null : 's'.$structureId;
    }

    private function trouverRacine(Collection $agents): ?Agent
    {
        $direction = $this->structures->first(fn (Structure $s) => $s->parent_id === null);

        return $direction ? ($this->responsables->get($direction->id) ?? $agents->firstWhere('structure_id', $direction->id)) : null;
    }

    private function division(Structure $structure): ?Structure
    {
        $s = $structure;
        while ($s) {
            if ($s->type === TypeStructure::Division) {
                return $s;
            }
            $s = $this->structures->get($s->parent_id);
        }

        return null;
    }

    private function couleur(Structure $structure): string
    {
        return $structure->couleur ?? $this->division($structure)?->couleur ?? Structure::COULEUR_DEFAUT;
    }
}
