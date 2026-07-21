<?php

namespace App\Domain\Enums;

enum CreditStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
