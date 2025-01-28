<?php
namespace App\Http\Requests\SurveyRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends StoreRequest
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
            'project_id'  => 'required|integer|exists:projects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'survey_name' => 'required|string|max:255',                             // Nombre de la encuesta (requerido)
            'description' => 'required|string|max:1000',                            // Descripción de la encuesta (requerida, máximo 1000 caracteres)
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
            'project_id.required'  => 'El ID del proyecto es obligatorio.',
            'survey_name.required' => 'El nombre de la encuesta es obligatorio.',
            'description.required' => 'La descripción de la encuesta es obligatoria.',
            'project_id.exists'    => 'El ID del proyecto no existe o está eliminado.',
            'survey_name.max'      => 'El nombre de la encuesta no debe exceder los 255 caracteres.',
            'description.max'      => 'La descripción de la encuesta no debe exceder los 1000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */

}
