<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Les données initiales ne doivent pas encombrer le journal des modifications.
        activity()->disableLogging();

        $this->call([
            RolesEtPermissionsSeeder::class,
            OrganigrammeSeeder::class,
            UtilisateursDemoSeeder::class,
            ProjetsDemoSeeder::class,
        ]);

        activity()->enableLogging();
    }
}
