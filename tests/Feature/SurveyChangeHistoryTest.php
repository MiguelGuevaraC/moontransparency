<?php

namespace Tests\Feature;

use App\Models\Ods;
use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\SurveyChangeLog;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyChangeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_records_changed_fields_for_survey_questions_and_options(): void
    {
        $user = $this->actingAsAdministrator();
        $project = Proyect::create(['name' => 'Proyecto auditable']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta auditable',
            'survey_type' => 'PRE',
            'description' => 'Descripción inicial',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pregunta inicial',
            'question_type' => 'OPCIONES',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        $option = SurveyQuestionOption::create([
            'survey_question_id' => $question->id,
            'description' => 'Opción inicial',
        ]);

        $survey->update(['description' => 'Descripción modificada']);
        $question->update([
            'question_text' => 'Pregunta modificada',
            'is_required' => false,
        ]);
        $option->update(['description' => 'Opción modificada']);

        $response = $this->getJson("/api/survey/{$survey->id}/history?per_page=100")
            ->assertOk()
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'survey_id', 'action', 'entity_type', 'entity_id',
                    'description', 'changes', 'user', 'created_at',
                ]],
                'links',
                'meta',
            ]);

        $history = collect($response->json('data'));
        $surveyUpdate = $history->first(fn (array $entry) => $entry['action'] === 'UPDATED'
            && $entry['entity_type'] === 'SURVEY');
        $questionUpdate = $history->first(fn (array $entry) => $entry['action'] === 'UPDATED'
            && $entry['entity_type'] === 'QUESTION');
        $optionUpdate = $history->first(fn (array $entry) => $entry['action'] === 'UPDATED'
            && $entry['entity_type'] === 'OPTION');

        $this->assertSame('Descripción inicial', $surveyUpdate['changes']['description']['old']);
        $this->assertSame('Descripción modificada', $surveyUpdate['changes']['description']['new']);
        $this->assertSame('Pregunta inicial', $questionUpdate['changes']['question_text']['old']);
        $this->assertSame('Pregunta modificada', $questionUpdate['changes']['question_text']['new']);
        $this->assertSame('Opción inicial', $optionUpdate['changes']['description']['old']);
        $this->assertSame('Opción modificada', $optionUpdate['changes']['description']['new']);
        $this->assertSame($user->id, SurveyChangeLog::latest('id')->value('user_id'));
    }

    public function test_history_can_filter_by_action_and_entity_type(): void
    {
        $this->actingAsAdministrator();
        $project = Proyect::create(['name' => 'Proyecto filtros']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta filtros',
            'survey_type' => 'PRE',
            'description' => 'Inicial',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $survey->update(['description' => 'Actualizada']);

        $this->getJson("/api/survey/{$survey->id}/history?action=UPDATED&entity_type=SURVEY")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'UPDATED')
            ->assertJsonPath('data.0.entity_type', 'SURVEY');
    }

    public function test_updating_a_question_through_the_admin_api_records_its_history(): void
    {
        $user = $this->actingAsAdministrator();
        $project = Proyect::create(['name' => 'Proyecto actualizado por API']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta actualizada por API',
            'survey_type' => 'PRE',
            'description' => 'Encuesta para probar el endpoint real',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Texto anterior',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);

        SurveyChangeLog::query()->delete();

        $this->putJson("/api/surveyquestion/{$question->id}", [
            'question_text' => 'Texto editado desde el panel',
        ])->assertOk();

        $this->getJson("/api/survey/{$survey->id}/history?per_page=100")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'UPDATED')
            ->assertJsonPath('data.0.entity_type', 'QUESTION')
            ->assertJsonPath('data.0.entity_id', $question->id)
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.changes.question_text.old', 'Texto anterior')
            ->assertJsonPath('data.0.changes.question_text.new', 'Texto editado desde el panel');
    }

    public function test_updating_a_survey_through_the_admin_api_records_its_history(): void
    {
        $user = $this->actingAsAdministrator();
        $project = Proyect::create(['name' => 'Proyecto de encuesta API']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta editable por API',
            'survey_type' => 'PRE',
            'description' => 'Descripción anterior',
            'status' => Survey::STATUS_ACTIVE,
        ]);

        SurveyChangeLog::query()->delete();

        $this->putJson("/api/survey/{$survey->id}", [
            'proyect_id' => $project->id,
            'survey_name' => $survey->survey_name,
            'survey_type' => $survey->survey_type,
            'description' => 'Descripción editada desde el panel',
            'status' => $survey->status,
        ])->assertOk();

        $this->getJson("/api/survey/{$survey->id}/history?per_page=100")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'UPDATED')
            ->assertJsonPath('data.0.entity_type', 'SURVEY')
            ->assertJsonPath('data.0.entity_id', $survey->id)
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.changes.description.old', 'Descripción anterior')
            ->assertJsonPath('data.0.changes.description.new', 'Descripción editada desde el panel');
    }

    public function test_linking_an_ods_through_the_admin_api_records_its_history(): void
    {
        $user = $this->actingAsAdministrator();
        $project = Proyect::create(['name' => 'Proyecto ODS auditable']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta ODS auditable',
            'survey_type' => 'PRE',
            'description' => 'Encuesta para auditar su ODS',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Pregunta con ODS',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        $ods = Ods::create([
            'code' => 'OH'.substr(uniqid(), -8),
            'name' => 'ODS para historial',
            'description' => 'ODS creado para la prueba de historial',
        ]);

        SurveyChangeLog::query()->delete();

        $this->postJson('/api/surveyquestionods', [
            'survey_question_id' => $question->id,
            'ods_id' => $ods->id,
        ])->assertCreated();

        $this->getJson("/api/survey/{$survey->id}/history?entity_type=ODS&per_page=100")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'CREATED')
            ->assertJsonPath('data.0.entity_type', 'ODS')
            ->assertJsonPath('data.0.user.id', $user->id)
            ->assertJsonPath('data.0.changes.survey_question_id.new', $question->id)
            ->assertJsonPath('data.0.changes.ods_id.new', $ods->id);
    }

    private function actingAsAdministrator(): User
    {
        $user = User::create([
            'number_document' => 'DOC-HISTORY-'.uniqid(),
            'names' => 'Administrador de historial',
            'username' => 'history-'.uniqid(),
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->value('id'),
        ]);
        Sanctum::actingAs($user);

        return $user;
    }
}
