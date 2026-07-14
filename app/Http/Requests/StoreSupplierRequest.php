<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends BaseCrudRequest
{
    protected string $modelClass = Supplier::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'document_type' => ['nullable', 'string'], 'document_number' => ['nullable', 'string', Rule::unique('suppliers')], 'phone' => ['nullable', 'string'], 'email' => ['nullable', 'email'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']];
    }
}
