<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\PieceJointe;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Périmètre de consultation : chaque compte ne voit que sa branche de l'organigramme
 * (Administrateur, Direction et Consultation voient toute la DNH ; un chargé
 * d'études voit ses propres projets).
 */
class PerimetreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    private function compte(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function branche(string $sigle): array
    {
        return Structure::where('sigle', $sigle)->firstOrFail()->descendantIds();
    }

    /** Projets attendus pour un chef : sa branche, plus ceux auxquels il est affecté ailleurs. */
    private function attendusPourChef(User $chef, string $sigle): array
    {
        return Projet::whereIn('structure_id', $this->branche($sigle))
            ->orWhereHas('agents', fn ($a) => $a->where('agents.id', $chef->agent_id))
            ->pluck('id')->all();
    }

    private function projetHorsDe(User $user, string $sigle): Projet
    {
        return Projet::whereNotIn('structure_id', $this->branche($sigle))
            ->whereDoesntHave('agents', fn ($a) => $a->where('agents.id', $user->agent_id))
            ->firstOrFail();
    }

    public function test_un_chef_de_section_ne_voit_que_sa_section(): void
    {
        $chef = $this->compte('chef.section@dnh.local'); // section SSTD
        $attendus = $this->attendusPourChef($chef, 'SSTD');
        $this->assertNotEmpty($attendus);
        $this->assertEqualsCanonicalizing($attendus, Projet::visiblesPar($chef)->pluck('id')->all());

        $sien = Projet::whereIn('structure_id', $this->branche('SSTD'))->firstOrFail();
        $autre = $this->projetHorsDe($chef, 'SSTD');

        $this->actingAs($chef)->get(route('admin.projets.index', ['archive' => 0]))->assertOk()
            ->assertViewHas('projets', fn ($p) => $p->getCollection()->every(fn ($pr) => in_array($pr->id, $attendus, true)));
        $this->actingAs($chef)->get(route('admin.projets.edit', $sien))->assertOk();
        $this->actingAs($chef)->get(route('admin.projets.edit', $autre))->assertForbidden();

        // Ses fichiers aussi sont hors de portée.
        $pj = PieceJointe::depuisFichier(UploadedFile::fake()->image('site.jpg'), $autre, null, $this->compte('admin@dnh.local'));
        $this->actingAs($chef)->get(route('admin.projets.pieces-jointes.apercu', [$autre, $pj]))->assertForbidden();
        $this->actingAs($chef)->get(route('admin.projets.pieces-jointes.show', [$autre, $pj]))->assertForbidden();
    }

    public function test_un_chef_de_division_voit_toute_sa_division_et_rien_d_autre(): void
    {
        $chef = $this->compte('chef2.division@dnh.local'); // division Aval (DAVP)
        $visibles = Projet::visiblesPar($chef)->pluck('id')->all();

        $this->assertEqualsCanonicalizing($this->attendusPourChef($chef, 'DAVP'), $visibles);
        // Toutes ses sections, y compris celle du chef de section.
        foreach (['SAI', 'SSTD'] as $section) {
            $ids = Projet::whereIn('structure_id', $this->branche($section))->pluck('id')->all();
            $this->assertEmpty(array_diff($ids, $visibles), "Projets de {$section} manquants");
        }
        $this->actingAs($chef)->get(route('admin.projets.edit', $this->projetHorsDe($chef, 'DAVP')))->assertForbidden();
    }

    public function test_un_charge_d_etudes_ne_voit_que_ses_projets(): void
    {
        $charge = $this->compte('charge.etudes@dnh.local');
        $sien = Projet::whereIn('structure_id', $this->branche('SSTD'))->firstOrFail();
        $sien->agents()->syncWithoutDetaching([$charge->agent_id => ['role' => 'membre']]);
        $attendus = $charge->agent->projets()->pluck('projets.id')->all();

        $this->assertEqualsCanonicalizing($attendus, Projet::visiblesPar($charge)->pluck('id')->all());
        $this->actingAs($charge)->get(route('admin.projets.edit', $sien))->assertOk();

        // Même un projet de sa propre section lui est fermé s'il n'y est pas affecté.
        $voisin = Projet::whereIn('structure_id', $this->branche('SSTD'))
            ->whereDoesntHave('agents', fn ($a) => $a->where('agents.id', $charge->agent_id))->first();
        if ($voisin) {
            $this->actingAs($charge)->get(route('admin.projets.edit', $voisin))->assertForbidden();
        }

        // Liste des agents : sa seule fiche.
        $this->actingAs($charge)->get(route('admin.agents.index'))->assertOk()
            ->assertViewHas('agents', fn ($p) => $p->total() === 1 && $p->first()->id === $charge->agent_id);
    }

    public function test_la_direction_et_la_consultation_voient_toute_la_dnh(): void
    {
        $tous = Projet::count();
        $projet = Projet::latest('id')->firstOrFail();

        foreach (['admin', 'dnh', 'dnah', 'consultation'] as $c) {
            $user = $this->compte("{$c}@dnh.local");
            $this->assertSame($tous, Projet::visiblesPar($user)->count(), $c);
            $this->assertSame(Agent::count(), Agent::visiblesPar($user)->count(), $c);
            $this->actingAs($user)->get(route('admin.projets.edit', $projet))->assertOk();
        }
    }

    public function test_la_liste_des_agents_d_un_chef_se_limite_a_sa_branche(): void
    {
        $chef = $this->compte('chef.section@dnh.local');
        $branche = $this->branche('SSTD');

        $this->actingAs($chef)->get(route('admin.agents.index', ['etat' => 'tous']))->assertOk()
            ->assertViewHas('agents', fn ($p) => $p->total() === Agent::whereIn('structure_id', $branche)->count()
                && $p->getCollection()->every(fn ($a) => in_array($a->structure_id, $branche, true)))
            ->assertViewHas('divisions', fn ($d) => $d->isEmpty())
            ->assertViewHas('sections', fn ($s) => $s->pluck('sigle')->all() === ['SSTD']);
    }

    public function test_le_tableau_de_bord_suit_le_perimetre(): void
    {
        $nonArchives = fn (User $u) => Projet::visiblesPar($u)->nonArchives()->count();

        $chef = $this->compte('chef.section@dnh.local');
        $this->actingAs($chef)->get('/admin')->assertOk()
            ->assertViewHas('total', $nonArchives($chef))
            ->assertSee('Périmètre');

        $dnh = $this->compte('dnh@dnh.local');
        $this->actingAs($dnh)->get('/admin')->assertOk()
            ->assertViewHas('total', Projet::nonArchives()->count())
            ->assertViewHas('perimetre', null)
            ->assertViewHas('divisions', fn ($d) => $d->count() === 3);
    }

    public function test_la_fiche_de_l_agent_masque_les_fichiers_hors_perimetre(): void
    {
        $chef = $this->compte('chef.section@dnh.local');
        $projet = $this->projetHorsDe($chef, 'SSTD');
        $projet->update(['archive' => false]);
        $agent = $projet->agents()->where('actif', true)->firstOrFail();
        PieceJointe::depuisFichier(UploadedFile::fake()->image('site.jpg'), $projet, null, $this->compte('admin@dnh.local'));

        $pour = fn (User $u) => collect($this->actingAs($u)->getJson(route('agents.fiche', $agent))->assertOk()->json('projets'))
            ->firstWhere('id', $projet->id);

        $horsPerimetre = $pour($chef);
        $this->assertFalse($horsPerimetre['voitFichiers']);
        $this->assertSame([], $horsPerimetre['documents']);
        $this->assertSame(1, $horsPerimetre['nbFichiers']);

        $this->assertTrue($pour($this->compte('consultation@dnh.local'))['voitFichiers']);
    }
}
