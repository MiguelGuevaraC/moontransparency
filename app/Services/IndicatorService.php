<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Indicator;
use App\Models\Indicator;

class IndicatorService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getIndicatorById(int $id): ?Indicator
    {
        return Indicator::find($id);
    }

    public function createIndicator(array $data): Indicator
    {
        $proyect = Indicator::create($data);
        return $proyect;
    }
    
    public function updateIndicator(Indicator $proyect, array $data): Indicator
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Indicator::find($id)?->delete() ?? false;
    }

}
