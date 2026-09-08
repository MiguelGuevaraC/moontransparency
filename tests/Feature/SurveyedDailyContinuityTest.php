<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedDailyContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_questions_from_days_one_to_seven_remain_in_one_participation(): void
    {
        [$survey, $dayQuestion, $dayOptions, $weightQuestion, $notesQuestion] = $this->createDailySurvey();
        $surveyedId = null;

        foreach (range(1, 7) as $day) {
            $payload = $this->payload(
                $survey,
                $dayQuestion,
                $dayOptions[$day],
                $weightQuestion,
                $notesQuestion,
                $day
            );

            $response = $day === 1
                ? $this->postJson('/api/response-survey', $payload)
                : $this->postJson("/api/response-survey/$surveyedId", $payload);

            $response
                ->assertOk()
                ->assertJsonPath('data.status', Surveyed::STATUS_DRAFT);

            $currentId = (int) $response->json('data.id');
            $surveyedId ??= $currentId;

            $this->assertSame($surveyedId, $currentId);
            $this->assertDatabaseCount('surveyeds', 1);
            $this->assertDatabaseCount('surveyed_measurements', $day);
            $this->assertDatabaseCount('surveyed_responses', $day * 3);
        }

        foreach (range(1, 7) as $day) {
            $this->assertDatabaseHas('surveyed_measurements', [
                'surveyed_id' => $surveyedId,
                'day_number' => $day,
            ]);

            $measurementId = (int) DB::table('surveyed_measurements')
                ->where('surveyed_id', $surveyedId)
                ->where('day_number', $day)
                ->value('id');

            $this->assertDatabaseHas('surveyed_responses', [
                'surveyed_id' => $surveyedId,
                'surveyed_measurement_id' => $measurementId,
                'survey_question_id' => $weightQuestion->id,
                'response_text' => (string) (20 - $day),
            ]);
        }

        Sanctum::actingAs(User::create([
            'number_document' => 'USR-DAYS-001',
            'username' => 'days-test',
            'password' => bcrypt('password'),
            'rol_id' => \App\Models\Rol::where('name', 'Encuestador')->value('id'),
            'status' => 'Activo',
        ]));

        $detail = $this->getJson("/api/surveyed/$surveyedId")
            ->assertOk()
            ->assertJsonPath('data.id', $surveyedId)
            ->json('data');

        $this->assertCount(7, $detail['measurements']);
        $this->assertSame(range(1, 7), array_column($detail['measurements'], 'day_number'));
        $this->assertCount(21, $detail['surveyed_responses']);

        foreach ($detail['measurements'] as $index => $measurement) {
            $day = $index + 1;

            $this->assertSame($day, (int) $measurement['day_number']);
            $this->assertCount(3, $measurement['responses']);
            $this->assertSame(
                [$day, $day, $day],
                array_map(static fn (array $answer) => (int) $answer['day_number'], $measurement['responses'])
            );

            $weightResponse = collect($measurement['responses'])
                ->firstWhere('survey_question_id', $weightQuestion->id);

            $this->assertSame((string) (20 - $day), $weightResponse['response_text']);
        }
    }

    public function test_saving_the_same_day_updates_answers_without_duplication(): void
    {
        [$survey, $dayQuestion, $dayOptions, $weightQuestion, $notesQuestion] = $this->createDailySurvey();
        $payload = $this->payload(
            $survey,
            $dayQuestion,
            $dayOptions[1],
            $weightQuestion,
            $notesQuestion,
            1
        );

        $created = $this->postJson('/api/response-survey', $payload)->assertOk();
        $surveyedId = (int) $created->json('data.id');

        $payload['responses'][1]['response_text'] = '99.50';
        $payload['responses'][2]['response_text'] = 'Medición corregida';

        $this->postJson("/api/response-survey/$surveyedId", $payload)
            ->assertOk()
            ->assertJsonPath('data.measurements.0.day_number', 1);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 1);
        $this->assertDatabaseCount('surveyed_responses', 3);
        $this->assertDatabaseCount('surveyed_response_options', 1);
        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $surveyedId,
            'survey_question_id' => $weightQuestion->id,
            'response_text' => '99.50',
        ]);
    }

    public function test_explicit_day_must_match_the_selected_day_option(): void
    {
        [$survey, $dayQuestion, $dayOptions, $weightQuestion, $notesQuestion] = $this->createDailySurvey();
        $payload = $this->payload(
            $survey,
            $dayQuestion,
            $dayOptions[2],
            $weightQuestion,
            $notesQuestion,
            2
        );
        $payload['day_number'] = 3;

        $this->postJson('/api/response-survey', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('day_number');

        $this->assertDatabaseCount('surveyeds', 0);
        $this->assertDatabaseCount('surveyed_measurements', 0);
        $this->assertDatabaseCount('surveyed_responses', 0);
    }

    public function test_day_number_respects_the_limit_configured_on_the_survey(): void
    {
        [$survey, $dayQuestion, $dayOptions, $weightQuestion, $notesQuestion] = $this->createDailySurvey();
        $survey->update(['expected_days' => 3]);
        $payload = $this->payload(
            $survey,
            $dayQuestion,
            $dayOptions[4],
            $weightQuestion,
            $notesQuestion,
            4
        );

        $this->postJson('/api/response-survey', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('responses.0.survey_question_option_id');

        $this->assertDatabaseCount('surveyeds', 0);
    }

    private function createDailySurvey(): array
    {
        $project = Proyect::create(['name' => 'Proyecto de continuidad']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT monitoreo',
            'status' => 'ACTIVA',
        ]);
        $dayQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'calculator_key' => 'measurement.day',
            'question_text' => 'Día de medición',
            'question_type' => 'OPCIONES',
            'type_field' => 'SELECT',
            'order' => 1,
            'is_required' => true,
        ]);
        $dayOptions = collect(range(1, 7))->mapWithKeys(function (int $day) use ($dayQuestion) {
            return [$day => SurveyQuestionOption::create([
                'survey_question_id' => $dayQuestion->id,
                'description' => (string) $day,
            ])];
        });
        $weightQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Peso final',
            'question_type' => 'LIBRE',
            'type_field' => 'NUMERO',
            'order' => 2,
            'is_required' => true,
        ]);
        $notesQuestion = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Observaciones',
            'question_type' => 'LIBRE',
            'type_field' => 'TEXTO',
            'order' => 3,
            'is_required' => false,
        ]);

        return [$survey, $dayQuestion, $dayOptions, $weightQuestion, $notesQuestion];
    }

    private function payload(
        Survey $survey,
        SurveyQuestion $dayQuestion,
        SurveyQuestionOption $dayOption,
        SurveyQuestion $weightQuestion,
        SurveyQuestion $notesQuestion,
        int $day
    ): array {
        return [
            'number_document' => 'HOGAR-001',
            'names' => 'Hogar de prueba',
            'survey_id' => $survey->id,
            'responses' => [
                [
                    'survey_question_id' => $dayQuestion->id,
                    'survey_question_option_id' => [$dayOption->id],
                ],
                [
                    'survey_question_id' => $weightQuestion->id,
                    'response_text' => (string) (20 - $day),
                ],
                [
                    'survey_question_id' => $notesQuestion->id,
                    'response_text' => "Medición del día $day",
                ],
            ],
        ];
    }
}
