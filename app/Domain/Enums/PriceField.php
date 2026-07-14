<?php

namespace App\Domain\Enums;

enum PriceField: string
{
    case PriceWithInvoice = 'price_with_invoice';
    case PriceWithoutInvoice = 'price_without_invoice';
    case Cost = 'cost';
}
