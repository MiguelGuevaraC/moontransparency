<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\SurveyCleanupAudit;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_back_up_and_clean_participations_explicitly(): void
    {
        Storage::fake('local');
        [$survey, $surveyed] = $this->createSurveyWithParticipation();
        $this->actingAsRole('Administrador');

        $response = $this->postJson("/api/survey/{$survey->id}/clean-participations", [
            'reason' => 'Se requiere corregir y volver a cargar el instrumento oficial.',
            'confirmation' => "LIMPIAR ENCUESTA {$survey->id}",
        ])->assertOk()
            ->assertJsonPath('data.survey_id', $survey->id)
            ->assertJsonPath('data.deleted_counts.participations', 1)
            ->assertJsonPath('data.deleted_counts.responses', 1);

        $audit = SurveyCleanupAudit::findOrFail($response->json('data.audit_id'));
        Storage::disk('local')->assertExists($audit->backup_path);
        $backup = json_decode(
            gzdecode(Storage::disk('local')->get($audit->backup_path)),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('survey-cleanup-backup/1.0', $backup['contract']);
        $this->assertSame($surveyed->id, $backup['participations'][0]['id']);
        $this->assertDatabaseMissing('surveyeds', ['id' => $surveyed->id]);
        $this->assertDatabaseMissing('surveyed_responses', ['surveyed_id' => $surveyed->id]);
        $this->assertDatabaseMissing('surveyed_measurements', ['surveyed_id' => $surveyed->id]);

        $this->putJson("/api/survey/{$survey->id}", $this->surveyPayload($survey, Survey::STATUS_INACTIVE))
            ->assertOk()
            ->assertJsonPath('data.status', Survey::STATUS_INACTIVE);
    }

    public function test_cleanup_requires_the_exact_confirmation_and_preserves_data_on_validation_error(): void
    {
        [$survey, $surveyed] = $this->createSurveyWithParticipation();
        $this->actingAsRole('Administrador');

        $this->postJson("/api/survey/{$survey->id}/clean-participations", [
            'reason' => 'Se solicita una limpieza controlada.',
            'confirmation' => 'CONFIRMAR',
        ])->assertUnprocessable()->assertJsonValidationErrors('confirmation');

        $this->assertDatabaseHas('surveyeds', ['id' => $surveyed->id]);
        $this->assertDatabaseCount('survey_cleanup_audits', 0);
    }

    public function test_moon_administrator_can_use_the_cleanup_permission(): void
    {
        Storage::fake('local');
        [$survey, $surveyed] = $this->createSurveyWithParticipation();
        $this->actingAsRole('Administrador Moon');

        $this->postJson("/api/survey/{$survey->id}/clean-participations", [
            'reason' => 'Limpieza autorizada para el administrador Moon.',
            'confirmation' => "LIMPIAR ENCUESTA {$survey->id}",
        ])->assertOk()
            ->assertJsonPath('data.survey_id', $survey->id);

        $this->assertDatabaseMissing('surveyeds', ['id' => $surveyed->id]);
    }

    public function test_cleanup_returns_a_conflict_when_there_is_nothing_to_clean(): void
    {
        $project = Proyect::create(['name' => 'Proyecto vacío']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta sin datos',
            'survey_type' => 'PRE',
            'description' => 'Sin participaciones.',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $this->actingAsRole('Administrador');

        $this->postJson("/api/survey/{$survey->id}/clean-participations", [
            'reason' => 'Se comprueba una limpieza sin datos.',
            'confirmation' => "LIMPIAR ENCUESTA {$survey->id}",
        ])->assertConflict();

        $this->assertDatabaseCount('survey_cleanup_audits', 0);
    }

    private function createSurveyWithParticipation(): array
    {
        $project = Proyect::create(['name' => 'Proyecto limpieza']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta para limpiar '.uniqid(),
            'survey_type' => 'PRE',
            'description' => 'Datos que requieren limpieza explícita.',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Respuesta que será respaldada',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => false,
            'eje' => 'Auditoría',
            'justification' => '-',
        ]);
        $respondent = Respondent::create([
            'number_document' => 'CLN'.random_int(10000000, 99999999),
            'names' => 'Persona respaldada',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        $measurement = SurveyedMeasurement::create([
            'surveyed_id' => $surveyed->id,
            'day_number' => 1,
        ]);
        SurveyedResponse::create([
            'surveyed_id' => $surveyed->id,
            'surveyed_measurement_id' => $measurement->id,
            'respondent_id' => $respondent->id,
            'survey_question_id' => $question->id,
            'response_text' => 'Dato respaldado',
        ]);

        return [$survey, $surveyed];
    }

    private function surveyPayload(Survey $survey, string $status): array
    {
        return [
            'proyect_id' => $survey->proyect_id,
            'survey_name' => $survey->survey_name,
            'survey_type' => $survey->survey_type,
            'description' => $survey->description,
            'status' => $status,
        ];
    }

    private function actingAsRole(string $roleName): User
    {
        $user = User::create([
            'number_document' => 'DOC-USER-'.uniqid(),
            'names' => 'Usuario '.$roleName,
            'username' => 'user-'.uniqid(),
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', $roleName)->firstOrFail()->id,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }
}
