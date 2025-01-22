<?php
namespace App\Http\Requests\RolRequest;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreRolRequest extends StoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Cambia esto si necesitas autorización específica
    }

    public function rules()
    {
        return [
            'name'   => [
                'required',
                'string',
                'max:255',
                'regex:/^\d+$/',
                Rule::unique('rols')->whereNull('deleted_at'), // Asegura que el valor sea único, considerando deleted_at
            ],
            'status' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required'   => 'El campo "nombre" es obligatorio.',
            'name.string'     => 'El campo "nombre" debe ser una cadena de texto.',
            'name.max'        => 'El campo "nombre" no puede tener más de 255 caracteres.',
            'name.unique'     => 'El campo nombre ya ha sido registrado.', // Mensaje si ya existe el nombre
            'name.regex'      => 'El campo : Nombre solo debe contener números.',
            'status.required' => 'El campo "estado" es obligatorio.',
            'status.string'   => 'El campo "estado" debe ser una cadena de texto.',
            'status.max'      => 'El campo "estado" no puede tener más de 255 caracteres.',
        ];
    }

}
