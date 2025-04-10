<?php
namespace App\Http\Requests\RespondentRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateRespondentRequest extends UpdateRequest
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
        return [

            'number_document' => 'required|string|max:20',
            'names'           => 'required|string|max:1000',
            'date_of_birth'   => 'sometimes|nullable|date',
            'phone'           => 'sometimes|nullable|string|max:255',
            'email'           => 'sometimes|nullable|email|max:255',
            'genero'          => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'number_document.string'   => 'El número de documento debe ser una cadena de texto.',
            'number_document.max'      => 'El número de documento no debe exceder los 20 caracteres.',
            'number_document.required' => 'El número de documento es requerido.',

            'names.string'             => 'Los nombres deben ser una cadena de texto.',
            'names.max'                => 'Los nombres no deben exceder los 1000 caracteres.',
            'names.required'           => 'El nombre es requerido.',

            'date_of_birth.date'       => 'La fecha de nacimiento debe ser una fecha válida.',

            'phone.string'             => 'El teléfono debe ser una cadena de texto.',
            'phone.max'                => 'El teléfono no debe exceder los 255 caracteres.',

            'email.email'              => 'El correo electrónico debe ser una dirección válida.',
            'email.max'                => 'El correo electrónico no debe exceder los 255 caracteres.',

            'genero.string'            => 'El género debe ser una cadena de texto.',
            'genero.max'               => 'El género no debe exceder los 255 caracteres.',
        ];
    }

}
