<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends BaseCrudRequest
{
    protected string $modelClass = Unit::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'abbreviation' => ['required', 'string', Rule::unique('units')], 'is_active' => ['boolean']];
    }
}
