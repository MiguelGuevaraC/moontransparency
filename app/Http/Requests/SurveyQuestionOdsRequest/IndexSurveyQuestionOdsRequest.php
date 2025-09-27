<?php

namespace App\Http\Requests\SurveyQuestionOdsRequest;

use App\Http\Requests\IndexRequest;

class IndexSurveyQuestionOdsRequest extends IndexRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'survey_question_id' => ['nullable', 'string'],
            'ods_id' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'survey_question_id' => 'Relación con survey question',
            'ods_id' => 'Relación con ods'
        ];
    }
}