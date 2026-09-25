<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_preview_an_inactive_survey_without_creating_responses(): void
    {
        $project = Proyect::create(['name' => 'Proyecto vista previa']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta en preparación',
            'survey_type' => 'PRE',
            'description' => 'Todavía no publicada',
            'status' => Survey::STATUS_INACTIVE,
        ]);
        $secondQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Segunda pregunta',
            'question_type' => 'LIBRE',
            'type_field' => 'DECIMAL',
            'order' => 2,
            'is_required' => false,
        ]);
        $firstQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Primera pregunta',
            'question_type' => 'OPCIONES',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        SurveyQuestionOption::create([
            'survey_question_id' => $firstQuestion->id,
            'description' => 'Opción visible',
        ]);
        $this->actingAsAdministrator();

        $this->getJson("/api/survey/{$survey->id}/preview")
            ->assertOk()
            ->assertJsonPath('data.preview_mode', true)
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.accepts_responses', false)
            ->assertJsonPath('data.status', Survey::STATUS_INACTIVE)
            ->assertJsonPath('data.survey_questions.0.id', $firstQuestion->id)
            ->assertJsonPath('data.survey_questions.0.survey_questions_options.0.description', 'Opción visible')
            ->assertJsonPath('data.survey_questions.1.id', $secondQuestion->id);

        $this->assertDatabaseCount('surveyeds', 0);
        $this->assertDatabaseCount('surveyed_responses', 0);
    }

    public function test_survey_preview_requires_authentication(): void
    {
        $this->getJson('/api/survey/1/preview')->assertUnauthorized();
    }

    private function actingAsAdministrator(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'DOC-PREVIEW-ADMIN',
            'names' => 'Administrador de vista previa',
            'username' => 'admin-preview',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->value('id'),
        ]));
    }
}
