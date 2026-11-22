<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les chefs de division voient et modifient les seules sections de leur division ;
 * les chefs de section ne voient que les agents de leur section.
 */
class PerimetreChefsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function chef(string $sigle): User
    {
        return User::role(User::ROLE_CHEF)
            ->whereHas('agent', fn ($a) => $a->where('est_responsable', true)
                ->whereHas('structure', fn ($s) => $s->where('sigle', $sigle)))
            ->firstOrFail();
    }

    private function structure(string $sigle): Structure
    {
        return Structure::where('sigle', $sigle)->firstOrFail();
    }

    public function test_le_chef_de_division_ne_liste_que_sa_division(): void
    {
        $this->actingAs($this->chef('DAVP'))->get(route('admin.structures.index'))
            ->assertOk()
            ->assertSee($this->structure('SSTD')->nom)
            ->assertSee($this->structure('SAI')->nom)
            ->assertDontSee($this->structure('SPCP')->nom)
            ->assertDontSee('+ Ajouter une structure');
    }

    public function test_le_chef_de_division_modifie_une_section_de_sa_division_sans_la_deplacer(): void
    {
        $section = $this->structure('SSTD');
        $autreDivision = $this->structure('DAMP');

        $this->actingAs($this->chef('DAVP'));
        $this->get(route('admin.structures.edit', $section))->assertOk();
        $this->put(route('admin.structures.update', $section), [
            'nom' => 'Section Stockage et Distribution',
            'sigle' => 'SSD',
            'type' => 'division',
            'parent_id' => $autreDivision->id,
            'ordre' => 4,
        ])->assertRedirect(route('admin.structures.index'));

        $section->refresh();
        $this->assertSame('Section Stockage et Distribution', $section->nom);
        $this->assertSame('SSD', $section->sigle);
        $this->assertSame('section', $section->type->value);
        $this->assertSame($this->structure('DAVP')->id, $section->parent_id);
    }

    public function test_le_chef_de_division_ne_touche_ni_aux_autres_divisions_ni_a_la_sienne_ni_a_la_creation(): void
    {
        $this->actingAs($this->chef('DAVP'));
        $this->get(route('admin.structures.edit', $this->structure('SPCP')))->assertForbidden();
        $this->put(route('admin.structures.update', $this->structure('SPCP')), ['nom' => 'X', 'ordre' => 1])->assertForbidden();
        $this->get(route('admin.structures.edit', $this->structure('DAVP')))->assertForbidden();
        $this->get(route('admin.structures.create'))->assertForbidden();
        $this->postJson(route('admin.structures.reordonner'), ['ids' => [$this->structure('SPCP')->id]])->assertForbidden();
        $this->postJson(route('admin.structures.reordonner'), ['ids' => [$this->structure('SSTD')->id, $this->structure('SAI')->id]])->assertOk();
    }

    public function test_le_chef_de_section_n_a_pas_acces_aux_structures(): void
    {
        $this->actingAs($this->chef('SSTD'));
        $this->get(route('admin.structures.index'))->assertForbidden();
        $this->get(route('admin.structures.edit', $this->structure('SSTD')))->assertForbidden();
    }

    public function test_le_chef_de_section_ne_voit_que_les_agents_de_sa_section(): void
    {
        $chef = $this->chef('SSTD');
        $voisin = Agent::actifs()->where('structure_id', $this->structure('SAI')->id)->where('est_responsable', false)->firstOrFail();
        // Même rattaché à lui comme supérieur, un agent d'une autre section reste invisible.
        $voisin->update(['superieur_id' => $chef->agent_id]);
        $chef = $chef->fresh();

        $perimetre = $chef->perimetre();
        $this->assertFalse($perimetre->voitAgent($voisin));
        $this->assertSame(
            [$this->structure('SSTD')->id],
            Agent::whereIn('id', $perimetre->agentIds())->distinct()->pluck('structure_id')->all(),
        );

        $noeuds = collect($this->actingAs($chef)->getJson(route('organigramme.donnees'))->assertOk()->json('noeuds'));
        $this->assertFalse($noeuds->contains('agentId', $voisin->id));
        $this->assertCount(1, $noeuds->whereNull('parentId'));

        $this->get(route('admin.agents.index'))->assertOk()->assertDontSee($voisin->nom_complet);
        $this->getJson(route('agents.fiche', $voisin))->assertNotFound();
    }
}
