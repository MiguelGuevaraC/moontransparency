<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\UpdateRequest;
use Illuminate\Validation\Rule;

class UpdateAccessRequest extends UpdateRequest
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
            'access' => [
                'required',
                'array',
                'exists:permissions,id,deleted_at,NULL', // Valida que cada ID exista en la tabla permissions
            ],
        ];
    }
    
    public function messages()
    {
        return [
            'access.required' => 'El campo "access" es obligatorio.',
            'access.array' => 'El campo "access" debe ser un arreglo de IDs de permisos.',
            'access.exists' => 'Al menos uno de los IDs en "access" no es válido.',
        ];
    }
    

}
