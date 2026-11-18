<?php

namespace Tests\Feature\Admin;

use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use App\Services\TableauDeBordService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_les_indicateurs_correspondent_aux_projets_non_archives(): void
    {
        $projets = Projet::nonArchives()->with('etapes')->get();
        $stats = app(TableauDeBordService::class)->calculer([]);

        $this->assertSame($projets->count(), $stats['total']);
        $this->assertSame($projets->count(), $stats['parStatut']->sum('nombre'));
        $this->assertSame(Projet::moyenneTaux($projets), $stats['tauxGlobal']);
        $this->assertSame($projets->filter->est_en_retard->count(), $stats['enRetard']->count());
        $this->assertCount(3, $stats['divisions']);
    }

    public function test_le_classement_des_sections_est_trie_par_taux_decroissant(): void
    {
        $taux = app(TableauDeBordService::class)->calculer([])['sections']->pluck('taux')->all();

        $trie = $taux;
        rsort($trie);
        $this->assertSame($trie, $taux);
    }

    public function test_le_filtre_par_division_ne_garde_que_ses_projets(): void
    {
        $davp = Structure::where('sigle', 'DAVP')->firstOrFail();
        $stats = app(TableauDeBordService::class)->calculer(['division' => $davp->id]);

        $attendu = Projet::nonArchives()->whereIn('structure_id', $davp->descendantIds())->count();
        $this->assertSame($attendu, $stats['total']);
        $this->assertGreaterThan(0, $attendu);
    }

    public function test_la_page_s_affiche_avec_et_sans_filtres(): void
    {
        $admin = User::where('email', 'admin@dnh.local')->firstOrFail();
        $sstd = Structure::where('sigle', 'SSTD')->firstOrFail();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee("Taux d'exécution global de la DNH", false)
            ->assertSee('Classement des sections');

        $this->actingAs($admin)->get('/admin?section='.$sstd->id.'&statut=en_cours')->assertOk();
        $this->actingAs($admin)->get('/admin?statut=inconnu')->assertSessionHasErrors('statut');
    }
}
