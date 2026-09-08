<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION_CODE = 'participations.reopen';

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $permission = DB::table('permissions')
                ->where('route', self::PERMISSION_CODE)
                ->first();
            $values = [
                'name' => 'Reabrir participaciones',
                'route' => self::PERMISSION_CODE,
                'type' => 'Participaciones',
                'status' => 'Activo',
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($permission) {
                DB::table('permissions')->where('id', $permission->id)->update($values);
                $permissionId = $permission->id;
            } else {
                $permissionId = DB::table('permissions')->insertGetId($values + [
                    'created_at' => $now,
                ]);
            }

            foreach (['Administrador', 'Administrador Moon'] as $roleName) {
                $role = DB::table('rols')->where('name', $roleName)->first();

                if (! $role) {
                    continue;
                }

                $assignment = DB::table('permission_rols')
                    ->where('rol_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->first();
                $assignmentValues = [
                    'name_permission' => 'Reabrir participaciones',
                    'name_rol' => $roleName,
                    'type' => 'Participaciones',
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];

                if ($assignment) {
                    DB::table('permission_rols')->where('id', $assignment->id)->update($assignmentValues);
                } else {
                    DB::table('permission_rols')->insert($assignmentValues + [
                        'rol_id' => $role->id,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $permission = DB::table('permissions')
                ->where('route', self::PERMISSION_CODE)
                ->first();

            if (! $permission) {
                return;
            }

            $now = now();
            DB::table('permission_rols')
                ->where('permission_id', $permission->id)
                ->update(['deleted_at' => $now, 'updated_at' => $now]);
            DB::table('permissions')
                ->where('id', $permission->id)
                ->update(['deleted_at' => $now, 'updated_at' => $now]);
        });
    }
};
