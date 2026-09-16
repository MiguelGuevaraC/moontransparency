<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_surveyor_history_only_contains_own_participations(): void
    {
        [$owner, $other, $ownParticipation, $otherParticipation] = $this->ownershipScenario();

        Sanctum::actingAs($owner);

        $this->getJson('/api/surveyed?all=true')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownParticipation->id)
            ->assertJsonPath('0.created_by', $owner->id);

        $this->getJson('/api/surveyed?all=true&created_by='.$other->id)
            ->assertOk()
            ->assertJsonCount(0);

        $this->getJson('/api/surveyedAll')
            ->assertOk()
            ->assertJsonMissing(['surveyed_id' => $otherParticipation->id]);
    }

    public function test_surveyor_cannot_view_or_modify_another_surveyors_participation(): void
    {
        [$owner, , $ownParticipation, $otherParticipation, $question] = $this->ownershipScenario();
        Sanctum::actingAs($owner);

        $this->getJson('/api/surveyed/'.$otherParticipation->id)->assertNotFound();
        $this->getJson('/api/surveyed/'.$otherParticipation->id.'/calculator')->assertForbidden();

        $this->putJson('/api/surveyed/'.$otherParticipation->id, $this->updatePayload($otherParticipation, $question, 'ALTERADA'))
            ->assertForbidden();

        $this->putJson('/api/surveyed/'.$ownParticipation->id, $this->updatePayload($ownParticipation, $question, 'PROPIA'))
            ->assertOk()
            ->assertJsonPath('data.id', $ownParticipation->id)
            ->assertJsonPath('data.can_edit', true);

        $this->assertDatabaseMissing('surveyed_responses', [
            'surveyed_id' => $otherParticipation->id,
            'response_text' => 'ALTERADA',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $ownParticipation->id,
            'response_text' => 'PROPIA',
        ]);
    }

    public function test_administrator_and_supervisor_can_view_all_participations(): void
    {
        [, , $first, $second] = $this->ownershipScenario();

        foreach (['Administrador', 'Supervisor'] as $roleName) {
            Sanctum::actingAs($this->user($roleName, 'viewer-'.strtolower($roleName), true));

            $this->getJson('/api/surveyed?all=true')
                ->assertOk()
                ->assertJsonCount(2)
                ->assertJsonFragment(['id' => $first->id])
                ->assertJsonFragment(['id' => $second->id]);
        }
    }

    public function test_authenticated_creation_records_the_surveyor_as_owner(): void
    {
        $surveyor = $this->user('Encuestador', 'creator-surveyor');
        $project = Proyect::create(['name' => 'Proyecto de creación']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta de creación',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Dato',
            'question_type' => 'LIBRE',
            'is_required' => false,
        ]);
        Sanctum::actingAs($surveyor);

        $this->postJson('/api/response-survey', [
            'number_document' => 'OWN-CREATE-001',
            'names' => 'Persona creada',
            'survey_id' => $survey->id,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => 'Respuesta',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.created_by', $surveyor->id)
            ->assertJsonPath('data.can_edit', true);
    }

    private function ownershipScenario(): array
    {
        $owner = $this->user('Encuestador', 'owner-surveyor');
        $other = $this->user('Encuestador', 'other-surveyor');
        $project = Proyect::create(['name' => 'Proyecto aislado']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta aislada',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Dato aislado',
            'question_type' => 'LIBRE',
            'is_required' => false,
        ]);
        $firstRespondent = Respondent::create(['number_document' => 'OWN-001', 'names' => 'Persona propia']);
        $secondRespondent = Respondent::create(['number_document' => 'OWN-002', 'names' => 'Persona ajena']);
        $ownParticipation = Surveyed::create([
            'respondent_id' => $firstRespondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);
        $otherParticipation = Surveyed::create([
            'respondent_id' => $secondRespondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
            'created_by' => $other->id,
            'updated_by' => $other->id,
        ]);

        return [$owner, $other, $ownParticipation, $otherParticipation, $question];
    }

    private function updatePayload(Surveyed $surveyed, SurveyQuestion $question, string $answer): array
    {
        return [
            'number_document' => $surveyed->respondent->number_document,
            'names' => $surveyed->respondent->names,
            'survey_id' => $surveyed->survey_id,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => $answer,
            ]],
        ];
    }

    private function user(string $roleName, string $username, bool $ensurePermissions = false): User
    {
        $role = Rol::firstOrCreate(['name' => $roleName], ['status' => Rol::STATUS_ACTIVE]);
        if ($ensurePermissions) {
            foreach (['participations.view', 'participations.manage'] as $code) {
                $permission = Permission::where('route', $code)->firstOrFail();
                $role->permissions()->syncWithoutDetaching([$permission->id => [
                    'name_permission' => $permission->name,
                    'name_rol' => $role->name,
                    'type' => $permission->type,
                ]]);
            }
        }

        return User::create([
            'number_document' => 'DOC-'.strtoupper($username),
            'names' => 'Usuario '.$username,
            'username' => $username,
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
