<?php

namespace App\Domain\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Partial => 'Parcial',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }
}
