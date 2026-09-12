<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Permission_rol;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolService
{
    public const REQUIRED_ROLES = ['Administrador', 'Administrador Moon', 'Encuestador'];

    public function getRolById(int $id): ?Rol
    {
        return Rol::with(['permissions', 'menus'])->find($id);
    }

    public function createRol(array $data): Rol
    {
        $data['status'] = $data['status'] ?? Rol::STATUS_ACTIVE;

        return Rol::create($data)->load(['permissions', 'menus']);
    }

    public function updateRol(Rol $rol, array $data): Rol
    {
        return DB::transaction(function () use ($rol, $data) {
            if (isset($data['name'])
                && $data['name'] !== $rol->name
                && in_array($rol->name, self::REQUIRED_ROLES, true)) {
                throw ValidationException::withMessages([
                    'role' => 'El nombre de un perfil mínimo del sistema no puede modificarse.',
                ]);
            }

            $status = $data['status'] ?? null;
            unset($data['status']);
            $rol->update($data);

            if (isset($data['name'])) {
                Permission_rol::withTrashed()
                    ->where('rol_id', $rol->id)
                    ->update(['name_rol' => $rol->name]);
            }

            if ($status === Rol::STATUS_INACTIVE) {
                return $this->deactivate($rol);
            }
            if ($status === Rol::STATUS_ACTIVE) {
                return $this->activate($rol);
            }

            return $rol->load(['permissions', 'menus']);
        });
    }

    public function activate(Rol $rol): Rol
    {
        $rol->update(['status' => Rol::STATUS_ACTIVE]);

        return $rol->load(['permissions', 'menus']);
    }

    public function deactivate(Rol $rol): Rol
    {
        if ($rol->users()->where('status', 'Activo')->exists()) {
            throw ValidationException::withMessages([
                'role' => 'No se puede desactivar un rol asignado a usuarios activos.',
            ]);
        }

        $rol->update(['status' => Rol::STATUS_INACTIVE]);

        return $rol->load(['permissions', 'menus']);
    }

    public function destroy(Rol $rol): void
    {
        if (in_array($rol->name, self::REQUIRED_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => 'Este perfil mínimo del sistema no puede eliminarse.',
            ]);
        }
        if ($rol->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'No se puede eliminar un rol que está asignado a usuarios.',
            ]);
        }

        $rol->delete();
    }

    public function setAccess(array $permissionIds, Rol $role): Rol
    {
        return DB::transaction(function () use ($permissionIds, $role) {
            $permissions = Permission::query()
                ->whereIn('id', $permissionIds)
                ->where('status', Permission::STATUS_ACTIVE)
                ->get();

            Permission_rol::where('rol_id', $role->id)->delete();
            foreach ($permissions as $permission) {
                $this->restoreAssignment($role, $permission);
            }

            return $role->load(['permissions', 'menus']);
        });
    }

    public function assignPermissions(array $permissionIds, Rol $role): Rol
    {
        return DB::transaction(function () use ($permissionIds, $role) {
            Permission::query()
                ->whereIn('id', $permissionIds)
                ->where('status', Permission::STATUS_ACTIVE)
                ->get()
                ->each(fn (Permission $permission) => $this->restoreAssignment($role, $permission));

            return $role->load(['permissions', 'menus']);
        });
    }

    public function revokePermission(Rol $role, Permission $permission): Rol
    {
        Permission_rol::where('rol_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();

        return $role->load(['permissions', 'menus']);
    }

    public function setMenus(array $menuIds, Rol $role): Rol
    {
        return DB::transaction(function () use ($menuIds, $role) {
            $menus = Menu::query()
                ->whereIn('id', $menuIds)
                ->where('status', Menu::STATUS_ACTIVE)
                ->get();

            $role->menus()->sync($menus->pluck('id')->all());

            $managedPermissionIds = DB::table('menu_permissions')
                ->distinct()
                ->pluck('permission_id');
            $selectedPermissionIds = DB::table('menu_permissions')
                ->whereIn('menu_id', $menus->pluck('id'))
                ->distinct()
                ->pluck('permission_id');

            Permission_rol::query()
                ->where('rol_id', $role->id)
                ->whereIn('permission_id', $managedPermissionIds)
                ->whereNotIn('permission_id', $selectedPermissionIds)
                ->delete();

            Permission::query()
                ->whereIn('id', $selectedPermissionIds)
                ->where('status', Permission::STATUS_ACTIVE)
                ->get()
                ->each(fn (Permission $permission) => $this->restoreAssignment($role, $permission));

            return $role->load(['permissions', 'menus']);
        });
    }

    private function restoreAssignment(Rol $role, Permission $permission): void
    {
        $assignment = Permission_rol::withTrashed()
            ->where('rol_id', $role->id)
            ->where('permission_id', $permission->id)
            ->first();
        $values = [
            'name_permission' => $permission->name,
            'name_rol' => $role->name,
            'type' => $permission->type,
        ];

        if ($assignment) {
            $assignment->restore();
            $assignment->update($values);

            return;
        }

        Permission_rol::create($values + [
            'rol_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }
}
