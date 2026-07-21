<?php

namespace Tests\Feature;

use App\Exports\GenericReportExport;
use App\Livewire\ReportIndex;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'admin@construir.local')->firstOrFail());
    }

    public function test_dashboard_renders_executive_kpis(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel ejecutivo')
            ->assertSee('Ventas del día')
            ->assertSee('Créditos pendientes');
    }

    public function test_reports_render_with_filters(): void
    {
        Livewire::test(ReportIndex::class, ['type' => 'sales'])
            ->set('search', 'DEMO')
            ->assertSee('Reporte de ventas');

        Livewire::test(ReportIndex::class, ['type' => 'inventory'])
            ->set('search', 'Producto')
            ->assertSee('Reporte de inventario');
    }

    public function test_report_excel_and_pdf_respond_successfully(): void
    {
        $service = app(ReportService::class);
        $items = $service->query('sales', [])->get();
        $excel = (new GenericReportExport($service->headings('sales'), $service->rows('sales', $items)))->download('ventas.xlsx');
        $this->assertSame(200, $excel->getStatusCode());
        $this->assertStringContainsString('spreadsheetml', (string) $excel->headers->get('Content-Type'));

        $pdf = $this->get(route('reports.pdf', ['type' => 'sales']));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('Content-Type'));
    }
}
