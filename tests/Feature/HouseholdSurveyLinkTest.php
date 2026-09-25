<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdSurveyLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_accepts_a_free_globally_unique_household_code(): void
    {
        [$baseline, , $baselineQuestion] = $this->linkedSurveys();

        $payload = $this->payload(
            $baseline,
            $baselineQuestion,
            'DOC-BASE-1',
            'Casa Los Pinos #7'
        );
        unset($payload['responses']);

        $created = $this->postJson('/api/response-survey', $payload)
            ->assertOk()
            ->assertJsonPath('data.household.code', 'Casa Los Pinos #7');

        $this->postJson('/api/response-survey', $this->payload(
            $baseline,
            $baselineQuestion,
            'DOC-BASE-2',
            'casa los pinos #7'
        ))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'El ID del hogar ya está registrado y debe ser único globalmente.'
            );

        $this->assertDatabaseHas('surveyeds', [
            'id' => $created->json('data.id'),
            'household_id' => $created->json('data.household.id'),
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $created->json('data.id'),
            'survey_question_id' => $baselineQuestion->id,
            'response_text' => 'Casa Los Pinos #7',
        ]);
        $this->assertDatabaseCount('households', 1);
    }

    public function test_monitoring_can_only_select_an_unused_household_from_its_finalized_baseline(): void
    {
        [$baseline, $monitoring, $baselineQuestion, $monitoringQuestion] = $this->linkedSurveys();

        $finalizedBaseline = $this->createAndFinalizeBaseline(
            $baseline,
            $baselineQuestion,
            'DOC-BASE-3',
            'HOGAR LIBRE 123'
        );
        $this->postJson('/api/response-survey', $this->payload(
            $baseline,
            $baselineQuestion,
            'DOC-BASE-4',
            'AÚN EN BORRADOR'
        ))->assertOk();

        config(['app.uuid' => 'household-options-key']);
        $this->withHeader('UUID', 'household-options-key')
            ->getJson('/api/survey-show/'.$monitoring->id.'/household-options?search=libre')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'HOGAR LIBRE 123')
            ->assertJsonPath('meta.total', 1);

        $monitoringPayload = $this->payload(
            $monitoring,
            $monitoringQuestion,
            'DOC-MON-1',
            'HOGAR LIBRE 123'
        );
        unset($monitoringPayload['household_code']);
        $monitoringParticipation = $this->postJson('/api/response-survey', $monitoringPayload)
            ->assertOk()
            ->assertJsonPath('data.household.id', $finalizedBaseline->json('data.household.id'));

        $this->postJson('/api/response-survey', $this->payload(
            $monitoring,
            $monitoringQuestion,
            'DOC-MON-2',
            'HOGAR LIBRE 123'
        ))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'El ID del hogar ya fue utilizado en esta encuesta de monitoreo.'
            );

        $this->postJson('/api/response-survey', $this->payload(
            $monitoring,
            $monitoringQuestion,
            'DOC-MON-3',
            'AÚN EN BORRADOR'
        ))
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'El ID del hogar no pertenece a una línea base finalizada vinculada.'
            );

        $this->withHeader('UUID', 'household-options-key')
            ->getJson('/api/survey-show/'.$monitoring->id.'/household-options')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeader('UUID', 'household-options-key')
            ->getJson('/api/survey-show/'.$monitoring->id.'/household-options?surveyed_id='.$monitoringParticipation->json('data.id'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'HOGAR LIBRE 123');
    }

    public function test_survey_detail_exposes_the_household_input_mode(): void
    {
        [$baseline, $monitoring, $baselineQuestion, $monitoringQuestion] = $this->linkedSurveys();
        config(['app.uuid' => 'household-contract-key']);

        $this->withHeader('UUID', 'household-contract-key')
            ->getJson('/api/survey-show/'.$baseline->id)
            ->assertOk()
            ->assertJsonPath('data.household_identifier.survey_question_id', $baselineQuestion->id)
            ->assertJsonPath('data.household_identifier.mode', 'FREE_TEXT')
            ->assertJsonPath('data.household_identifier.uniqueness', 'GLOBAL');

        $this->withHeader('UUID', 'household-contract-key')
            ->getJson('/api/survey-show/'.$monitoring->id)
            ->assertOk()
            ->assertJsonPath('data.household_identifier.survey_question_id', $monitoringQuestion->id)
            ->assertJsonPath('data.household_identifier.mode', 'SEARCHABLE_SELECT')
            ->assertJsonPath('data.household_identifier.linked_pre_survey.id', $baseline->id)
            ->assertJsonPath(
                'data.household_identifier.options_endpoint',
                url('/api/survey-show/'.$monitoring->id.'/household-options')
            );
    }

    private function createAndFinalizeBaseline(
        Survey $survey,
        SurveyQuestion $question,
        string $document,
        string $code
    ) {
        $payload = $this->payload($survey, $question, $document, $code);
        unset($payload['household_code']);
        $created = $this->postJson('/api/response-survey', $payload)->assertOk();

        return $this->postJson(
            '/api/response-survey/'.$created->json('data.id').'/finalize',
            $payload
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'FINALIZADA');
    }

    private function linkedSurveys(): array
    {
        $project = Proyect::create(['name' => 'Proyecto vínculo de hogares']);
        $monitoring = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT monitoreo',
            'survey_type' => 'POST',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $baseline = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
            'post_survey_id' => $monitoring->id,
        ]);

        $baselineQuestion = $this->householdQuestion($baseline);
        $monitoringQuestion = $this->householdQuestion($monitoring);

        return [$baseline, $monitoring, $baselineQuestion, $monitoringQuestion];
    }

    private function householdQuestion(Survey $survey): SurveyQuestion
    {
        return SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'ID del hogar',
            'calculator_key' => 'household.identifier',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
    }

    private function payload(
        Survey $survey,
        SurveyQuestion $question,
        string $document,
        string $householdCode
    ): array {
        return [
            'number_document' => $document,
            'names' => 'Persona '.$document,
            'survey_id' => $survey->id,
            'household_code' => $householdCode,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => $householdCode,
            ]],
        ];
    }
}
