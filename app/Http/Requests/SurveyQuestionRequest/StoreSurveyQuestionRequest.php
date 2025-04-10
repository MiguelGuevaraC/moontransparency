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
            'survey_id'     => 'required|integer|exists:surveys,id,deleted_at,NULL',
            'question_type' => 'required|string|max:255',
            'question_text' => 'required|string|max:1000',
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
            'survey_id.required'     => 'El campo encuesta es obligatorio.',
            'survey_id.integer'      => 'El campo encuesta debe ser un número entero.',
            'survey_id.exists'       => 'La encuesta seleccionada no existe o ha sido eliminada.',

            'question_type.required' => 'El tipo de pregunta es obligatorio.',
            'question_type.string'   => 'El tipo de pregunta debe ser una cadena de texto.',
            'question_type.max'      => 'El tipo de pregunta no debe exceder los 255 caracteres.',

            'question_text.required' => 'El texto de la pregunta es obligatorio.',
            'question_text.string'   => 'El texto de la pregunta debe ser una cadena de texto.',
            'question_text.max'      => 'El texto de la pregunta no debe exceder los 1000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
