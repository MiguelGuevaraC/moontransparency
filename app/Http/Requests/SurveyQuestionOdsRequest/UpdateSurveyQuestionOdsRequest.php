<?php

namespace App\Http\Requests\SurveyQuestionOdsRequest;

use App\Http\Requests\UpdateRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UpdateSurveyQuestionOdsRequest",
 *     @OA\Property(property="survey_question_id", type="integer"),
 *     @OA\Property(property="ods_id", type="integer")
 * )
 */
class UpdateSurveyQuestionOdsRequest extends UpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'survey_question_id' => ['nullable', 'integer', 'exists:survey_questions,id'],
            'ods_id' => [
                'nullable',
                'integer',
                'exists:ods,id',
                Rule::unique('survey_question_ods', 'ods_id')
                    ->ignore($this->route('id')) // ignora el registro actual
                    ->where(fn ($query) => $query
                        ->where('survey_question_id', $this->survey_question_id)
                        ->whereNull('deleted_at')
                    ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'survey_question_id' => 'Relación con survey question',
            'ods_id' => 'Relación con ODS',
        ];
    }

    public function messages(): array
    {
        return [
            'ods_id.unique' => 'Este ODS ya está asignado a la pregunta seleccionada.',
        ];
    }
}
