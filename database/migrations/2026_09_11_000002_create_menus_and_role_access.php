<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MODULE_PERMISSIONS = [
        ['name' => 'Ver proyectos', 'route' => 'projects.view', 'type' => 'Proyectos'],
        ['name' => 'Administrar proyectos', 'route' => 'projects.manage', 'type' => 'Proyectos'],
        ['name' => 'Ver aliados', 'route' => 'allies.view', 'type' => 'Aliados'],
        ['name' => 'Administrar aliados', 'route' => 'allies.manage', 'type' => 'Aliados'],
        ['name' => 'Ver ODS', 'route' => 'ods.view', 'type' => 'ODS'],
        ['name' => 'Ver actividades', 'route' => 'activities.view', 'type' => 'Actividades'],
        ['name' => 'Administrar actividades', 'route' => 'activities.manage', 'type' => 'Actividades'],
        ['name' => 'Ver indicadores', 'route' => 'indicators.view', 'type' => 'Indicadores'],
        ['name' => 'Administrar indicadores', 'route' => 'indicators.manage', 'type' => 'Indicadores'],
        ['name' => 'Ver donaciones', 'route' => 'donations.view', 'type' => 'Donaciones'],
        ['name' => 'Administrar donaciones', 'route' => 'donations.manage', 'type' => 'Donaciones'],
        ['name' => 'Ver imágenes', 'route' => 'images.view', 'type' => 'Imágenes'],
        ['name' => 'Administrar imágenes', 'route' => 'images.manage', 'type' => 'Imágenes'],
    ];

    private const MENUS = [
        ['code' => 'projects', 'name' => 'Proyectos', 'path' => '/proyectos', 'icon' => 'account_tree', 'sort_order' => 10, 'permissions' => ['projects.view', 'projects.manage']],
        ['code' => 'users', 'name' => 'Usuarios', 'path' => '/usuarios', 'icon' => 'people', 'sort_order' => 20, 'permissions' => ['users.view', 'users.create', 'users.update', 'users.delete']],
        ['code' => 'roles', 'name' => 'Roles', 'path' => '/roles', 'icon' => 'badge', 'sort_order' => 30, 'permissions' => ['roles.view', 'roles.create', 'roles.update', 'roles.deactivate', 'roles.delete', 'roles.assign_permissions', 'roles.revoke_permissions']],
        ['code' => 'allies', 'name' => 'Aliados', 'path' => '/aliados', 'icon' => 'diversity', 'sort_order' => 40, 'permissions' => ['allies.view', 'allies.manage']],
        ['code' => 'ods', 'name' => 'ODS', 'path' => '/ods', 'icon' => 'dashboard', 'sort_order' => 50, 'permissions' => ['ods.view']],
        ['code' => 'activities', 'name' => 'Actividades', 'path' => '/actividades', 'icon' => 'monitor_heart', 'sort_order' => 60, 'permissions' => ['activities.view', 'activities.manage']],
        ['code' => 'indicators', 'name' => 'Indicadores', 'path' => '/indicadores', 'icon' => 'monitoring', 'sort_order' => 70, 'permissions' => ['indicators.view', 'indicators.manage']],
        ['code' => 'donations', 'name' => 'Donaciones', 'path' => '/donaciones', 'icon' => 'volunteer_activism', 'sort_order' => 80, 'permissions' => ['donations.view', 'donations.manage']],
        ['code' => 'images', 'name' => 'Imágenes', 'path' => '/imagenes', 'icon' => 'image', 'sort_order' => 90, 'permissions' => ['images.view', 'images.manage']],
        ['code' => 'respondents', 'name' => 'Encuestados', 'path' => '/encuestados', 'icon' => 'person_search', 'sort_order' => 100, 'permissions' => ['respondents.view', 'respondents.manage']],
        ['code' => 'surveys', 'name' => 'Encuestas', 'path' => '/encuestas', 'icon' => 'assignment', 'sort_order' => 110, 'permissions' => ['surveys.view', 'surveys.manage', 'surveys.clean_participations']],
        ['code' => 'survey_history', 'name' => 'Historial de Encuestas', 'path' => '/historialencuestas', 'icon' => 'history', 'sort_order' => 120, 'permissions' => ['participations.view', 'participations.manage', 'participations.import', 'participations.export', 'participations.reopen']],
        ['code' => 'calculator', 'name' => 'Calculadora', 'path' => '/calculadora', 'icon' => 'calculate', 'sort_order' => 130, 'permissions' => ['calculator.view']],
    ];

    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('path')->unique();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status')->default('Activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'permission_id']);
        });

        Schema::create('menu_rols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('rols')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_id', 'rol_id']);
        });

        DB::transaction(function () {
            $now = now();

            foreach (self::MODULE_PERMISSIONS as $definition) {
                DB::table('permissions')->updateOrInsert(
                    ['route' => $definition['route']],
                    $definition + ['status' => 'Activo', 'deleted_at' => null, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            $permissionIds = DB::table('permissions')
                ->whereNull('deleted_at')
                ->pluck('id', 'route');

            foreach (self::MENUS as $definition) {
                $permissions = $definition['permissions'];
                unset($definition['permissions']);

                $menuId = DB::table('menus')->insertGetId($definition + [
                    'status' => 'Activo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($permissions as $permissionCode) {
                    if ($permissionIds->has($permissionCode)) {
                        DB::table('menu_permissions')->insert([
                            'menu_id' => $menuId,
                            'permission_id' => $permissionIds[$permissionCode],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            $this->backfillModulePermissions($permissionIds, $now);
            $this->backfillRoleMenus($now);
        });
    }

    private function backfillModulePermissions($permissionIds, $now): void
    {
        $legacyViewId = $permissionIds['content.view'] ?? null;
        $legacyManageId = $permissionIds['content.manage'] ?? null;
        $viewCodes = ['projects.view', 'allies.view', 'ods.view', 'activities.view', 'indicators.view', 'donations.view', 'images.view'];
        $manageCodes = ['projects.manage', 'allies.manage', 'activities.manage', 'indicators.manage', 'donations.manage', 'images.manage'];

        $roles = DB::table('rols')->whereNull('deleted_at')->get();
        foreach ($roles as $role) {
            $assignedIds = DB::table('permission_rols')
                ->where('rol_id', $role->id)
                ->whereNull('deleted_at')
                ->pluck('permission_id');
            $codes = [];

            if ($legacyViewId && $assignedIds->contains($legacyViewId)) {
                $codes = array_merge($codes, $viewCodes);
            }
            if ($legacyManageId && $assignedIds->contains($legacyManageId)) {
                $codes = array_merge($codes, $manageCodes);
            }
            if (in_array($role->name, ['Administrador', 'Administrador Moon'], true)) {
                $codes = $permissionIds->keys()->all();
            }

            foreach (array_unique($codes) as $code) {
                if (! $permissionIds->has($code)) {
                    continue;
                }

                $permission = DB::table('permissions')->where('id', $permissionIds[$code])->first();
                $this->restorePermissionAssignment($role, $permission, $now);
            }
        }
    }

    private function backfillRoleMenus($now): void
    {
        $menus = DB::table('menus')->orderBy('sort_order')->get();
        $roles = DB::table('rols')->whereNull('deleted_at')->get();

        foreach ($roles as $role) {
            $assignedPermissionIds = DB::table('permission_rols')
                ->where('rol_id', $role->id)
                ->whereNull('deleted_at')
                ->pluck('permission_id');

            foreach ($menus as $menu) {
                $menuPermissionIds = DB::table('menu_permissions')
                    ->where('menu_id', $menu->id)
                    ->orderBy('id')
                    ->pluck('permission_id');
                $hasVisibleAccess = $menuPermissionIds->isNotEmpty()
                    && $assignedPermissionIds->contains($menuPermissionIds->first());

                if ($hasVisibleAccess) {
                    DB::table('menu_rols')->insert([
                        'menu_id' => $menu->id,
                        'rol_id' => $role->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function restorePermissionAssignment(object $role, object $permission, $now): void
    {
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
            DB::table('permission_rols')->where('id', $assignment->id)->update($values);
        } else {
            DB::table('permission_rols')->insert($values + [
                'rol_id' => $role->id,
                'permission_id' => $permission->id,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_rols');
        Schema::dropIfExists('menu_permissions');
        Schema::dropIfExists('menus');

        $codes = collect(self::MODULE_PERMISSIONS)->pluck('route')->all();
        $permissionIds = DB::table('permissions')->whereIn('route', $codes)->pluck('id');
        DB::table('permission_rols')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
