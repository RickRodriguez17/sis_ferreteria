<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTemplateExport
{
    public function headings(): array
    {
        return ['code', 'barcode', 'name', 'description', 'category', 'brand', 'unit', 'min_stock', 'cost', 'is_active', 'presentation_name', 'equivalence', 'price_without_invoice', 'price_with_invoice'];
    }

    public function array(): array
    {
        return [['', '', 'Martillo demo', 'Descripción', 'Herramientas', 'Truper', 'und', 2, 10, 1, 'Unidad', 1, 15, 17]];
    }

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([$this->headings(), ...$this->array()]);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
