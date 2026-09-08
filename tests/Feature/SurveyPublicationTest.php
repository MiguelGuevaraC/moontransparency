<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_activate_and_deactivate_a_survey_without_participations(): void
    {
        $survey = $this->createSurvey(Survey::STATUS_INACTIVE);
        $this->actingAsAdministrator();

        $this->putJson("/api/survey/{$survey->id}", $this->payload($survey, Survey::STATUS_ACTIVE))
            ->assertOk()
            ->assertJsonPath('data.status', Survey::STATUS_ACTIVE);

        $this->putJson("/api/survey/{$survey->id}", $this->payload($survey, Survey::STATUS_INACTIVE))
            ->assertOk()
            ->assertJsonPath('data.status', Survey::STATUS_INACTIVE);
    }

    public function test_a_survey_with_responses_cannot_be_deactivated(): void
    {
        $survey = $this->createSurvey(Survey::STATUS_ACTIVE);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pregunta con respuesta',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
            'eje' => 'Prueba',
            'justification' => '-',
        ]);
        $respondent = Respondent::create([
            'number_document' => 'DOC-PUBLICACION-001',
            'names' => 'Persona de prueba',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        SurveyedResponse::create([
            'surveyed_id' => $surveyed->id,
            'respondent_id' => $respondent->id,
            'survey_question_id' => $question->id,
            'response_text' => 'Respuesta conservada',
        ]);
        $this->actingAsAdministrator();

        $this->putJson("/api/survey/{$survey->id}", $this->payload($survey, Survey::STATUS_INACTIVE))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status')
            ->assertJsonPath(
                'errors.status.0',
                'No se puede inactivar una encuesta con participaciones o respuestas registradas. Primero debe ejecutarse y aprobarse una limpieza explícita de sus datos.'
            );

        $this->assertSame(Survey::STATUS_ACTIVE, $survey->fresh()->status);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyed->id,
            'response_text' => 'Respuesta conservada',
        ]);
    }

    public function test_soft_deleted_participations_still_prevent_deactivation_until_data_is_really_cleaned(): void
    {
        $survey = $this->createSurvey(Survey::STATUS_ACTIVE);
        $respondent = Respondent::create([
            'number_document' => 'DOC-PUBLICACION-002',
            'names' => 'Persona eliminada lógicamente',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        $surveyed->delete();
        $this->actingAsAdministrator();

        $this->putJson("/api/survey/{$survey->id}", $this->payload($survey, Survey::STATUS_INACTIVE))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $surveyed->forceDelete();

        $this->putJson("/api/survey/{$survey->id}", $this->payload($survey, Survey::STATUS_INACTIVE))
            ->assertOk()
            ->assertJsonPath('data.status', Survey::STATUS_INACTIVE);
    }

    public function test_geobosques_questions_remain_editable_from_the_frontend_api(): void
    {
        $survey = $this->createSurvey(Survey::STATUS_ACTIVE);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Texto original',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
            'eje' => 'Ubicación',
            'justification' => '-',
        ]);
        $this->actingAsAdministrator();

        $this->putJson("/api/surveyquestion/{$question->id}", [
            'question_text' => 'Texto editado por el usuario',
            'order' => 2,
            'is_required' => true,
            'eje' => 'Ubicación actualizada',
        ])
            ->assertOk()
            ->assertJsonPath('data.question_text', 'Texto editado por el usuario')
            ->assertJsonPath('data.order', 2);

        $this->assertDatabaseHas('survey_questions', [
            'id' => $question->id,
            'question_text' => 'Texto editado por el usuario',
            'order' => 2,
            'is_required' => true,
        ]);
    }

    private function createSurvey(string $status): Survey
    {
        $project = Proyect::create(['name' => 'Proyecto publicación']);

        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta de publicación '.uniqid(),
            'survey_type' => 'PRE',
            'description' => 'Encuesta para validar su publicación.',
            'status' => $status,
        ]);
    }

    private function payload(Survey $survey, string $status): array
    {
        return [
            'proyect_id' => $survey->proyect_id,
            'survey_name' => $survey->survey_name,
            'survey_type' => $survey->survey_type,
            'description' => $survey->description,
            'status' => $status,
        ];
    }

    private function actingAsAdministrator(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'DOC-'.uniqid(),
            'names' => 'Administrador de encuestas',
            'username' => 'admin-'.uniqid(),
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->firstOrFail()->id,
        ]));
    }
}
