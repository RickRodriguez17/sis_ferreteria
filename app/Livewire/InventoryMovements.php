<?php

namespace App\Livewire;

use App\Domain\Enums\MovementDirection;
use App\Domain\Enums\StockMovementType;
use App\Livewire\Traits\WithTableState;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Component;

class InventoryMovements extends Component
{
    use WithTableState;

    public string $locationId = '';

    public string $productId = '';

    public string $direction = '';

    public string $type = '';

    public function render()
    {
        $productIds = $this->search !== '' ? Product::query()->search($this->search)->select('id') : null;
        $movements = StockMovement::query()->with(['product:id,name,code', 'location:id,name', 'creator:id,name'])->when($productIds, fn ($query) => $query->whereIn('product_id', $productIds))->when($this->locationId !== '', fn ($query) => $query->where('location_id', $this->locationId))->when($this->productId !== '', fn ($query) => $query->where('product_id', $this->productId))->when($this->direction !== '', fn ($query) => $query->where('direction', $this->direction))->when($this->type !== '', fn ($query) => $query->where('type', $this->type))->latest()->paginate($this->perPage);

        return view('livewire.inventory-movements', ['movements' => $movements, 'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'code']), 'locations' => Location::query()->orderBy('name')->get(['id', 'name']), 'directions' => MovementDirection::cases(), 'types' => StockMovementType::cases()])->layout('layouts.app');
    }
}
