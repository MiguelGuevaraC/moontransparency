<?php
namespace App\Http\Requests\RolRequest;

use App\Http\Requests\UpdateRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends UpdateRequest
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
        $id = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^\d+$/',
                Rule::unique('rols')->whereNull('deleted_at')->ignore($id), // Ignora el ID del registro actual
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'El campo "nombre" es obligatorio.',
            'name.string'   => 'El campo "nombre" debe ser una cadena de texto.',
            'name.max'      => 'El campo "nombre" no puede tener más de 255 caracteres.',
            'name.unique'   => 'El campo nombre ya ha sido registrado.', // Mensaje si ya existe el nombre
            'name.regex'    => 'El campo : Nombre solo debe contener números.',
        ];
    }

}
