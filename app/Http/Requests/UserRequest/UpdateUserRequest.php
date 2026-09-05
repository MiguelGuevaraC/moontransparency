<?php

namespace App\Http\Requests\UserRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends UpdateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = (int) $this->route('id');

        return [
            'type_document' => ['sometimes', 'nullable', 'string', 'max:30'],
            'number_document' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('users', 'number_document')->ignore($userId)],
            'names' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'status' => ['sometimes', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'rol_id' => ['sometimes', 'required', 'integer', Rule::exists('rols', 'id')->whereNull('deleted_at')],
        ];
    }
    

/**
 * Obtén los mensajes personalizados para las reglas de validación.
 */
    public function messages()
    {
        return [
            'username.regex' => 'El nombre de usuario solo puede contener letras, números, punto, guion y guion bajo.',
            'rol_id.exists' => 'El rol seleccionado no existe o fue eliminado.',
        ];
    }

}
