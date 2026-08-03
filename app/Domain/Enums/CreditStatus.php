<?php

namespace App\Domain\Enums;

enum CreditStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Pendiente',
            self::Partial => 'Parcial',
            self::Paid => 'Pagado',
            self::Overdue => 'Vencido',
            self::Cancelled => 'Anulado',
        };
    }
}
