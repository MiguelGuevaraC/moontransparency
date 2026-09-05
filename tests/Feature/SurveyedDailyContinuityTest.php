<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedDailyContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_days_one_to_seven_remain_in_one_retrievable_participation(): void
    {
        $project = Proyect::create(['name' => 'Proyecto de continuidad']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Mediciones de siete días',
            'status' => 'ACTIVA',
        ]);
        $questions = collect(range(1, 7))->mapWithKeys(function (int $day) use ($survey) {
            $question = SurveyQuestion::create([
                'survey_id' => $survey->id,
                'question_text' => "Medición día $day",
                'question_type' => 'LIBRE',
                'type_field' => "DIA_$day",
                'order' => $day,
                'is_required' => true,
            ]);

            return [$day => $question];
        });

        $surveyedId = null;

        foreach (range(1, 7) as $day) {
            $payload = $this->payload($survey, $questions[$day], $day);

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
            $this->assertDatabaseCount('surveyed_responses', $day);
        }

        foreach (range(1, 7) as $day) {
            $this->assertDatabaseHas('surveyed_responses', [
                'surveyed_id' => $surveyedId,
                'respondent_id' => Surveyed::findOrFail($surveyedId)->respondent_id,
                'survey_question_id' => $questions[$day]->id,
                'response_text' => (string) (20 - $day),
            ]);
        }

        Sanctum::actingAs(User::create([
            'number_document' => 'USR-DAYS-001',
            'username' => 'days-test',
            'password' => bcrypt('password'),
            'status' => 'Activo',
        ]));

        $detail = $this->getJson("/api/surveyed/$surveyedId")
            ->assertOk()
            ->assertJsonPath('data.id', $surveyedId)
            ->json('data.surveyed_responses');

        $this->assertCount(7, $detail);
        $this->assertSame(
            range(1, 7),
            array_map(static fn (array $response) => (int) $response['survey_question_order'], $detail)
        );
        $this->assertSame(
            array_map(static fn (int $day) => "DIA_$day", range(1, 7)),
            array_column($detail, 'survey_question_type_field')
        );

        foreach ($detail as $response) {
            $this->assertSame($surveyedId, (int) $response['surveyed_id']);
            $this->assertNotNull($response['respondent_id']);
            $this->assertArrayHasKey('survey_question_id', $response);
            $this->assertArrayHasKey('response_text', $response);
        }
    }

    private function payload(Survey $survey, SurveyQuestion $question, int $day): array
    {
        return [
            'number_document' => 'HOGAR-001',
            'names' => 'Hogar de prueba',
            'survey_id' => $survey->id,
            'responses' => [
                [
                    'survey_question_id' => $question->id,
                    'response_text' => (string) (20 - $day),
                ],
            ],
        ];
    }
}
