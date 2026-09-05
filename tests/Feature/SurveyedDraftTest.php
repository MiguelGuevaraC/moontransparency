<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_an_incomplete_survey_as_a_draft(): void
    {
        [$survey, $firstQuestion, $secondQuestion] = $this->createSurveyWithTwoRequiredQuestions();

        $response = $this->postJson('/api/response-survey', $this->payload($survey, [
            [
                'survey_question_id' => $firstQuestion->id,
                'response_text' => 'Respuesta del día 1',
            ],
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_responses', 1);
        $this->assertDatabaseMissing('surveyed_responses', [
            'survey_question_id' => $secondQuestion->id,
        ]);
    }

    public function test_it_updates_the_same_draft_and_preserves_previous_answers(): void
    {
        [$survey, $firstQuestion, $secondQuestion] = $this->createSurveyWithTwoRequiredQuestions();

        $created = $this->postJson('/api/response-survey', $this->payload($survey, [
            [
                'survey_question_id' => $firstQuestion->id,
                'response_text' => 'Día 1',
            ],
        ]))->assertOk();

        $surveyedId = $created->json('data.id');

        $this->postJson("/api/response-survey/$surveyedId", $this->payload($survey, [
            [
                'survey_question_id' => $secondQuestion->id,
                'response_text' => 'Día 2',
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.id', $surveyedId)
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_responses', 2);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'survey_question_id' => $firstQuestion->id,
            'response_text' => 'Día 1',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'survey_question_id' => $secondQuestion->id,
            'response_text' => 'Día 2',
        ]);
    }

    public function test_repeated_option_saves_do_not_create_active_duplicates(): void
    {
        $survey = $this->createSurvey();
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Opciones del día',
            'question_type' => 'OPCIONES',
            'is_required' => true,
        ]);
        $firstOption = SurveyQuestionOption::create([
            'survey_question_id' => $question->id,
            'description' => 'Opción A',
        ]);
        $secondOption = SurveyQuestionOption::create([
            'survey_question_id' => $question->id,
            'description' => 'Opción B',
        ]);

        $created = $this->postJson('/api/response-survey', $this->payload($survey, [
            [
                'survey_question_id' => $question->id,
                'survey_question_option_id' => [$firstOption->id],
            ],
        ]))->assertOk();

        $surveyedId = $created->json('data.id');
        $updatePayload = $this->payload($survey, [
            [
                'survey_question_id' => $question->id,
                'survey_question_option_id' => [$firstOption->id, $secondOption->id],
            ],
        ]);

        $this->postJson("/api/response-survey/$surveyedId", $updatePayload)->assertOk();
        $this->postJson("/api/response-survey/$surveyedId", $updatePayload)->assertOk();

        $this->assertDatabaseCount('surveyed_responses', 1);
        $this->assertDatabaseCount('surveyed_response_options', 2);
    }

    public function test_an_authenticated_user_can_retrieve_a_draft_to_continue_it(): void
    {
        [$survey, $question] = $this->createSurveyWithTwoRequiredQuestions();
        $created = $this->postJson('/api/response-survey', $this->payload($survey, [
            [
                'survey_question_id' => $question->id,
                'response_text' => 'Información parcial',
            ],
        ]))->assertOk();

        $user = User::create([
            'number_document' => 'USR-001',
            'username' => 'admin-test',
            'password' => bcrypt('password'),
            'status' => 'Activo',
            'rol_id' => \App\Models\Rol::where('name', 'Encuestador')->value('id'),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/surveyed/'.$created->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('data.surveyed_responses.0.response_text', 'Información parcial');
    }

    public function test_update_returns_not_found_for_an_unknown_id(): void
    {
        $survey = $this->createSurvey();

        $this->postJson('/api/response-survey/999999', $this->payload($survey, []))
            ->assertNotFound();
    }

    private function createSurveyWithTwoRequiredQuestions(): array
    {
        $survey = $this->createSurvey();
        $firstQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Medición día 1',
            'question_type' => 'LIBRE',
            'is_required' => true,
        ]);
        $secondQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Medición día 2',
            'question_type' => 'LIBRE',
            'is_required' => true,
        ]);

        return [$survey, $firstQuestion, $secondQuestion];
    }

    private function createSurvey(): Survey
    {
        $project = Proyect::create(['name' => 'Proyecto de prueba']);

        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta de prueba',
            'status' => 'ACTIVA',
        ]);
    }

    private function payload(Survey $survey, array $responses): array
    {
        return [
            'number_document' => 'DOC-001',
            'names' => 'Persona de prueba',
            'survey_id' => $survey->id,
            'responses' => $responses,
        ];
    }
}
