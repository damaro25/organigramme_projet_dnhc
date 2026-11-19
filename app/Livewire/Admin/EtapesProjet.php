<?php

namespace App\Livewire\Admin;

use App\Models\Etape;
use App\Models\PieceJointe;
use App\Models\Projet;
use App\Models\User;
use App\Notifications\EtapeTermineeNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestion des étapes d'un projet sans rechargement de la page :
 * ajout, modification, suppression, tri par glisser-déposer, validation avec date,
 * commentaire et pièces jointes (justificatifs), consultables ensuite.
 */
class EtapesProjet extends Component
{
    use WithFileUploads;

    /** Nombre maximal de fichiers par envoi, et taille maximale d'un fichier (Ko). */
    public const MAX_FICHIERS = 10;

    public const TAILLE_MAX_KO = 10240;

    public Projet $projet;

    public array $nouvelleEtape = ['libelle' => '', 'poids' => 1];

    public ?int $enEdition = null;

    public array $edition = ['libelle' => '', 'poids' => 1];

    /** Date et commentaire de chaque étape terminée, modifiables sur place. */
    public array $cochage = [];

    /** Étape dont le formulaire de validation (ou d'ajout de pièces) est ouvert. */
    public ?int $enValidation = null;

    public array $validation = ['date_realisation' => null, 'commentaire' => ''];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> fichiers prêts à joindre */
    public array $fichiers = [];

    /**
     * Dernière sélection (champ fichier ou glisser-déposer) : elle s'ajoute à $fichiers
     * au lieu de la remplacer, pour joindre des fichiers venant de plusieurs dossiers.
     *
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>
     */
    public array $nouveauxFichiers = [];

    public function mount(Projet $projet): void
    {
        $this->authorize('view', $projet);
        $this->projet = $projet;
        $this->chargerCochage();
    }

    #[Computed]
    public function etapes()
    {
        return $this->projet->etapes()->with(['validateur', 'piecesJointes'])->get();
    }

    #[Computed]
    public function taux(): int
    {
        return $this->projet->unsetRelation('etapes')->taux_execution;
    }

    #[Computed]
    public function peutGerer(): bool
    {
        return auth()->user()->can('gererEtapes', $this->projet);
    }

    #[Computed]
    public function peutCocher(): bool
    {
        return auth()->user()->can('cocherEtapes', $this->projet);
    }

    public function ajouter(): void
    {
        $this->authorize('gererEtapes', $this->projet);
        $donnees = $this->validate([
            'nouvelleEtape.libelle' => ['required', 'string', 'max:255'],
            'nouvelleEtape.poids' => ['required', 'integer', 'min:1', 'max:100'],
        ])['nouvelleEtape'];

        $this->projet->etapes()->create($donnees);
        $this->reset('nouvelleEtape');
        $this->rafraichir('Étape ajoutée.');
    }

    public function editer(int $id): void
    {
        $this->authorize('gererEtapes', $this->projet);
        $etape = $this->etape($id);
        $this->enEdition = $etape->id;
        $this->edition = ['libelle' => $etape->libelle, 'poids' => $etape->poids];
    }

    public function enregistrer(): void
    {
        $this->authorize('gererEtapes', $this->projet);
        $donnees = $this->validate([
            'edition.libelle' => ['required', 'string', 'max:255'],
            'edition.poids' => ['required', 'integer', 'min:1', 'max:100'],
        ])['edition'];

        $this->etape($this->enEdition)->update($donnees);
        $this->enEdition = null;
        $this->rafraichir('Étape modifiée.');
    }

    public function annuler(): void
    {
        $this->enEdition = null;
        $this->resetValidation();
    }

    public function supprimer(int $id): void
    {
        $this->authorize('gererEtapes', $this->projet);
        $this->etape($id)->delete();
        $this->rafraichir('Étape supprimée.');
    }

