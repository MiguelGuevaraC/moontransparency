<?php
namespace App\Http\Requests\DonationRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends StoreRequest
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
            'proyect_id' => 'required|exists:proyects,id,deleted_at,NULL', // Asegura que el proyecto exista y no esté eliminado
            'activity_id' => 'required|exists:activities,id,deleted_at,NULL', // Asegura que la actividad exista y no esté eliminada
            'date_donation' => 'required|date', // Asegura que la fecha esté en formato válido
            'ally_id' => 'required|exists:allies,id,deleted_at,NULL', // Asegura que el aliado exista y no esté eliminado
            'details' => 'required|string|max:500', // Detalles de la donación
            'contribution_type' => 'required|string|max:100', // Tipo de contribución
            'amount' => 'required|numeric|min:0', // Monto de la donación
            'evidence' => 'nullable|string|url|max:255', // Ruta del archivo de evidencia (opcional)
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
            'proyect_id.required' => 'El ID del proyecto es obligatorio.',
            'proyect_id.exists' => 'El ID de proyecto seleccionado no es válido o el proyecto ha sido eliminado.',
            'activity_id.required' => 'El ID de la actividad es obligatorio.',
            'date_donation.required' => 'La fecha de la donación es obligatoria.',
            'ally_id.required' => 'El ID del aliado es obligatorio.',
            'details.required' => 'Los detalles de la donación son obligatorios.',
            'contribution_type.required' => 'El tipo de contribución es obligatorio.',
            'amount.required' => 'El monto de la donación es obligatorio.',
            'evidence.url' => 'La evidencia debe ser una URL válida.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
