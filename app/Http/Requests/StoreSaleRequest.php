<?php

namespace App\Http\Requests;

use App\Domain\Enums\PaymentType;
use App\Models\Sale;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends BaseCrudRequest
{
    protected string $modelClass = Sale::class;

    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'exists:customers,id'], 'quotation_id' => ['nullable', 'exists:quotations,id'], 'with_invoice' => ['required', 'boolean'], 'payment_type' => ['required', Rule::enum(PaymentType::class)], 'subtotal' => ['required', 'numeric', 'decimal:0,2'], 'discount' => ['required', 'numeric', 'decimal:0,2'], 'total' => ['required', 'numeric', 'decimal:0,2'], 'location_id' => ['required', 'exists:locations,id'], 'cash_session_id' => ['nullable', 'exists:cash_sessions,id'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.presentation_id' => ['nullable', 'exists:presentations,id'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,4'], 'items.*.unit_price' => ['nullable', 'numeric', 'decimal:0,2'], 'items.*.subtotal' => ['nullable', 'numeric', 'decimal:0,2'], 'items.*.price_pending' => ['boolean']];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                $pending = (bool) ($item['price_pending'] ?? false);
                if ($pending && $this->input('payment_type') !== PaymentType::Credit->value) {
                    $validator->errors()->add("items.{$index}.price_pending", 'El precio pendiente solo está permitido en ventas a crédito.');
                }
                if (! $pending && ! isset($item['unit_price'])) {
                    $validator->errors()->add("items.{$index}.unit_price", 'El precio es obligatorio cuando la línea no está pendiente.');
                }
                if ($pending && ((float) ($item['unit_price'] ?? 0) !== 0.0 || (float) ($item['subtotal'] ?? 0) !== 0.0)) {
                    $validator->errors()->add("items.{$index}.price_pending", 'Una línea con precio pendiente debe guardar precio y subtotal en cero.');
                }
            }
        }];
    }
}
