<?php

namespace App\Http\Requests;

use App\Domain\Enums\PaymentType;
use App\Domain\Enums\PurchaseStatus;
use App\Models\Purchase;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends BaseCrudRequest
{
    protected string $modelClass = Purchase::class;

    public function rules(): array
    {
        return ['supplier_id' => ['required', 'exists:suppliers,id'], 'status' => ['nullable', Rule::enum(PurchaseStatus::class)], 'payment_type' => ['required', Rule::enum(PaymentType::class)], 'total' => ['required', 'numeric', 'decimal:0,2'], 'expected_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,4'], 'items.*.subtotal' => ['required', 'numeric', 'decimal:0,2']];
    }
}
