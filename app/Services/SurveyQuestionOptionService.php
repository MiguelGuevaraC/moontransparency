<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_SurveyQuestionOption;
use App\Models\SurveyQuestionOption;

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

    public function createSurveyQuestionOption(array $data): SurveyQuestionOption
    {
        $proyect = SurveyQuestionOption::create($data);
        return $proyect;
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
