<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentPdfController extends Controller
{
    public function sale(Sale $sale): Response
    {
        abort_unless(auth()->user()?->can('view', $sale), 403);
        $sale->load(['customer', 'items.product', 'items.presentation', 'credit']);

        return Pdf::loadView('pdf.sale', compact('sale'))
            ->setPaper('a4')
            ->download("venta-{$sale->code}.pdf");
    }

    public function quotation(Quotation $quotation): Response
    {
        abort_unless(auth()->user()?->can('view', $quotation), 403);
        $quotation->load(['customer', 'items.product', 'items.presentation']);

        return Pdf::loadView('pdf.quotation', compact('quotation'))
            ->setPaper('a4')
            ->download("cotizacion-{$quotation->code}.pdf");
    }
}
