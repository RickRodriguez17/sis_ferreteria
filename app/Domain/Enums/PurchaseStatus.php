<?php

namespace App\Domain\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
