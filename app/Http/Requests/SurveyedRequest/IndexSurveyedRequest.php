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
        return [

            'respondent_id'      => 'nullable|string',
            'survey_id'          => 'nullable|string',

            'survey.proyect_id'  => 'nullable|string',
            'created_at'         => 'nullable|string',
            'survey.survey_type' => 'nullable|string',
        ];
    }
}
