<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\StoreRequest;
use App\Models\Rol;
use Illuminate\Validation\Rule;

class StoreRolRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                'regex:/^[\pL\pN ._-]+$/u',
                Rule::unique('rols', 'name')->whereNull('deleted_at'),
            ],
            'status' => ['nullable', Rule::in([Rol::STATUS_ACTIVE, Rol::STATUS_INACTIVE])],
        ];
    }
}
