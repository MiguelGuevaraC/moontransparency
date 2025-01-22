<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_rol;
use App\Models\Rol;

class RolService
{

    public function getRolById(int $id): ?Rol
    {
        return Rol::find($id);
    }

    public function createRol(array $data): Rol
    {
        return Rol::create($data);
    }

    public function updateRol($Rol, array $data)
    {
        $Rol->update($data);
        return $Rol;
    }

    public function destroyById($id)
    {
        // Encuentra y elimina el registro si existe
        return Rol::find($id)?->delete() ?? false;
    }
    public function setAccess(array $permissions, $role): Rol
    {
        // Obtiene los permisos que se asignarán
        $validPermissions = Permission::whereIn('id', $permissions)->get();

        // Sincroniza los permisos con el rol
        $role->permissions()->sync([]);

        // Reasocia los permisos y actualiza los campos adicionales en la tabla intermedia
        foreach ($validPermissions as $permission) {
            Permission_rol::Create(
                [
                    'name_permission' => $permission->name,
                    'name_rol' => $role->name,
                    'type' => $role->type,
                    'rol_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        return Rol::find($role->id);
    }

}
