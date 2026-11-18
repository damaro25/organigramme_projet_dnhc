<?php

namespace App\Enums;

enum GroupeStructure: string
{
    case AppuiTechnique = 'appui_technique';
    case PersonnelAppui = 'personnel_appui';

    public function libelle(): string
    {
        return match ($this) {
            self::AppuiTechnique => "Services d'appui technique",
            self::PersonnelAppui => "Personnel d'appui",
        };
    }
}
