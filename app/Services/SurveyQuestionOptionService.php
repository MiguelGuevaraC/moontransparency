<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_SurveyQuestionOption;
use App\Models\SurveyQuestionOption;
use Illuminate\Support\Facades\DB;

class SurveyQuestionOptionService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyQuestionOptionById(int $id): ?SurveyQuestionOption
    {
        return SurveyQuestionOption::find($id);
    }

    public function createSurveyQuestionOptions(array $data)
    {
        return DB::transaction(function () use ($data) {

            $surveyQuestionId = $data['survey_question_id'];
            $options = $data['options'] ?? [];
            $replaceExisting = $data['replace_existing'] ?? false;

            if ($replaceExisting) {
                SurveyQuestionOption::where('survey_question_id', $surveyQuestionId)->delete();
            }

            $created = collect();

            foreach ($options as $opt) {
                $payload = [
                    'survey_question_id' => $surveyQuestionId,
                    'description'        => $opt['description'],
                ];

                $created->push(SurveyQuestionOption::create($payload));
            }

            return $created;
        });
    }
    
    public function updateSurveyQuestionOption(SurveyQuestionOption $proyect, array $data): SurveyQuestionOption
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return SurveyQuestionOption::find($id)?->delete() ?? false;
    }

}
