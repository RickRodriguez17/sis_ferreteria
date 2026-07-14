<?php

namespace App\Http\Requests;

use App\Models\Brand;

class StoreBrandRequest extends BaseCrudRequest
{
    protected string $modelClass = Brand::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'is_active' => ['boolean']];
    }
}
