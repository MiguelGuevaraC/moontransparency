<?php
namespace App\Http\Requests\ContactSenderRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ContactSendRequest",
 *     required={"name_contact", "subject", "description", "contact_email"},
 *     @OA\Property(property="name_contact", type="string", description="Nombre de la persona de contacto", example="Juan Pérez", maxLength=255),
 *     @OA\Property(property="subject", type="string", description="Asunto del mensaje", example="Consulta sobre servicios", maxLength=255),
 *     @OA\Property(property="description", type="string", description="Descripción o contenido del mensaje", example="Me gustaría obtener más información sobre sus servicios."),
 *     @OA\Property(property="contact_email", type="string", format="email", description="Correo electrónico de contacto", example="contacto@empresa.com", maxLength=255),
 *     @OA\Property(property="sender_email", type="string", format="email", nullable=true, description="Correo electrónico del remitente", example="juan.perez@gmail.com", maxLength=255),
 *     @OA\Property(property="status", type="string", nullable=true, description="Estado del mensaje", example="pending", enum={"pending", "read", "answered"})
 * )
 */
class StoreContactSenderRequest extends StoreRequest
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

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
