<?php

namespace App\Http\Requests;

use App\Domain\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends BaseCrudRequest
{
    protected string $modelClass = Customer::class;

    public function rules(): array
    {
        return ['type' => ['required', Rule::enum(CustomerType::class)], 'name' => ['required', 'string'], 'document_type' => ['nullable', 'string'], 'document_number' => ['nullable', 'string'], 'phone' => ['nullable', 'string'], 'email' => ['nullable', 'email'], 'address' => ['nullable', 'string'], 'credit_limit' => ['nullable', 'numeric', 'decimal:0,2'], 'is_active' => ['boolean']];
    }
}
