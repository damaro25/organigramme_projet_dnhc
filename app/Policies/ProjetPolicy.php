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

    /** Ouvrir un projet, ses étapes et ses fichiers : seulement s'il peut le modifier, le valider ou en cocher les étapes. */
    public function view(User $user, Projet $projet): bool
    {
        return $user->can('acceder_admin') && $projet->estVisiblePar($user);
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_projets') && $user->structuresGerees() !== [];
    }

    /** Un chef modifie les projets de sa structure et de ses sous-structures. */
    public function update(User $user, Projet $projet): bool
    {
        return $user->can('gerer_projets') && in_array($projet->structure_id, $user->structuresGerees(), true);
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
        return $user->can('valider_projets');
    }

    /** Ajout, modification, suppression et tri des étapes. */
    public function gererEtapes(User $user, Projet $projet): bool
    {
        return $this->update($user, $projet);
    }

    /** Cocher une étape : chef de la structure, ou agent affecté au projet. */
    public function cocherEtapes(User $user, Projet $projet): bool
    {
        if (! $user->can('cocher_etapes')) {
            return false;
        }

        return $this->update($user, $projet) || $user->estAffecteAuProjet($projet);
    }
}
