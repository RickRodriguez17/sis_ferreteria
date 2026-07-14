<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseCrudRequest extends FormRequest
{
    protected string $modelClass;

    public function authorize(): bool
    {
        $ability = $this->isMethod('POST') ? 'create' : 'update';
        $model = $this->route(array_key_first($this->route()?->parameters() ?? []));

        return (bool) $this->user()?->can($ability, $model ?: $this->modelClass);
    }
}
