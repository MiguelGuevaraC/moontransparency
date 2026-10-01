<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MenuAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_catalog_contains_only_visible_system_modules_in_display_order(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-catalog', 'Administrador'));

        $response = $this->getJson('/api/menu')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.code', 'users_roles')
            ->assertJsonPath('data.0.name', 'Usuarios y roles')
            ->assertJsonPath('data.3.code', 'survey_history')
            ->assertJsonMissing(['code' => 'home'])
            ->assertJsonMissing(['code' => 'calculator'])
            ->assertJsonMissing(['code' => 'allies']);

        $this->assertArrayNotHasKey('permissions', $response->json('data.3'));
    }

    public function test_login_returns_the_role_menus_for_dynamic_navigation(): void
    {
        $user = $this->userWithRole('admin-menu-login', 'Administrador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.menu_codes.0', 'users_roles')
            ->assertJsonMissing(['code' => 'home'])
            ->assertJsonFragment([
                'code' => 'survey_history',
                'name' => 'Historial de Encuestas',
            ])
            ->assertJsonMissing(['code' => 'calculator']);
    }

    public function test_login_keeps_legacy_users_and_roles_menus_for_current_frontend(): void
    {
        $user = $this->userWithRole('admin-menu-legacy', 'Administrador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.menu_codes', ['users_roles', 'users', 'roles', 'respondents', 'surveys', 'survey_history'])
            ->assertJsonPath('user.rol.menu_codes', ['users_roles', 'users', 'roles', 'respondents', 'surveys', 'survey_history'])
            ->assertJsonPath('user.menus.1.path', '/usuarios')
            ->assertJsonPath('user.menus.1.status', Menu::STATUS_ACTIVE)
            ->assertJsonPath('user.menus.2.path', '/roles');

        Sanctum::actingAs($user);

        $this->getJson('/api/authenticate')
            ->assertOk()
            ->assertJsonPath('user.menu_codes', ['users_roles', 'users', 'roles', 'respondents', 'surveys', 'survey_history']);

        $this->getJson('/api/rol/'.$user->rol_id)
            ->assertOk()
            ->assertJsonPath('data.menu_codes', ['users_roles', 'respondents', 'surveys', 'survey_history']);

        $this->assertDatabaseHas('menus', ['code' => 'users', 'status' => Menu::STATUS_INACTIVE]);
    }

    public function test_login_does_not_add_legacy_menus_without_users_roles_access(): void
    {
        $user = $this->userWithRole('surveyor-menu-legacy', 'Encuestador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.menu_codes', ['respondents', 'surveys', 'survey_history']);
    }

    public function test_public_platform_exposes_the_new_name_and_technological_tools(): void
    {
        config([
            'app.uuid' => 'public-platform-key',
            'co2.calculator_public_url' => 'https://develop.garzasoft.com/moontransparency/public',
        ]);

        $this->withHeader('UUID', 'public-platform-key')
            ->getJson('/api/platform/public')
            ->assertOk()
            ->assertJsonPath('data.name', 'Portal de Impacto Moon Group')
            ->assertJsonCount(1, 'data.navigation')
            ->assertJsonPath('data.navigation.0.name', 'Herramientas Digitales')
            ->assertJsonPath('data.navigation.0.children.0.type', 'external')
            ->assertJsonMissingPath('data.navigation.0.children.0.url')
            ->assertJsonPath('data.navigation.0.children.0.tagline', 'Información que impulsa decisiones sostenibles')
            ->assertJsonPath(
                'data.navigation.0.children.1.embed_link_endpoint',
                'https://develop.garzasoft.com/moontransparency/public/api/calculator/co2/embed-link'
            )
            ->assertJsonPath('data.navigation.0.children.1.tagline', 'Medir para reducir nuestra huella')
            ->assertJsonPath('data.navigation.0.children.2.code', 'forest_pressure')
            ->assertJsonPath('data.navigation.0.children.2.name', 'Presión sobre el Bosque')
            ->assertJsonPath('data.navigation.0.children.2.tagline', 'Comprender para conservar')
            ->assertJsonPath('data.navigation.0.children.2.type', 'iframe')
            ->assertJsonMissing(['code' => 'projects'])
            ->assertJsonMissing(['code' => 'contact'])
            ->assertJsonMissing(['code' => 'allies'])
            ->assertJsonMissing(['code' => 'surveys']);
    }

    public function test_assigning_menus_to_a_role_synchronizes_its_internal_permissions(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-sync', 'Administrador'));
        $role = Rol::create(['name' => 'Analista', 'status' => Rol::STATUS_ACTIVE]);
        $history = Menu::where('code', 'survey_history')->firstOrFail();
        $usersRoles = Menu::where('code', 'users_roles')->firstOrFail();

        $this->putJson("/api/rol/{$role->id}/menus", [
            'menus' => [$usersRoles->id, $history->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.menu_codes.0', 'users_roles')
            ->assertJsonPath('data.menu_codes.1', 'survey_history')
            ->assertJsonFragment(['users.view'])
            ->assertJsonFragment(['roles.view']);

        $this->assertDatabaseHas('menu_rols', ['rol_id' => $role->id, 'menu_id' => $history->id]);
        $this->assertDatabaseHas('menu_rols', ['rol_id' => $role->id, 'menu_id' => $usersRoles->id]);
        $this->assertTrue($role->fresh()->permissions()->where('route', 'participations.view')->exists());
        $this->assertTrue($role->fresh()->permissions()->where('route', 'roles.view')->exists());

        $this->putJson("/api/rol/{$role->id}/menus", [
            'menus' => [$history->id],
        ])->assertOk()->assertJsonMissing(['roles.view']);

        $this->assertDatabaseMissing('menu_rols', ['rol_id' => $role->id, 'menu_id' => $usersRoles->id]);
        $rolesPermission = Permission::where('route', 'roles.view')->firstOrFail();
        $this->assertSoftDeleted('permission_rols', [
            'rol_id' => $role->id,
            'permission_id' => $rolesPermission->id,
        ]);
    }

    public function test_surveyor_cannot_receive_excel_permissions_through_menu_assignment(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-surveyor', 'Administrador'));
        $surveyor = Rol::where('name', 'Encuestador')->firstOrFail();
        $history = Menu::where('code', 'survey_history')->firstOrFail();

        $this->putJson("/api/rol/{$surveyor->id}/menus", [
            'menus' => [$history->id],
        ])->assertOk()
            ->assertJsonFragment(['participations.view'])
            ->assertJsonMissing(['participations.import'])
            ->assertJsonMissing(['participations.export']);

        $this->assertFalse($surveyor->fresh()->permissions()->whereIn('route', [
            'participations.import',
            'participations.export',
        ])->exists());
    }

    public function test_admin_roles_cannot_lose_users_and_roles_access(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-guard', 'Administrador'));
        $history = Menu::where('code', 'survey_history')->firstOrFail();
        $rolesView = Permission::where('route', 'roles.view')->firstOrFail();
        $surveysView = Permission::where('route', 'surveys.view')->firstOrFail();

        foreach (Rol::whereIn('name', ['Administrador', 'Administrador Moon'])->get() as $admin) {
            $this->putJson("/api/rol/{$admin->id}/menus", ['menus' => [$history->id]])
                ->assertUnprocessable()
                ->assertJsonPath('errors.menus.0', 'Los roles Administrador deben conservar el acceso a Usuarios y roles.');

            $this->putJson("/api/rol/{$admin->id}/setaccess", ['access' => [$surveysView->id]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('permissions');

            $this->deleteJson("/api/rol/{$admin->id}/permissions/{$rolesView->id}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('permissions');

            $this->assertTrue($admin->fresh()->menus()->where('code', 'users_roles')->exists());
            $this->assertTrue($admin->fresh()->permissions()->where('route', 'roles.view')->exists());
            $this->assertTrue($admin->fresh()->permissions()->where('route', 'users.create')->exists());
        }

        $admin = Rol::where('name', 'Administrador')->firstOrFail();
        $usersRoles = Menu::where('code', 'users_roles')->firstOrFail();

        $this->putJson("/api/rol/{$admin->id}/menus", ['menus' => [$usersRoles->id, $history->id]])
            ->assertOk()
            ->assertJsonPath('data.menu_codes', ['users_roles', 'survey_history']);

        $this->deleteJson("/api/rol/{$admin->id}/permissions/{$surveysView->id}")
            ->assertOk();
    }

    public function test_menu_assignment_rejects_unknown_menu_ids(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-validation', 'Administrador'));
        $role = Rol::create(['name' => 'Auditor de menú', 'status' => Rol::STATUS_ACTIVE]);

        $this->putJson("/api/rol/{$role->id}/menus", ['menus' => [999999]])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    private function userWithRole(string $username, string $roleName): User
    {
        return User::create([
            'number_document' => 'DOC-'.strtoupper($username),
            'names' => 'Usuario '.$username,
            'username' => $username,
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', $roleName)->value('id'),
        ]);
    }
}
