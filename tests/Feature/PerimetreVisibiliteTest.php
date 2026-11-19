<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un compte ne voit que sa fiche et la branche de l'organigramme placée sous lui ;
 * jamais les branches supérieures ou voisines. L'administrateur et la Direction voient tout.
 */
class PerimetreVisibiliteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** Compte de démonstration lié au titulaire de la structure (ou au premier chargé d'études). */
    private function compte(string $role, ?string $sigle = null): User
    {
        return User::role($role)
            ->when($sigle, fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('est_responsable', true)
                ->whereHas('structure', fn ($s) => $s->where('sigle', $sigle))))
            ->firstOrFail();
    }

    private function titulaire(string $sigle): Agent
    {
        return Structure::where('sigle', $sigle)->firstOrFail()->agents()->where('est_responsable', true)->firstOrFail();
    }

    private function noeuds(User $user): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($user)->getJson(route('organigramme.donnees'))->assertOk()->json('noeuds'));
    }

    public function test_un_visiteur_doit_se_connecter_pour_voir_l_organigramme(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->getJson(route('organigramme.donnees'))->assertUnauthorized();
        $this->get(route('agents.fiche', $this->titulaire('DNH')))->assertRedirect('/login');
        $this->get('/organigramme.pdf')->assertRedirect('/login');
    }

    public function test_l_administrateur_voit_tout_l_organigramme(): void
    {
        $noeuds = $this->noeuds($this->compte(User::ROLE_ADMIN));

        $this->assertSame('DNH', $noeuds->whereNull('parentId')->first()['sigle']);
        $this->assertTrue($noeuds->contains('sigle', 'DAMP'));
    }

    public function test_le_directeur_national_place_a_la_racine_voit_tout(): void
    {
        $user = $this->compte(User::ROLE_DIRECTION, 'DNH');

        $this->assertTrue($user->perimetre()->estGlobal());
        $this->assertSame('DNH', $this->noeuds($user)->whereNull('parentId')->first()['sigle']);
    }

    public function test_la_direction_nationale_adjointe_voit_tout(): void
    {
        $user = $this->compte(User::ROLE_DIRECTION, 'DNAH');
        $noeuds = $this->noeuds($user);

        $this->assertTrue($user->perimetre()->estGlobal());
        $this->assertSame('DNH', $noeuds->whereNull('parentId')->first()['sigle']);
        $this->assertTrue($noeuds->contains('sigle', 'DAMP'));
        $this->actingAs($user)->getJson(route('agents.fiche', $this->titulaire('DNH')))->assertOk();
        $this->actingAs($user)->get('/organigramme.pdf')->assertOk();
    }

    public function test_un_chef_de_division_ne_voit_que_sa_division(): void
    {
        $user = $this->compte(User::ROLE_CHEF, 'DAVP');
        $noeuds = $this->noeuds($user);

        // Il est la racine affichée, sans les services rattachés de la DNH.
        $racines = $noeuds->whereNull('parentId');
        $this->assertCount(1, $racines);
        $this->assertSame($user->agent_id, $racines->first()['agentId']);
        $this->assertArrayNotHasKey('cotes', $racines->first());

        // Ses sections sont visibles, pas les branches supérieures ni voisines.
        $sigles = $noeuds->pluck('sigle')->unique();
        $this->assertTrue($sigles->contains('SSTD'));
        $this->assertFalse($sigles->contains('DNH'));
        $this->assertFalse($sigles->contains('DNAH'));
        $this->assertFalse($sigles->contains('DAMP'));
        $this->assertFalse($sigles->contains('SPCP'));
    }

    public function test_un_chef_de_section_ne_voit_ni_sa_division_ni_les_autres_sections(): void
    {
        $user = $this->compte(User::ROLE_CHEF, 'SSTD');
        $noeuds = $this->noeuds($user);

        $this->assertSame($user->agent_id, $noeuds->whereNull('parentId')->first()['agentId']);
        $this->assertSame(['SSTD'], $noeuds->pluck('sigle')->unique()->values()->all());
        $this->assertGreaterThan(1, $noeuds->count(), 'Ses chargés d\'études doivent être visibles.');
    }

    public function test_un_charge_d_etudes_ne_voit_que_lui_meme(): void
    {
        $user = $this->compte(User::ROLE_CHARGE);
        $noeuds = $this->noeuds($user);

        $this->assertCount(1, $noeuds);
        $this->assertSame($user->agent_id, $noeuds->first()['agentId']);
    }

    public function test_le_titulaire_d_un_service_rattache_ne_voit_que_son_service(): void
    {
        $user = $this->compte(User::ROLE_CONSULTATION, 'SAF');
        $noeuds = $this->noeuds($user);

        $this->assertSame($user->agent_id, $noeuds->whereNull('parentId')->first()['agentId']);
        $this->assertSame(['SAF'], $noeuds->pluck('sigle')->unique()->values()->all());
    }

    public function test_la_fiche_d_un_agent_d_une_branche_superieure_ou_voisine_est_introuvable(): void
    {
        $chefSection = $this->compte(User::ROLE_CHEF, 'SSTD');

        $this->actingAs($chefSection);
        $this->getJson(route('agents.fiche', $this->titulaire('DAVP')))->assertNotFound();  // supérieur
        $this->getJson(route('agents.fiche', $this->titulaire('SAI')))->assertNotFound();   // voisin
        $this->get(route('agents.fiche.pdf', $this->titulaire('DNH')))->assertNotFound();
        $this->getJson(route('agents.fiche', $chefSection->agent))->assertOk();             // lui-même

        $subordonne = Agent::where('structure_id', $chefSection->agent->structure_id)->where('est_responsable', false)->where('actif', true)->firstOrFail();
        $this->getJson(route('agents.fiche', $subordonne))->assertOk();
    }

    public function test_l_export_pdf_complet_est_reserve_aux_comptes_qui_voient_tout(): void
    {
        $this->actingAs($this->compte(User::ROLE_CHEF, 'DAVP'))->get('/organigramme.pdf')->assertForbidden();
        $this->actingAs($this->compte(User::ROLE_ADMIN))->get('/organigramme.pdf')->assertOk();
    }

    public function test_la_liste_des_agents_est_limitee_a_la_branche(): void
    {
        $user = $this->compte(User::ROLE_CHEF, 'DAVP');
        $hors = $this->titulaire('DAMP');

        $this->actingAs($user)->get(route('admin.agents.index'))
            ->assertOk()
            ->assertSee($this->titulaire('SSTD')->nom_complet)
            ->assertDontSee($hors->nom_complet)
            ->assertDontSee($this->titulaire('DNH')->nom_complet);
    }

    public function test_un_projet_hors_branche_est_inaccessible(): void
    {
        $user = $this->compte(User::ROLE_CHEF, 'SSTD');
        $dehors = Projet::whereIn('structure_id', Structure::where('sigle', 'DAMP')->firstOrFail()->descendantIds())
            ->whereDoesntHave('agents', fn ($a) => $a->whereIn('agents.id', $user->perimetre()->agentIds()))
            ->firstOrFail();
        $dedans = Projet::whereIn('structure_id', Structure::where('sigle', 'SSTD')->firstOrFail()->descendantIds())->firstOrFail();

        $this->actingAs($user);
        $this->get(route('admin.projets.edit', $dehors))->assertForbidden();
        $this->get(route('admin.projets.edit', $dedans))->assertOk();
        $this->get(route('admin.projets.index'))->assertOk()->assertDontSee($dehors->titre)->assertSee($dedans->titre);
    }

    public function test_le_tableau_de_bord_ne_compte_que_les_projets_de_la_branche(): void
    {
        $user = $this->compte(User::ROLE_CHEF, 'DAVP');
        $attendus = $user->perimetre()->restreindreProjets(Projet::query())->nonArchives()->count();

        $this->actingAs($user)->get(route('admin.tableau-de-bord'))
            ->assertOk()
            ->assertViewHas('total', $attendus)
            ->assertViewHas('divisions', fn ($d) => $d->pluck('structure.sigle')->all() === ['DAVP']);
        $this->assertLessThan(Projet::nonArchives()->count(), $attendus);
    }

    public function test_un_compte_sans_fiche_agent_ne_voit_rien(): void
    {
        $user = User::factory()->create(['agent_id' => null]);
        $user->assignRole(User::ROLE_CONSULTATION);

        $this->assertSame([], $this->noeuds($user)->all());
        $this->actingAs($user)->get(route('admin.projets.index'))->assertOk()->assertViewHas('projets', fn ($p) => $p->total() === 0);
    }
}
