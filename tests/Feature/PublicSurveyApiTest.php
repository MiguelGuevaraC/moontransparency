<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSurveyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_active_surveys_without_login_and_in_display_order(): void
    {
        config(['app.uuid' => 'public-surveys-key']);
        $project = Proyect::create(['name' => 'Proyecto público']);
        $second = $this->survey($project, 'Encuesta posterior', 'POST', 20);
        $first = $this->survey($project, 'Encuesta inicial', 'PRE', 10);
        $inactive = $this->survey($project, 'Encuesta inactiva', 'PRE', 1, Survey::STATUS_INACTIVE);
        SurveyQuestion::create([
            'survey_id' => $first->id,
            'question_text' => 'Pregunta pública',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'is_required' => false,
        ]);

        $response = $this->withHeader('UUID', 'public-surveys-key')
            ->getJson('/api/surveys-public?all=true')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.name', 'Encuesta inicial')
            ->assertJsonPath('data.0.project.id', $project->id)
            ->assertJsonPath('data.0.questions_count', 1)
            ->assertJsonPath('data.0.detail_endpoint', url('/api/survey-show/'.$first->id))
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonMissing(['id' => $inactive->id]);

        $this->assertArrayNotHasKey('survey_questions', $response->json('data.0'));
    }

    public function test_it_filters_public_surveys_and_returns_pagination_metadata(): void
    {
        config(['app.uuid' => 'public-surveys-key']);
        $firstProject = Proyect::create(['name' => 'Primer proyecto']);
        $secondProject = Proyect::create(['name' => 'Segundo proyecto']);
        $expected = $this->survey($firstProject, 'KPT línea base', 'PRE', 1);
        $this->survey($firstProject, 'KPT monitoreo', 'POST', 2);
        $this->survey($secondProject, 'Otra línea base', 'PRE', 3);

        $this->withHeader('UUID', 'public-surveys-key')
            ->getJson('/api/surveys-public?project_id='.$firstProject->id.'&survey_name=línea&survey_type=PRE&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expected->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_public_survey_list_and_detail_require_uuid(): void
    {
        config(['app.uuid' => 'public-surveys-key']);
        $project = Proyect::create(['name' => 'Proyecto protegido']);
        $survey = $this->survey($project, 'Encuesta protegida', 'PRE', 1);

        $this->getJson('/api/surveys-public')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'UUID de acceso público inválido.');
        $this->getJson('/api/survey-show/'.$survey->id)
            ->assertUnauthorized();

        $this->withHeader('UUID', 'public-surveys-key')
            ->getJson('/api/survey-show/'.$survey->id)
            ->assertOk()
            ->assertJsonPath('data.id', $survey->id)
            ->assertJsonPath('data.status', Survey::STATUS_ACTIVE);
    }

    private function survey(
        Proyect $project,
        string $name,
        string $type,
        int $order,
        string $status = Survey::STATUS_ACTIVE
    ): Survey {
        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => $name,
            'survey_type' => $type,
            'description' => 'Descripción pública',
            'status' => $status,
            'display_order' => $order,
        ]);
    }
}
