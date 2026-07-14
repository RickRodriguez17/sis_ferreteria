<?php

namespace App\Http\Requests;

use App\Models\Location;

class StoreLocationRequest extends BaseCrudRequest
{
    protected string $modelClass = Location::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'code' => ['nullable', 'string'], 'is_active' => ['boolean'], 'is_default' => ['boolean']];
    }
}
