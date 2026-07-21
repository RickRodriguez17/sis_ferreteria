<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportPdfController extends Controller
{
    public function __invoke(Request $request, string $type, ReportService $service): Response
    {
        abort_unless($request->user()?->can('reports.view'), 403);
        $filters = $request->only(['from', 'to', 'search', 'status', 'customer_id', 'supplier_id', 'product_id', 'location_id', 'method']);
        $items = $service->query($type, array_filter($filters))->get();

        $data = [
            'title' => $service->title($type),
            'headings' => $service->headings($type),
            'rows' => $service->rows($type, $items),
        ];
        if ($request->boolean('print')) {
            return response()->view('pdf.report', $data);
        }

        return Pdf::loadView('pdf.report', $data)->setPaper('a4', 'landscape')->download("reporte-{$type}.pdf");
    }
}
