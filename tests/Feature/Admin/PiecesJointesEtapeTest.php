<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EtapesProjet;
use App\Models\PieceJointe;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Pièces jointes (justificatifs) jointes lors de la validation d'une étape, puis consultées. */
class PiecesJointesEtapeTest extends TestCase
{
    use RefreshDatabase;

    private User $chef;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        Notification::fake();

        $this->chef = User::where('email', 'chef.section@dnh.local')->firstOrFail();
        $this->projet = Projet::whereIn('structure_id', Structure::where('sigle', 'SSTD')->firstOrFail()->descendantIds())
            ->whereHas('etapes', fn ($q) => $q->where('est_terminee', false))
            ->firstOrFail();
    }

    public function test_valider_une_etape_avec_plusieurs_pieces_jointes(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->set('validation.commentaire', 'Procès-verbal signé')
            ->set('fichiers', [
                UploadedFile::fake()->create('proces-verbal.pdf', 200, 'application/pdf'),
                UploadedFile::fake()->image('photo-chantier.jpg', 800, 600),
            ])
            ->call('valider')
            ->assertHasNoErrors()
            ->assertSet('enValidation', null)
            ->assertDispatched('taux-mis-a-jour');

        $etape->refresh();
        $this->assertTrue($etape->est_terminee);
        $this->assertSame('Procès-verbal signé', $etape->commentaire);
        $this->assertSame($this->chef->id, $etape->valide_par);
        $this->assertCount(2, $etape->piecesJointes);
        foreach ($etape->piecesJointes as $pj) {
            $this->assertSame($this->projet->id, $pj->projet_id);
            Storage::disk('public')->assertExists($pj->chemin);
        }
    }

    public function test_plusieurs_selections_successives_s_ajoutent_sans_doublon(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();
        $pv = UploadedFile::fake()->create('proces-verbal.pdf', 200, 'application/pdf');

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            // Première sélection : deux fichiers d'un dossier.
            ->set('nouveauxFichiers', [$pv, UploadedFile::fake()->image('photo-1.jpg')])
            ->assertCount('fichiers', 2)
            ->assertSet('nouveauxFichiers', [])
            // Deuxième sélection, depuis un autre dossier : elle s'ajoute ; le PV déjà choisi n'est pas doublé.
            ->set('nouveauxFichiers', [UploadedFile::fake()->create('rapport.xlsx', 80), $pv])
            ->assertCount('fichiers', 3)
            ->call('retirerFichier', 1)
            ->assertCount('fichiers', 2)
            ->call('valider')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(['proces-verbal.pdf', 'rapport.xlsx'], $etape->fresh()->piecesJointes->pluck('nom')->all());
    }

    public function test_un_format_refuse_dans_une_selection_est_signale(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->set('nouveauxFichiers', [UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream')])
            ->assertHasErrors(['nouveauxFichiers.0'])
            ->assertCount('fichiers', 0);
    }

    public function test_valider_sans_piece_jointe_reste_possible(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->call('valider')
            ->assertHasNoErrors();

        $this->assertTrue($etape->fresh()->est_terminee);
        $this->assertCount(0, $etape->fresh()->piecesJointes);
    }

    public function test_joindre_des_pieces_a_une_etape_deja_terminee(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();
        $etape->cocher($this->chef);

        $composant = Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->call('valider')
            ->assertHasErrors(['fichiers' => 'required']);

        $composant->set('fichiers', [UploadedFile::fake()->create('rapport.docx', 50)])
            ->call('valider')
            ->assertHasNoErrors();

        $this->assertCount(1, $etape->fresh()->piecesJointes);
    }

    public function test_les_formats_non_autorises_sont_refuses(): void
    {
        $etape = $this->projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->set('fichiers', [UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream')])
            ->call('valider')
            ->assertHasErrors(['fichiers.0']);

        $this->assertFalse($etape->fresh()->est_terminee);
    }

    public function test_la_piece_jointe_s_ouvre_dans_le_navigateur_pour_qui_voit_le_projet(): void
    {
        $etape = $this->projet->etapes()->firstOrFail();
        Storage::disk('public')->put('projets/pv.pdf', '%PDF-1.4 test');
        $pj = $etape->piecesJointes()->create([
            'projet_id' => $this->projet->id, 'nom' => 'pv.pdf', 'chemin' => 'projets/pv.pdf',
            'taille' => 13, 'mime' => 'application/pdf', 'ajoute_par' => $this->chef->id,
        ]);
        $url = route('admin.projets.pieces-jointes.apercu', [$this->projet, $pj]);

        $reponse = $this->actingAs($this->chef)->get($url)->assertOk();
        $this->assertStringStartsWith('inline', $reponse->headers->get('content-disposition'));
        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));

        // Un chef d'une autre division ne voit pas le projet, donc pas ses pièces.
        $this->actingAs($this->compteChefDe('DAMP'))->get($url)->assertForbidden();
    }

    public function test_la_fiche_agent_liste_les_pieces_jointes_des_etapes(): void
    {
        $etape = $this->projet->etapes()->firstOrFail();
        $etape->piecesJointes()->create([
            'projet_id' => $this->projet->id, 'nom' => 'pv.pdf', 'chemin' => 'projets/pv.pdf',
            'taille' => 13, 'mime' => 'application/pdf', 'ajoute_par' => $this->chef->id,
        ]);

        $etapes = collect($this->actingAs($this->chef)->getJson(route('agents.fiche', $this->chef->agent))->assertOk()->json('structure.projets'))
            ->firstWhere('id', $this->projet->id)['etapes'];

        $piece = collect($etapes)->pluck('pieces')->flatten(1)->firstWhere('nom', 'pv.pdf');
        $this->assertNotNull($piece);
        $this->assertStringContainsString('/apercu', $piece['apercu']);
        $this->assertSame('pdf', $piece['type']);
        $this->assertSame('13 o', $piece['taille']);
    }

    public function test_la_fiche_agent_liste_les_pieces_jointes_du_projet(): void
    {
        $this->projet->piecesJointes()->create([
            'nom' => 'note.docx', 'chemin' => 'projets/note.docx', 'taille' => 1536,
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ajoute_par' => $this->chef->id,
        ]);

        $piece = collect(collect($this->actingAs($this->chef)->getJson(route('agents.fiche', $this->chef->agent))->assertOk()->json('structure.projets'))
            ->firstWhere('id', $this->projet->id)['pieces'])->firstWhere('nom', 'note.docx');

        $this->assertSame('autre', $piece['type']);
        $this->assertSame('1,5 Ko', $piece['taille']);
        $this->assertStringContainsString('/pieces-jointes/', $piece['telechargement']);
    }

    public function test_un_charge_d_etudes_ne_joint_rien(): void
    {
        $charge = User::where('email', 'charge.etudes@dnh.local')->firstOrFail();
        $this->projet->agents()->syncWithoutDetaching([$charge->agent_id => ['role' => 'membre']]);
        $etape = $this->projet->etapes()->firstOrFail();

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('ouvrirValidation', $etape->id)
            ->assertForbidden();
    }

    public function test_supprimer_une_piece_jointe_supprime_le_fichier(): void
    {
        $etape = $this->projet->etapes()->firstOrFail();
        Storage::disk('public')->put('projets/a-supprimer.pdf', '%PDF');
        $pj = $etape->piecesJointes()->create([
            'projet_id' => $this->projet->id, 'nom' => 'a-supprimer.pdf', 'chemin' => 'projets/a-supprimer.pdf',
            'taille' => 4, 'mime' => 'application/pdf', 'ajoute_par' => $this->chef->id,
        ]);

        Livewire::actingAs($this->chef)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('supprimerPieceJointe', $pj->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($pj);
        Storage::disk('public')->assertMissing('projets/a-supprimer.pdf');
    }

    private function compteChefDe(string $sigle): User
    {
        $chef = Structure::where('sigle', $sigle)->firstOrFail()->agents()->where('est_responsable', true)->firstOrFail();

        $user = User::factory()->create(['agent_id' => $chef->id]);
        $user->assignRole(User::ROLE_CHEF);

        return $user;
    }
}
