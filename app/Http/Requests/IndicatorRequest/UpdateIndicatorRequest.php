<?php
namespace App\Http\Requests\IndicatorRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateIndicatorRequest extends UpdateRequest
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
            'proyect_id' => 'nullable|integer|exists:proyects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'indicator_name' => 'nullable|string|max:255', // El nombre del indicador es opcional en la actualización
            'target_value' => 'nullable|numeric|min:0', // El valor objetivo es opcional pero debe ser un número mayor o igual a 0
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
            'unit' => 'nullable|string|max:50', // La unidad de medida es opcional en la actualización
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
            'proyect_id.integer' => 'El ID del proyecto debe ser un número entero.',
            'proyect_id.exists' => 'El ID del proyecto no existe o está eliminado.',
            'indicator_name.string' => 'El nombre del indicador debe ser una cadena de texto.',
            'indicator_name.max' => 'El nombre del indicador no debe exceder los 255 caracteres.',
            'target_value.numeric' => 'El valor objetivo debe ser un número.',
            'target_value.min' => 'El valor objetivo debe ser mayor o igual a 0.',
            'progress_value.numeric' => 'El valor de progreso debe ser un número.',
            'progress_value.min' => 'El valor de progreso debe ser mayor o igual a 0.',
            'unit.string' => 'La unidad de medida debe ser una cadena de texto.',
            'unit.max' => 'La unidad de medida no debe exceder los 50 caracteres.',
        ];
    }

}
