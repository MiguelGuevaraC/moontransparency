<?php
namespace App\Http\Requests\SurveyQuestionOptionRequest;

use App\Http\Requests\IndexRequest;

class IndexSurveyQuestionOptionRequest extends IndexRequest
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
            'survey_question_id' => 'nullable|string',
            'description'        => 'nullable|string',
        ];
    }
}
