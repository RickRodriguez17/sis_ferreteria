<?php

namespace App\Http\Requests;

use App\Models\Presentation;

class StorePresentationRequest extends BaseCrudRequest
{
    protected string $modelClass = Presentation::class;

    public function rules(): array
    {
        return ['product_id' => ['required', 'exists:products,id'], 'name' => ['required', 'string'], 'equivalence' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'price_without_invoice' => ['required', 'numeric', 'decimal:0,2'], 'price_with_invoice' => ['required', 'numeric', 'decimal:0,2'], 'is_active' => ['boolean'], 'sort_order' => ['integer', 'min:0']];
    }
}
