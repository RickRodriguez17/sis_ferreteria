<?php

namespace App\Http\Requests;

use App\Models\Credit;

class StoreCreditRequest extends BaseCrudRequest
{
    protected string $modelClass = Credit::class;

    public function rules(): array
    {
        return ['customer_id' => ['required', 'exists:customers,id'], 'sale_id' => ['required', 'exists:sales,id', 'unique:credits,sale_id'], 'original_amount' => ['required', 'numeric', 'decimal:0,2'], 'paid_amount' => ['nullable', 'numeric', 'decimal:0,2'], 'balance' => ['required', 'numeric', 'decimal:0,2'], 'due_date' => ['nullable', 'date']];
    }
}
