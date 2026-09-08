<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedReopening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedReopeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_reopen_a_finalized_survey_with_audit_trail(): void
    {
        Carbon::setTestNow('2026-09-07 09:30:00');
        $completedAt = Carbon::parse('2026-09-06 18:15:00');
        $expectedReopenedAt = now()->utc()->format('Y-m-d\TH:i:s.u\Z');
        $surveyed = $this->createParticipation(Surveyed::STATUS_FINALIZED, $completedAt);
        $administrator = $this->userWithRole('admin-reopen', 'Administrador');
        Sanctum::actingAs($administrator);

        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'Corregir la medición registrada para el día 4.',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $surveyed->id)
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.completed_at', null)
            ->assertJsonPath('data.updated_by', $administrator->id)
            ->assertJsonCount(1, 'data.reopenings')
            ->assertJsonPath('data.reopenings.0.reason', 'Corregir la medición registrada para el día 4.')
            ->assertJsonPath('data.reopenings.0.previous_status', Surveyed::STATUS_FINALIZED)
            ->assertJsonPath('data.reopenings.0.reopened_by', $administrator->id)
            ->assertJsonPath('data.reopenings.0.reopened_at', $expectedReopenedAt);

        $surveyed->refresh();
        $this->assertSame(Surveyed::STATUS_DRAFT, $surveyed->status);
        $this->assertNull($surveyed->completed_at);
        $this->assertSame($administrator->id, $surveyed->updated_by);

        $audit = SurveyedReopening::firstOrFail();
        $this->assertSame($surveyed->id, $audit->surveyed_id);
        $this->assertSame($administrator->id, $audit->reopened_by);
        $this->assertSame($completedAt->toDateTimeString(), $audit->previous_completed_at?->toDateTimeString());
    }

    public function test_reopened_survey_can_be_edited_without_losing_the_audit(): void
    {
        $surveyed = $this->createParticipation(
            Surveyed::STATUS_FINALIZED,
            Carbon::parse('2026-09-06 18:15:00')
        );
        Sanctum::actingAs($this->userWithRole('admin-edit-reopen', 'Administrador Moon'));

        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'Completar un dato observado en la revisión.',
        ])->assertOk();

        $this->postJson("/api/response-survey/{$surveyed->id}", [
            'number_document' => $surveyed->respondent->number_document,
            'names' => 'Nombre corregido',
            'survey_id' => $surveyed->survey_id,
            'responses' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('data.respondent.names', 'Nombre corregido')
            ->assertJsonCount(1, 'data.reopenings');

        $this->assertDatabaseCount('surveyed_reopenings', 1);
        $this->assertDatabaseHas('surveyed_reopenings', [
            'surveyed_id' => $surveyed->id,
            'reason' => 'Completar un dato observado en la revisión.',
        ]);
    }

    public function test_surveyor_cannot_reopen_even_if_the_permission_is_assigned(): void
    {
        $surveyed = $this->createParticipation(
            Surveyed::STATUS_FINALIZED,
            Carbon::parse('2026-09-06 18:15:00')
        );
        $surveyor = $this->userWithRole('surveyor-reopen', 'Encuestador');
        $permission = Permission::where('route', 'participations.reopen')->firstOrFail();
        $surveyor->rol->permissions()->attach($permission->id, [
            'name_permission' => $permission->name,
            'name_rol' => $surveyor->rol->name,
            'type' => $permission->type,
        ]);
        Sanctum::actingAs($surveyor);

        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'Intento no autorizado.',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo un administrador puede reabrir una encuesta finalizada.');

        $this->assertSame(Surveyed::STATUS_FINALIZED, $surveyed->fresh()->status);
        $this->assertDatabaseCount('surveyed_reopenings', 0);
    }

    public function test_refinalization_and_a_second_reopening_preserve_both_audit_events(): void
    {
        Carbon::setTestNow('2026-09-07 10:00:00');
        $surveyed = $this->createParticipation(
            Surveyed::STATUS_FINALIZED,
            Carbon::parse('2026-09-06 18:15:00')
        );
        Sanctum::actingAs($this->userWithRole('admin-repeat-reopen', 'Administrador'));

        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'Primera corrección.',
        ])->assertOk();

        Carbon::setTestNow('2026-09-07 11:00:00');
        $this->postJson("/api/response-survey/{$surveyed->id}/finalize", [
            'number_document' => $surveyed->respondent->number_document,
            'names' => $surveyed->respondent->names,
            'survey_id' => $surveyed->survey_id,
            'responses' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_FINALIZED);

        Carbon::setTestNow('2026-09-07 12:00:00');
        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'Segunda corrección.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT)
            ->assertJsonCount(2, 'data.reopenings')
            ->assertJsonPath('data.reopenings.0.reason', 'Segunda corrección.')
            ->assertJsonPath('data.reopenings.1.reason', 'Primera corrección.');

        $this->assertDatabaseCount('surveyed_reopenings', 2);
        $this->assertSame(
            '2026-09-07 11:00:00',
            SurveyedReopening::latest('id')->firstOrFail()->previous_completed_at?->toDateTimeString()
        );
    }

    public function test_permission_is_required_even_for_an_administrator(): void
    {
        $surveyed = $this->createParticipation(
            Surveyed::STATUS_FINALIZED,
            Carbon::parse('2026-09-06 18:15:00')
        );
        $administrator = $this->userWithRole('admin-without-reopen', 'Administrador');
        $permission = Permission::where('route', 'participations.reopen')->firstOrFail();
        $administrator->rol->permissions()->detach($permission->id);
        Sanctum::actingAs($administrator);

        $this->postJson("/api/surveyed/{$surveyed->id}/reopen", [
            'reason' => 'No debe ejecutarse sin permiso.',
        ])
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'participations.reopen');

        $this->assertDatabaseCount('surveyed_reopenings', 0);
    }

    public function test_reopening_requires_a_reason_and_a_finalized_participation(): void
    {
        $finalized = $this->createParticipation(
            Surveyed::STATUS_FINALIZED,
            Carbon::parse('2026-09-06 18:15:00')
        );
        $draft = $this->createParticipation(Surveyed::STATUS_DRAFT);
        Sanctum::actingAs($this->userWithRole('admin-validation', 'Administrador'));

        $this->postJson("/api/surveyed/{$finalized->id}/reopen", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->postJson("/api/surveyed/{$draft->id}/reopen", [
            'reason' => 'El registro ya era borrador.',
        ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Solo se puede reabrir una encuesta que se encuentre FINALIZADA.');

        $this->postJson('/api/surveyed/999999/reopen', [
            'reason' => 'Registro inexistente.',
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Respuesta de encuesta no encontrada.');

        $this->assertDatabaseCount('surveyed_reopenings', 0);
    }

    private function createParticipation(string $status, ?Carbon $completedAt = null): Surveyed
    {
        $project = Proyect::create(['name' => 'Proyecto reapertura '.uniqid()]);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta reapertura '.uniqid(),
            'status' => 'ACTIVA',
        ]);
        $respondent = Respondent::create([
            'number_document' => 'DOC-'.uniqid(),
            'names' => 'Persona de prueba',
        ]);

        return Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => $status,
            'completed_at' => $completedAt,
        ])->load('respondent');
    }

    private function userWithRole(string $username, string $roleName): User
    {
        return User::create([
            'number_document' => 'DOC-'.strtoupper($username),
            'names' => 'Usuario '.$username,
            'username' => $username,
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', $roleName)->firstOrFail()->id,
        ]);
    }
}
