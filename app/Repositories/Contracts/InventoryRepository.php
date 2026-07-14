<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface InventoryRepository
{
    public function byLocation(?int $locationId = null): Collection;

    public function belowMinimum(): Collection;
}
