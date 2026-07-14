<?php

namespace App\Http\Requests;

use App\Models\Quotation;

class StoreQuotationRequest extends BaseCrudRequest
{
    protected string $modelClass = Quotation::class;

    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'exists:customers,id'], 'with_invoice' => ['required', 'boolean'], 'valid_until' => ['nullable', 'date'], 'subtotal' => ['required', 'numeric', 'decimal:0,2'], 'total' => ['required', 'numeric', 'decimal:0,2'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.presentation_id' => ['nullable', 'exists:presentations,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'items.*.unit_price' => ['required', 'numeric', 'decimal:0,2'], 'items.*.subtotal' => ['required', 'numeric', 'decimal:0,2']];
    }
}
