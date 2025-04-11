<?php
namespace App\Http\Requests\SurveyRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends UpdateRequest
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
            'status.in'  => 'Solo aceptan valores como ACTIVA,INACTIVA.',
            'proyect_id'  => 'nullable|integer|exists:proyects,id,deleted_at,NULL', // El proyecto debe existir y no estar eliminado
            'survey_name' => 'nullable|string|max:255',                             // El nombre de la encuesta es opcional en la actualización
            'description' => 'nullable|string|max:1000',                            // La descripción es opcional en la actualización
            'status'      => 'nullable|string|in:ACTIVA,INACTIVA',
            'survey_type' => 'required|string|max:255',
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
            'proyect_id.exists'  => 'El ID del proyecto no existe o está eliminado.',
            'survey_name.string' => 'El nombre de la encuesta debe ser una cadena de texto.',
            'survey_name.max'    => 'El nombre de la encuesta no debe exceder los 255 caracteres.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max'    => 'La descripción de la encuesta no debe exceder los 1000 caracteres.',
        ];
    }

}
