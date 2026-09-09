<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_catalog_returns_all_permissions_by_default(): void
    {
        Sanctum::actingAs($this->administrator());

        $activePermissionCount = Permission::where('status', Permission::STATUS_ACTIVE)->count();

        $this->getJson('/api/permission')
            ->assertOk()
            ->assertJsonCount($activePermissionCount, 'data')
            ->assertJsonMissingPath('links')
            ->assertJsonFragment([
                'name' => 'Leer roles',
                'code' => 'roles.view',
                'route' => 'roles.view',
                'type' => 'Roles',
                'status' => Permission::STATUS_ACTIVE,
            ]);
    }

    public function test_permission_catalog_can_still_be_paginated_explicitly(): void
    {
        Sanctum::actingAs($this->administrator());

        $this->getJson('/api/permission?all=false&per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    private function administrator(): User
    {
        return User::create([
            'number_document' => 'DOC-PERM-ADMIN',
            'names' => 'Administrador permisos',
            'username' => 'admin-permisos-api',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->value('id'),
        ]);
    }
}
