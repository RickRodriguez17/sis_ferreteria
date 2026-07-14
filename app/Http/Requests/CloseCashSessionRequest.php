<?php

namespace App\Http\Requests;

use App\Models\CashSession;

class CloseCashSessionRequest extends BaseCrudRequest
{
    protected string $modelClass = CashSession::class;

    public function rules(): array
    {
        return ['counted_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2']];
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('close', $this->route('cash_session'));
    }
}
