<?php

namespace Tests\Feature;

use App\Http\Resources\SurveyResource;
use App\Models\Household;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use App\Services\KptCo2SurveyConfigurator;
use App\Services\SurveyedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KptCo2SurveyConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_synchronizes_the_excel_instrument_and_preserves_existing_survey_ids(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $baseline = $this->survey($project, 'KPT línea base', 'PRE');
        $monitoring = $this->survey($project, 'KPT monitoreo', 'POST');
        $oldQuestion = SurveyQuestion::create([
            'survey_id' => $baseline->id,
            'question_text' => 'ID del hogar',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'calculator_key' => 'household.identifier',
            'order' => 1,
            'is_required' => true,
        ]);

        $configured = app(KptCo2SurveyConfigurator::class)->configure($project);

        $this->assertSame($baseline->id, $configured['baseline']->id);
        $this->assertSame($monitoring->id, $configured['monitoring']->id);
        $this->assertSame($monitoring->id, $configured['baseline']->post_survey_id);
        $this->assertNull($configured['monitoring']->post_survey_id);
        $this->assertSame('KPT_CO2_BASELINE', $configured['baseline']->code);
        $this->assertSame('KPT_CO2_MONITORING', $configured['monitoring']->code);
        $this->assertCount(13, $configured['baseline']->survey_questions);
        $this->assertCount(21, $configured['monitoring']->survey_questions);
        $this->assertSame(
            $oldQuestion->id,
            $configured['baseline']->survey_questions->firstWhere('instrument_key', 'baseline.household_id')->id
        );
        $this->assertFalse(
            $configured['baseline']->survey_questions->contains('calculator_key', 'measurement.day')
        );
        $this->assertSame(
            ['DECIMAL'],
            $configured['baseline']->survey_questions
                ->filter(fn ($question) => str_ends_with((string) $question->calculator_key, '_kg'))
                ->pluck('type_field')
                ->unique()
                ->values()
                ->all()
        );

        $monitoringResource = (new SurveyResource($configured['monitoring']))->resolve();
        $this->assertSame(
            ['MONITORING_COMBINED', 'MONITORING_MOON_ONLY'],
            array_keys($monitoringResource['variants'])
        );
        $this->assertSame(
            'SEARCHABLE_SELECT',
            $monitoringResource['household_identifier']['mode']
        );
        $this->assertSame(
            $configured['baseline']->id,
            $monitoringResource['household_identifier']['linked_pre_survey']['id']
        );
    }

    public function test_configuration_is_idempotent(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $configurator = app(KptCo2SurveyConfigurator::class);
        $first = $configurator->configure($project);
        $questionIds = collect($first)
            ->flatMap(fn (Survey $survey) => $survey->survey_questions->pluck('id'))
            ->all();

        $second = $configurator->configure($project);

        $this->assertSame($first['baseline']->id, $second['baseline']->id);
        $this->assertSame($first['monitoring']->id, $second['monitoring']->id);
        $this->assertSame(
            $questionIds,
            collect($second)->flatMap(fn (Survey $survey) => $survey->survey_questions->pluck('id'))->all()
        );
        $this->assertDatabaseCount('surveys', 2);
        $this->assertDatabaseCount('survey_questions', 34);
    }

    public function test_command_is_dry_run_by_default(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);

        $this->artisan('survey:sync-kpt-co2', ['project' => $project->id])
            ->expectsOutputToContain('Vista previa completada')
            ->assertSuccessful();

        $this->assertDatabaseCount('surveys', 0);
    }

    public function test_command_backs_up_and_cleans_drafts_before_synchronizing(): void
    {
        Storage::fake('local');
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $baseline = $this->survey($project, 'KPT línea base', 'PRE');
        $monitoring = $this->survey($project, 'KPT monitoreo', 'POST');
        Surveyed::create(['survey_id' => $baseline->id, 'status' => Surveyed::STATUS_DRAFT]);
        Surveyed::create(['survey_id' => $monitoring->id, 'status' => Surveyed::STATUS_DRAFT]);
        $actor = $this->administrator();

        $this->artisan('survey:sync-kpt-co2', [
            'project' => $project->id,
            '--apply' => true,
            '--clean-drafts' => true,
            '--actor' => $actor->id,
            '--confirmation' => 'SYNC_KPT_CO2',
        ])->expectsOutputToContain('Encuestas KPT CO2 sincronizadas correctamente')
            ->assertSuccessful();

        $this->assertDatabaseCount('surveyeds', 0);
        $this->assertDatabaseCount('survey_cleanup_audits', 2);
        $this->assertSame($monitoring->id, $baseline->fresh()->post_survey_id);
        $this->assertSame('KPT_CO2_BASELINE', $baseline->fresh()->code);
        $this->assertSame('KPT_CO2_MONITORING', $monitoring->fresh()->code);
    }

    public function test_command_refuses_to_replace_finalized_data(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $baseline = $this->survey($project, 'KPT línea base', 'PRE');
        Surveyed::create([
            'survey_id' => $baseline->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => now(),
        ]);

        $this->artisan('survey:sync-kpt-co2', [
            'project' => $project->id,
            '--apply' => true,
        ])->expectsOutputToContain('Existen participaciones finalizadas')
            ->assertFailed();

        $this->assertNull($baseline->fresh()->code);
    }

    public function test_command_backs_up_and_migrates_finalized_data_without_deleting_it(): void
    {
        Storage::fake('local');
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $baseline = $this->survey($project, 'KPT línea base', 'PRE');
        $monitoring = $this->survey($project, 'KPT monitoreo', 'POST');
        $baseline->update(['post_survey_id' => $monitoring->id]);
        $baselineQuestions = [
            'household' => $this->legacyQuestion($baseline, 1, 'ID del hogar', 'household.identifier', 'CORTO'),
            'initial' => $this->legacyQuestion($baseline, 11, 'Peso inicial de leña (solo el día 1)', 'baseline.initial_wood_kg'),
            'observations' => $this->legacyQuestion($baseline, 16, 'Observaciones del día de medición', null, 'LARGO'),
        ];
        $monitoringQuestions = [
            'household' => $this->legacyQuestion($monitoring, 1, 'ID del hogar', 'household.identifier', 'CORTO'),
            'moon_initial' => $this->legacyQuestion($monitoring, 10, 'Peso inicial de leña (cocina mejorada) (solo el día 1)', 'monitoring.moon.initial_wood_kg'),
            'traditional_initial' => $this->legacyQuestion($monitoring, 14, 'Peso inicial de leña (cocina tradicional) (solo el día 1)', 'monitoring.traditional.initial_wood_kg'),
            'observations' => $this->legacyQuestion($monitoring, 23, 'Observaciones del día de medición', null, 'LARGO'),
        ];
        $baselineParticipation = $this->finalizedParticipation($baseline, 'BASE-100', 'Familia finalizada');
        $monitoringParticipation = $this->finalizedParticipation($monitoring, 'MON-100', 'Familia monitoreada');
        $baselineDay = SurveyedMeasurement::create([
            'surveyed_id' => $baselineParticipation->id,
            'day_number' => 1,
        ]);
        $monitoringDay = SurveyedMeasurement::create([
            'surveyed_id' => $monitoringParticipation->id,
            'day_number' => 1,
        ]);

        $this->legacyResponse($baselineParticipation, $baselineDay, $baselineQuestions['household'], 'FAMILIA-100');
        $baselineInitial = $this->legacyResponse($baselineParticipation, $baselineDay, $baselineQuestions['initial'], '12.5');
        $baselineObservation = $this->legacyResponse($baselineParticipation, $baselineDay, $baselineQuestions['observations'], 'Sin incidentes');
        $this->legacyResponse($monitoringParticipation, $monitoringDay, $monitoringQuestions['household'], 'FAMILIA-100');
        $moonInitial = $this->legacyResponse($monitoringParticipation, $monitoringDay, $monitoringQuestions['moon_initial'], '8.5');
        $this->legacyResponse($monitoringParticipation, $monitoringDay, $monitoringQuestions['traditional_initial'], '10.5');
        $monitoringObservation = $this->legacyResponse($monitoringParticipation, $monitoringDay, $monitoringQuestions['observations'], 'Monitoreo conforme');
        $draft = Surveyed::create([
            'survey_id' => $baseline->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);
        $actor = $this->administrator();

        $this->artisan('survey:sync-kpt-co2', [
            'project' => $project->id,
            '--apply' => true,
            '--clean-drafts' => true,
            '--migrate-finalized' => true,
            '--actor' => $actor->id,
            '--confirmation' => 'SYNC_KPT_CO2',
        ])->expectsOutputToContain('Respaldo finalizadas:')
            ->expectsOutputToContain('Encuestas KPT CO2 sincronizadas correctamente')
            ->assertSuccessful();

        $this->assertDatabaseMissing('surveyeds', ['id' => $draft->id]);
        $this->assertDatabaseHas('surveyeds', [
            'id' => $baselineParticipation->id,
            'status' => Surveyed::STATUS_FINALIZED,
        ]);
        $this->assertDatabaseHas('surveyeds', [
            'id' => $monitoringParticipation->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'survey_variant' => 'MONITORING_COMBINED',
        ]);
        $householdId = $baselineParticipation->fresh()->household_id;
        $this->assertNotNull($householdId);
        $this->assertSame($householdId, $monitoringParticipation->fresh()->household_id);
        $this->assertDatabaseHas('households', [
            'id' => $householdId,
            'code' => 'FAMILIA-100',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $baselineInitial->id,
            'surveyed_measurement_id' => null,
            'response_text' => '12.5',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $moonInitial->id,
            'surveyed_measurement_id' => null,
            'response_text' => '8.5',
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $baselineObservation->id,
            'survey_question_id' => $baselineQuestions['observations']->id,
            'surveyed_measurement_id' => $baselineDay->id,
        ]);
        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $monitoringObservation->id,
            'survey_question_id' => $monitoringQuestions['observations']->id,
            'surveyed_measurement_id' => $monitoringDay->id,
        ]);
        $this->assertDatabaseCount('survey_instrument_migration_audits', 1);
        $audit = \App\Models\SurveyInstrumentMigrationAudit::firstOrFail();
        Storage::disk('local')->assertExists($audit->backup_path);
        $this->assertSame(2, $audit->migrated_counts['finalized_participations']);
        $this->assertDatabaseCount('survey_cleanup_audits', 1);
    }

    public function test_monitoring_keeps_global_answers_outside_days_and_rejects_mixed_cases(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $configured = app(KptCo2SurveyConfigurator::class)->configure($project);
        $baseline = $configured['baseline'];
        $monitoring = $configured['monitoring'];
        $household = Household::create(['code' => 'HOG-TEST-001']);
        $baselinePerson = Respondent::create([
            'number_document' => 'BASE-001',
            'names' => 'Familia base',
        ]);
        Surveyed::create([
            'survey_id' => $baseline->id,
            'respondent_id' => $baselinePerson->id,
            'household_id' => $household->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => now(),
        ]);
        $children = $monitoring->survey_questions->firstWhere('instrument_key', 'monitoring.children');
        $moonOnlyInitial = $monitoring->survey_questions->firstWhere('instrument_key', 'monitoring.moon_only.initial');
        $combinedRemaining = $monitoring->survey_questions->firstWhere('instrument_key', 'monitoring.combined.moon_remaining');
        $service = app(SurveyedService::class);

        $participation = $service->createSurveyed([
            'number_document' => 'MON-001',
            'names' => 'Familia monitoreada',
            'survey_id' => $monitoring->id,
            'household_code' => $household->code,
            'survey_variant' => 'MONITORING_MOON_ONLY',
            'responses' => [[
                'survey_question_id' => $children->id,
                'response_text' => '2',
            ]],
        ]);

        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $participation->id,
            'survey_question_id' => $children->id,
            'surveyed_measurement_id' => null,
            'response_text' => '2',
        ]);

        $service->updateSurveyedById($participation->id, [
            'names' => 'Familia monitoreada',
            'survey_id' => $monitoring->id,
            'day_number' => 1,
            'responses' => [[
                'survey_question_id' => $moonOnlyInitial->id,
                'response_text' => '10,5',
            ]],
        ]);

        $this->assertSame('MONITORING_MOON_ONLY', $participation->fresh()->survey_variant);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $participation->id,
            'survey_question_id' => $moonOnlyInitial->id,
            'response_text' => '10.5',
        ]);

        $this->expectException(ValidationException::class);
        $service->updateSurveyedById($participation->id, [
            'names' => 'Familia monitoreada',
            'survey_id' => $monitoring->id,
            'day_number' => 1,
            'responses' => [[
                'survey_question_id' => $combinedRemaining->id,
                'response_text' => '5',
            ]],
        ]);
    }

    private function survey(Proyect $project, string $name, string $type): Survey
    {
        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => $name,
            'survey_type' => $type,
            'description' => 'Versión anterior',
            'status' => Survey::STATUS_ACTIVE,
            'expected_days' => 7,
        ]);
    }

    private function legacyQuestion(
        Survey $survey,
        int $order,
        string $text,
        ?string $calculatorKey,
        string $fieldType = 'NUMERICO'
    ): SurveyQuestion {
        return SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => $text,
            'question_type' => 'LIBRE',
            'type_field' => $fieldType,
            'calculator_key' => $calculatorKey,
            'order' => $order,
            'is_required' => true,
        ]);
    }

    private function finalizedParticipation(Survey $survey, string $document, string $names): Surveyed
    {
        $respondent = Respondent::create([
            'number_document' => $document,
            'names' => $names,
        ]);

        return Surveyed::create([
            'survey_id' => $survey->id,
            'respondent_id' => $respondent->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => now(),
        ]);
    }

    private function legacyResponse(
        Surveyed $participation,
        SurveyedMeasurement $measurement,
        SurveyQuestion $question,
        string $value
    ): SurveyedResponse {
        return SurveyedResponse::create([
            'surveyed_id' => $participation->id,
            'respondent_id' => $participation->respondent_id,
            'surveyed_measurement_id' => $measurement->id,
            'survey_question_id' => $question->id,
            'response_text' => $value,
        ]);
    }

    private function administrator(): User
    {
        $role = Rol::firstOrCreate(
            ['name' => 'Administrador'],
            ['status' => Rol::STATUS_ACTIVE]
        );

        return User::create([
            'type_document' => 'DNI',
            'number_document' => '00000001',
            'names' => 'Administrador KPT',
            'username' => 'admin-kpt',
            'password' => 'secret123',
            'email' => 'admin-kpt@example.test',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
