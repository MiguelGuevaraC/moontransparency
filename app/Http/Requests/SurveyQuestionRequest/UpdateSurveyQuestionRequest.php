<?php
namespace App\Http\Requests\SurveyQuestionRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateSurveyQuestionRequest extends UpdateRequest
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
     * All fields are optional (nullable). If present, they must respect the defined rules.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Opcional: si viene, debe ser integer y existir en surveys
            'survey_id'     => 'nullable|integer|exists:surveys,id,deleted_at,NULL',

            // Opcional: si viene, validar tipo aceptado
            'question_type' => 'nullable|string|max:255|in:LIBRE,OPCIONES,UBICACION,FILE',

            // Opcional: tipo de campo (input, textarea, select, etc.)
            'type_field'    => 'nullable|string|max:255',

            // Opcional: texto de la pregunta hasta 1000 caracteres
            'question_text' => 'nullable|string|max:1000',

            // Opcional: orden dentro de la encuesta (no negativo)
            'order'         => 'nullable|integer|min:0',

            // Opcional: bandera si es obligatoria
            'is_required'   => 'nullable|boolean',

            
            'eje' => 'nullable|string|max:255',
            'justification' => 'nullable|string',
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
            'survey_id.integer'      => 'El campo encuesta debe ser un número entero.',
            'survey_id.exists'       => 'La encuesta seleccionada no existe o ha sido eliminada.',

            'question_type.string'   => 'El tipo de pregunta debe ser una cadena de texto.',
            'question_type.max'      => 'El tipo de pregunta no debe exceder los 255 caracteres.',
            'question_type.in'       => 'El tipo de pregunta solo acepta LIBRE, OPCIONES, FILE y UBICACION.',

            'type_field.string'      => 'El campo type_field debe ser una cadena de texto.',
            'type_field.max'         => 'El campo type_field no debe exceder los 255 caracteres.',

            'question_text.string'   => 'El texto de la pregunta debe ser una cadena de texto.',
            'question_text.max'      => 'El texto de la pregunta no debe exceder los 1000 caracteres.',

            'order.integer'          => 'El campo orden debe ser un número entero.',
            'order.min'              => 'El campo orden no puede ser negativo.',

            'is_required.boolean'    => 'El campo is_required debe ser verdadero o falso (true/false).',
           'eje.required' => 'El campo Eje es obligatorio.',
            'eje.string' => 'El campo Eje debe ser una cadena de texto.',
            'eje.max' => 'El campo Eje no debe exceder los 255 caracteres.',

            'justification.required' => 'La justificación es obligatoria.',
            'justification.string' => 'La justificación debe ser una cadena de texto.',

        
        ];
    }

    /**
     * Atributos personalizados para mensajes de error.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'survey_id'     => 'encuesta',
            'question_type' => 'tipo de pregunta',
            'type_field'    => 'tipo de campo',
            'question_text' => 'texto de la pregunta',
            'order'         => 'orden',
            'is_required'   => 'es obligatoria',
        ];
    }
}
