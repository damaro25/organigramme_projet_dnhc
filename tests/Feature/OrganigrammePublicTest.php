<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Structure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganigrammePublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // L'organigramme est réservé aux comptes connectés ; l'administrateur le voit en entier.
        $this->actingAs(\App\Models\User::role(\App\Models\User::ROLE_ADMIN)->firstOrFail());
    }

    public function test_la_page_publique_s_affiche(): void
    {
        $this->get('/')->assertOk()->assertSee('Direction Nationale des Hydrocarbures');
    }

    public function test_les_donnees_de_l_arbre_respectent_l_organigramme(): void
    {
        $noeuds = collect($this->getJson(route('organigramme.donnees'))->assertOk()->json('noeuds'));

        // Une seule racine (le DNH) portant les 6 services rattachés sur le côté.
        $racines = $noeuds->whereNull('parentId');
        $this->assertCount(1, $racines);
        $racine = $racines->first();
        $this->assertSame('DNH', $racine['sigle']);
        $this->assertCount(3, $racine['cotes']['appui_technique']);
        $this->assertCount(3, $racine['cotes']['personnel_appui']);

        // Les services rattachés ne sont pas dans la hiérarchie principale.
        $this->assertFalse($noeuds->contains(fn ($n) => in_array($n['sigle'], ['SAF', 'Courrier'], true)));

        // Services Déconcentrés reliés au DNAH en pointillés.
        $dnah = $noeuds->firstWhere('sigle', 'DNAH');
        $sd = $noeuds->firstWhere('sigle', 'SD');
        $this->assertSame($dnah['id'], $sd['parentId']);
        $this->assertSame('fonctionnel', $sd['lien']);

        // Replié au niveau des sections : les 45 chargés d'études sont masqués au départ.
        $charges = $noeuds->filter(fn ($n) => $n['typeStructure'] === 'section' && ! $n['estResponsable']);
        $this->assertCount(45, $charges);
        $this->assertTrue($charges->every(fn ($n) => $n['_expanded'] === false));
    }

    public function test_la_fiche_d_un_chef_de_section_inclut_le_taux_consolide(): void
    {
        $chef = Structure::where('sigle', 'SSTD')->first()->agents()->where('est_responsable', true)->first();

        $this->getJson(route('agents.fiche', $chef))
            ->assertOk()
            ->assertJsonPath('agent.nom', $chef->nom_complet)
            ->assertJsonStructure(['projets' => [['titre', 'taux', 'couleur', 'etapes' => [['libelle', 'terminee', 'date']]]], 'structure' => ['taux', 'projets']]);
    }

    public function test_la_fiche_d_un_agent_inactif_est_introuvable(): void
    {
        $agent = Agent::where('est_responsable', false)->first();
        $agent->update(['actif' => false]);

        $this->getJson(route('agents.fiche', $agent))->assertNotFound();
    }
}
