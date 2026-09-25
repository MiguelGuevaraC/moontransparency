<?php

namespace Tests\Feature;

use App\Http\Resources\SurveyResource;
use App\Models\Household;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
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
        ])->expectsOutputToContain('Hay participaciones finalizadas')
            ->assertFailed();

        $this->assertNull($baseline->fresh()->code);
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
