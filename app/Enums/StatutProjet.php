<?php

namespace App\Enums;

enum StatutProjet: string
{
    case NonDemarre = 'non_demarre';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Suspendu = 'suspendu';

    public function libelle(): string
    {
        return match ($this) {
            self::NonDemarre => 'Non démarré',
            self::EnCours => 'En cours',
            self::Termine => 'Terminé',
            self::Suspendu => 'Suspendu',
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function classes(): string
    {
        return match ($this) {
            self::NonDemarre => 'bg-slate-100 text-slate-700',
            self::EnCours => 'bg-blue-100 text-blue-800',
            self::Termine => 'bg-green-100 text-green-800',
            self::Suspendu => 'bg-amber-100 text-amber-800',
        };
    }
}
