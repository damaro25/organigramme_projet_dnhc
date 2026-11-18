<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EtapesProjet;
use App\Models\Agent;
use App\Models\Projet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class JournalTest extends TestCase
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

    public function test_seuls_l_administrateur_et_la_direction_consultent_le_journal(): void
    {
        $this->actingAs($this->compte('admin@dnh.local'))->get('/admin/journal')->assertOk();
        $this->actingAs($this->compte('dnh@dnh.local'))->get('/admin/journal')->assertOk();

        foreach (['chef.section', 'charge.etudes', 'consultation'] as $c) {
            $this->actingAs($this->compte("{$c}@dnh.local"))->get('/admin/journal')->assertForbidden();
        }
    }

    public function test_le_changement_de_photo_est_journalise_avec_son_auteur(): void
    {
        Storage::fake('public');
        $admin = $this->compte('admin@dnh.local');
        $agent = Agent::firstOrFail();

        $this->actingAs($admin)->post("/admin/agents/{$agent->id}/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg', 600, 600),
        ])->assertRedirect();

        $entree = Activity::where('subject_type', Agent::class)->where('subject_id', $agent->id)->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $entree->causer_id);
        $this->assertNull($entree->properties['old']['photo']);
        $this->assertSame($agent->fresh()->photo, $entree->properties['attributes']['photo']);

        $this->actingAs($admin)->get('/admin/journal?type=agents')
            ->assertOk()
            ->assertSee($agent->fresh()->nom_complet)
            ->assertSee('Nouvelle photo');
    }

    public function test_la_modification_d_un_projet_montre_l_ancienne_et_la_nouvelle_valeur(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $projet = Projet::firstOrFail();
        $ancien = $projet->titre;

        $projet->update(['titre' => 'Titre corrigé pour le journal']);
        Activity::latest('id')->first()->update(['causer_id' => $admin->id, 'causer_type' => User::class]);

        $this->actingAs($admin)->get('/admin/journal?type=projets&utilisateur='.$admin->id)
            ->assertOk()
            ->assertSeeInOrder(['Titre :', $ancien, 'Titre corrigé pour le journal']);
    }

    public function test_cocher_une_etape_est_journalise(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $projet = Projet::whereHas('etapes', fn ($q) => $q->where('est_terminee', false))->firstOrFail();
        $etape = $projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($admin)->test(EtapesProjet::class, ['projet' => $projet])->call('basculer', $etape->id);

        $entree = Activity::where('log_name', 'etapes')->latest('id')->firstOrFail();
        $this->assertSame('Étape cochée', $entree->description);
        $this->assertSame($admin->id, $entree->causer_id);
        $this->assertFalse($entree->properties['old']['est_terminee']);
        $this->assertTrue($entree->properties['attributes']['est_terminee']);
    }

    public function test_le_changement_d_affectation_est_journalise(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $projet = Projet::with('agents')->firstOrFail();
        $nouvel = Agent::whereNotIn('id', $projet->agents->pluck('id'))->firstOrFail();

        $affectations = $projet->agents->map(fn ($a) => ['agent_id' => $a->id, 'role' => $a->pivot->role instanceof \BackedEnum ? $a->pivot->role->value : $a->pivot->role])->all();
        $affectations[] = ['agent_id' => $nouvel->id, 'role' => 'membre'];

        $this->actingAs($admin)->put("/admin/projets/{$projet->id}", [
            'titre' => $projet->titre,
            'description' => $projet->description,
            'structure_id' => $projet->structure_id,
            'date_debut' => $projet->date_debut?->format('Y-m-d'),
            'date_fin_prevue' => $projet->date_fin_prevue?->format('Y-m-d'),
            'statut' => $projet->statut->value,
            'affectations' => $affectations,
        ])->assertSessionHasNoErrors();

        $entree = Activity::where('description', 'Affectations du projet modifiées')->latest('id')->firstOrFail();
        $this->assertStringContainsString($nouvel->nom_complet.' (Membre)', $entree->properties['attributes']['affectations']);
        $this->assertStringNotContainsString($nouvel->nom_complet, (string) $entree->properties['old']['affectations']);
    }
}
