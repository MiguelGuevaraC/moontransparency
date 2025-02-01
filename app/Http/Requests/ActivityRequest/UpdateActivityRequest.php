<?php
namespace App\Http\Requests\ActivityRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends UpdateRequest
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
            'name'             => 'nullable|string|max:255',                             // El nombre de la actividad es opcional en la actualización
            'start_date'       => 'nullable|date',                                       // La fecha de inicio es opcional
            'end_date'         => 'nullable|date|after_or_equal:start_date',             // La fecha de fin es opcional pero debe ser posterior o igual a la fecha de inicio
            'proyect_id'       => 'nullable|integer|exists:proyects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'objective'        => 'nullable|string|max:500',                             // El objetivo de la actividad es opcional
            'total_amount'     => 'nullable|numeric|min:0',                              // El monto total es opcional pero debe ser numérico y mayor o igual a 0
            'collected_amount' => 'nullable|numeric|min:0',   // El monto recolectado es opcional y debe ser un número entre 0 y el monto total
         
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
            'name.string'              => 'El nombre de la actividad debe ser una cadena de texto.',
            'start_date.date'          => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.date'            => 'La fecha de fin debe ser una fecha válida.',
            'proyect_id.exists'        => 'El ID del proyecto no existe o está eliminado.',
            'objective.string'         => 'El objetivo de la actividad debe ser una cadena de texto.',
            'total_amount.numeric'     => 'El monto total debe ser un número.',
            'collected_amount.numeric' => 'El monto recolectado debe ser un número.',
          
            'collected_amount.max'     => 'El monto recolectado no puede ser mayor que el monto total.',
        ];
    }

}
