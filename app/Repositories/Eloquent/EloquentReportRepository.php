<?php

namespace App\Repositories\Eloquent;

use App\Models\CashMovement;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Repositories\Contracts\ReportRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentReportRepository implements ReportRepository
{
    public function salesByRange(string $from, string $to): Collection
    {
        return Sale::query()->whereBetween('created_at', [$from, $to])->selectRaw('DATE(created_at) as day, COUNT(*) as total_sales, SUM(total) as amount')->groupBy('day')->orderBy('day')->get();
    }

    public function bestSelling(string $from, string $to): Collection
    {
        return SaleItem::query()->whereHas('sale', fn ($query) => $query->whereBetween('created_at', [$from, $to]))->selectRaw('product_id, SUM(base_quantity) as quantity')->groupBy('product_id')->orderByDesc('quantity')->with('product')->get();
    }

    public function creditsDue(string $until): Collection
    {
        return Credit::query()->where('balance', '>', 0)->whereDate('due_date', '<=', $until)->with(['customer', 'sale'])->get();
    }

    public function salesReport(array $filters = []): Builder
    {
        return Sale::query()->with(['customer', 'location', 'creator'])
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['customer_id'] ?? null, fn ($q, $value) => $q->where('customer_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($inner) => $inner->where('code', 'like', "%{$value}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$value}%"))))
            ->latest();
    }

    public function purchasesReport(array $filters = []): Builder
    {
        return Purchase::query()->with(['supplier', 'creator'])
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['supplier_id'] ?? null, fn ($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('code', 'like', "%{$value}%"))
            ->latest();
    }

    public function inventoryReport(array $filters = []): Builder
    {
        return Inventory::query()->with(['product.category', 'product.brand', 'location'])
            ->when($filters['location_id'] ?? null, fn ($q, $value) => $q->where('location_id', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->whereHas('product', fn ($product) => $product->where(fn ($inner) => $inner->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%")->orWhere('barcode', 'like', "%{$value}%"))))
            ->orderBy('product_id');
    }

    public function kardexReport(array $filters = []): Builder
    {
        return StockMovement::query()->with(['product', 'location', 'creator', 'reference'])
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['location_id'] ?? null, fn ($q, $value) => $q->where('location_id', $value))
            ->when($filters['product_id'] ?? null, fn ($q, $value) => $q->where('product_id', $value))
            ->latest();
    }

    public function customersReport(array $filters = []): Builder
    {
        return Customer::query()->withCount(['sales', 'credits'])->withSum('credits', 'balance')
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($inner) => $inner->where('name', 'like', "%{$value}%")->orWhere('document_number', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%")))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('is_active', $value === 'active'))
            ->latest();
    }

    public function suppliersReport(array $filters = []): Builder
    {
        return Supplier::query()->withCount('purchases')
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($inner) => $inner->where('name', 'like', "%{$value}%")->orWhere('document_number', 'like', "%{$value}%")))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('is_active', $value === 'active'))
            ->latest();
    }

    public function cashReport(array $filters = []): Builder
    {
        return CashMovement::query()->with(['cashSession.register', 'paymentAccount', 'creator', 'reference'])
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['method'] ?? null, fn ($q, $value) => $q->where('method', $value))
            ->latest();
    }

    public function creditsReport(array $filters = []): Builder
    {
        return Credit::query()->with(['customer', 'sale'])
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->whereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$value}%")))
            ->latest();
    }

    public function bestSellingReport(array $filters = []): Builder
    {
        return SaleItem::query()->with('product')
            ->selectRaw('product_id, SUM(quantity) as quantity, SUM(subtotal) as amount')
            ->whereHas('sale', function ($q) use ($filters): void {
                $q->where('status', 'completed')
                    ->when($filters['from'] ?? null, fn ($inner, $value) => $inner->whereDate('created_at', '>=', $value))
                    ->when($filters['to'] ?? null, fn ($inner, $value) => $inner->whereDate('created_at', '<=', $value));
            })->groupBy('product_id')->orderByDesc('quantity');
    }

    public function lowStockReport(array $filters = []): Builder
    {
        return Inventory::query()->with(['product', 'location'])
            ->whereHas('product', fn ($q) => $q->whereColumn('inventory.quantity', '<=', 'products.min_stock'))
            ->when($filters['location_id'] ?? null, fn ($q, $value) => $q->where('location_id', $value))
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->whereHas('product', fn ($product) => $product->where(fn ($inner) => $inner->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%")->orWhere('barcode', 'like', "%{$value}%"))))
            ->orderBy('quantity');
    }
}
