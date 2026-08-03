<?php

namespace App\Domain\Enums;

enum CustomerType: string
{
    case Registered = 'registered';
    case Occasional = 'occasional';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registrado',
            self::Occasional => 'Ocasional',
        };
    }
}
