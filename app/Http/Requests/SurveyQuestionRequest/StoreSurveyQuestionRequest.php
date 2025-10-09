<?php
namespace App\Http\Requests\SurveyQuestionRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreSurveyQuestionRequest extends StoreRequest
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
            'survey_id' => 'required|integer|exists:surveys,id,deleted_at,NULL',
            'question_type' => 'required|string|max:255|in:LIBRE,OPCIONES,UBICACION,FILE',
            'type_field' => 'required|string|max:255',
            'question_text' => 'required|string|max:1000',

            // NUEVOS: orden dentro de la encuesta y bandera si es requerido (true/false)
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',



            'eje' => 'nullable|string|max:255',
            'justification' => 'required|string',

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
            'survey_id.required' => 'El campo encuesta es obligatorio.',
            'survey_id.integer' => 'El campo encuesta debe ser un número entero.',
            'survey_id.exists' => 'La encuesta seleccionada no existe o ha sido eliminada.',

            'question_type.required' => 'El tipo de pregunta es obligatorio.',
            'question_type.string' => 'El tipo de pregunta debe ser una cadena de texto.',
            'question_type.max' => 'El tipo de pregunta no debe exceder los 255 caracteres.',
            'question_type.in' => 'El tipo de pregunta solo acepta LIBRE, OPCIONES, FILE Y UBICACION.',

            'question_text.required' => 'El texto de la pregunta es obligatorio.',
            'question_text.string' => 'El texto de la pregunta debe ser una cadena de texto.',
            'question_text.max' => 'El texto de la pregunta no debe exceder los 1000 caracteres.',

            // mensajes para order
            'order.integer' => 'El campo orden debe ser un número entero.',
            'order.min' => 'El campo orden no puede ser negativo.',

            // mensajes para is_required
            'is_required.required' => 'Debes indicar si la pregunta es obligatoria o no (true/false).',
            'is_required.boolean' => 'El campo is_required debe ser verdadero o falso (true/false).',

            'justification.required' => 'La justificación es obligatoria.',
            'justification.string' => 'La justificación debe ser una cadena de texto.',


            'eje.required' => 'El campo Eje es obligatorio.',
            'eje.string' => 'El campo Eje debe ser una cadena de texto.',
            'eje.max' => 'El campo Eje no debe exceder los 255 caracteres.',


        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'is_requeride' => 'es obligatoria',
            'order' => 'orden',

        ];
    }
}
