<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Co2Calculation;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Co2CalculatorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_rech_with_baseline_and_combined_monitoring_from_the_database(): void
    {
        [$project, $baseline, $monitoring] = $this->createKptDataset();
        $this->authenticate();

        $this->postJson('/api/calculator/co2', [
            'project_id' => $project->id,
            'baseline_survey_id' => $baseline->id,
            'monitoring_survey_id' => $monitoring->id,
        ])->assertOk()
            ->assertJsonPath('data.calculation_id', 1)
            ->assertJsonPath('data.source.baseline_survey.id', $baseline->id)
            ->assertJsonPath('data.source.monitoring_survey.id', $monitoring->id)
            ->assertJsonPath('data.source.selected_households', 1)
            ->assertJsonPath('data.calculation.contract.methodology', 'RECH v5.0')
            ->assertJsonPath('data.calculation.contract.formula_version', config('co2.formula_version'))
            ->assertJsonPath('data.calculation.contract.factor_source', config('co2.factor_source'))
            ->assertJsonPath('data.calculation.status.calculation_ready', true)
            ->assertJsonPath('data.calculation.status.baseline_sample_size', 1)
            ->assertJsonPath('data.calculation.status.monitoring_sample_size', 1)
            ->assertJsonPath('data.calculation.families.monitoring.0.monitoring_participation_id', 2)
            ->assertJsonPath('data.source.households.0.monitoring_scenario', 'MONITORING_COMBINED')
            ->assertJsonPath('data.source.households.0.baseline_days.0.available_weight_kg', 20)
            ->assertJsonPath('data.source.households.0.monitoring_components.moon_group_days.0.available_weight_kg', 20)
            ->assertJsonPath('data.source.households.0.monitoring_components.traditional_days.0.available_weight_kg', 15)
            ->assertJsonPath('data.calculation.statistics.baseline.mean_kg_household_day', 14.5)
            ->assertJsonPath('data.calculation.statistics.monitoring.mean_kg_household_day', 14.2)
            ->assertJsonStructure(['data' => ['calculation' => ['emissions' => [
                'baseline_final_bey',
                'project_final_aey',
                'gross_reduction',
                'leakage' => ['embodied', 'market', 'total_ley'],
                'net_reduction_ery',
            ]]]]);

        $this->assertDatabaseHas('co2_calculations', [
            'id' => 1,
            'project_id' => $project->id,
            'baseline_survey_id' => $baseline->id,
            'monitoring_survey_id' => $monitoring->id,
            'executed_by' => auth()->id(),
            'methodology' => 'RECH v5.0',
            'formula_version' => config('co2.formula_version'),
        ]);

        $this->getJson('/api/calculator/co2/history?project_id='.$project->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.project.id', $project->id)
            ->assertJsonPath('data.0.formula_version', config('co2.formula_version'));
    }

    public function test_configuration_lists_only_surveys_with_calculator_mapping(): void
    {
        [$project, $baseline, $monitoring] = $this->createKptDataset();
        Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta sin KPT',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $this->authenticate();

        $response = $this->getJson('/api/calculator/co2/configuration?project_id='.$project->id)
            ->assertOk()
            ->assertJsonPath('data.project.id', $project->id)
            ->assertJsonPath('data.sample_limit', 20)
            ->assertJsonPath('data.default_parameters.monitoring_method', 'MANUAL')
            ->assertJsonCount(2, 'data.surveys');

        $this->assertSame([$baseline->id, $monitoring->id], array_column($response->json('data.surveys'), 'id'));
    }

    public function test_calculation_requires_authentication(): void
    {
        $this->postJson('/api/calculator/co2', [])->assertUnauthorized();
    }

    public function test_calculator_requires_its_specific_permission(): void
    {
        $surveyor = User::create([
            'number_document' => 'USR-RECH-002',
            'username' => 'rech-sin-permiso',
            'password' => 'password',
            'rol_id' => Rol::where('name', 'Encuestador')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($surveyor);

        $this->getJson('/api/calculator/co2/configuration?project_id=1')
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'calculator.view');
        $this->postJson('/api/calculator/co2', [])
            ->assertForbidden()
            ->assertJsonPath('required_permission', 'calculator.view');
    }

    private function createKptDataset(): array
    {
        $project = Proyect::create(['name' => 'Proyecto RECH']);
        $baseline = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
            'expected_days' => 7,
        ]);
        $monitoring = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT monitoreo',
            'survey_type' => 'POST',
            'status' => Survey::STATUS_ACTIVE,
            'expected_days' => 7,
        ]);
        $baseline->update(['post_survey_id' => $monitoring->id]);
        $respondent = Respondent::create(['number_document' => 'DOC-RECH-1', 'names' => 'Familia RECH']);
        $household = Household::create(['code' => 'HOG-00000001']);
        $baselineParticipation = Surveyed::create([
            'respondent_id' => $respondent->id,
            'household_id' => $household->id,
            'survey_id' => $baseline->id,
            'status' => Surveyed::STATUS_FINALIZED,
        ]);
        $monitoringParticipation = Surveyed::create([
            'respondent_id' => $respondent->id,
            'household_id' => $household->id,
            'survey_id' => $monitoring->id,
            'status' => Surveyed::STATUS_FINALIZED,
        ]);
        $baselineMeasurement = SurveyedMeasurement::create(['surveyed_id' => $baselineParticipation->id, 'day_number' => 1]);
        $monitoringMeasurement = SurveyedMeasurement::create(['surveyed_id' => $monitoringParticipation->id, 'day_number' => 1]);

        $members = [
            'household.children_0_14' => 3,
            'household.women_over_14' => 0,
            'household.men_15_59' => 2,
            'household.men_over_59' => 0,
        ];
        foreach ($members as $key => $value) {
            $this->questionAndAnswer($baseline, $baselineParticipation, $baselineMeasurement, $respondent, $key, $value, 'person');
            $this->questionAndAnswer($monitoring, $monitoringParticipation, $monitoringMeasurement, $respondent, $key, $value, 'person');
        }

        foreach ([
            'baseline.initial_wood_kg' => 20,
            'baseline.additional_wood_kg' => null,
            'baseline.remaining_wood_kg' => 5,
            'baseline.charcoal_kg' => 0.5,
        ] as $key => $value) {
            $this->questionAndAnswer($baseline, $baselineParticipation, $baselineMeasurement, $respondent, $key, $value, 'kg');
        }
        foreach ([
            'monitoring.moon.initial_wood_kg' => 20,
            'monitoring.moon.additional_wood_kg' => null,
            'monitoring.moon.remaining_wood_kg' => 12,
            'monitoring.moon.charcoal_kg' => 0.3,
            'monitoring.traditional.initial_wood_kg' => 15,
            'monitoring.traditional.additional_wood_kg' => null,
            'monitoring.traditional.remaining_wood_kg' => 8,
            'monitoring.traditional.charcoal_kg' => 0.5,
        ] as $key => $value) {
            $this->questionAndAnswer($monitoring, $monitoringParticipation, $monitoringMeasurement, $respondent, $key, $value, 'kg');
        }

        return [$project, $baseline->fresh(), $monitoring];
    }

    private function questionAndAnswer(
        Survey $survey,
        Surveyed $participation,
        SurveyedMeasurement $measurement,
        Respondent $respondent,
        string $key,
        $value,
        string $unit
    ): void {
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => $key,
            'question_type' => 'LIBRE',
            'type_field' => 'NUMERICO',
            'is_required' => false,
            'calculator_key' => $key,
            'calculator_value_type' => 'number',
            'calculator_unit' => $unit,
        ]);

        if ($value !== null) {
            SurveyedResponse::create([
                'surveyed_id' => $participation->id,
                'surveyed_measurement_id' => $measurement->id,
                'respondent_id' => $respondent->id,
                'survey_question_id' => $question->id,
                'response_text' => (string) $value,
            ]);
        }
    }

    private function authenticate(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-RECH-001',
            'username' => 'rech-test',
            'password' => 'password',
            'rol_id' => Rol::where('name', 'Administrador')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]));
    }
}
