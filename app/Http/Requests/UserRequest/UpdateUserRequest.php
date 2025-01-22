<?php

namespace App\Http\Requests\UserRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\Person;
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
        $id = $this->route('id'); // Obtén el ID de la ruta, que se asume que es el ID del usuario
    
        return [
      
        ];
    }
    

/**
 * Obtén los mensajes personalizados para las reglas de validación.
 */
    public function messages()
    {
        return [

        ];
    }

}
