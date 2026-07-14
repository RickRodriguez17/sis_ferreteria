<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly CodeGenerator $codes) {}

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $data['code'] ??= $this->codes->product();
            $product = Product::create($data);
            $this->syncRelations($product, $data);

            return $product->fresh(['presentations', 'attributeValues', 'images']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update($data);
            $this->syncRelations($product, $data);

            return $product->fresh(['presentations', 'attributeValues', 'images']);
        });
    }

    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('attribute_value_ids', $data)) {
            $product->attributeValues()->sync($data['attribute_value_ids'] ?? []);
        }

        if (array_key_exists('presentations', $data)) {
            $product->presentations()->delete();
            $product->presentations()->createMany($data['presentations'] ?? []);
        }

        if (array_key_exists('images', $data)) {
            $product->images()->delete();
            $product->images()->createMany($data['images'] ?? []);
        }
    }
}
