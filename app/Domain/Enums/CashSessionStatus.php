<?php

namespace App\Domain\Enums;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
