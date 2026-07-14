<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface KardexRepository
{
    public function forProduct(Product $product, ?int $locationId = null, ?string $from = null, ?string $to = null): Collection;
}
