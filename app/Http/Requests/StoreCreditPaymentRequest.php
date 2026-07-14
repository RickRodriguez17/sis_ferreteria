<?php

namespace App\Http\Requests;

use App\Domain\Enums\PaymentMethod;
use App\Models\CreditPayment;
use Illuminate\Validation\Rule;

class StoreCreditPaymentRequest extends BaseCrudRequest
{
    protected string $modelClass = CreditPayment::class;

    public function rules(): array
    {
        return ['credit_id' => ['required', 'exists:credits,id'], 'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'], 'method' => ['required', Rule::enum(PaymentMethod::class)], 'cash_session_id' => ['nullable', 'exists:cash_sessions,id'], 'paid_at' => ['required', 'date'], 'notes' => ['nullable', 'string']];
    }
}
