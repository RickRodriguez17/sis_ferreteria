<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['code', 'barcode', 'name', 'description', 'category', 'brand', 'unit', 'min_stock', 'cost', 'is_active', 'presentation_name', 'equivalence', 'price_without_invoice', 'price_with_invoice'];
    }

    public function array(): array
    {
        return [['', '', 'Martillo demo', 'Descripción', 'Herramientas', 'Truper', 'und', 2, 10, 1, 'Unidad', 1, 15, 17]];
    }
}
