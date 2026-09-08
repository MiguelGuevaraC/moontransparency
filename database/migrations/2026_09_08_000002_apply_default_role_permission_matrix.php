<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SURVEYOR_PERMISSIONS = [
        'content.view',
        'surveys.view',
        'participations.view',
        'participations.manage',
        'respondents.view',
        'respondents.manage',
    ];

    private const MOON_ADMIN_PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'content.view',
        'content.manage',
        'surveys.view',
        'surveys.manage',
        'participations.view',
        'participations.manage',
        'participations.import',
        'participations.export',
        'participations.reopen',
        'respondents.view',
        'respondents.manage',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $allPermissions = DB::table('permissions')
                ->whereNull('deleted_at')
                ->where('status', 'Activo')
                ->pluck('route')
                ->filter()
                ->values()
                ->all();

            $this->syncRole('Administrador', $allPermissions);
            $this->syncRole('Administrador Moon', self::MOON_ADMIN_PERMISSIONS);
            $this->syncRole('Encuestador', self::SURVEYOR_PERMISSIONS);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $allPermissions = DB::table('permissions')
                ->whereNull('deleted_at')
                ->where('status', 'Activo')
                ->pluck('route')
                ->filter()
                ->values()
                ->all();

            $this->syncRole('Administrador', $allPermissions);
            $this->syncRole('Administrador Moon', $allPermissions);
            $this->syncRole('Encuestador', self::SURVEYOR_PERMISSIONS);
        });
    }

    private function syncRole(string $roleName, array $permissionCodes): void
    {
        $role = DB::table('rols')
            ->where('name', $roleName)
            ->whereNull('deleted_at')
            ->first();

        if (! $role) {
            return;
        }

        $now = now();
        $permissions = DB::table('permissions')
            ->whereIn('route', $permissionCodes)
            ->whereNull('deleted_at')
            ->where('status', 'Activo')
            ->get()
            ->keyBy('id');

        DB::table('permission_rols')
            ->where('rol_id', $role->id)
            ->whereNull('deleted_at')
            ->whereNotIn('permission_id', $permissions->keys()->all())
            ->update(['deleted_at' => $now, 'updated_at' => $now]);

        foreach ($permissions as $permission) {
            $assignment = DB::table('permission_rols')
                ->where('rol_id', $role->id)
                ->where('permission_id', $permission->id)
                ->first();
            $values = [
                'name_permission' => $permission->name,
                'name_rol' => $roleName,
                'type' => $permission->type,
                'deleted_at' => null,
                'updated_at' => $now,
            ];

            if ($assignment) {
                DB::table('permission_rols')->where('id', $assignment->id)->update($values);
            } else {
                DB::table('permission_rols')->insert($values + [
                    'rol_id' => $role->id,
                    'permission_id' => $permission->id,
                    'created_at' => $now,
                ]);
            }
        }
    }
};
