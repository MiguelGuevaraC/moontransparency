<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_valid_credentials_and_never_returns_the_password(): void
    {
        $user = $this->createUser('login-activo', User::STATUS_ACTIVE);

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user', 'message'])
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_rejects_an_inactive_user_with_a_generic_error(): void
    {
        $user = $this->createUser('login-inactivo', User::STATUS_INACTIVE);

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'Password!2026',
        ])
            ->assertStatus(422)
            ->assertExactJson(['error' => 'Credenciales inválidas']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_only_an_administrator_can_manage_users(): void
    {
        $operatorRole = Rol::create(['name' => 'Encuestador', 'status' => User::STATUS_ACTIVE]);
        $operator = $this->createUser('encuestador', User::STATUS_ACTIVE, $operatorRole);
        Sanctum::actingAs($operator);

        $this->getJson('/api/user?all=true')->assertForbidden();
    }

    public function test_administrator_can_list_create_show_update_activate_deactivate_and_soft_delete_users(): void
    {
        $role = $this->administratorRole();
        $administrator = $this->createUser('admin-gestion', User::STATUS_ACTIVE, $role);
        Sanctum::actingAs($administrator);

        $createdResponse = $this->postJson('/api/user', [
            'type_document' => 'DNI',
            'number_document' => '77889911',
            'names' => 'Usuario gestionado',
            'username' => 'usuario.gestionado',
            'password' => 'ClaveSegura!2026',
            'email' => 'gestionado@example.test',
            'rol_id' => $role->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', User::STATUS_ACTIVE)
            ->assertJsonMissingPath('data.password');

        $userId = (int) $createdResponse->json('data.id');
        $created = User::findOrFail($userId);
        $this->assertTrue(Hash::check('ClaveSegura!2026', $created->password));
        $this->assertNotSame('ClaveSegura!2026', $created->password);

        $this->getJson('/api/user?all=true&username=usuario.gestionado')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $userId)
            ->assertJsonMissingPath('0.password');

        $this->getJson("/api/user/$userId")
            ->assertOk()
            ->assertJsonPath('data.names', 'Usuario gestionado')
            ->assertJsonMissingPath('data.password');

        $this->putJson("/api/user/$userId", ['names' => 'Usuario actualizado'])
            ->assertOk()
            ->assertJsonPath('data.names', 'Usuario actualizado');

        $this->patchJson("/api/user/$userId/deactivate")
            ->assertOk()
            ->assertJsonPath('data.status', User::STATUS_INACTIVE);

        $this->postJson('/api/login', [
            'username' => 'usuario.gestionado',
            'password' => 'ClaveSegura!2026',
        ])->assertStatus(422);

        $this->patchJson("/api/user/$userId/activate")
            ->assertOk()
            ->assertJsonPath('data.status', User::STATUS_ACTIVE);

        $this->deleteJson("/api/user/$userId")
            ->assertOk()
            ->assertJsonPath('message', 'Usuario eliminado lógicamente.');

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNull(User::find($userId));
    }

    public function test_administrator_cannot_deactivate_or_delete_own_account(): void
    {
        $administrator = $this->createUser(
            'admin-protegido',
            User::STATUS_ACTIVE,
            $this->administratorRole()
        );
        Sanctum::actingAs($administrator);

        $this->patchJson("/api/user/{$administrator->id}/deactivate")->assertStatus(422);
        $this->putJson("/api/user/{$administrator->id}", [
            'status' => User::STATUS_INACTIVE,
        ])->assertStatus(422);
        $this->deleteJson("/api/user/{$administrator->id}")->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'status' => User::STATUS_ACTIVE,
            'deleted_at' => null,
        ]);
    }

    public function test_authenticated_administrative_flow_records_who_created_and_updated_a_participation(): void
    {
        $role = $this->administratorRole();
        $creator = $this->createUser('admin-creador', User::STATUS_ACTIVE, $role);
        $editor = $this->createUser('admin-editor', User::STATUS_ACTIVE, $role);
        $project = Proyect::create(['name' => 'Proyecto auditado']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta auditada',
            'status' => 'ACTIVA',
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Dato auditable',
            'question_type' => 'LIBRE',
            'is_required' => false,
        ]);

        Sanctum::actingAs($creator);
        $created = $this->postJson('/api/response-survey', [
            'number_document' => 'AUDIT-001',
            'names' => 'Persona auditada',
            'survey_id' => $survey->id,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => 'Primera respuesta',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.created_by', $creator->id)
            ->assertJsonPath('data.updated_by', $creator->id);

        $surveyedId = (int) $created->json('data.id');

        Sanctum::actingAs($editor);
        $this->postJson("/api/response-survey/$surveyedId", [
            'number_document' => 'AUDIT-001',
            'names' => 'Persona auditada',
            'survey_id' => $survey->id,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => 'Respuesta actualizada',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.created_by', $creator->id)
            ->assertJsonPath('data.updated_by', $editor->id)
            ->assertJsonPath('data.created_by_user.id', $creator->id)
            ->assertJsonPath('data.updated_by_user.id', $editor->id);

        $this->assertDatabaseHas('surveyeds', [
            'id' => $surveyedId,
            'created_by' => $creator->id,
            'updated_by' => $editor->id,
        ]);
    }

    private function administratorRole(): Rol
    {
        return Rol::firstOrCreate(
            ['name' => 'Administrador'],
            ['status' => User::STATUS_ACTIVE]
        );
    }

    private function createUser(string $username, string $status, ?Rol $role = null): User
    {
        return User::create([
            'number_document' => 'DOC-'.strtoupper($username),
            'names' => 'Usuario '.$username,
            'username' => $username,
            'password' => 'Password!2026',
            'status' => $status,
            'rol_id' => $role?->id,
        ]);
    }
}
