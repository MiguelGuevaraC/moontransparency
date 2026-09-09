<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ADMINISTRATOR_ROLES = ['Administrador', 'Administrador Moon'];

    public function up(): void
    {
        DB::transaction(function () {
            $roles = DB::table('rols')
                ->whereIn('name', self::ADMINISTRATOR_ROLES)
                ->whereNull('deleted_at')
                ->get();
            $permissions = DB::table('permissions')
                ->whereNull('deleted_at')
                ->where('status', 'Activo')
                ->get();
            $now = now();

            foreach ($roles as $role) {
                foreach ($permissions as $permission) {
                    $assignment = DB::table('permission_rols')
                        ->where('rol_id', $role->id)
                        ->where('permission_id', $permission->id)
                        ->first();
                    $values = [
                        'name_permission' => $permission->name,
                        'name_rol' => $role->name,
                        'type' => $permission->type,
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ];

                    if ($assignment) {
                        DB::table('permission_rols')
                            ->where('id', $assignment->id)
                            ->update($values);
                    } else {
                        DB::table('permission_rols')->insert($values + [
                            'rol_id' => $role->id,
                            'permission_id' => $permission->id,
                            'created_at' => $now,
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // No se revocan permisos administrativos durante un rollback para evitar
        // eliminar asignaciones que pudieron existir antes de esta migración.
    }
};
