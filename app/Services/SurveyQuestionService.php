<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_SurveyQuestion;
use App\Models\SurveyQuestion;

class SurveyQuestionService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyQuestionById(int $id): ?SurveyQuestion
    {
        return SurveyQuestion::find($id);
    }

    public function createSurveyQuestion(array $data): SurveyQuestion
    {
        $proyect = SurveyQuestion::create($data);
        return $proyect;
    }
    
    public function updateSurveyQuestion(SurveyQuestion $proyect, array $data): SurveyQuestion
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return SurveyQuestion::find($id)?->delete() ?? false;
    }

}
