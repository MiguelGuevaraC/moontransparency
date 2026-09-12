<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODE = 'calculator.view';

    private const ADMINISTRATOR_ROLES = ['Administrador', 'Administrador Moon'];

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $permission = DB::table('permissions')->where('route', self::CODE)->first();
            $values = [
                'name' => 'Ver calculadora de CO2',
                'route' => self::CODE,
                'type' => 'Calculadora',
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

            $roles = DB::table('rols')
                ->whereIn('name', self::ADMINISTRATOR_ROLES)
                ->whereNull('deleted_at')
                ->get();

            foreach ($roles as $role) {
                $assignment = DB::table('permission_rols')
                    ->where('rol_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->first();
                $assignmentValues = [
                    'name_permission' => $values['name'],
                    'name_rol' => $role->name,
                    'type' => $values['type'],
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];

                if ($assignment) {
                    DB::table('permission_rols')
                        ->where('id', $assignment->id)
                        ->update($assignmentValues);
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
            $permission = DB::table('permissions')->where('route', self::CODE)->first();

            if (! $permission) {
                return;
            }

            DB::table('permission_rols')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        });
    }
};
