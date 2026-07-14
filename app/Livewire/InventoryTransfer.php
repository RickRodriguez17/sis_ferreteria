<?php

namespace App\Livewire;

use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Services\InventoryService;
use Livewire\Component;

class InventoryTransfer extends Component
{
    public ?int $productId = null;

    public ?int $fromLocationId = null;

    public ?int $toLocationId = null;

    public string $quantity = '';

    public string $error = '';

    public function save(InventoryService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.transfer'), 403);
        $this->validate(['productId' => ['required', 'exists:products,id'], 'fromLocationId' => ['required', 'exists:locations,id', 'different:toLocationId'], 'toLocationId' => ['required', 'exists:locations,id'], 'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4']]);
        try {
            $service->transfer(Product::findOrFail($this->productId), Location::findOrFail($this->fromLocationId), Location::findOrFail($this->toLocationId), $this->quantity);
            $this->reset(['productId', 'fromLocationId', 'toLocationId', 'quantity', 'error']);
            session()->flash('success', 'Transferencia registrada.');
        } catch (InsufficientStockException) {
            $this->error = 'No hay stock suficiente en la ubicación de origen.';
        }
    }

    public function render()
    {
        return view('livewire.inventory-transfer', ['products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code']), 'locations' => Location::query()->active()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
