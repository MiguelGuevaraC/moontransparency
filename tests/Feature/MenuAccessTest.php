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
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.code', 'home')
            ->assertJsonPath('data.0.name', 'Inicio')
            ->assertJsonPath('data.1.code', 'users_roles')
            ->assertJsonPath('data.1.name', 'Usuarios y roles')
            ->assertJsonPath('data.4.code', 'survey_history')
            ->assertJsonMissing(['code' => 'calculator'])
            ->assertJsonMissing(['code' => 'allies']);

        $this->assertArrayNotHasKey('permissions', $response->json('data.4'));
    }

    public function test_login_returns_the_role_menus_for_dynamic_navigation(): void
    {
        $user = $this->userWithRole('admin-menu-login', 'Administrador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.menu_codes.0', 'home')
            ->assertJsonPath('user.menu_codes.1', 'users_roles')
            ->assertJsonFragment([
                'code' => 'survey_history',
                'name' => 'Historial de Encuestas',
            ])
            ->assertJsonMissing(['code' => 'calculator']);
    }

    public function test_public_platform_exposes_the_new_name_and_technological_tools(): void
    {
        config([
            'app.uuid' => 'public-platform-key',
            'co2.calculator_public_url' => 'https://develop.garzasoft.com/moontransparency/public',
            'platform.sustainability_dashboard_url' => 'https://app.powerbi.com/dashboard',
        ]);

        $this->withHeader('UUID', 'public-platform-key')
            ->getJson('/api/platform/public')
            ->assertOk()
            ->assertJsonPath('data.name', 'Portal de Impacto Moon Group')
            ->assertJsonPath(
                'data.navigation.2.children.0.url',
                'https://app.powerbi.com/dashboard'
            )
            ->assertJsonPath(
                'data.navigation.2.children.1.embed_link_endpoint',
                'https://develop.garzasoft.com/moontransparency/public/api/calculator/co2/embed-link'
            )
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
