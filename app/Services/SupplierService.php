<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function create(array $data): Supplier
    {
        return DB::transaction(fn (): Supplier => Supplier::create($data));
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data): Supplier {
            $supplier->update($data);

            return $supplier->fresh();
        });
    }
}
