<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_can_be_searched_by_participation_and_respondent(): void
    {
        [$first, $second] = $this->createHistoryRecords();
        $this->authenticate();

        $this->getJson('/api/surveyed?all=true&id='.$first->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('0.can_edit', true);

        $this->getJson('/api/surveyed?all=true&respondent=Ana')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $first->id);

        $this->getJson('/api/surveyed?all=true&respondent=DOC-BETO')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $second->id)
            ->assertJsonPath('0.status', Surveyed::STATUS_FINALIZED)
            ->assertJsonPath('0.can_edit', false);
    }

    public function test_existing_project_survey_and_date_filters_still_work(): void
    {
        [$first, $second, $firstProject, $firstSurvey] = $this->createHistoryRecords();
        $this->authenticate();

        $this->getJson(
            '/api/surveyed?all=true'
            .'&project_id='.$firstProject->id
            .'&survey_id='.$firstSurvey->id
            .'&from=2026-09-01&to=2026-09-05'
        )
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $first->id);

        $this->getJson(
            '/api/surveyed?all=true'
            .'&survey%24proyect_id='.$firstProject->id
            .'&survey_id='.$firstSurvey->id
        )
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $first->id);

        $this->getJson('/api/surveyed?all=true&status='.Surveyed::STATUS_FINALIZED)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $second->id);
    }

    public function test_view_returns_all_saved_days_without_modifying_the_participation(): void
    {
        [$surveyed, $question] = $this->createParticipationWithTwoDays();
        $this->authenticate();
        $updatedAt = $surveyed->fresh()->updated_at?->toDateTimeString();

        $this->getJson('/api/surveyed/'.$surveyed->id)
            ->assertOk()
            ->assertJsonPath('data.id', $surveyed->id)
            ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonCount(2, 'data.measurements')
            ->assertJsonCount(2, 'data.surveyed_responses')
            ->assertJsonPath('data.measurements.0.day_number', 1)
            ->assertJsonPath('data.measurements.0.responses.0.response_text', '10.50')
            ->assertJsonPath('data.measurements.1.day_number', 2)
            ->assertJsonPath('data.measurements.1.responses.0.response_text', '11.25');

        $this->assertSame($updatedAt, $surveyed->fresh()->updated_at?->toDateTimeString());
        $this->assertDatabaseCount('surveyed_responses', 2);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyed->id,
            'survey_question_id' => $question->id,
            'response_text' => '10.50',
        ]);
    }

    public function test_edit_can_continue_a_draft_with_all_previous_answers_available(): void
    {
        [$surveyed, $question] = $this->createParticipationWithTwoDays();
        $this->authenticate();

        $detail = $this->getJson('/api/surveyed/'.$surveyed->id)
            ->assertOk()
            ->assertJsonPath('data.can_edit', true)
            ->json('data');

        $this->assertCount(2, $detail['measurements']);

        $this->postJson('/api/response-survey/'.$surveyed->id, [
            'number_document' => $surveyed->respondent->number_document,
            'names' => $surveyed->respondent->names,
            'survey_id' => $surveyed->survey_id,
            'day_number' => 2,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => '12.00',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $surveyed->id)
            ->assertJsonPath('data.measurements.1.responses.0.response_text', '12.00');

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 2);
        $this->assertDatabaseCount('surveyed_responses', 2);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyed->id,
            'survey_question_id' => $question->id,
            'response_text' => '10.50',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyed->id,
            'survey_question_id' => $question->id,
            'response_text' => '12.00',
        ]);
    }

    public function test_history_and_view_require_authentication(): void
    {
        [$first] = $this->createHistoryRecords();

        $this->getJson('/api/surveyed')->assertUnauthorized();
        $this->getJson('/api/surveyed/'.$first->id)->assertUnauthorized();
    }

    private function createHistoryRecords(): array
    {
        $firstProject = Proyect::create(['name' => 'Proyecto uno']);
        $secondProject = Proyect::create(['name' => 'Proyecto dos']);
        $firstSurvey = Survey::create([
            'proyect_id' => $firstProject->id,
            'survey_name' => 'Encuesta uno',
            'status' => 'ACTIVA',
        ]);
        $secondSurvey = Survey::create([
            'proyect_id' => $secondProject->id,
            'survey_name' => 'Encuesta dos',
            'status' => 'ACTIVA',
        ]);
        $ana = Respondent::create([
            'number_document' => 'DOC-ANA-001',
            'names' => 'Ana Torres',
        ]);
        $beto = Respondent::create([
            'number_document' => 'DOC-BETO-002',
            'names' => 'Beto Rojas',
        ]);
        $first = Surveyed::create([
            'respondent_id' => $ana->id,
            'survey_id' => $firstSurvey->id,
            'status' => Surveyed::STATUS_DRAFT,
            'created_at' => '2026-09-03 10:00:00',
        ]);
        $second = Surveyed::create([
            'respondent_id' => $beto->id,
            'survey_id' => $secondSurvey->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => '2026-09-04 11:00:00',
            'created_at' => '2026-08-20 10:00:00',
        ]);

        return [$first, $second, $firstProject, $firstSurvey];
    }

    private function createParticipationWithTwoDays(): array
    {
        $project = Proyect::create(['name' => 'Proyecto para reanudar']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta para reanudar',
            'status' => 'ACTIVA',
        ]);
        $respondent = Respondent::create([
            'number_document' => 'DOC-RESUME-001',
            'names' => 'Persona para reanudar',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Peso medido',
            'question_type' => 'LIBRE',
            'order' => 1,
            'is_required' => true,
        ]);

        foreach ([1 => '10.50', 2 => '11.25'] as $day => $value) {
            $measurement = SurveyedMeasurement::create([
                'surveyed_id' => $surveyed->id,
                'day_number' => $day,
            ]);
            SurveyedResponse::create([
                'respondent_id' => $respondent->id,
                'surveyed_id' => $surveyed->id,
                'surveyed_measurement_id' => $measurement->id,
                'survey_question_id' => $question->id,
                'response_text' => $value,
            ]);
        }

        return [$surveyed, $question];
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-HISTORY-001',
            'username' => 'history-test',
            'password' => bcrypt('password'),
            'status' => 'Activo',
        ]));
    }
}
