<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), ['document_number' => ['nullable', 'string', Rule::unique('suppliers')->ignore($this->route('supplier'))]]);
    }
}
