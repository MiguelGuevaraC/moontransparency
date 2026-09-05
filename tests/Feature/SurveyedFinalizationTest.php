<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyedFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_finalization_when_a_required_question_is_missing(): void
    {
        [$survey, $firstQuestion] = $this->createSurveyWithRequiredQuestions();
        $surveyedId = $this->createDraft($survey, $firstQuestion);

        $this->postJson(
            "/api/response-survey/$surveyedId/finalize",
            $this->payload($survey, [])
        )->assertUnprocessable();

        $this->assertDatabaseHas('surveyeds', [
            'id' => $surveyedId,
            'status' => Surveyed::STATUS_DRAFT,
            'completed_at' => null,
        ]);
    }

    public function test_it_saves_pending_answers_and_finalizes_the_survey(): void
    {
        [$survey, $firstQuestion, $secondQuestion] = $this->createSurveyWithRequiredQuestions();
        $surveyedId = $this->createDraft($survey, $firstQuestion);

        $response = $this->postJson(
            "/api/response-survey/$surveyedId/finalize",
            $this->payload($survey, [
                [
                    'survey_question_id' => $secondQuestion->id,
                    'response_text' => 'Respuesta final',
                ],
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_FINALIZED);

        $this->assertNotNull($response->json('data.completed_at'));
        $this->assertDatabaseHas('surveyeds', [
            'id' => $surveyedId,
            'status' => Surveyed::STATUS_FINALIZED,
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'survey_question_id' => $secondQuestion->id,
            'response_text' => 'Respuesta final',
        ]);
    }

    public function test_a_finalized_survey_cannot_be_edited_as_a_draft(): void
    {
        [$survey, $firstQuestion, $secondQuestion] = $this->createSurveyWithRequiredQuestions();
        $surveyedId = $this->createDraft($survey, $firstQuestion);

        $this->postJson(
            "/api/response-survey/$surveyedId/finalize",
            $this->payload($survey, [
                [
                    'survey_question_id' => $secondQuestion->id,
                    'response_text' => 'Día 2',
                ],
            ])
        )->assertOk();

        $this->postJson(
            "/api/response-survey/$surveyedId",
            $this->payload($survey, [
                [
                    'survey_question_id' => $firstQuestion->id,
                    'response_text' => 'Intento de modificación',
                ],
            ])
        )->assertStatus(409);

        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'survey_question_id' => $firstQuestion->id,
            'response_text' => 'Día 1',
        ]);
        $this->assertDatabaseMissing('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'response_text' => 'Intento de modificación',
        ]);
    }

    public function test_finalizing_an_already_finalized_survey_is_idempotent(): void
    {
        [$survey, $firstQuestion, $secondQuestion] = $this->createSurveyWithRequiredQuestions();
        $surveyedId = $this->createDraft($survey, $firstQuestion);
        $payload = $this->payload($survey, [
            [
                'survey_question_id' => $secondQuestion->id,
                'response_text' => 'Día 2',
            ],
        ]);

        $firstFinalization = $this->postJson(
            "/api/response-survey/$surveyedId/finalize",
            $payload
        )->assertOk();
        $completedAt = $firstFinalization->json('data.completed_at');

        $this->postJson(
            "/api/response-survey/$surveyedId/finalize",
            $payload
        )
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_FINALIZED)
            ->assertJsonPath('data.completed_at', $completedAt);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_responses', 2);
    }

    private function createSurveyWithRequiredQuestions(): array
    {
        $project = Proyect::create(['name' => 'Proyecto de prueba']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta de prueba',
            'status' => 'ACTIVA',
        ]);
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

    private function createDraft(Survey $survey, SurveyQuestion $question): int
    {
        $response = $this->postJson('/api/response-survey', $this->payload($survey, [
            [
                'survey_question_id' => $question->id,
                'response_text' => 'Día 1',
            ],
        ]))->assertOk();

        return (int) $response->json('data.id');
    }

    private function payload(Survey $survey, array $responses): array
    {
        return [
            'number_document' => 'DOC-FINAL-001',
            'names' => 'Persona de prueba',
            'survey_id' => $survey->id,
            'responses' => $responses,
        ];
    }
}
