<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODE = 'surveys.clean_participations';

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $permission = DB::table('permissions')->where('route', self::CODE)->first();
            $values = [
                'name' => 'Limpiar participaciones de encuestas',
                'route' => self::CODE,
                'type' => 'Encuestas',
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

            $administrator = DB::table('rols')
                ->where('name', 'Administrador')
                ->whereNull('deleted_at')
                ->first();

            if (! $administrator) {
                return;
            }

            $assignment = DB::table('permission_rols')
                ->where('rol_id', $administrator->id)
                ->where('permission_id', $permissionId)
                ->first();
            $assignmentValues = [
                'name_permission' => $values['name'],
                'name_rol' => $administrator->name,
                'type' => $values['type'],
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($assignment) {
                DB::table('permission_rols')->where('id', $assignment->id)->update($assignmentValues);
            } else {
                DB::table('permission_rols')->insert($assignmentValues + [
                    'rol_id' => $administrator->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $permission = DB::table('permissions')->where('route', self::CODE)->first();

            if (! $permission) {
                return;
            }

            DB::table('permission_rols')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        });
    }
};
