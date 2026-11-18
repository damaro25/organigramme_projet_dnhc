<?php

namespace App\Policies;

use App\Models\Structure;
use App\Models\User;

class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer_structures');
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_structures');
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->can('gerer_structures');
    }
}
