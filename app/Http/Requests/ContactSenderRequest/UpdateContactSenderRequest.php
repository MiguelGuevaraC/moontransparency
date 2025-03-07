<?php
namespace App\Http\Requests\ContactSenderRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateContactSenderRequest extends UpdateRequest
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
            'name_contact'  => 'required|string|max:255',
            'subject'       => 'required|string|max:255',
            'description'   => 'required|string',
            'contact_email' => 'required|email|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name_contact.required'  => 'El nombre de contacto es obligatorio.',
            'name_contact.string'    => 'El nombre de contacto debe ser texto.',
            'name_contact.max'       => 'El nombre de contacto no debe exceder los 255 caracteres.',

            'subject.required'       => 'El asunto es obligatorio.',
            'subject.string'         => 'El asunto debe ser texto.',
            'subject.max'            => 'El asunto no debe exceder los 255 caracteres.',

            'description.required'   => 'La descripción es obligatoria.',
            'description.string'     => 'La descripción debe ser texto.',

            'contact_email.required' => 'El correo de contacto es obligatorio.',
            'contact_email.email'    => 'El correo de contacto debe ser una dirección de email válida.',
            'contact_email.max'      => 'El correo de contacto no debe exceder los 255 caracteres.',
        ];
    }

}
