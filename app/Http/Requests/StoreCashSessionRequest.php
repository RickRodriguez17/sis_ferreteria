<?php

namespace App\Http\Requests;

use App\Models\CashSession;

class StoreCashSessionRequest extends BaseCrudRequest
{
    protected string $modelClass = CashSession::class;

    public function rules(): array
    {
        return ['cash_register_id' => ['required', 'exists:cash_registers,id'], 'opening_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2']];
    }
}
