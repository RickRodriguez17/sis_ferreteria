<?php

namespace App\Domain\Enums;

enum QuotationStatus: string
{
    case Open = 'open';
    case Converted = 'converted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Converted => 'Convertida',
            self::Expired => 'Vencida',
            self::Cancelled => 'Cancelada',
        };
    }
}
