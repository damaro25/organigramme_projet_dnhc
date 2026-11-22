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
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PiecesJointesTest extends TestCase
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

    /** Un vrai petit PDF : le type est reconnu par son contenu, pas seulement par son nom. */
    private function pdf(string $nom = 'rapport.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nom, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
    }

    /** Projet dont le chef de section est responsable, avec le chargé d'études comme membre. */
    private function projetDuCharge(): array
    {
        $charge = $this->compte('charge.etudes@dnh.local');
        $projet = $this->compte('chef.section@dnh.local')->agent->projetsResponsable()->firstOrFail();
        $projet->agents()->syncWithoutDetaching([$charge->agent_id => ['role' => 'membre']]);

        return [$charge, $projet];
    }

    public function test_un_projet_est_cree_avec_plusieurs_fichiers(): void
    {
        $admin = $this->compte('admin@dnh.local');

        $this->actingAs($admin)->post(route('admin.projets.store'), [
            'titre' => 'Contrôle des dépôts de Kaloum',
            'structure_id' => Structure::where('type', 'section')->value('id'),
            'statut' => 'en_cours',
            'pieces_jointes' => [$this->pdf('termes-de-reference.pdf'), UploadedFile::fake()->image('site.jpg'), $this->pdf('budget.pdf')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $projet = Projet::where('titre', 'Contrôle des dépôts de Kaloum')->firstOrFail();
        $this->assertSame(['budget.pdf', 'site.jpg', 'termes-de-reference.pdf'], $projet->documents()->orderBy('nom')->pluck('nom')->all());
        $projet->documents->each(fn ($pj) => Storage::disk('public')->assertExists($pj->chemin));
        $this->assertTrue(Activity::where('description', 'Documents ajoutés')->where('subject_id', $projet->id)->exists());
    }

    public function test_les_formats_non_acceptes_et_le_nombre_maximal_sont_controles(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $donnees = ['titre' => 'Essai', 'structure_id' => Structure::where('type', 'section')->value('id'), 'statut' => 'en_cours'];

        $this->actingAs($admin)->post(route('admin.projets.store'), $donnees + [
            'pieces_jointes' => [UploadedFile::fake()->createWithContent('outil.exe', 'MZ binaire')],
        ])->assertSessionHasErrors('pieces_jointes.0');

        $this->actingAs($admin)->post(route('admin.projets.store'), $donnees + [
            'pieces_jointes' => array_map(fn ($i) => $this->pdf("piece-{$i}.pdf"), range(1, PieceJointe::NOMBRE_MAX + 1)),
        ])->assertSessionHasErrors('pieces_jointes');
    }

    public function test_un_charge_joint_plusieurs_justificatifs_a_une_etape_validee(): void
    {
        [$charge, $projet] = $this->projetDuCharge();
        $etape = $projet->etapes()->where('est_terminee', false)->firstOrFail();

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $projet])
            ->call('basculer', $etape->id)
            ->set("fichiers.{$etape->id}", [$this->pdf('proces-verbal.pdf'), UploadedFile::fake()->image('photo-chantier.png')])
            ->call('joindre', $etape->id)
            ->assertHasNoErrors();

        $pieces = $etape->fresh()->piecesJointes;
        $this->assertCount(2, $pieces);
        $this->assertTrue($pieces->every(fn ($pj) => $pj->projet_id === $projet->id && $pj->ajoute_par === $charge->id));
        $pieces->each(fn ($pj) => Storage::disk('public')->assertExists($pj->chemin));
        $this->assertStringStartsWith("projets/{$projet->id}/etapes/{$etape->id}/", $pieces->first()->chemin);
        // Les justificatifs d'étapes ne se mélangent pas aux documents du projet.
        $this->assertSame(0, $projet->documents()->count());
        $this->assertTrue(Activity::where('description', 'Justificatifs joints')->where('subject_id', $etape->id)->exists());
    }

    public function test_un_agent_non_affecte_ne_peut_pas_joindre_de_fichier(): void
    {
        $charge = $this->compte('charge.etudes@dnh.local');
        $autre = Projet::whereDoesntHave('agents', fn ($q) => $q->whereKey($charge->agent_id))->firstOrFail();
        $etape = $autre->etapes()->firstOrFail();

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $autre])
            ->set("fichiers.{$etape->id}", [$this->pdf()])
            ->call('joindre', $etape->id)
            ->assertForbidden();

        $this->assertSame(0, $etape->piecesJointes()->count());
    }

    public function test_un_format_refuse_a_l_etape(): void
    {
        [$charge, $projet] = $this->projetDuCharge();
        $etape = $projet->etapes()->firstOrFail();

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $projet])
            ->set("fichiers.{$etape->id}", [UploadedFile::fake()->createWithContent('script.exe', 'MZ binaire')])
            ->call('joindre', $etape->id)
            ->assertHasErrors("fichiers.{$etape->id}.0");

        $this->assertSame(0, $etape->piecesJointes()->count());
    }

    public function test_seul_l_auteur_ou_un_gestionnaire_retire_un_justificatif(): void
    {
        [$charge, $projet] = $this->projetDuCharge();
        $etape = $projet->etapes()->firstOrFail();
        $admin = $this->compte('admin@dnh.local');
        $deAdmin = PieceJointe::depuisFichier($this->pdf('admin.pdf'), $projet, $etape, $admin);
        $duCharge = PieceJointe::depuisFichier($this->pdf('charge.pdf'), $projet, $etape, $charge);

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $projet])
            ->call('retirerFichier', $deAdmin->id)->assertForbidden();
        $this->assertModelExists($deAdmin);

        Livewire::actingAs($charge)->test(EtapesProjet::class, ['projet' => $projet])
            ->call('retirerFichier', $duCharge->id)->assertHasNoErrors();
        $this->assertModelMissing($duCharge);
        Storage::disk('public')->assertMissing($duCharge->chemin);

        // Le chef de section, gestionnaire du projet, peut retirer le fichier de l'administrateur.
        Livewire::actingAs($this->compte('chef.section@dnh.local'))->test(EtapesProjet::class, ['projet' => $projet])
            ->call('retirerFichier', $deAdmin->id);
        $this->assertModelMissing($deAdmin);
    }

    public function test_la_visionneuse_affiche_pdf_et_photos_et_telecharge_le_reste(): void
    {
        $projet = Projet::firstOrFail();
        $etape = $projet->etapes()->firstOrFail();
        $admin = $this->compte('admin@dnh.local');
        $pdf = PieceJointe::depuisFichier($this->pdf('note.pdf'), $projet, null, $admin);
        $photo = PieceJointe::depuisFichier(UploadedFile::fake()->image('site.jpg'), $projet, $etape, $admin);
        $tableur = PieceJointe::depuisFichier(UploadedFile::fake()->create('budget.xlsx', 10), $projet, null, $admin);

        $consultation = $this->compte('consultation@dnh.local');

        $this->actingAs($consultation)->get(route('admin.projets.pieces-jointes.apercu', [$projet, $pdf]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=note.pdf');
        $this->actingAs($consultation)->get(route('admin.projets.pieces-jointes.apercu', [$projet, $photo]))
            ->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringStartsWith('attachment', $this->actingAs($consultation)
            ->get(route('admin.projets.pieces-jointes.apercu', [$projet, $tableur]))->headers->get('Content-Disposition'));

        // La page du projet affiche la visionneuse et les deux familles de fichiers.
        $this->actingAs($consultation)->get(route('admin.projets.edit', $projet))
            ->assertOk()->assertSee('note.pdf')->assertSee('site.jpg')->assertSee('voir-fichier');

        // Un visiteur non connecté, ou un fichier d'un autre projet : refusé.
        auth()->logout();
        $this->get(route('admin.projets.pieces-jointes.apercu', [$projet, $pdf]))->assertRedirect(route('login'));
        $autre = Projet::whereKeyNot($projet->id)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.projets.pieces-jointes.apercu', [$autre, $pdf]))->assertNotFound();
    }

    public function test_supprimer_une_etape_ou_un_projet_efface_les_fichiers_du_disque(): void
    {
        $admin = $this->compte('admin@dnh.local');
        $projet = Projet::firstOrFail();
        [$etape1, $etape2] = $projet->etapes()->take(2)->get()->all();
        $justificatif = PieceJointe::depuisFichier($this->pdf('pv.pdf'), $projet, $etape1, $admin);

        Livewire::actingAs($admin)->test(EtapesProjet::class, ['projet' => $projet])->call('supprimer', $etape1->id);
        Storage::disk('public')->assertMissing($justificatif->chemin);
        $this->assertModelMissing($justificatif);

        $document = PieceJointe::depuisFichier($this->pdf('doc.pdf'), $projet, null, $admin);
        $autreJustificatif = PieceJointe::depuisFichier($this->pdf('pv2.pdf'), $projet, $etape2, $admin);
        $this->actingAs($admin)->delete(route('admin.projets.destroy', $projet))->assertRedirect();
        Storage::disk('public')->assertMissing([$document->chemin, $autreJustificatif->chemin]);
    }
}
