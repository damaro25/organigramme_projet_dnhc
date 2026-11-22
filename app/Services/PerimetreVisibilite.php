<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Etape;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ce qu'un compte connecté a le droit de voir : sa propre fiche agent et la branche
 * de l'organigramme placée sous lui (ses subordonnés, et leurs subordonnés…).
 * Les branches supérieures et voisines lui sont invisibles.
 *
 * - L'administrateur et la Direction (DNH et DNAH) voient tout.
 * - Le titulaire du nœud racine (Directeur National) voit tout, puisque tout est sous lui.
 * - Un compte sans fiche agent, ou dont la fiche est désactivée, ne voit rien.
 * - Un chef ne sort jamais de sa structure : le chef de division voit les sections de sa
 *   division, le chef de section les seuls agents de sa section, même si un agent d'une
 *   autre structure lui est rattaché comme supérieur.
 *
 * Obtenu par User::perimetre(), qui le garde en mémoire pour la durée de la requête.
 */
class PerimetreVisibilite
{
    private bool $global;

    /** @var array<int, true> agents actifs de la branche */
    private array $agents = [];

    /** @var array<int, true> structures dont le titulaire (ou le nœud) est dans la branche, et leurs sous-structures */
    private array $structures = [];

    /** Nœuds de l'organigramme visibles, au format d3-org-chart. */
    private array $noeuds;

    /** @var int[]|null cache des projets visibles */
    private ?array $projets = null;

    public function __construct(private ?User $user, OrganigrammeBuilder $builder)
    {
        $this->global = (bool) ($user?->estAdministrateur() || $user?->hasRole(User::ROLE_DIRECTION));
        $this->noeuds = $builder->construirePour($user?->agent, $this->global);

        if ($this->global || $this->noeuds === []) {
            return;
        }

        // Le titulaire de la racine voit l'organigramme complet : il n'a aucune branche au-dessus de lui.
        $racine = collect($this->noeuds)->firstWhere('estRacine', true);
        if ($racine && $racine['agentId'] === $user->agent_id) {
            $this->global = true;

            return;
        }

        if ($dirigee = $user->structureDirigee()) {
            $this->limiterA($dirigee->descendantIds(), $user->agent_id);
        }

        foreach ($this->noeuds as $noeud) {
            if ($noeud['type'] === 'agent') {
                $this->agents[$noeud['agentId']] = true;
                if ($noeud['estResponsable']) {
                    $this->ajouterStructure($noeud['structureId']);
                }
            } elseif ($noeud['type'] === 'structure') {
                $this->ajouterStructure($noeud['structureId']);
            }
        }
    }

    public static function pour(?User $user): self
    {
        return new self($user, app(OrganigrammeBuilder::class));
    }

    /** Vrai si le compte voit toute l'organisation. */
    public function estGlobal(): bool
    {
        return $this->global;
    }

    /** Nœuds de l'organigramme à afficher. */
    public function noeuds(): array
    {
        return $this->noeuds;
    }

    /** @return int[] agents actifs visibles (vide si global : utiliser estGlobal()). */
    public function agentIds(): array
    {
        return array_keys($this->agents);
    }

    /** @return int[] structures visibles (vide si global : utiliser estGlobal()). */
    public function structureIds(): array
    {
        return array_keys($this->structures);
    }

    public function voitAgent(Agent $agent): bool
    {
        if ($this->global) {
            return true;
        }

        return isset($this->agents[$agent->id])
            || (! $agent->actif && isset($this->structures[$agent->structure_id]));
    }

    public function voitStructure(Structure|int $structure): bool
    {
        return $this->global || isset($this->structures[$structure instanceof Structure ? $structure->id : $structure]);
    }

    public function voitProjet(Projet $projet): bool
    {
        return $this->global || in_array($projet->id, $this->projetIds(), true);
    }

    /* ---------------- Restrictions de requêtes ---------------- */

    /** Agents de la branche ; les agents désactivés sont rattachés par leur structure. */
    public function restreindreAgents(Builder $q): Builder
    {
        if ($this->global) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->whereIn('agents.id', $this->agentIds())
            ->orWhere(fn ($i) => $i->where('agents.actif', false)->whereIn('agents.structure_id', $this->structureIds())));
    }

    /** Projets d'une structure de la branche, ou auxquels un agent de la branche est affecté. */
    public function restreindreProjets(Builder $q): Builder
    {
        if ($this->global) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->whereIn('projets.structure_id', $this->structureIds())
            ->orWhereHas('agents', fn ($a) => $a->whereIn('agents.id', $this->agentIds())));
    }

    public function restreindreStructures(Builder $q): Builder
    {
        return $this->global ? $q : $q->whereIn('structures.id', $this->structureIds());
    }

    /** @return int[] */
    public function projetIds(): array
    {
        return $this->projets ??= $this->restreindreProjets(Projet::query())->pluck('projets.id')->all();
    }

    /** @return int[] */
    public function etapeIds(): array
    {
        return Etape::query()->whereIn('projet_id', $this->projetIds())->pluck('id')->all();
    }

    /**
     * Garde les seuls nœuds des structures autorisées ; un nœud dont le parent a été
     * retiré est rattaché au chef, qui reste la racine affichée.
     */
    private function limiterA(array $structureIds, int $agentRacineId): void
    {
        $autorisees = array_flip($structureIds);
        $gardes = array_values(array_filter($this->noeuds,
            fn ($n) => ! isset($n['structureId']) || isset($autorisees[$n['structureId']])));
        $ids = array_flip(array_column($gardes, 'id'));
        $racine = 'a'.$agentRacineId;

        $this->noeuds = array_map(function ($n) use ($ids, $racine) {
            if ($n['id'] !== $racine && ($n['parentId'] ?? null) !== null && ! isset($ids[$n['parentId']])) {
                $n['parentId'] = $racine;
            }

            return $n;
        }, $gardes);
    }

    private function ajouterStructure(?int $id): void
    {
        if ($id === null || isset($this->structures[$id])) {
            return;
        }
        $structure = Structure::find($id);
        foreach ($structure?->descendantIds() ?? [] as $sid) {
            $this->structures[$sid] = true;
        }
    }
}
