<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesEtPermissionsSeeder extends Seeder
{
    /** Permission => rôles qui la reçoivent. Le périmètre (sa structure, ses projets) est vérifié par les Policies. */
    public const MATRICE = [
        // Le compte Consultation ne modifie rien : il n'a pas accès à l'administration
        // (règle « on ne voit que ce qu'on peut modifier »).
        'acceder_admin' => ['administrateur', 'direction', 'chef_structure', 'charge_etudes'],
        'gerer_agents' => ['administrateur', 'chef_structure'],
        'modifier_photos' => ['administrateur', 'chef_structure', 'charge_etudes'],
        'gerer_projets' => ['administrateur', 'chef_structure'],
        'cocher_etapes' => ['administrateur', 'chef_structure', 'charge_etudes'],
        'valider_projets' => ['administrateur', 'direction'],
        'gerer_structures' => ['administrateur'],
        'gerer_utilisateurs' => ['administrateur'],
        'voir_journal' => ['administrateur', 'direction'],
        'exporter_pdf' => ['administrateur', 'direction', 'chef_structure', 'charge_etudes', 'consultation'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = collect([
            User::ROLE_ADMIN, User::ROLE_DIRECTION, User::ROLE_CHEF, User::ROLE_CHARGE, User::ROLE_CONSULTATION,
        ])->mapWithKeys(fn ($nom) => [$nom => Role::findOrCreate($nom, 'web')]);

        foreach (self::MATRICE as $permission => $titulaires) {
            Permission::findOrCreate($permission, 'web');
            foreach ($titulaires as $role) {
                $roles[$role]->givePermissionTo($permission);
            }
        }
    }
}
