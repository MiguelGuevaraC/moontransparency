<?php

namespace Tests\Feature;

use App\Http\Controllers\ApiExternaController;
use App\Http\Resources\UserResource;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_resources_never_expose_passwords_or_tokens(): void
    {
        $user = $this->user();
        $payload = (new UserResource($user))->resolve();

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
        $this->assertArrayNotHasKey('token', $payload);
    }

    public function test_authenticate_does_not_echo_the_bearer_token(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->withToken('sensitive-input-token')
            ->getJson('/api/authenticate')
            ->assertOk()
            ->assertJsonMissing(['token' => 'sensitive-input-token']);
    }

    public function test_external_identity_service_fails_closed_when_credentials_are_missing(): void
    {
        config([
            'services.search_identity.dni.url' => null,
            'services.search_identity.dni.token' => null,
        ]);

        $response = app(ApiExternaController::class)->searchByDni('12345678');

        $this->assertSame(503, $response->status());
        $this->assertStringNotContainsString('token', $response->getContent());
    }

    public function test_cors_does_not_allow_every_origin_by_default(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }

    private function user(): User
    {
        return User::create([
            'number_document' => 'DOC-SECURITY-001',
            'username' => 'security-user',
            'password' => 'Password!2026',
            'remember_token' => 'hidden-token',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->firstOrFail()->id,
        ]);
    }
}
