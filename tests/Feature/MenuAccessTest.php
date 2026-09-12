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
            ->assertJsonCount(13, 'data')
            ->assertJsonPath('data.0.code', 'projects')
            ->assertJsonPath('data.11.code', 'survey_history')
            ->assertJsonPath('data.12.code', 'calculator')
            ->assertJsonPath('data.12.path', '/calculadora');

        $this->assertArrayNotHasKey('permissions', $response->json('data.12'));
    }

    public function test_login_returns_the_role_menus_for_dynamic_navigation(): void
    {
        $user = $this->userWithRole('admin-menu-login', 'Administrador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.menu_codes.12', 'calculator')
            ->assertJsonFragment([
                'code' => 'calculator',
                'name' => 'Calculadora',
                'path' => '/calculadora',
            ]);
    }

    public function test_assigning_menus_to_a_role_synchronizes_its_internal_permissions(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-menu-sync', 'Administrador'));
        $role = Rol::create(['name' => 'Analista', 'status' => Rol::STATUS_ACTIVE]);
        $history = Menu::where('code', 'survey_history')->firstOrFail();
        $calculator = Menu::where('code', 'calculator')->firstOrFail();

        $this->putJson("/api/rol/{$role->id}/menus", [
            'menus' => [$history->id, $calculator->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.menu_codes.0', 'survey_history')
            ->assertJsonPath('data.menu_codes.1', 'calculator')
            ->assertJsonFragment(['calculator.view']);

        $this->assertDatabaseHas('menu_rols', ['rol_id' => $role->id, 'menu_id' => $history->id]);
        $this->assertDatabaseHas('menu_rols', ['rol_id' => $role->id, 'menu_id' => $calculator->id]);
        $this->assertTrue($role->fresh()->permissions()->where('route', 'participations.view')->exists());
        $this->assertTrue($role->fresh()->permissions()->where('route', 'calculator.view')->exists());

        $this->putJson("/api/rol/{$role->id}/menus", [
            'menus' => [$history->id],
        ])->assertOk()->assertJsonMissing(['calculator.view']);

        $this->assertDatabaseMissing('menu_rols', ['rol_id' => $role->id, 'menu_id' => $calculator->id]);
        $calculatorPermission = Permission::where('route', 'calculator.view')->firstOrFail();
        $this->assertSoftDeleted('permission_rols', [
            'rol_id' => $role->id,
            'permission_id' => $calculatorPermission->id,
        ]);
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
