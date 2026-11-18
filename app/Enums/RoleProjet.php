<?php

namespace App\Enums;

enum RoleProjet: string
{
    case Responsable = 'responsable';
    case Membre = 'membre';

    public function libelle(): string
    {
        return match ($this) {
            self::Responsable => 'Responsable',
            self::Membre => 'Membre',
        };
    }
}
