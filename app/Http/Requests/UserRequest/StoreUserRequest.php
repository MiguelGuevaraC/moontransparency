<?php

namespace App\Http\Requests\UserRequest;

use App\Http\Requests\StoreRequest;
use App\Models\Person;
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
