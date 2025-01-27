<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Ods;
use App\Models\Ods;

class OdsService
{

    public function getOdsById(int $id): ?Ods
    {
        return Ods::find($id);
    }

}
