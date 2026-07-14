<?php

namespace App\Domain\Enums;

enum QuotationStatus: string
{
    case Open = 'open';
    case Converted = 'converted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
