<?php

namespace App\Enums;

enum TypeStructure: string
{
    case Direction = 'direction';
    case ServiceRattache = 'service_rattache';
    case Division = 'division';
    case Section = 'section';
    case ServiceDeconcentre = 'service_deconcentre';

    public function libelle(): string
    {
        return match ($this) {
            self::Direction => 'Direction',
            self::ServiceRattache => 'Service rattaché',
            self::Division => 'Division',
            self::Section => 'Section',
            self::ServiceDeconcentre => 'Service déconcentré',
        };
    }
}
