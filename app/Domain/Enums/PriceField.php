<?php

namespace App\Domain\Enums;

enum PriceField: string
{
    case PriceWithInvoice = 'price_with_invoice';
    case PriceWithoutInvoice = 'price_without_invoice';
    case Cost = 'cost';

    public function label(): string
    {
        return match ($this) {
            self::PriceWithInvoice => 'Precio con factura',
            self::PriceWithoutInvoice => 'Precio sin factura',
            self::Cost => 'Costo',
        };
    }
}
