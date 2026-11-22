<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Un compte de démonstration par rôle, chacun lié à une fiche agent. */
class UtilisateursDemoSeeder extends Seeder
{
    public const MOT_DE_PASSE = 'Dnh@2026';

    public function run(): void
    {
        $responsable = fn (string $sigle) => Structure::where('sigle', $sigle)->firstOrFail()
            ->agents()->where('est_responsable', true)->firstOrFail();

        $premierCharge = Structure::where('sigle', 'SSTD')->firstOrFail()
            ->agents()->where('est_responsable', false)->orderBy('ordre')->firstOrFail();

        $comptes = [
            ['admin@dnh.local', User::ROLE_ADMIN, $responsable('Informatique')],
            ['dnh@dnh.local', User::ROLE_DIRECTION, $responsable('DNH')],
            ['dnah@dnh.local', User::ROLE_DIRECTION, $responsable('DNAH')],
            // Un compte par chef de division : Planification / Économie, Aval, Amont.
            ['chef1.division@dnh.local', User::ROLE_CHEF, $responsable('DPE')],
            ['chef2.division@dnh.local', User::ROLE_CHEF, $responsable('DAVP')],
            ['chef3.division@dnh.local', User::ROLE_CHEF, $responsable('DAMP')],
            ['chef.section@dnh.local', User::ROLE_CHEF, $responsable('SSTD')],
            ['charge.etudes@dnh.local', User::ROLE_CHARGE, $premierCharge],
            ['consultation@dnh.local', User::ROLE_CONSULTATION, $responsable('SAF')],
        ];

        foreach ($comptes as [$email, $role, $agent]) {
            /** @var Agent $agent */
            $user = User::create([
                'name' => $agent->nom_complet,
                'email' => $email,
                'password' => self::MOT_DE_PASSE,
                'agent_id' => $agent->id,
                'actif' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);
        }
    }
}
