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
            'proyect_id'        => 'required|integer|exists:proyects,id,deleted_at,NULL',   // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'activity_id'       => 'required|integer|exists:activities,id,deleted_at,NULL', // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'date_donation'     => 'required|date',                                         // El campo es opcional para actualizar, debe ser una fecha válida
            'ally_id'           => 'required|integer|exists:allies,id,deleted_at,NULL',     // El campo es opcional para actualizar, pero debe existir y no estar eliminado
            'details'           => 'nullable|string|max:500',                               // Detalles de la donación (opcional en la actualización)
            'contribution_type' => 'nullable|string|max:100',                               // Tipo de contribución (opcional en la actualización)
            'amount'            => 'nullable|numeric|min:0',                                // Monto de la donación (opcional en la actualización)
            'images.*'          => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf|max:2048',
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
  
            'images.*.file' => 'Cada archivo debe ser un archivo válido.',
            'images.*.mimes' => 'Los archivos deben ser de tipo: jpeg, jpg, png, gif o pdf.',
            'images.*.max' => 'Cada archivo no debe superar los 2MB.'
        ];
    }

}
