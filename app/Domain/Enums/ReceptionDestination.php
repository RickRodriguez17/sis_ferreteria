<?php

namespace App\Domain\Enums;

enum ReceptionDestination: string
{
    case Store = 'tienda';
    case Work = 'obra';

    public function label(): string
    {
        return match ($this) {
            self::Store => 'Tienda',
            self::Work => 'Obra',
        };
    }
}
