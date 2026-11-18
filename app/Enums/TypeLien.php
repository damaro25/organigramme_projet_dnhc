<?php

namespace App\Enums;

enum TypeLien: string
{
    case Hierarchique = 'hierarchique';
    case Fonctionnel = 'fonctionnel';

    public function libelle(): string
    {
        return match ($this) {
            self::Hierarchique => 'Hiérarchique',
            self::Fonctionnel => 'Fonctionnel',
        };
    }
}
