<?php

namespace App\Livewire;

use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Services\InventoryService;
use Livewire\Component;

class InventoryAdjust extends Component
{
    public ?int $productId = null;

    public ?int $locationId = null;

    public string $delta = '';

    public string $notes = '';

    public string $error = '';

    public function save(InventoryService $service): void
    {
        abort_unless(auth()->user()?->can('inventory.adjust'), 403);
        $this->validate(['productId' => ['required', 'exists:products,id'], 'locationId' => ['required', 'exists:locations,id'], 'delta' => ['required', 'numeric', 'not_in:0', 'decimal:0,4'], 'notes' => ['required', 'string', 'max:500']]);
        try {
            $service->adjust(Product::findOrFail($this->productId), Location::findOrFail($this->locationId), $this->delta, $this->notes);
            $this->reset(['productId', 'locationId', 'delta', 'notes', 'error']);
            session()->flash('success', 'Ajuste registrado.');
        } catch (InsufficientStockException) {
            $this->error = 'El ajuste dejaría el stock por debajo de cero.';
        }
    }

    public function render()
    {
        return view('livewire.inventory-adjust', ['products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code']), 'locations' => Location::query()->active()->orderBy('name')->get(['id', 'name'])])->layout('layouts.app');
    }
}
