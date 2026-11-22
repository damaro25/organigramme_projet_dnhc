<?php

namespace App\Policies;

use App\Models\Structure;
use App\Models\User;

/**
 * L'administrateur gère toutes les structures. Le chef de division voit et modifie
 * les sections de sa division (libellé, sigle, couleur, ordre), sans pouvoir en créer
 * ni les déplacer.
 */
class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer_structures') || $user->estChefDeDivision();
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_structures');
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->can('gerer_structures')
            || in_array($structure->id, $user->sectionsModifiables(), true);
    }
}
