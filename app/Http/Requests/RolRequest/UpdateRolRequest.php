<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\Rol;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends UpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = (int) $this->route('id');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                'regex:/^[\pL\pN ._-]+$/u',
                Rule::unique('rols', 'name')->whereNull('deleted_at')->ignore($roleId),
            ],
            'status' => ['sometimes', Rule::in([Rol::STATUS_ACTIVE, Rol::STATUS_INACTIVE])],
        ];
    }
}
