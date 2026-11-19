<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

class ProjetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('acceder_admin');
    }

    /** Consultation : projets de la branche du compte, ou auxquels un agent de sa branche est affecté. */
    public function view(User $user, Projet $projet): bool
    {
        return $user->can('acceder_admin') && $user->perimetre()->voitProjet($projet);
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_projets') && $user->structuresGerees() !== [];
    }

    /** Un chef modifie les projets de sa structure et de ses sous-structures. */
    public function update(User $user, Projet $projet): bool
    {
        return $user->can('gerer_projets')
            && in_array($projet->structure_id, $user->structuresGerees(), true)
            && $user->perimetre()->voitProjet($projet);
    }

    public function delete(User $user, Projet $projet): bool
    {
        return $this->update($user, $projet);
    }

    public function dupliquer(User $user, Projet $projet): bool
    {
        return $this->update($user, $projet);
    }

    public function archiver(User $user, Projet $projet): bool
    {
        return $this->update($user, $projet);
    }

    /** Validation par la direction (DNH / DNAH). */
    public function valider(User $user, Projet $projet): bool
    {
        return $user->can('valider_projets') && $user->perimetre()->voitProjet($projet);
    }

    /** Ajout, modification, suppression et tri des étapes. */
    public function gererEtapes(User $user, Projet $projet): bool
    {
        return $this->update($user, $projet);
    }

    /**
     * Faire évoluer un projet (cocher ses étapes) : tout compte qui en a la permission,
     * pour les projets de sa branche (structure de la branche ou agent de la branche affecté).
     */
    public function cocherEtapes(User $user, Projet $projet): bool
    {
        return $user->can('cocher_etapes') && $user->perimetre()->voitProjet($projet);
    }
}
