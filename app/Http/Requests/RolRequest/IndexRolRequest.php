<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\IndexRequest;
use App\Models\Rol;
use Illuminate\Validation\Rule;

class IndexRolRequest extends IndexRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in([Rol::STATUS_ACTIVE, Rol::STATUS_INACTIVE])],
            'sort' => ['nullable', Rule::in(Rol::sorts)],
        ]);
    }
}
