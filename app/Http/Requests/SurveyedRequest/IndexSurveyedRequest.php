<?php
namespace App\Http\Requests\SurveyedRequest;

use App\Http\Requests\IndexRequest;

class IndexSurveyedRequest extends IndexRequest
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
        return array_merge(parent::rules(), [
            'id' => 'nullable|integer|min:1',
            'respondent' => 'nullable|string|max:255',
            'respondent_name' => 'nullable|string|max:255',
            'number_document' => 'nullable|string|max:20',
            'respondent_id' => 'nullable|integer|min:1',
            'survey_id' => 'nullable|integer|min:1',
            'project_id' => 'nullable|integer|min:1',
            'survey.proyect_id' => 'nullable|string',
            'created_at' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'survey.survey_type' => 'nullable|string',
            'status' => 'nullable|string|in:BORRADOR,FINALIZADA',
            'response_text' => 'nullable|string|max:1000',
            'response_text_lena' => 'nullable|string|max:1000',
            'surveyed_responses.response_text' => 'nullable|string',
        ]);
    }
}
