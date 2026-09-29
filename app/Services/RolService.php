<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Permission_rol;
use App\Models\Rol;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolService
{
    public const REQUIRED_ROLES = ['Administrador', 'Administrador Moon', 'Encuestador'];

    private const SURVEYOR_FORBIDDEN_PERMISSIONS = [
        'participations.import',
        'participations.export',
    ];

    private const ADMIN_ROLES = ['Administrador', 'Administrador Moon'];

    private const USERS_ROLES_MENU_CODE = 'users_roles';

    private const USERS_ROLES_ACCESS_MESSAGE = 'Los roles Administrador deben conservar el acceso a Usuarios y roles.';

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
                ->when($this->isSurveyor($role), fn ($query) => $query
                    ->whereNotIn('route', self::SURVEYOR_FORBIDDEN_PERMISSIONS))
                ->get();

            if ($this->protectedPermissionIds($role)->diff($permissions->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['permissions' => self::USERS_ROLES_ACCESS_MESSAGE]);
            }

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
                ->when($this->isSurveyor($role), fn ($query) => $query
                    ->whereNotIn('route', self::SURVEYOR_FORBIDDEN_PERMISSIONS))
                ->get()
                ->each(fn (Permission $permission) => $this->restoreAssignment($role, $permission));

            return $role->load(['permissions', 'menus']);
        });
    }

    public function revokePermission(Rol $role, Permission $permission): Rol
    {
        if ($this->protectedPermissionIds($role)->contains($permission->id)) {
            throw ValidationException::withMessages(['permissions' => self::USERS_ROLES_ACCESS_MESSAGE]);
        }

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

            if ($this->isAdmin($role) && ! $menus->contains('code', self::USERS_ROLES_MENU_CODE)) {
                throw ValidationException::withMessages(['menus' => self::USERS_ROLES_ACCESS_MESSAGE]);
            }

            $role->menus()->sync($menus->pluck('id')->all());

            $managedPermissionIds = DB::table('menu_permissions')
                ->distinct()
                ->pluck('permission_id');
            $selectedPermissionIds = DB::table('menu_permissions')
                ->whereIn('menu_id', $menus->pluck('id'))
                ->distinct()
                ->pluck('permission_id');

            if ($this->isSurveyor($role)) {
                $forbiddenPermissionIds = Permission::query()
                    ->whereIn('route', self::SURVEYOR_FORBIDDEN_PERMISSIONS)
                    ->pluck('id');
                $selectedPermissionIds = $selectedPermissionIds->diff($forbiddenPermissionIds)->values();
            }

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
        if ($this->isSurveyor($role)
            && in_array($permission->route, self::SURVEYOR_FORBIDDEN_PERMISSIONS, true)) {
            Permission_rol::query()
                ->where('rol_id', $role->id)
                ->where('permission_id', $permission->id)
                ->delete();

            return;
        }

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

    private function isSurveyor(Rol $role): bool
    {
        return $role->name === 'Encuestador';
    }

    private function isAdmin(Rol $role): bool
    {
        return in_array($role->name, self::ADMIN_ROLES, true);
    }

    private function protectedPermissionIds(Rol $role): Collection
    {
        if (! $this->isAdmin($role)) {
            return collect();
        }

        return DB::table('menu_permissions')
            ->join('menus', 'menus.id', '=', 'menu_permissions.menu_id')
            ->where('menus.code', self::USERS_ROLES_MENU_CODE)
            ->pluck('menu_permissions.permission_id');
    }
}
