<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
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

        $response = $this->getJson("/api/survey/{$survey->id}/preview")
            ->assertOk()
            ->assertJsonPath('data.preview_mode', true)
            ->assertJsonPath('data.read_only', true)
            ->assertJsonPath('data.accepts_responses', false)
            ->assertJsonPath('data.status', Survey::STATUS_INACTIVE)
            ->assertJsonPath('data.survey_questions.0.id', $firstQuestion->id)
            ->assertJsonPath('data.survey_questions.0.survey_questions_options.0.description', 'Opción visible')
            ->assertJsonPath('data.survey_questions.1.id', $secondQuestion->id)
            ->assertJsonStructure(['data' => ['iframe_url', 'viewer_url', 'expires_at']]);

        $iframeUrl = $response->json('data.iframe_url');
        $this->assertStringStartsWith('http://localhost/encuestas/', $iframeUrl);
        $this->get($iframeUrl)
            ->assertOk()
            ->assertHeader(
                'Content-Security-Policy',
                "frame-ancestors 'self' https://moongroup-admin.vercel.app"
            )
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Encuesta en preparación', false)
            ->assertSee('Primera pregunta', false)
            ->assertSee('Opción visible', false)
            ->assertSee('Segunda pregunta', false)
            ->assertDontSee('Vista previa en modo solo lectura', false)
            ->assertDontSee('Registro diario', false)
            ->assertDontSee('Todavía no publicada', false)
            ->assertDontSee('<form', false);

        $this->assertDatabaseCount('surveyeds', 0);
        $this->assertDatabaseCount('surveyed_responses', 0);
    }

    public function test_survey_preview_requires_authentication(): void
    {
        $this->getJson('/api/survey/1/preview')->assertUnauthorized();
    }

    public function test_iframe_preview_requires_a_valid_temporary_signature(): void
    {
        $project = Proyect::create(['name' => 'Proyecto sin firma']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta protegida',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_INACTIVE,
        ]);

        $this->get("/encuestas/{$survey->id}/vista-previa")
            ->assertForbidden();
    }

    public function test_monitoring_preview_displays_the_linked_household_as_a_select(): void
    {
        $project = Proyect::create(['name' => 'Proyecto monitoreo']);
        $baseline = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base previa',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $monitoring = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT monitoreo posterior',
            'survey_type' => 'POST',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $baseline->update(['post_survey_id' => $monitoring->id]);
        SurveyQuestion::create([
            'survey_id' => $monitoring->id,
            'question_text' => 'ID del hogar',
            'calculator_key' => 'household.identifier',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        $url = URL::temporarySignedRoute(
            'surveys.preview.embed',
            now()->addMinute(),
            ['survey' => $monitoring->id],
            false
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('KPT monitoreo posterior', false)
            ->assertSee('Buscar y seleccionar un ID de hogar de línea base', false)
            ->assertSee('<select class="control" disabled', false);
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
