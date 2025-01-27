<?php
namespace App\Http\Requests\AllyRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateAllyRequest extends UpdateRequest
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
     * @return array
     */
    public function rules()
    {
        $id = $this->route('id'); // Obtén el ID de la ruta, que se asume que es el ID del usuario
    
        return [
            'ruc_dni'            => 'required|string|max:11|unique:allies,ruc_dni,{$id},id,deleted_at,NULL',
            'first_name'         => 'nullable|string|max:100',
            'last_name'          => 'nullable|string|max:100',
            'business_name'      => 'nullable|string',
            'phone'              => 'nullable|string|regex:/^\d{9}$/',
            'email'              => 'nullable|email',
            'images'             => 'nullable|array',
            'images.*'           => 'file|mimes:jpeg,png,jpg,gif|max:2048',
            'area_of_interest'   => 'required|string|max:255',
            'participation_type' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'ruc_dni.required'            => 'El RUC o DNI es obligatorio.',
            'ruc_dni.string'              => 'El RUC o DNI debe ser una cadena de texto.',
            'ruc_dni.max'                 => 'El RUC o DNI no puede tener más de 11 caracteres.',
            'ruc_dni.unique'              => 'El RUC o DNI ya está registrado.',

            'first_name.required'         => 'El nombre es obligatorio.',
            'first_name.string'           => 'El nombre debe ser una cadena de texto.',
            'first_name.max'              => 'El nombre no puede tener más de 100 caracteres.',

            'last_name.required'          => 'El apellido es obligatorio.',
            'last_name.string'            => 'El apellido debe ser una cadena de texto.',
            'last_name.max'               => 'El apellido no puede tener más de 100 caracteres.',

            'business_name.required'      => 'El nombre comercial es obligatorio.',
            'business_name.string'        => 'El nombre comercial debe ser una cadena de texto.',

            'phone.required'              => 'El número de teléfono es obligatorio.',
            'phone.string'                => 'El número de teléfono debe ser una cadena de texto.',
            'phone.regex'                 => 'El número de teléfono debe tener exactamente 9 dígitos.',

            'email.required'              => 'El correo electrónico es obligatorio.',
            'email.email'                 => 'El correo electrónico debe tener un formato válido.',

            'images.required'             => 'Debe proporcionar al menos una imagen.',
            'images.array'                => 'El campo de imágenes debe ser un arreglo.',
            'images.*.file'               => 'Cada archivo de imagen debe ser un archivo válido.',
            'images.*.mimes'              => 'Cada imagen debe estar en formato jpeg, png, jpg o gif.',
            'images.*.max'                => 'Cada imagen no puede superar los 2 MB.',

            'area_of_interest.required'   => 'El área de interés es obligatoria.',
            'area_of_interest.string'     => 'El área de interés debe ser una cadena de texto.',
            'area_of_interest.max'        => 'El área de interés no puede tener más de 255 caracteres.',

            'participation_type.required' => 'El tipo de participación es obligatorio.',
            'participation_type.string'   => 'El tipo de participación debe ser una cadena de texto.',
            'participation_type.max'      => 'El tipo de participación no puede tener más de 255 caracteres.',
        ];
    }

}
