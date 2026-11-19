<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EtapesProjet;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\EtapeTermineeNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AutorisationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function compte(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function projetDeSection(string $sigle): Projet
    {
        return Projet::whereIn('structure_id', Structure::where('sigle', $sigle)->firstOrFail()->descendantIds())->firstOrFail();
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $this->compte('consultation@dnh.local')->update(['actif' => false]);

        $this->post('/login', ['email' => 'consultation@dnh.local', 'password' => 'Dnh@2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_chaque_role_accede_au_tableau_de_bord(): void
    {
        foreach (['admin', 'dnh', 'chef.section', 'charge.etudes', 'consultation'] as $c) {
            $this->actingAs($this->compte("{$c}@dnh.local"))->get('/admin')->assertOk();
        }
    }

    public function test_seul_l_administrateur_gere_structures_et_utilisateurs(): void
    {
        $this->actingAs($this->compte('admin@dnh.local'))->get('/admin/structures')->assertOk();
        $this->actingAs($this->compte('admin@dnh.local'))->get('/admin/utilisateurs')->assertOk();

        foreach (['dnh', 'chef.division', 'charge.etudes', 'consultation'] as $c) {
            $this->actingAs($this->compte("{$c}@dnh.local"))->get('/admin/structures')->assertForbidden();
            $this->actingAs($this->compte("{$c}@dnh.local"))->get('/admin/utilisateurs')->assertForbidden();
        }
    }

    public function test_un_chef_de_section_modifie_les_projets_de_sa_section_uniquement(): void
    {
        $chef = $this->compte('chef.section@dnh.local'); // section SSTD
        $sien = $this->projetDeSection('SSTD');
        $autre = $this->projetDeSection('SPCP');

        $this->actingAs($chef)->put(route('admin.projets.update', $sien), [
            'titre' => 'Titre modifié', 'structure_id' => $sien->structure_id, 'statut' => 'en_cours',
        ])->assertRedirect();
        $this->assertSame('Titre modifié', $sien->fresh()->titre);

        $this->actingAs($chef)->put(route('admin.projets.update', $autre), [
            'titre' => 'Interdit', 'structure_id' => $autre->structure_id, 'statut' => 'en_cours',
        ])->assertForbidden();
    }

    public function test_un_chef_de_division_gere_toutes_les_sections_de_sa_division(): void
    {
        $chef = $this->compte('chef.division@dnh.local'); // division Aval
        $this->assertTrue($chef->can('update', $this->projetDeSection('SAI')));
        $this->assertFalse($chef->can('update', $this->projetDeSection('SESD')));
    }

    public function test_la_direction_modifie_et_valide_tout(): void
    {
        $dnah = $this->compte('dnah@dnh.local');
        $projet = $this->projetDeSection('SSTD');

        $this->actingAs($dnah)->get(route('admin.projets.edit', $projet))->assertOk();
        $this->assertTrue($dnah->can('update', $projet));
        $this->assertTrue($dnah->can('cocherEtapes', $projet));
        $this->assertTrue($dnah->can('update', \App\Models\Agent::whereHas('structure', fn ($q) => $q->where('sigle', 'DAMP'))->firstOrFail()));
        $this->assertFalse($dnah->can('viewAny', \App\Models\User::class));
        $this->assertFalse($dnah->can('viewAny', Structure::class));

        $dnh = $this->compte('dnh@dnh.local');
        $this->actingAs($dnh)->patch(route('admin.projets.valider', $projet))->assertRedirect();
        $this->assertNotNull($projet->fresh()->valide_le);
    }

    public function test_la_consultation_est_en_lecture_seule(): void
    {
        $c = $this->compte('consultation@dnh.local');
        $projet = Projet::first();

        $this->actingAs($c)->get(route('admin.projets.index'))->assertOk();
        $this->actingAs($c)->get(route('admin.projets.create'))->assertForbidden();
        $this->assertFalse($c->can('cocherEtapes', $projet));
        $this->assertFalse($c->can('valider', $projet));
    }

    public function test_un_charge_d_etudes_coche_les_etapes_de_ses_projets_et_le_responsable_est_notifie(): void
    {
        Notification::fake();
        $charge = $this->compte('charge.etudes@dnh.local');
        // Projet dont le chef de section (qui a un compte) est responsable ; le chargé y est membre.
        $chef = $this->compte('chef.section@dnh.local');
        $sien = $chef->agent->projetsResponsable()->firstOrFail();
        $sien->agents()->syncWithoutDetaching([$charge->agent_id => ['role' => 'membre']]);
        $autre = Projet::whereDoesntHave('agents', fn ($q) => $q->whereKey($charge->agent_id))->firstOrFail();
        $etape = $sien->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $sien])
            ->call('basculer', $etape->id)
            ->assertDispatched('taux-mis-a-jour');

        $this->assertTrue($etape->fresh()->est_terminee);
        $this->assertNotNull($etape->fresh()->date_realisation);

        // Le chargé ne peut ni gérer les étapes de son projet, ni cocher celles d'un autre.
        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $sien])
            ->set('nouvelleEtape', ['libelle' => 'Interdit', 'poids' => 1])->call('ajouter')->assertForbidden();
        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $autre])
            ->call('basculer', $autre->etapes()->first()->id)->assertForbidden();

        // Notification au(x) responsable(s) disposant d'un compte, sauf l'auteur.
        Notification::assertSentTo($chef, EtapeTermineeNotification::class);
        Notification::assertNotSentTo($charge, EtapeTermineeNotification::class);
    }

    public function test_le_taux_se_recalcule_apres_cochage(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $projet = Projet::factory()->create(['structure_id' => Structure::first()->id]);
        $a = $projet->etapes()->create(['libelle' => 'A', 'poids' => 3]);
        $projet->etapes()->create(['libelle' => 'B', 'poids' => 1]);

        Livewire::actingAs($admin)->test(EtapesProjet::class, ['projet' => $projet])
            ->assertSet('taux', 0)
            ->call('basculer', $a->id)
            ->assertDispatched('taux-mis-a-jour', taux: 75);
    }
}
