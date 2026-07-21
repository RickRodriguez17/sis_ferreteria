<?php

namespace App\Livewire;

use App\Exports\GenericReportExport;
use App\Livewire\Traits\WithTableState;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ReportService;
use Livewire\Component;

class ReportIndex extends Component
{
    use WithTableState;

    public string $type = 'sales';

    public string $from = '';

    public string $to = '';

    public string $status = '';

    public string $customerId = '';

    public string $supplierId = '';

    public string $productId = '';

    public string $locationId = '';

    public string $method = '';

    public function mount(?string $type = null): void
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);
        if ($type && array_key_exists($type, $this->reportTypes())) {
            $this->type = $type;
        }
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function exportExcel(ReportService $service)
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);
        $items = $service->query($this->type, $this->filters())->get();

        return (new GenericReportExport($service->headings($this->type), $service->rows($this->type, $items)))->download($this->type.'.xlsx');
    }

    public function printUrl(): string
    {
        return route('reports.pdf', array_merge(['type' => $this->type], $this->filters(), ['print' => 1]));
    }

    public function pdfUrl(): string
    {
        return route('reports.pdf', array_merge(['type' => $this->type], $this->filters()));
    }

    private function filters(): array
    {
        return array_filter(['from' => $this->from, 'to' => $this->to, 'search' => $this->search, 'status' => $this->status, 'customer_id' => $this->customerId, 'supplier_id' => $this->supplierId, 'product_id' => $this->productId, 'location_id' => $this->locationId, 'method' => $this->method]);
    }

    private function reportTypes(): array
    {
        return ['sales' => 'Ventas', 'purchases' => 'Compras', 'inventory' => 'Inventario', 'kardex' => 'Kardex', 'customers' => 'Clientes', 'suppliers' => 'Proveedores', 'cash' => 'Caja', 'credits' => 'Créditos', 'best-selling' => 'Más vendidos', 'low-stock' => 'Poco stock'];
    }

    public function render(ReportService $service)
    {
        $items = $service->query($this->type, $this->filters())->paginate($this->perPage);

        return view('livewire.report-index', [
            'items' => $items,
            'reportTypes' => $this->reportTypes(),
            'title' => $service->title($this->type),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
