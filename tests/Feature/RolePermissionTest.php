<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimum_profiles_and_permission_catalog_are_available(): void
    {
        $this->assertDatabaseHas('rols', ['name' => 'Administrador', 'status' => Rol::STATUS_ACTIVE]);
        $this->assertDatabaseHas('rols', ['name' => 'Encuestador', 'status' => Rol::STATUS_ACTIVE]);
        $this->assertDatabaseHas('permissions', ['route' => 'users.view', 'status' => Permission::STATUS_ACTIVE]);
        $this->assertDatabaseHas('permissions', ['route' => 'roles.assign_permissions', 'status' => Permission::STATUS_ACTIVE]);
        $this->assertDatabaseHas('permissions', ['route' => 'participations.reopen', 'status' => Permission::STATUS_ACTIVE]);
        $this->assertDatabaseHas('permissions', ['route' => 'surveys.clean_participations', 'status' => Permission::STATUS_ACTIVE]);

        $administrator = Rol::where('name', 'Administrador')->firstOrFail();
        $moonAdministrator = Rol::where('name', 'Administrador Moon')->firstOrFail();
        $surveyor = Rol::where('name', 'Encuestador')->firstOrFail();
        $activePermissionCount = Permission::where('status', Permission::STATUS_ACTIVE)->count();
        $this->assertCount($activePermissionCount, $administrator->permissions);
        $this->assertCount($activePermissionCount, $moonAdministrator->permissions);
        $this->assertCount(6, $surveyor->permissions);
        $this->assertTrue($administrator->permissions()->where('route', 'users.view')->exists());
        $this->assertTrue($administrator->permissions()->where('route', 'participations.reopen')->exists());
        $this->assertTrue($administrator->permissions()->where('route', 'surveys.clean_participations')->exists());
        $this->assertTrue($moonAdministrator->permissions()->where('route', 'users.delete')->exists());
        $this->assertTrue($moonAdministrator->permissions()->where('route', 'participations.reopen')->exists());
        $this->assertTrue($moonAdministrator->permissions()->where('route', 'roles.assign_permissions')->exists());
        $this->assertTrue($moonAdministrator->permissions()->where('route', 'surveys.clean_participations')->exists());
        $this->assertFalse($surveyor->permissions()->where('route', 'users.view')->exists());
        $this->assertFalse($surveyor->permissions()->where('route', 'participations.reopen')->exists());
        $this->assertFalse($surveyor->permissions()->where('route', 'surveys.clean_participations')->exists());
        $this->assertTrue($surveyor->permissions()->where('route', 'participations.manage')->exists());
    }

    public function test_administrator_can_list_create_edit_deactivate_and_activate_roles(): void
    {
        Sanctum::actingAs($this->userWithRole('admin-roles', 'Administrador'));

        $this->getJson('/api/rol?all=true')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Administrador'])
            ->assertJsonFragment(['name' => 'Encuestador']);

        $created = $this->postJson('/api/rol', ['name' => 'Auditor'])
            ->assertCreated()
            ->assertJsonPath('data.status', Rol::STATUS_ACTIVE);
        $roleId = (int) $created->json('data.id');

        $this->putJson("/api/rol/$roleId", ['name' => 'Auditor ambiental'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Auditor ambiental');
        $this->patchJson("/api/rol/$roleId/deactivate")
            ->assertOk()
            ->assertJsonPath('data.status', Rol::STATUS_INACTIVE);
        $this->patchJson("/api/rol/$roleId/activate")
            ->assertOk()
            ->assertJsonPath('data.status', Rol::STATUS_ACTIVE);
    }

    public function test_permissions_are_assigned_and_enforced_instead_of_role_name(): void
    {
        $administrator = $this->userWithRole('admin-permisos', 'Administrador');
        $surveyor = $this->userWithRole('encuestador-permisos', 'Encuestador');
        $surveyorRole = $surveyor->rol;
        $viewUsers = Permission::where('route', 'users.view')->firstOrFail();

        Sanctum::actingAs($surveyor);
        $this->getJson('/api/user?all=true')
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'users.view');

        Sanctum::actingAs($administrator);
        $this->postJson("/api/rol/{$surveyorRole->id}/permissions", [
            'access' => [$viewUsers->id],
        ])
            ->assertOk()
            ->assertJsonFragment(['code' => 'users.view']);

        Sanctum::actingAs($surveyor);
        $this->getJson('/api/user?all=true')->assertOk();
        $this->postJson('/api/user', [])->assertForbidden();

        Sanctum::actingAs($administrator);
        $this->deleteJson("/api/rol/{$surveyorRole->id}/permissions/{$viewUsers->id}")
            ->assertOk();

        Sanctum::actingAs($surveyor);
        $this->getJson('/api/user?all=true')->assertForbidden();
    }

    public function test_surveyor_can_use_survey_flow_but_cannot_manage_roles(): void
    {
        $surveyor = $this->userWithRole('encuestador-flujo', 'Encuestador');
        Sanctum::actingAs($surveyor);

        $this->getJson('/api/survey?all=true')->assertOk();
        $this->getJson('/api/surveyed?all=true')->assertOk();
        $this->getJson('/api/rol?all=true')->assertForbidden();
        $this->postJson('/api/rol', ['name' => 'Rol no autorizado'])->assertForbidden();
    }

    public function test_login_returns_permission_codes_for_frontend_authorization(): void
    {
        $user = $this->userWithRole('login-permisos', 'Encuestador');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.rol.name', 'Encuestador')
            ->assertJsonFragment(['participations.manage'])
            ->assertJsonMissing(['users.view']);
    }

    public function test_inactive_roles_cannot_authorize_endpoints(): void
    {
        $role = Rol::create(['name' => 'Temporal', 'status' => Rol::STATUS_ACTIVE]);
        $permission = Permission::where('route', 'users.view')->firstOrFail();
        $role->permissions()->attach($permission->id, [
            'name_permission' => $permission->name,
            'name_rol' => $role->name,
            'type' => $permission->type,
        ]);
        $user = $this->createUser('rol-inactivo', $role);
        $role->update(['status' => Rol::STATUS_INACTIVE]);

        Sanctum::actingAs($user);
        $this->getJson('/api/user?all=true')->assertForbidden();
    }

    private function userWithRole(string $username, string $roleName): User
    {
        return $this->createUser($username, Rol::where('name', $roleName)->firstOrFail());
    }

    private function createUser(string $username, Rol $role): User
    {
        return User::create([
            'number_document' => 'DOC-'.strtoupper($username),
            'names' => 'Usuario '.$username,
            'username' => $username,
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
