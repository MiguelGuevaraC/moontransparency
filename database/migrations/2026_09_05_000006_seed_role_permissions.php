<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'Leer usuarios', 'route' => 'users.view', 'type' => 'Usuarios'],
            ['name' => 'Crear usuarios', 'route' => 'users.create', 'type' => 'Usuarios'],
            ['name' => 'Editar usuarios', 'route' => 'users.update', 'type' => 'Usuarios'],
            ['name' => 'Eliminar usuarios', 'route' => 'users.delete', 'type' => 'Usuarios'],
            ['name' => 'Leer roles', 'route' => 'roles.view', 'type' => 'Roles'],
            ['name' => 'Crear roles', 'route' => 'roles.create', 'type' => 'Roles'],
            ['name' => 'Editar roles', 'route' => 'roles.update', 'type' => 'Roles'],
            ['name' => 'Desactivar roles', 'route' => 'roles.deactivate', 'type' => 'Roles'],
            ['name' => 'Eliminar roles', 'route' => 'roles.delete', 'type' => 'Roles'],
            ['name' => 'Asignar permisos', 'route' => 'roles.assign_permissions', 'type' => 'Roles'],
            ['name' => 'Revocar permisos', 'route' => 'roles.revoke_permissions', 'type' => 'Roles'],
            ['name' => 'Leer contenido', 'route' => 'content.view', 'type' => 'Contenido'],
            ['name' => 'Administrar contenido', 'route' => 'content.manage', 'type' => 'Contenido'],
            ['name' => 'Leer encuestas', 'route' => 'surveys.view', 'type' => 'Encuestas'],
            ['name' => 'Administrar encuestas', 'route' => 'surveys.manage', 'type' => 'Encuestas'],
            ['name' => 'Leer participaciones', 'route' => 'participations.view', 'type' => 'Participaciones'],
            ['name' => 'Administrar participaciones', 'route' => 'participations.manage', 'type' => 'Participaciones'],
            ['name' => 'Importar participaciones', 'route' => 'participations.import', 'type' => 'Participaciones'],
            ['name' => 'Exportar participaciones', 'route' => 'participations.export', 'type' => 'Participaciones'],
            ['name' => 'Leer encuestados', 'route' => 'respondents.view', 'type' => 'Encuestados'],
            ['name' => 'Administrar encuestados', 'route' => 'respondents.manage', 'type' => 'Encuestados'],
        ];

        DB::transaction(function () use ($permissions, $now) {
            $legacyCodes = [
                1 => 'users.view', 2 => 'users.create', 3 => 'users.update',
                4 => 'users.delete', 5 => 'roles.view',
                6 => 'roles.assign_permissions', 7 => 'roles.revoke_permissions',
            ];
            foreach ($legacyCodes as $id => $code) {
                DB::table('permissions')->where('id', $id)->update(['route' => $code]);
            }

            foreach ($permissions as $permission) {
                $existing = DB::table('permissions')->where('route', $permission['route'])->first();
                $values = $permission + ['status' => 'Activo', 'updated_at' => $now, 'deleted_at' => null];
                if ($existing) {
                    DB::table('permissions')->where('id', $existing->id)->update($values);
                } else {
                    DB::table('permissions')->insert($values + ['created_at' => $now]);
                }
            }

            $roleIds = [];
            foreach (['Administrador', 'Administrador Moon', 'Encuestador'] as $roleName) {
                $role = DB::table('rols')->where('name', $roleName)->first();
                if ($role) {
                    DB::table('rols')->where('id', $role->id)->update([
                        'status' => 'Activo', 'deleted_at' => null, 'updated_at' => $now,
                    ]);
                    $roleIds[$roleName] = $role->id;
                } else {
                    $roleIds[$roleName] = DB::table('rols')->insertGetId([
                        'name' => $roleName, 'status' => 'Activo', 'created_at' => $now,
                        'updated_at' => $now, 'deleted_at' => null,
                    ]);
                }
            }

            $permissionRows = DB::table('permissions')->whereIn(
                'route', collect($permissions)->pluck('route')
            )->get()->keyBy('route');

            foreach (['Administrador', 'Administrador Moon'] as $roleName) {
                foreach ($permissionRows as $permission) {
                    $this->restoreAssignment($roleIds[$roleName], $roleName, $permission, $now);
                }
            }

            foreach (['content.view', 'surveys.view', 'participations.view', 'participations.manage', 'respondents.view', 'respondents.manage'] as $code) {
                $this->restoreAssignment($roleIds['Encuestador'], 'Encuestador', $permissionRows[$code], $now);
            }
        });
    }

    private function restoreAssignment(int $roleId, string $roleName, object $permission, $now): void
    {
        $pivot = DB::table('permission_rols')
            ->where('rol_id', $roleId)->where('permission_id', $permission->id)->first();
        $values = [
            'name_permission' => $permission->name, 'name_rol' => $roleName,
            'type' => $permission->type, 'deleted_at' => null, 'updated_at' => $now,
        ];
        if ($pivot) {
            DB::table('permission_rols')->where('id', $pivot->id)->update($values);
        } else {
            DB::table('permission_rols')->insert($values + [
                'rol_id' => $roleId, 'permission_id' => $permission->id, 'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // No se eliminan datos de autorización para no revocar accesos de negocio.
    }
};
