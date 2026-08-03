<?php

namespace App\Domain\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Credit = 'credit';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Contado',
            self::Credit => 'Crédito',
            self::Mixed => 'Mixto',
        };
    }
}
