<?php

namespace App\Domain\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qr = 'qr';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Qr => 'QR',
            self::Transfer => 'Transferencia',
        };
    }
}
