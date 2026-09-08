<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedCalculatorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_stable_seven_day_contract_without_duplicate_days(): void
    {
        [$surveyed, $questions] = $this->createCalculatorParticipation();
        $this->authenticate();

        $response = $this->getJson('/api/surveyed/'.$surveyed->id.'/calculator')
            ->assertOk()
            ->assertJsonPath('data.contract.version', '1.1')
            ->assertJsonPath('data.contract.expected_days', 7)
            ->assertJsonPath('data.contract.units.weight', 'kg')
            ->assertJsonPath('data.contract.units.people', 'person')
            ->assertJsonPath('data.contract.missing_value', null)
            ->assertJsonPath('data.participation.id', $surveyed->id)
            ->assertJsonPath('data.participation.status', Surveyed::STATUS_DRAFT)
            ->assertJsonPath('data.participation.data_state', 'PARTIAL')
            ->assertJsonPath('data.participation.is_partial', true)
            ->assertJsonPath('data.respondent.number_document', 'DOC-CALC-001')
            ->assertJsonPath('data.household.identifier', 'HOGAR-77')
            ->assertJsonPath('data.household.source', 'LEGACY_RESPONSE')
            ->assertJsonPath('data.survey.name', 'KPT línea base')
            ->assertJsonPath('data.project.name', 'Proyecto calculadora')
            ->assertJsonPath('data.recorded_days', [1, 3])
            ->assertJsonPath('data.missing_days', [2, 4, 5, 6, 7])
            ->assertJsonCount(7, 'data.days')
            ->assertJsonCount(4, 'data.fields');

        $days = $response->json('data.days');

        $this->assertSame(range(1, 7), array_column($days, 'day_number'));
        $this->assertSame(range(1, 7), array_values(array_unique(array_column($days, 'day_number'))));

        $weightKey = 'question_'.$questions['weight']->id;
        $childrenKey = 'question_'.$questions['children']->id;
        $dayKey = 'question_'.$questions['day']->id;

        $this->assertTrue($days[0]['recorded']);
        $this->assertSame(12.5, $days[0]['values'][$weightKey]['value']);
        $this->assertSame('12.50', $days[0]['values'][$weightKey]['raw_value']);
        $this->assertSame('kg', $days[0]['values'][$weightKey]['unit']);
        $this->assertSame('valid', $days[0]['values'][$weightKey]['validation_status']);
        $this->assertSame(2, $days[0]['values'][$childrenKey]['value']);
        $this->assertSame('person', $days[0]['values'][$childrenKey]['unit']);
        $this->assertSame(['1'], $days[0]['values'][$dayKey]['value']);
        $this->assertSame('day', $days[0]['values'][$dayKey]['unit']);

        $this->assertFalse($days[1]['recorded']);
        $this->assertNull($days[1]['measurement_id']);
        $this->assertNull($days[1]['values'][$weightKey]['value']);
        $this->assertSame('missing', $days[1]['values'][$weightKey]['validation_status']);

        $this->assertTrue($days[2]['recorded']);
        $this->assertNull($days[2]['values'][$weightKey]['value']);
        $this->assertSame('sin dato', $days[2]['values'][$weightKey]['raw_value']);
        $this->assertSame('invalid', $days[2]['values'][$weightKey]['validation_status']);
    }

    public function test_it_identifies_a_final_participation(): void
    {
        [$surveyed] = $this->createCalculatorParticipation();
        $surveyed->update([
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => '2026-09-05 12:30:00',
        ]);
        $this->authenticate();

        $this->getJson('/api/surveyed/'.$surveyed->id.'/calculator')
            ->assertOk()
            ->assertJsonPath('data.participation.status', Surveyed::STATUS_FINALIZED)
            ->assertJsonPath('data.participation.data_state', 'FINAL')
            ->assertJsonPath('data.participation.is_partial', false)
            ->assertJsonPath('data.participation.completed_at', '2026-09-05T12:30:00-05:00');
    }

    public function test_calculator_contract_requires_authentication(): void
    {
        [$surveyed] = $this->createCalculatorParticipation();

        $this->getJson('/api/surveyed/'.$surveyed->id.'/calculator')
            ->assertUnauthorized();
    }

    public function test_calculator_contract_returns_not_found_for_an_unknown_participation(): void
    {
        $this->authenticate();

        $this->getJson('/api/surveyed/999999/calculator')
            ->assertNotFound()
            ->assertJsonPath('message', 'Respuesta de encuesta no encontrada.');
    }

    private function createCalculatorParticipation(): array
    {
        $project = Proyect::create(['name' => 'Proyecto calculadora']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base',
            'survey_type' => 'PRE',
            'status' => 'ACTIVA',
        ]);
        $respondent = Respondent::create([
            'number_document' => 'DOC-CALC-001',
            'names' => 'Familia de prueba',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        $household = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'ID del hogar',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        $children = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Número de niños/as de 0 a 14 años',
            'question_type' => 'LIBRE',
            'type_field' => 'NUMERICO',
            'order' => 2,
            'is_required' => true,
        ]);
        $dayQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Día de medición',
            'question_type' => 'OPCIONES',
            'type_field' => 'LISTADO',
            'order' => 3,
            'is_required' => true,
        ]);
        $dayOne = SurveyQuestionOption::create([
            'survey_question_id' => $dayQuestion->id,
            'description' => '1',
        ]);
        $weight = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Peso inicial de leña',
            'question_type' => 'LIBRE',
            'type_field' => 'NUMERICO',
            'order' => 4,
            'is_required' => true,
        ]);

        $dayOneMeasurement = SurveyedMeasurement::create([
            'surveyed_id' => $surveyed->id,
            'day_number' => 1,
        ]);
        $this->createAnswer($surveyed, $respondent, $dayOneMeasurement, $household, 'HOGAR-77');
        $this->createAnswer($surveyed, $respondent, $dayOneMeasurement, $children, '2');
        $dayAnswer = $this->createAnswer($surveyed, $respondent, $dayOneMeasurement, $dayQuestion, null);
        SurveyedResponseOption::create([
            'surveyed_response_id' => $dayAnswer->id,
            'survey_question_options_id' => $dayOne->id,
            'surveyed_id' => $surveyed->id,
            'respondent_id' => $respondent->id,
        ]);
        $this->createAnswer($surveyed, $respondent, $dayOneMeasurement, $weight, '12.50');

        $dayThreeMeasurement = SurveyedMeasurement::create([
            'surveyed_id' => $surveyed->id,
            'day_number' => 3,
        ]);
        $this->createAnswer($surveyed, $respondent, $dayThreeMeasurement, $weight, 'sin dato');

        return [$surveyed, [
            'household' => $household,
            'children' => $children,
            'day' => $dayQuestion,
            'weight' => $weight,
        ]];
    }

    private function createAnswer(
        Surveyed $surveyed,
        Respondent $respondent,
        SurveyedMeasurement $measurement,
        SurveyQuestion $question,
        ?string $value
    ): SurveyedResponse {
        return SurveyedResponse::create([
            'respondent_id' => $respondent->id,
            'surveyed_id' => $surveyed->id,
            'surveyed_measurement_id' => $measurement->id,
            'survey_question_id' => $question->id,
            'response_text' => $value,
        ]);
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-CALC-001',
            'username' => 'calculator-test',
            'password' => bcrypt('password'),
            'rol_id' => \App\Models\Rol::where('name', 'Encuestador')->value('id'),
            'status' => 'Activo',
        ]));
    }
}
