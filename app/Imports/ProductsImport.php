<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];

    public int $processed = 0;

    public int $created = 0;

    public int $updated = 0;

    public function __construct(private readonly ProductService $products) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $offset => $row) {
            $line = $offset + 2;
            $data = $row->toArray();
            $validator = Validator::make($data, ['name' => ['required', 'string'], 'category' => ['required', 'string'], 'brand' => ['required', 'string'], 'unit' => ['required', 'string'], 'presentation_name' => ['required', 'string'], 'equivalence' => ['required', 'numeric', 'gt:0'], 'price_without_invoice' => ['required', 'numeric', 'min:0'], 'price_with_invoice' => ['required', 'numeric', 'min:0']]);
            if ($validator->fails()) {
                $this->errors[$line] = $validator->errors()->all();

                continue;
            }
            try {
                $category = Category::firstOrCreate(['name' => $data['category']], ['is_active' => true]);
                $brand = Brand::firstOrCreate(['name' => $data['brand']], ['is_active' => true]);
                $unit = Unit::firstOrCreate(['abbreviation' => $data['unit']], ['name' => $data['unit'], 'is_active' => true]);
                $product = Product::query()->when($data['code'] ?? null, fn ($query) => $query->where('code', $data['code']))->when(! ($data['code'] ?? null) && ($data['barcode'] ?? null), fn ($query) => $query->where('barcode', $data['barcode']))->first();
                $payload = ['code' => $data['code'] ?? null, 'barcode' => $data['barcode'] ?? null, 'name' => $data['name'], 'description' => $data['description'] ?? null, 'category_id' => $category->id, 'brand_id' => $brand->id, 'unit_id' => $unit->id, 'min_stock' => $data['min_stock'] ?? 0, 'cost' => $data['cost'] ?? 0, 'is_active' => (bool) ($data['is_active'] ?? true), 'presentations' => [['name' => $data['presentation_name'], 'equivalence' => $data['equivalence'], 'price_without_invoice' => $data['price_without_invoice'], 'price_with_invoice' => $data['price_with_invoice'], 'is_active' => true, 'sort_order' => 0]]];
                $product ? $this->products->update($product, $payload) : $this->products->create($payload);
                $this->processed++;
                $product ? $this->updated++ : $this->created++;
            } catch (\Throwable $exception) {
                $this->errors[$line] = [$exception->getMessage()];
            }
        }
    }
}
