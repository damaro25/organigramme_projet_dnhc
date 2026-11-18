<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer_utilisateurs');
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_utilisateurs');
    }

    public function update(User $user, User $cible): bool
    {
        return $user->can('gerer_utilisateurs');
    }
}
