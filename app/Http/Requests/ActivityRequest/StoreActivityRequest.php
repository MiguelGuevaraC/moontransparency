<?php
namespace App\Http\Requests\ActivityRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends StoreRequest
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
            'name'         => 'required|string|max:255',                             // Nombre de la actividad (requerido)
            'start_date'   => 'required|date',                                       // Fecha de inicio (requerida, debe ser una fecha válida)
            'end_date'     => 'required|date|after_or_equal:start_date',             // Fecha de fin (requerida, debe ser una fecha válida y posterior o igual a la fecha de inicio)
            'proyect_id'   => 'required|integer|exists:proyects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'objective'    => 'required|string|max:500',                             // Objetivo de la actividad (requerido)
            'total_amount' => 'required|numeric|min:0',                              // Monto total de la actividad (requerido, debe ser un número mayor o igual a 0)
            'status'       => ['required', 'string', 'in:PENDIENTE,EN EJECUCION,FINALIZADO'],

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
            'name.required'           => 'El nombre de la actividad es obligatorio.',
            'start_date.required'     => 'La fecha de inicio es obligatoria.',
            'end_date.required'       => 'La fecha de fin es obligatoria.',
            'proyect_id.required'     => 'El ID del proyecto es obligatorio.',
            'objective.required'      => 'El objetivo de la actividad es obligatorio.',
            'total_amount.required'   => 'El monto total es obligatorio.',

            'status.required'         => 'El estado es obligatorio.',
            'status.string'           => 'El estado debe ser un texto válido.',
            'status.in'               => 'El estado debe ser uno de los siguientes valores: PENDIENTE, EN EJECUCION Y FINALIZADO.',

            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'proyect_id.exists'       => 'El ID del proyecto no existe o está eliminado.',
            'collected_amount.max'    => 'El monto recolectado no puede ser mayor que el monto total.',

        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
