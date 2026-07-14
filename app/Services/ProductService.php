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

    public function update(Product $product, array $data, bool $preservePresentationPrices = false): Product
    {
        return DB::transaction(function () use ($product, $data, $preservePresentationPrices): Product {
            $product->update($data);
            $this->syncRelations($product, $data, $preservePresentationPrices);

            return $product->fresh(['presentations', 'attributeValues', 'images']);
        });
    }

    private function syncRelations(Product $product, array $data, bool $preservePresentationPrices = false): void
    {
        if (array_key_exists('attribute_value_ids', $data)) {
            $product->attributeValues()->sync($data['attribute_value_ids'] ?? []);
        }

        if (array_key_exists('related_product_ids', $data)) {
            $product->related()->sync($data['related_product_ids'] ?? []);
        }

        if (array_key_exists('presentations', $data)) {
            $presentations = collect($data['presentations'] ?? []);
            $existingIds = $presentations->pluck('id')->filter()->all();
            $product->presentations()->whereNotIn('id', $existingIds ?: [0])->delete();
            $presentations->each(function (array $presentation) use ($product, $preservePresentationPrices): void {
                $id = $presentation['id'] ?? null;
                unset($presentation['id']);
                if ($preservePresentationPrices && $id) {
                    unset($presentation['price_without_invoice'], $presentation['price_with_invoice']);
                }
                if ($id) {
                    $product->presentations()->whereKey($id)->update($presentation);
                } else {
                    $product->presentations()->create($presentation);
                }
            });
        }

        if (array_key_exists('images', $data)) {
            $product->images()->delete();
            $product->images()->createMany($data['images'] ?? []);
        }
    }
}
