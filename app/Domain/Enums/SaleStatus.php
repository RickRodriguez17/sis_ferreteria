<?php

namespace App\Domain\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }
}
