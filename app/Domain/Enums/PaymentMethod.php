<?php

namespace App\Domain\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qr = 'qr';
    case Transfer = 'transfer';
}
