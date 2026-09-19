<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const HIDDEN_MENU_CODES = [
        'users',
        'roles',
        'allies',
        'ods',
        'activities',
        'indicators',
        'donations',
        'images',
        'calculator',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            DB::table('menus')->where('code', 'projects')->update([
                'code' => 'home',
                'name' => 'Inicio',
                'path' => '/inicio',
                'icon' => 'home',
                'sort_order' => 10,
                'status' => 'Activo',
                'updated_at' => $now,
            ]);

            $combinedMenuId = DB::table('menus')->insertGetId([
                'code' => 'users_roles',
                'name' => 'Usuarios y roles',
                'path' => '/usuarios-roles',
                'icon' => 'manage_accounts',
                'sort_order' => 20,
                'status' => 'Activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sourceMenuIds = DB::table('menus')
                ->whereIn('code', ['users', 'roles'])
                ->pluck('id');
            $permissionIds = DB::table('menu_permissions')
                ->whereIn('menu_id', $sourceMenuIds)
                ->distinct()
                ->pluck('permission_id');
            $roleIds = DB::table('menu_rols')
                ->whereIn('menu_id', $sourceMenuIds)
                ->distinct()
                ->pluck('rol_id');

            foreach ($permissionIds as $permissionId) {
                DB::table('menu_permissions')->insertOrIgnore([
                    'menu_id' => $combinedMenuId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach ($roleIds as $roleId) {
                DB::table('menu_rols')->insertOrIgnore([
                    'menu_id' => $combinedMenuId,
                    'rol_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('menus')->whereIn('code', self::HIDDEN_MENU_CODES)->update([
                'status' => 'Inactivo',
                'updated_at' => $now,
            ]);

            DB::table('menus')->where('code', 'respondents')->update(['sort_order' => 30, 'updated_at' => $now]);
            DB::table('menus')->where('code', 'surveys')->update(['sort_order' => 40, 'updated_at' => $now]);
            DB::table('menus')->where('code', 'survey_history')->update(['sort_order' => 50, 'updated_at' => $now]);

            $forbiddenPermissionIds = DB::table('permissions')
                ->whereIn('route', ['participations.import', 'participations.export'])
                ->pluck('id');
            $surveyorRoleIds = DB::table('rols')
                ->where('name', 'Encuestador')
                ->pluck('id');

            DB::table('permission_rols')
                ->whereIn('rol_id', $surveyorRoleIds)
                ->whereIn('permission_id', $forbiddenPermissionIds)
                ->update(['deleted_at' => $now, 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $now = now();
            $combinedMenu = DB::table('menus')->where('code', 'users_roles')->first();

            if ($combinedMenu) {
                DB::table('menu_rols')->where('menu_id', $combinedMenu->id)->delete();
                DB::table('menu_permissions')->where('menu_id', $combinedMenu->id)->delete();
                DB::table('menus')->where('id', $combinedMenu->id)->delete();
            }

            DB::table('menus')->where('code', 'home')->update([
                'code' => 'projects',
                'name' => 'Proyectos',
                'path' => '/proyectos',
                'icon' => 'account_tree',
                'sort_order' => 10,
                'updated_at' => $now,
            ]);
            DB::table('menus')->whereIn('code', self::HIDDEN_MENU_CODES)->update([
                'status' => 'Activo',
                'updated_at' => $now,
            ]);
            DB::table('menus')->where('code', 'respondents')->update(['sort_order' => 100, 'updated_at' => $now]);
            DB::table('menus')->where('code', 'surveys')->update(['sort_order' => 110, 'updated_at' => $now]);
            DB::table('menus')->where('code', 'survey_history')->update(['sort_order' => 120, 'updated_at' => $now]);
        });
    }
};
