<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Respondent;
use App\Models\Respondent;

class RespondentService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getRespondentById(int $id): ?Respondent
    {
        return Respondent::find($id);
    }

    public function createRespondent(array $data): Respondent
    {
        $proyect = Respondent::create($data);
        return $proyect;
    }
    
    public function updateRespondent(Respondent $proyect, array $data): Respondent
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Respondent::find($id)?->delete() ?? false;
    }

}
