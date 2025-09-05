<?php
namespace App\Http\Requests\SurveyRequest;

use App\Http\Requests\IndexRequest;

class IndexSurveyRequest extends IndexRequest
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

            'post_survey_id'  => 'nullable|string',
            'proyect_id'  => 'nullable|string',
            'survey_name' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }
}
