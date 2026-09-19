<?php

namespace App\Http\Requests\SurveyRequest;

use Illuminate\Foundation\Http\FormRequest;

class PublicSurveyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'min:1', 'exists:proyects,id'],
            'survey_name' => ['nullable', 'string', 'max:255'],
            'survey_type' => ['nullable', 'string', 'in:PRE,POST'],
            'all' => ['nullable', 'string', 'in:true,false'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
