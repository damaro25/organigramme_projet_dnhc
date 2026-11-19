<?php

namespace App\Livewire\Admin;

use App\Models\Etape;
use App\Models\Projet;
use App\Models\User;
use App\Notifications\EtapeTermineeNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Gestion des étapes d'un projet sans rechargement de la page :
 * ajout, modification, suppression, tri par glisser-déposer, cochage avec date et commentaire.
 */
class EtapesProjet extends Component
{
    public Projet $projet;

    public array $nouvelleEtape = ['libelle' => '', 'poids' => 1];

    public ?int $enEdition = null;

    public array $edition = ['libelle' => '', 'poids' => 1];

    /** Date et commentaire de chaque étape terminée, modifiables sur place. */
    public array $cochage = [];

    public function mount(Projet $projet): void
    {
        $this->authorize('view', $projet);
        $this->projet = $projet;
        $this->chargerCochage();
    }

    #[Computed]
    public function etapes()
    {
        return $this->projet->etapes()->with('validateur')->get();
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
