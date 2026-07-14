<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\KardexRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentKardexRepository implements KardexRepository
{
    public function forProduct(Product $product, ?int $locationId = null, ?string $from = null, ?string $to = null): Collection
    {
        return StockMovement::query()->where('product_id', $product->id)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->with(['location', 'reference'])->orderBy('created_at')->get();
    }
}
