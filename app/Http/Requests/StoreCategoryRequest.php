<?php

namespace App\Http\Requests;

use App\Models\Category;

class StoreCategoryRequest extends BaseCrudRequest
{
    protected string $modelClass = Category::class;

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'exists:categories,id'], 'is_active' => ['boolean']];
    }
}
