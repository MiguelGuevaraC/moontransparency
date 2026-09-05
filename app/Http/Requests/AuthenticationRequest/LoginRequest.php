<?php

namespace App\Http\Requests\AuthenticationRequest;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends StoreRequest
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
     * Obtener las reglas de validación para la solicitud.
     */
    public function rules()
    {
        return [
            "username" => [
                "required",
                'string',
                'max:255',
            ],
            "password" => [
                "required",
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Obtener los mensajes de error personalizados para la validación.
     */
    public function messages()
    {
        return [
            "username.required" => "El Username es obligatorio.",
    
            "password.required" => "La contraseña es obligatoria.",
        ];
    }

    /**
     * Verifica que solo los parámetros permitidos estén presentes.
     */
    public function validateResolved()
    {
        parent::validateResolved();

        // Parámetros permitidos
        $allowed = ['username', 'password'];

        // Obtiene todos los parámetros de la solicitud
        $extraParams = array_diff(array_keys($this->all()), $allowed);

        // Si existen parámetros adicionales, aborta la solicitud con error 400
        if (!empty($extraParams)) {
            // Mensaje de error para parámetros no permitidos
            
            abort(422, "Parámetros adicionales no permitidos: " . implode(', ', $extraParams));
        }
    }
}
