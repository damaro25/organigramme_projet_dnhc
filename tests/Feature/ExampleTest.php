<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** L'accueil (organigramme) est réservé aux comptes connectés. */
    public function test_l_accueil_redirige_un_visiteur_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_l_accueil_s_affiche_pour_un_compte_connecte(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::role(User::ROLE_ADMIN)->firstOrFail())
            ->get('/')
            ->assertOk();
    }
}
