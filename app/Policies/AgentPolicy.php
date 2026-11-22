<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

/** L'administrateur est autorisé partout (Gate::before dans AppServiceProvider). */
class AgentPolicy
{
    /** Liste des agents : seulement pour qui peut en modifier au moins un. */
    public function viewAny(User $user): bool
    {
        return $user->can('acceder_admin') && $user->peutModifierDesAgents();
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_agents') && $user->structuresGerees() !== [];
    }

    /** Un chef modifie les agents de sa structure et de ses sous-structures. */
    public function update(User $user, Agent $agent): bool
    {
        return $user->can('gerer_agents') && in_array($agent->structure_id, $user->structuresGerees(), true);
    }

    /** Photo : chef pour ses agents, chacun pour sa propre fiche. */
    public function updatePhoto(User $user, Agent $agent): bool
    {
        if (! $user->can('modifier_photos')) {
            return false;
        }

        return $user->agent_id === $agent->id || in_array($agent->structure_id, $user->structuresGerees(), true);
    }

    /** Désactivation (départ, mutation) : réservée à l'administrateur. */
    public function desactiver(User $user, Agent $agent): bool
    {
        return false;
    }
}
