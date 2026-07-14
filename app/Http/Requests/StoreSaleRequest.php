<?php

namespace App\Http\Requests;

use App\Domain\Enums\PaymentType;
use App\Models\Sale;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends BaseCrudRequest
{
    protected string $modelClass = Sale::class;

    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'exists:customers,id'], 'quotation_id' => ['nullable', 'exists:quotations,id'], 'with_invoice' => ['required', 'boolean'], 'payment_type' => ['required', Rule::enum(PaymentType::class)], 'subtotal' => ['required', 'numeric', 'decimal:0,2'], 'discount' => ['required', 'numeric', 'decimal:0,2'], 'total' => ['required', 'numeric', 'decimal:0,2'], 'location_id' => ['required', 'exists:locations,id'], 'cash_session_id' => ['nullable', 'exists:cash_sessions,id'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.presentation_id' => ['nullable', 'exists:presentations,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'items.*.unit_price' => ['required', 'numeric', 'decimal:0,2'], 'items.*.subtotal' => ['required', 'numeric', 'decimal:0,2'], 'items.*.price_pending' => ['boolean']];
    }
}
