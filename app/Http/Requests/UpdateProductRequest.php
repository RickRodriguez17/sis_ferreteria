<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $product = $this->route('product');

        return array_merge(parent::rules(), ['code' => ['sometimes', 'string', Rule::unique('products')->ignore($product)], 'barcode' => ['nullable', 'string', Rule::unique('products')->ignore($product)]]);
    }
}
