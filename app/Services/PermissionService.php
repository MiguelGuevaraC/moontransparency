<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_rol;
use App\Models\Rol;

class PermissionService
{

    public function getPermissionById(int $id): ?Permission
    {
        return Permission::find($id);
    }



}
