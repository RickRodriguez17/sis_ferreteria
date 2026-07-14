<?php

namespace App\Domain\Enums;

enum PaymentType: string
{
    case Cash = 'cash';
    case Credit = 'credit';
    case Mixed = 'mixed';
}
