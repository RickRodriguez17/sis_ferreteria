<?php

namespace App\Http\Requests;

use App\Models\AttributeValue;

class StoreAttributeValueRequest extends BaseCrudRequest
{
    protected string $modelClass = AttributeValue::class;

    public function rules(): array
    {
        return ['attribute_id' => ['required', 'exists:attributes,id'], 'value' => ['required', 'string']];
    }
}
