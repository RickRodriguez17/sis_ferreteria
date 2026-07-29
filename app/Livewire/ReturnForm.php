<?php

namespace App\Livewire;

use App\Exceptions\InsufficientStockException;
use App\Models\CustomerReturn;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\SupplierReturn;
use App\Services\ReturnService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class ReturnForm extends Component
{
    public string $type = 'customer';

    public string $sourceId = '';

    public string $notes = '';

    /** @var array<int, array<string, mixed>> */
    public array $returnItems = [];

    public function mount(string $type): void
    {
        abort_unless(in_array($type, ['customer', 'supplier'], true), 404);
        $this->type = $type;
        Gate::authorize('create', $this->policyModel());
    }

    public function updatedSourceId(): void
    {
        $this->loadSource();
    }

    public function loadSource(): void
    {
        $this->returnItems = [];
        if ($this->sourceId === '') {
            return;
        }

        if ($this->type === 'customer') {
            $source = Sale::query()->with(['items.product', 'items.presentation', 'customer'])->where('status', 'completed')->findOrFail($this->sourceId);
            foreach ($source->items as $item) {
                $returned = (float) CustomerReturn::query()
                    ->where('sale_id', $source->id)
                    ->join('customer_return_items', 'customer_returns.id', '=', 'customer_return_items.customer_return_id')
                    ->where('customer_return_items.sale_item_id', $item->id)
                    ->sum('customer_return_items.quantity_base');
                $available = max(0, (float) $item->base_quantity - $returned);
                if ($available > 0) {
                    $this->returnItems[] = ['line_id' => $item->id, 'product' => $item->product->name, 'available' => $available, 'unit_price' => (float) $item->unit_price, 'quantity' => ''];
                }
            }
        } else {
            $source = Reception::query()->with(['items.product', 'purchase.supplier'])->findOrFail($this->sourceId);
            foreach ($source->items as $item) {
                $returned = (float) SupplierReturn::query()
                    ->where('reception_id', $source->id)
                    ->join('supplier_return_items', 'supplier_returns.id', '=', 'supplier_return_items.supplier_return_id')
                    ->where('supplier_return_items.reception_item_id', $item->id)
                    ->sum('supplier_return_items.quantity_base');
                $available = max(0, (float) $item->quantity - $returned);
                if ($available > 0) {
                    $this->returnItems[] = ['line_id' => $item->id, 'product' => $item->product->name, 'available' => $available, 'unit_price' => (float) $item->unit_cost, 'quantity' => ''];
                }
            }
        }
    }

    public function save(ReturnService $service): void
    {
        Gate::authorize('create', $this->policyModel());
        $this->validate([
            'sourceId' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'returnItems.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ], [], ['sourceId' => $this->type === 'customer' ? 'venta' : 'recepción', 'returnItems.*.quantity' => 'cantidad']);

        $items = collect($this->returnItems)
            ->filter(fn (array $item): bool => (float) ($item['quantity'] ?? 0) > 0)
            ->map(fn (array $item): array => [
                ($this->type === 'customer' ? 'sale_item_id' : 'reception_item_id') => (int) $item['line_id'],
                'quantity' => $item['quantity'],
            ])
            ->values()
            ->all();
        if ($items === []) {
            $this->addError('returnItems', 'Selecciona al menos un producto y una cantidad mayor que cero.');

            return;
        }

        try {
            if ($this->type === 'customer') {
                $service->customer(Sale::findOrFail($this->sourceId), $items, $this->notes);
                $message = 'Devolución de cliente registrada correctamente.';
            } else {
                $service->supplier(Reception::findOrFail($this->sourceId), $items, $this->notes);
                $message = 'Devolución a proveedor registrada correctamente.';
            }
            $this->dispatch('toast', message: $message, type: 'success');
            $this->redirectRoute('returns.index', ['type' => $this->type], navigate: true);
        } catch (Throwable $exception) {
            $message = $exception instanceof InsufficientStockException
                ? 'No hay stock suficiente en la ubicación para completar la devolución a proveedor.'
                : $exception->getMessage();
            $this->addError('form', $message);
            $this->dispatch('alert', type: 'error', title: 'No fue posible registrar la devolución', message: $message);
        }
    }

    public function render()
    {
        $sources = $this->type === 'customer'
            ? Sale::query()->with('customer:id,name')->where('status', 'completed')->latest()->limit(100)->get(['id', 'code', 'customer_id', 'total', 'created_at'])
            : Reception::query()->with('purchase.supplier:id,name')->latest('received_at')->limit(100)->get(['id', 'code', 'purchase_id', 'received_at']);

        return view('livewire.return-form', compact('sources'))->layout('layouts.app');
    }

    private function policyModel(): string
    {
        return $this->type === 'customer' ? CustomerReturn::class : SupplierReturn::class;
    }
}
