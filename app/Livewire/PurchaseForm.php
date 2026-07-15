<?php

namespace App\Livewire;

use App\Domain\Enums\PaymentType;
use App\Domain\Enums\PurchaseStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ProductService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PurchaseForm extends Component
{
    public ?Purchase $purchase = null;

    public ?int $supplierId = null;

    public string $paymentType = 'cash';

    public ?string $expectedDate = null;

    public ?string $notes = null;

    public array $items = [];

    public array $supplierHints = [];

    public string $productSearch = '';

    public bool $showProductModal = false;

    public string $quickName = '';

    public ?int $quickCategoryId = null;

    public ?int $quickBrandId = null;

    public ?int $quickUnitId = null;

    public function mount(?Purchase $purchase = null): void
    {
        $this->purchase = $purchase?->exists ? $purchase->load('items') : null;
        Gate::authorize($this->purchase ? 'update' : 'create', $this->purchase ?: Purchase::class);
        if ($this->purchase) {
            abort_if(in_array(PurchaseStatus::from((string) $this->purchase->getRawOriginal('status'))->value, ['completed', 'cancelled'], true), 403);
            $this->supplierId = $this->purchase->supplier_id;
            $this->paymentType = (string) $this->purchase->getRawOriginal('payment_type');
            $this->expectedDate = $this->purchase->expected_date ? Carbon::parse((string) $this->purchase->expected_date)->format('Y-m-d') : null;
            $this->notes = $this->purchase->notes;
            $this->items = $this->purchase->items->map(fn ($item): array => ['id' => $item->id, 'product_id' => $item->product_id, 'quantity_ordered' => (string) $item->quantity_ordered, 'quantity_received' => (string) $item->quantity_received, 'unit_cost' => (string) $item->unit_cost, 'subtotal' => (string) $item->subtotal])->all();
        }
    }

    public function addItem(int $productId): void
    {
        $product = Product::with('presentations')->findOrFail($productId);
        $this->items[] = ['product_id' => $product->id, 'quantity_ordered' => '1', 'quantity_received' => '0', 'unit_cost' => (string) $product->cost, 'subtotal' => (string) $product->cost];
        $supplier = app(PurchaseService::class)->latestSupplierForProduct($productId);
        $this->supplierHints[$productId] = $supplier?->name;
        $this->productSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['subtotal'] = number_format((float) $item['quantity_ordered'] * (float) $item['unit_cost'], 2, '.', '');
        }
    }

    public function save(PurchaseService $service): void
    {
        $this->validate(['supplierId' => ['required', 'exists:suppliers,id'], 'paymentType' => ['required', 'in:cash,credit,mixed'], 'expectedDate' => ['nullable', 'date'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0']]);
        $items = array_map(fn (array $item): array => ['id' => $item['id'] ?? null, 'product_id' => $item['product_id'], 'quantity_ordered' => $item['quantity_ordered'], 'quantity_received' => $item['quantity_received'] ?? 0, 'unit_cost' => $item['unit_cost'], 'subtotal' => (float) $item['quantity_ordered'] * (float) $item['unit_cost']], $this->items);
        $data = ['supplier_id' => $this->supplierId, 'payment_type' => $this->paymentType, 'expected_date' => $this->expectedDate, 'notes' => $this->notes, 'total' => collect($items)->sum('subtotal'), 'items' => $items];
        if ($this->purchase) {
            Gate::authorize('update', $this->purchase);
            $saved = $service->update($this->purchase, $data);
        } else {
            $saved = $service->create($data);
        }
        session()->flash('success', 'Compra guardada correctamente.');
        $this->redirectRoute('purchases.show', $saved, navigate: true);
    }

    public function openQuickProduct(): void
    {
        Gate::authorize('create', Product::class);
        $this->showProductModal = true;
        $this->dispatch('open-modal', 'quick-product');
    }

    public function createQuickProduct(ProductService $service): void
    {
        $this->validate(['quickName' => ['required', 'string', 'max:255'], 'quickCategoryId' => ['required', 'exists:categories,id'], 'quickBrandId' => ['required', 'exists:brands,id'], 'quickUnitId' => ['required', 'exists:units,id']]);
        $product = $service->create(['name' => $this->quickName, 'category_id' => $this->quickCategoryId, 'brand_id' => $this->quickBrandId, 'unit_id' => $this->quickUnitId, 'is_active' => true, 'presentations' => [['name' => 'Unidad', 'equivalence' => 1, 'price_without_invoice' => 0, 'price_with_invoice' => 0, 'is_active' => true, 'sort_order' => 0]]]);
        $this->addItem($product->id);
        $this->showProductModal = false;
        $this->dispatch('close-modal', 'quick-product');
    }

    public function render()
    {
        $productIds = collect($this->items)->pluck('product_id')->filter()->unique();
        $selectedProducts = Product::query()->whereIn('id', $productIds)->get(['id', 'name']);

        return view('livewire.purchase-form', ['suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']), 'products' => Product::query()->active()->when($this->productSearch !== '', fn ($query) => $query->search($this->productSearch))->orderBy('name')->limit(20)->get(['id', 'name', 'code', 'cost']), 'selectedProducts' => $selectedProducts->keyBy('id'), 'categories' => Category::query()->active()->orderBy('name')->get(['id', 'name']), 'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']), 'units' => Unit::query()->active()->orderBy('name')->get(['id', 'name']), 'paymentTypes' => PaymentType::cases()])->layout('layouts.app');
    }
}
