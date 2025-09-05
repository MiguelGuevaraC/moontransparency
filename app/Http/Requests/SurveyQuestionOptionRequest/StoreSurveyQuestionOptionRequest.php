<?php
namespace App\Http\Requests\SurveyQuestionOptionRequest;

use App\Http\Requests\StoreRequest;

class StoreSurveyQuestionOptionRequest extends StoreRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'survey_question_id'        => 'required|integer|exists:survey_questions,id,deleted_at,NULL',
            'options'                   => 'required|array|min:1',
            'options.*.description'     => 'required|string|max:255',
            // si quieres otros campos por opción:
            // 'options.*.code'          => 'nullable|string|max:50',
            'replace_existing'          => 'sometimes|boolean', // opcional: borrar opciones previas
        ];
    }

    public function messages()
    {
        return [
            'survey_question_id.required'    => 'El campo encuesta/pregunta es obligatorio.',
            'survey_question_id.integer'     => 'El campo encuesta/pregunta debe ser un número entero.',
            'survey_question_id.exists'      => 'La pregunta seleccionada no existe o ha sido eliminada.',

            'options.required'               => 'Debe enviar al menos una opción.',
            'options.array'                  => 'El campo options debe ser un arreglo.',
            'options.*.description.required' => 'Cada opción debe tener una descripción.',
            'options.*.description.string'   => 'La descripción de la opción debe ser una cadena.',
            'options.*.description.max'      => 'La descripción no debe exceder los 255 caracteres.',
            'replace_existing.boolean'       => 'replace_existing debe ser booleano (true|false).',
        ];
    }
}