    /** Appelé par SortableJS avec la nouvelle liste ordonnée d'identifiants. */
    public function reordonner(array $ids): void
    {
        $this->authorize('gererEtapes', $this->projet);
        $this->projet->reordonnerEtapes(array_map('intval', $ids));
        $this->rafraichir('Ordre des étapes enregistré.');
    }

    public function basculer(int $id): void
    {
        $this->authorize('cocherEtapes', $this->projet);
        $etape = $this->etape($id);

        if ($etape->est_terminee) {
            $etape->decocher();
            $this->rafraichir('Étape décochée.');

            return;
        }

        $etape->cocher(auth()->user());
        $this->notifierResponsables($etape);
        $this->rafraichir('Étape terminée ✔');
    }

    /**
     * Ouvre le formulaire de validation d'une étape (date, commentaire, pièces jointes),
     * ou, pour une étape déjà terminée, celui d'ajout de pièces jointes.
     */
    public function ouvrirValidation(int $id): void
    {
        $this->authorize('cocherEtapes', $this->projet);
        $etape = $this->etape($id);
        $this->enValidation = $etape->id;
        $this->validation = [
            'date_realisation' => ($etape->date_realisation ?? today())->toDateString(),
            'commentaire' => (string) $etape->commentaire,
        ];
        $this->fichiers = [];
        $this->resetValidation();
    }

    public function fermerValidation(): void
    {
        $this->reset('enValidation', 'fichiers', 'nouveauxFichiers');
        $this->resetValidation();
    }

    /** Ajoute la nouvelle sélection aux fichiers déjà choisis (sans doublon, dans la limite autorisée). */
    public function updatedNouveauxFichiers(): void
    {
        $this->validate(
            ['nouveauxFichiers.*' => self::regleFichier()],
            self::messagesFichier('nouveauxFichiers'),
        );

        $deja = collect($this->fichiers)->map(fn ($f) => $f->getClientOriginalName().'|'.$f->getSize())->all();
        foreach ($this->nouveauxFichiers as $fichier) {
            $cle = $fichier->getClientOriginalName().'|'.$fichier->getSize();
            if (in_array($cle, $deja, true)) {
                continue;
            }
            if (count($this->fichiers) >= self::MAX_FICHIERS) {
                $this->addError('fichiers', 'Vous pouvez joindre au plus '.self::MAX_FICHIERS.' fichiers à la fois.');
                break;
            }
            $this->fichiers[] = $fichier;
            $deja[] = $cle;
        }

        $this->nouveauxFichiers = [];
    }

    public function retirerFichier(int $index): void
    {
        unset($this->fichiers[$index]);
        $this->fichiers = array_values($this->fichiers);
    }

