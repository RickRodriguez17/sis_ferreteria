<?php

namespace App\Http\Requests;

use App\Models\Attribute;

class StoreAttributeRequest extends BaseCrudRequest
{
    protected string $modelClass = Attribute::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'is_active' => ['boolean']];
    }
}
