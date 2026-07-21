<?php

namespace App\Services;

use App\Repositories\Contracts\ReportRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private readonly ReportRepository $repository) {}

    public function query(string $type, array $filters = []): Builder
    {
        return match ($type) {
            'sales' => $this->repository->salesReport($filters),
            'purchases' => $this->repository->purchasesReport($filters),
            'inventory' => $this->repository->inventoryReport($filters),
            'kardex' => $this->repository->kardexReport($filters),
            'customers' => $this->repository->customersReport($filters),
            'suppliers' => $this->repository->suppliersReport($filters),
            'cash' => $this->repository->cashReport($filters),
            'credits' => $this->repository->creditsReport($filters),
            'best-selling' => $this->repository->bestSellingReport($filters),
            'low-stock' => $this->repository->lowStockReport($filters),
            default => $this->repository->salesReport($filters),
        };
    }

    public function title(string $type): string
    {
        return [
            'sales' => 'Reporte de ventas',
            'purchases' => 'Reporte de compras',
            'inventory' => 'Reporte de inventario',
            'kardex' => 'Reporte de kardex',
            'customers' => 'Reporte de clientes',
            'suppliers' => 'Reporte de proveedores',
            'cash' => 'Reporte de caja',
            'credits' => 'Reporte de créditos',
            'best-selling' => 'Productos más vendidos',
            'low-stock' => 'Productos con poco stock',
        ][$type] ?? 'Reporte';
    }

    public function headings(string $type): array
    {
        return match ($type) {
            'sales' => ['Código', 'Fecha', 'Cliente', 'Estado', 'Total'],
            'purchases' => ['Código', 'Fecha', 'Proveedor', 'Estado', 'Total'],
            'inventory', 'low-stock' => ['Producto', 'Ubicación', 'Cantidad', 'Mínimo'],
            'kardex' => ['Fecha', 'Producto', 'Ubicación', 'Tipo', 'Dirección', 'Cantidad', 'Saldo'],
            'customers' => ['Nombre', 'Documento', 'Ventas', 'Créditos', 'Saldo'],
            'suppliers' => ['Proveedor', 'Documento', 'Compras', 'Estado'],
            'cash' => ['Fecha', 'Tipo', 'Método', 'Cuenta', 'Monto', 'Descripción'],
            'credits' => ['Cliente', 'Venta', 'Estado', 'Original', 'Pagado', 'Saldo', 'Vencimiento'],
            'best-selling' => ['Producto', 'Cantidad', 'Importe'],
            default => [],
        };
    }

    public function rows(string $type, Collection $items): array
    {
        return $items->map(function ($item) use ($type): array {
            return match ($type) {
                'sales' => [$item->code, $item->created_at?->format('d/m/Y'), $item->customer?->name ?: 'Ocasional', $item->status->value, number_format((float) $item->total, 2)],
                'purchases' => [$item->code, $item->created_at?->format('d/m/Y'), $item->supplier?->name, $item->status->value, number_format((float) $item->total, 2)],
                'inventory', 'low-stock' => [$item->product?->name, $item->location?->name, $item->quantity, $item->product?->min_stock],
                'kardex' => [$item->created_at?->format('d/m/Y H:i'), $item->product?->name, $item->location?->name, $item->type->value, $item->direction->value, $item->quantity, $item->balance_after],
                'customers' => [$item->name, $item->document_number ?? '—', $item->sales_count, $item->credits_count, number_format((float) ($item->credits_sum_balance ?? 0), 2)],
                'suppliers' => [$item->name, $item->document_number ?? '—', $item->purchases_count, $item->is_active ? 'Activo' : 'Inactivo'],
                'cash' => [$item->created_at?->format('d/m/Y H:i'), $item->type->value, $item->method->value, $item->paymentAccount?->name ?: '—', number_format((float) $item->amount, 2), $item->description ?? '—'],
                'credits' => [$item->customer?->name, $item->sale?->code ?: '—', $item->status->value, number_format((float) $item->original_amount, 2), number_format((float) $item->paid_amount, 2), number_format((float) $item->balance, 2), $item->due_date?->format('d/m/Y') ?: '—'],
                'best-selling' => [$item->product?->name, $item->quantity, number_format((float) $item->amount, 2)],
                default => [],
            };
        })->all();
    }
}
