<?php
namespace App\Http\Requests\SurveyQuestionRequest;

use App\Http\Requests\IndexRequest;

class IndexSurveyQuestionRequest extends IndexRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question_text' => 'nullable|string',
            'question_type' => 'nullable|string',
            'survey_id'     => 'nullable|string',
        ];
    }
}
