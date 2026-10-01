<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\User;
use App\Services\KptCo2SurveyConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Flujo reportado: la app guarda cada día por separado, reabre la encuesta
 * y luego intenta finalizarla.
 */
class KptReopenAndFinalizeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reopened_participation_returns_the_initial_data_saved_on_day_one(): void
    {
        $survey = $this->baselineSurvey();
        $id = $this->saveDay($survey, 1, includeSetup: true);
        $this->saveDay($survey, 2, includeSetup: false, id: $id);

        $setupIds = $this->setupQuestions($survey)->pluck('id')->all();
        $responses = collect($this->getJson("/api/surveyed/$id")->assertOk()->json('data.surveyed_responses'));
        $setup = $responses->whereIn('survey_question_id', $setupIds)->values();

        $this->assertCount(4, $setup);
        $this->assertSame([null], $setup->pluck('day_number')->unique()->values()->all());
        $this->assertSame('HOG-PRUEBA-1', $setup->firstWhere('survey_question_id', $setupIds[0])['response_text']);

        // Las respuestas por día (measurements[].responses) no incluyen los datos iniciales.
        $dayOne = collect($this->getJson("/api/surveyed/$id")->json('data.measurements'))->firstWhere('day_number', 1);
        $this->assertEmpty(collect($dayOne['responses'])->whereIn('survey_question_id', $setupIds));
    }

    public function test_finalization_requires_the_seven_days(): void
    {
        $survey = $this->baselineSurvey();
        $id = $this->saveDay($survey, 1, includeSetup: true);
        $this->saveDay($survey, 2, includeSetup: false, id: $id);

        $errors = $this->postJson("/api/response-survey/$id/finalize", $this->identity($survey))
            ->assertUnprocessable()
            ->json('errors');

        // Con los días 1 y 2 guardados faltan 8 preguntas diarias x 5 días.
        // El primer error es "pregunta obligatoria ... para el día 3", como en el reporte.
        $this->assertCount(40, $errors);
        $this->assertStringContainsString('para el día 3', array_values($errors)[0][0]);
        $this->assertTrue(collect(array_keys($errors))->every(fn ($key) => preg_match('/\.day_[3-7]$/', $key) === 1));

        foreach (range(3, 7) as $day) {
            $this->saveDay($survey, $day, includeSetup: false, id: $id);
        }

        $this->postJson("/api/response-survey/$id/finalize", $this->identity($survey))
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_FINALIZED);
    }

    private function baselineSurvey(): Survey
    {
        $survey = app(KptCo2SurveyConfigurator::class)
            ->configure(Proyect::create(['name' => 'Proyecto KPT']))['baseline'];
        $survey->update(['status' => Survey::STATUS_ACTIVE]);
        Sanctum::actingAs($this->supervisor());

        return $survey->fresh(['survey_questions.survey_questions_options']);
    }

    private function saveDay(Survey $survey, int $day, bool $includeSetup, ?int $id = null): int
    {
        $responses = $survey->survey_questions
            ->filter(fn (SurveyQuestion $question) => $question->response_scope === SurveyQuestion::RESPONSE_SCOPE_MEASUREMENT
                ? $question->appliesToDay($day)
                : $includeSetup)
            ->map(fn (SurveyQuestion $question) => $this->answer($question, $day))
            ->values()
            ->all();
        $payload = $this->identity($survey) + [
            'day_number' => $day,
            'responses' => $responses,
        ];

        $response = $id
            ? $this->postJson("/api/response-survey/$id", $payload)
            : $this->postJson('/api/response-survey', $payload);

        return (int) $response->assertOk()->json('data.id');
    }

    private function identity(Survey $survey): array
    {
        return [
            'number_document' => '45678912',
            'names' => 'Persona KPT',
            'survey_id' => $survey->id,
            'household_code' => 'HOG-PRUEBA-1',
        ];
    }

    private function answer(SurveyQuestion $question, int $day): array
    {
        if ($question->question_type === 'OPCIONES') {
            return [
                'survey_question_id' => $question->id,
                'survey_question_option_id' => [$question->survey_questions_options->first()->id],
            ];
        }

        $text = match (true) {
            $question->calculator_key === 'household.identifier' => 'HOG-PRUEBA-1',
            $question->calculator_value_type === 'date' => sprintf('2026-09-%02d', $day),
            $question->calculator_value_type === 'time' => '07:30',
            default => '2',
        };

        return ['survey_question_id' => $question->id, 'response_text' => $text];
    }

    private function setupQuestions(Survey $survey)
    {
        return $survey->survey_questions
            ->where('response_scope', SurveyQuestion::RESPONSE_SCOPE_PARTICIPATION)
            ->sortBy('order')
            ->values();
    }

    private function supervisor(): User
    {
        $role = Rol::firstOrCreate(['name' => 'Supervisor'], ['status' => Rol::STATUS_ACTIVE]);
        foreach (['participations.view', 'participations.manage'] as $code) {
            $permission = Permission::where('route', $code)->firstOrFail();
            $role->permissions()->syncWithoutDetaching([$permission->id => [
                'name_permission' => $permission->name,
                'name_rol' => $role->name,
                'type' => $permission->type,
            ]]);
        }

        return User::create([
            'number_document' => 'DOC-SUPERVISOR-KPT',
            'names' => 'Supervisor KPT',
            'username' => 'supervisor-kpt',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
