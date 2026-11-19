<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EtapesProjet;
use App\Models\Etape;
use App\Models\PieceJointe;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Plusieurs pièces jointes justificatives lors de la validation d'une étape, et leur visualisation. */
class PiecesJointesEtapeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Projet $projet;

    private Etape $etape;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@dnh.local')->firstOrFail();
        $this->projet = Projet::factory()->create(['structure_id' => Structure::first()->id]);
        $this->etape = $this->projet->etapes()->create(['libelle' => 'Réception des travaux', 'poids' => 1]);
    }

    private function fichiers(): array
    {
        return [
            UploadedFile::fake()->create('proces-verbal.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->image('photo-chantier.jpg'),
            UploadedFile::fake()->create('rapport.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }

    public function test_on_joint_plusieurs_fichiers_a_une_etape_validee(): void
    {
        Livewire::actingAs($this->admin)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('basculer', $this->etape->id)
            ->set("fichiers.{$this->etape->id}", $this->fichiers())
            ->call('joindre', $this->etape->id)
            ->assertHasNoErrors()
            ->assertDispatched('notification', message: '3 pièces jointes ajoutées.')
            ->assertSee('proces-verbal.pdf')
            ->assertSee('photo-chantier.jpg')
            ->assertSee('rapport.docx');

        $pieces = $this->etape->piecesJointes()->get();
        $this->assertCount(3, $pieces);
        $pieces->each(function (PieceJointe $pj) {
            $this->assertSame($this->projet->id, $pj->projet_id);
            $this->assertSame($this->admin->id, $pj->ajoute_par);
            Storage::disk('public')->assertExists($pj->chemin);
        });

        // Les justificatifs d'étape ne se mélangent pas aux documents généraux du projet.
        $this->assertCount(0, $this->projet->piecesJointes()->get());
        $this->assertCount(3, $this->projet->toutesPiecesJointes()->get());
    }

    public function test_on_ne_joint_pas_de_fichier_a_une_etape_non_terminee(): void
    {
        Livewire::actingAs($this->admin)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->set("fichiers.{$this->etape->id}", $this->fichiers())
            ->call('joindre', $this->etape->id)
            ->assertForbidden();

        $this->assertSame(0, PieceJointe::count());
    }

    public function test_les_types_et_le_nombre_de_fichiers_sont_controles(): void
    {
        $this->etape->cocher($this->admin);

        Livewire::actingAs($this->admin)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->set("fichiers.{$this->etape->id}", [UploadedFile::fake()->create('script.exe', 10)])
            ->call('joindre', $this->etape->id)
            ->assertHasErrors("fichiers.{$this->etape->id}.0")
            ->set("fichiers.{$this->etape->id}", array_map(
                fn ($i) => UploadedFile::fake()->create("doc-{$i}.pdf", 10, 'application/pdf'),
                range(1, EtapesProjet::MAX_FICHIERS + 1)
            ))
            ->call('joindre', $this->etape->id)
            ->assertHasErrors("fichiers.{$this->etape->id}");

        $this->assertSame(0, PieceJointe::count());
    }

    public function test_un_pdf_ou_une_image_se_visualise_dans_le_navigateur(): void
    {
        $this->etape->cocher($this->admin);
        Livewire::actingAs($this->admin)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->set("fichiers.{$this->etape->id}", $this->fichiers())
            ->call('joindre', $this->etape->id);

        $pdf = PieceJointe::where('nom', 'proces-verbal.pdf')->firstOrFail();
        $image = PieceJointe::where('nom', 'photo-chantier.jpg')->firstOrFail();
        $word = PieceJointe::where('nom', 'rapport.docx')->firstOrFail();

        $this->actingAs($this->admin);
        $this->get(route('admin.projets.pieces-jointes.apercu', [$this->projet, $pdf]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=proces-verbal.pdf');
        $this->get(route('admin.projets.pieces-jointes.apercu', [$this->projet, $image]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        // Un document Word ne s'affiche pas : il se télécharge.
        $this->get(route('admin.projets.pieces-jointes.apercu', [$this->projet, $word]))->assertNotFound();
        $this->get(route('admin.projets.pieces-jointes.show', [$this->projet, $word]))->assertDownload('rapport.docx');

        // Un fichier n'est visible qu'à travers son propre projet.
        $autre = Projet::factory()->create(['structure_id' => $this->projet->structure_id]);
        $this->get(route('admin.projets.pieces-jointes.apercu', [$autre, $pdf]))->assertNotFound();
    }

    public function test_supprimer_l_etape_ou_la_piece_jointe_efface_le_fichier(): void
    {
        $this->etape->cocher($this->admin);
        $composant = Livewire::actingAs($this->admin)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->set("fichiers.{$this->etape->id}", $this->fichiers())
            ->call('joindre', $this->etape->id);

        $premiere = $this->etape->piecesJointes()->firstOrFail();
        $composant->call('supprimerPieceJointe', $premiere->id)->assertDispatched('notification', message: 'Pièce jointe supprimée.');
        Storage::disk('public')->assertMissing($premiere->chemin);
        $this->assertCount(2, $this->etape->piecesJointes()->get());

        $chemins = $this->etape->piecesJointes()->pluck('chemin');
        $composant->call('supprimer', $this->etape->id);
        $chemins->each(fn ($c) => Storage::disk('public')->assertMissing($c));
        $this->assertSame(0, PieceJointe::count());
    }

    public function test_un_compte_de_consultation_ne_peut_ni_joindre_ni_supprimer(): void
    {
        $this->etape->cocher($this->admin);
        $piece = $this->etape->piecesJointes()->create([
            'projet_id' => $this->projet->id, 'nom' => 'pv.pdf', 'chemin' => 'x/pv.pdf', 'taille' => 10,
            'mime' => 'application/pdf', 'ajoute_par' => $this->admin->id,
        ]);
        $consultation = User::where('email', 'consultation@dnh.local')->firstOrFail();

        Livewire::actingAs($consultation)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->assertSee('pv.pdf')
            ->assertDontSee('Joindre des fichiers')
            ->set("fichiers.{$this->etape->id}", $this->fichiers())
            ->call('joindre', $this->etape->id)->assertForbidden();

        Livewire::actingAs($consultation)->test(EtapesProjet::class, ['projet' => $this->projet])
            ->call('supprimerPieceJointe', $piece->id)->assertForbidden();

        $this->assertModelExists($piece);
    }
}
