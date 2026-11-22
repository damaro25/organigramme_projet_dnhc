<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Règle « un compte ne voit que ce qu'il peut modifier » : le rôle Consultation,
 * qui ne modifie rien, perd l'accès à l'administration sur les bases déjà remplies
 * (les nouvelles installations l'obtiennent par RolesEtPermissionsSeeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('name', 'consultation')->where('guard_name', 'web')->first();
        if ($role && Permission::query()->where('name', 'acceder_admin')->exists()) {
            $role->revokePermissionTo('acceder_admin');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'consultation')->where('guard_name', 'web')->first();
        if ($role && Permission::query()->where('name', 'acceder_admin')->exists()) {
            $role->givePermissionTo('acceder_admin');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
