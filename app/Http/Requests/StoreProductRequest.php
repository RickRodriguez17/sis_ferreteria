<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseCrudRequest
{
    protected string $modelClass = Product::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:100', Rule::unique('products')], 'barcode' => ['nullable', 'string', Rule::unique('products')], 'category_id' => ['required', 'exists:categories,id'], 'brand_id' => ['required', 'exists:brands,id'], 'unit_id' => ['required', 'exists:units,id'], 'min_stock' => ['nullable', 'numeric', 'decimal:0,4'], 'cost' => ['nullable', 'numeric', 'decimal:0,4'], 'is_active' => ['boolean']];
    }
}
