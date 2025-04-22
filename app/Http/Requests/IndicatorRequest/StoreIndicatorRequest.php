<?php
namespace App\Http\Requests\IndicatorRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreIndicatorRequest extends StoreRequest
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
            'proyect_id' => 'required|integer|exists:proyects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'indicator_name' => 'required|string|max:255', // Nombre del indicador (requerido)
            'target_value' => 'required|numeric|min:0', // Valor objetivo (requerido, debe ser un número mayor o igual a 0)
            'progress_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $target = $this->input('target_value');
                    if (is_numeric($target) && $value > $target) {
                        $fail('El valor de progreso no puede ser mayor al valor objetivo.');
                    }
                }
            ],
            'unit' => 'required|string|max:50', // Unidad de medida (requerida, debe ser una cadena de texto)
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
            'indicator_name.required' => 'El nombre del indicador es obligatorio.',
            'target_value.required' => 'El valor objetivo es obligatorio.',
            'progress_value.required' => 'El valor de progreso es obligatorio.',
            'unit.required' => 'La unidad de medida es obligatoria.',
            'proyect_id.exists' => 'El ID del proyecto no existe o está eliminado.',
            'indicator_name.max' => 'El nombre del indicador no debe exceder los 255 caracteres.',
            'target_value.numeric' => 'El valor objetivo debe ser un número.',
            'target_value.min' => 'El valor objetivo debe ser mayor o igual a 0.',
            'progress_value.numeric' => 'El valor de progreso debe ser un número.',
            'progress_value.min' => 'El valor de progreso debe ser mayor o igual a 0.',
            'unit.max' => 'La unidad de medida no debe exceder los 50 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
