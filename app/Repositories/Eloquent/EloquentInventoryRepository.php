<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentInventoryRepository implements InventoryRepository
{
    public function byLocation(?int $locationId = null): Collection
    {
        return Inventory::query()->with(['product', 'location'])->when($locationId, fn ($query) => $query->where('location_id', $locationId))->get();
    }

    public function belowMinimum(): Collection
    {
        return Inventory::query()->with('product')->whereHas('product', fn ($query) => $query->whereColumn('inventory.quantity', '<', 'products.min_stock'))->get();
    }
}