    /** Valide l'étape ouverte (si elle ne l'est pas déjà) et enregistre les pièces jointes. */
    public function valider(): void
    {
        $this->authorize('cocherEtapes', $this->projet);
        $etape = $this->etape((int) $this->enValidation);
        $dejaTerminee = $etape->est_terminee;

        $this->validate([
            'validation.date_realisation' => [$dejaTerminee ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
            'validation.commentaire' => ['nullable', 'string', 'max:1000'],
            'fichiers' => [$dejaTerminee ? 'required' : 'nullable', 'array', 'max:'.self::MAX_FICHIERS],
            'fichiers.*' => self::regleFichier(),
        ], [
            'validation.date_realisation.before_or_equal' => 'La date de réalisation ne peut pas être dans le futur.',
            'fichiers.required' => 'Choisissez au moins un fichier à joindre.',
            'fichiers.max' => 'Vous pouvez joindre au plus '.self::MAX_FICHIERS.' fichiers à la fois.',
            ...self::messagesFichier('fichiers'),
        ], [
            'validation.date_realisation' => 'date de réalisation',
            'validation.commentaire' => 'commentaire',
        ]);

        if (! $dejaTerminee) {
            $etape->cocher(auth()->user(), $this->validation['date_realisation'], $this->validation['commentaire'] ?: null);
        }

        foreach ($this->fichiers as $fichier) {
            $etape->piecesJointes()->create([
                'projet_id' => $this->projet->id,
                'nom' => $fichier->getClientOriginalName(),
                'chemin' => $fichier->store("projets/{$this->projet->id}/etapes/{$etape->id}", 'public'),
                'taille' => $fichier->getSize(),
                'mime' => $fichier->getMimeType(),
                'ajoute_par' => auth()->id(),
            ]);
        }

        $nombre = count($this->fichiers);
        $this->fermerValidation();

        if (! $dejaTerminee) {
            $this->notifierResponsables($etape);
        }

        $this->rafraichir(match (true) {
            ! $dejaTerminee && $nombre > 0 => "Étape terminée ✔ avec {$nombre} pièce(s) jointe(s).",
            ! $dejaTerminee => 'Étape terminée ✔',
            default => "{$nombre} pièce(s) jointe(s) ajoutée(s).",
        });
    }

    /** Supprime une pièce jointe d'une étape (fichier compris). */
    public function supprimerPieceJointe(int $id): void
    {
        $this->authorize('cocherEtapes', $this->projet);
        PieceJointe::query()
            ->where('projet_id', $this->projet->id)
            ->whereNotNull('etape_id')
            ->findOrFail($id)
            ->delete();

        $this->rafraichir('Pièce jointe supprimée.');
    }

    /** Enregistre la date de réalisation ou le commentaire d'une étape terminée. */
    public function enregistrerCochage(int $id): void
    {
        $this->authorize('cocherEtapes', $this->projet);
        $this->validate([
            "cochage.{$id}.date_realisation" => ['required', 'date', 'before_or_equal:today'],
            "cochage.{$id}.commentaire" => ['nullable', 'string', 'max:1000'],
        ], [
            "cochage.{$id}.date_realisation.before_or_equal" => 'La date de réalisation ne peut pas être dans le futur.',
        ], [
            "cochage.{$id}.date_realisation" => 'date de réalisation',
            "cochage.{$id}.commentaire" => 'commentaire',
        ]);

        $this->etape($id)->update([
            'date_realisation' => $this->cochage[$id]['date_realisation'],
            'commentaire' => $this->cochage[$id]['commentaire'] ?: null,
        ]);
        $this->rafraichir('Étape mise à jour.');
    }

    private static function regleFichier(): array
    {
        return ['file', 'mimes:'.PieceJointe::EXTENSIONS, 'max:'.self::TAILLE_MAX_KO];
    }

    private static function messagesFichier(string $champ): array
    {
        return [
            "{$champ}.*.mimes" => 'Formats acceptés : PDF, images (JPG, PNG, WebP), Word, Excel, PowerPoint, OpenDocument.',
            "{$champ}.*.max" => 'Chaque fichier ne doit pas dépasser 10 Mo.',
            "{$champ}.*.file" => 'Le fichier n\'a pas pu être envoyé.',
        ];
    }

    public function render()
    {
        return view('livewire.admin.etapes-projet');
    }

    private function etape(int $id): Etape
    {
        return $this->projet->etapes()->findOrFail($id);
    }

    private function chargerCochage(): void
    {
        $this->cochage = $this->projet->etapes()->get()->mapWithKeys(fn (Etape $e) => [$e->id => [
            'date_realisation' => $e->date_realisation?->toDateString(),
            'commentaire' => $e->commentaire,
        ]])->all();
    }

    private function rafraichir(string $message): void
    {
        unset($this->etapes, $this->taux);
        $this->chargerCochage();
        $this->dispatch('taux-mis-a-jour', taux: $this->taux);
        $this->dispatch('notification', message: $message);
    }

    private function notifierResponsables(Etape $etape): void
    {
        $destinataires = User::query()
            ->where('actif', true)
            ->whereIn('agent_id', $this->projet->responsables()->pluck('agents.id'))
            ->whereKeyNot(auth()->id())
            ->get();

        Notification::send($destinataires, new EtapeTermineeNotification($etape->load('projet'), auth()->user()));
    }
}
