<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Survey;
use App\Models\Survey;

class SurveyService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyById(int $id): ?Survey
    {
        return Survey::find($id);
    }

    public function createSurvey(array $data): Survey
    {
        $proyect = Survey::create($data);
        return $proyect;
    }
    
    public function updateSurvey(Survey $proyect, array $data): Survey
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Survey::find($id)?->delete() ?? false;
    }

}
