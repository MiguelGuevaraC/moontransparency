<?php
namespace App\Http\Requests\DonationRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateDonationRequest extends UpdateRequest
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
            'proyect_id' => 'nullable|integer|exists:proyects,id,deleted_at,NULL', // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'activity_id' => 'nullable|integer|exists:activities,id,deleted_at,NULL', // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'date_donation' => 'nullable|date', // El campo es opcional para actualizar, debe ser una fecha válida
            'ally_id' => 'nullable|integer|exists:allies,id,deleted_at,NULL', // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'details' => 'nullable|string|max:500', // Detalles de la donación (opcional en la actualización)
            'contribution_type' => 'nullable|string|max:100', // Tipo de contribución (opcional en la actualización)
            'amount' => 'nullable|numeric|min:0', // Monto de la donación (opcional en la actualización)
            'evidence' => 'nullable|string|url|max:255', // Ruta del archivo de evidencia (opcional en la actualización)
        ];
    }

    /**
     * Obtener los mensajes personalizados de validación.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'proyect_id.exists' => 'El ID del proyecto no existe o está eliminado.',
            'activity_id.exists' => 'El ID de la actividad no existe o está eliminado.',
            'date_donation.date' => 'La fecha de la donación debe ser una fecha válida.',
            'ally_id.exists' => 'El ID del aliado no existe o está eliminado.',
            'details.string' => 'Los detalles deben ser una cadena de texto.',
            'contribution_type.string' => 'El tipo de contribución debe ser una cadena de texto.',
            'amount.numeric' => 'El monto de la donación debe ser un valor numérico.',
            'evidence.url' => 'La evidencia debe ser una URL válida.',
        ];
    }


}
