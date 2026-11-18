<?php

namespace App\Notifications;

use App\Models\Etape;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Prévient le responsable d'un projet qu'une étape vient d'être cochée. */
class EtapeTermineeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Etape $etape, public ?User $auteur = null) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projet = $this->etape->projet;

        return (new MailMessage)
            ->subject("Étape terminée : {$projet->titre}")
            ->greeting('Bonjour,')
            ->line("L'étape « {$this->etape->libelle} » du projet « {$projet->titre} » a été marquée comme terminée"
                .($this->auteur ? " par {$this->auteur->name}" : '').'.')
            ->line("Taux d'exécution du projet : {$projet->taux_execution} %.")
            ->action('Voir le projet', route('admin.projets.edit', $projet))
            ->salutation('Direction Nationale des Hydrocarbures');
    }

    public function toArray(object $notifiable): array
    {
        $projet = $this->etape->projet;

        return [
            'projet_id' => $projet->id,
            'projet' => $projet->titre,
            'etape' => $this->etape->libelle,
            'auteur' => $this->auteur?->name,
            'taux' => $projet->taux_execution,
            'message' => "Étape « {$this->etape->libelle} » terminée sur « {$projet->titre} »",
        ];
    }
}
