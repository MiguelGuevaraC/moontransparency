<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Activity;
use App\Models\Activity;

class ActivityService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getActivityById(int $id): ?Activity
    {
        return Activity::find($id);
    }

    public function createActivity(array $data): Activity
    {
        $proyect = Activity::create($data);
        return $proyect;
    }
    
    public function updateActivity(Activity $proyect, array $data): Activity
    {
      $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Activity::find($id)?->delete() ?? false;
    }

}
