<?php

namespace App\Http\Requests\UserRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreUserRequest extends StoreRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'type_document' => ['nullable', 'string', 'max:30'],
            'number_document' => ['required', 'string', 'max:30', Rule::unique('users', 'number_document')],
            'names' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'rol_id' => ['required', 'integer', Rule::exists('rols', 'id')->whereNull('deleted_at')],
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
