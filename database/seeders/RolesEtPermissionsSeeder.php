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
        'acceder_admin' => ['administrateur', 'direction', 'chef_structure', 'charge_etudes', 'consultation'],
        'gerer_agents' => ['administrateur', 'direction', 'chef_structure'],
        'modifier_photos' => ['administrateur', 'direction', 'chef_structure', 'charge_etudes'],
        'gerer_projets' => ['administrateur', 'direction', 'chef_structure'],
        'cocher_etapes' => ['administrateur', 'direction', 'chef_structure'],
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

        foreach (array_keys(self::MATRICE) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Chaque rôle reçoit exactement ses permissions de la matrice : relancer ce seeder
        // retire aussi celles qui n'y figurent plus.
        foreach ($roles as $nom => $role) {
            $role->syncPermissions(array_keys(array_filter(self::MATRICE, fn ($titulaires) => in_array($nom, $titulaires, true))));
        }
    }
}
