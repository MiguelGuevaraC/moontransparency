<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyDecimalResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_answers_accept_dot_or_comma_and_are_stored_canonically(): void
    {
        [$survey, $question] = $this->surveyAndQuestion('DECIMAL');

        $created = $this->postJson('/api/response-survey', $this->payload(
            $survey,
            $question,
            '12,500'
        ))->assertOk();

        $this->assertDatabaseHas('surveyed_responses', [
            'surveyed_id' => $created->json('data.id'),
            'survey_question_id' => $question->id,
            'response_text' => '12.5',
        ]);

        $this->postJson(
            '/api/response-survey/'.$created->json('data.id'),
            $this->payload($survey, $question, 7.25)
        )->assertOk();

        $this->assertSame(
            '7.25',
            SurveyedResponse::where('survey_question_id', $question->id)->value('response_text')
        );
    }

    public function test_integer_answers_reject_decimals(): void
    {
        [$survey, $question] = $this->surveyAndQuestion('NUMERICO');

        $this->postJson('/api/response-survey', $this->payload(
            $survey,
            $question,
            '3.5'
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responses.0.response_text']);

        $this->assertDatabaseCount('surveyed_responses', 0);
    }

    private function surveyAndQuestion(string $fieldType): array
    {
        $project = Proyect::create(['name' => 'Proyecto decimales']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta decimal '.$fieldType,
            'status' => 'ACTIVA',
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_type' => 'LIBRE',
            'type_field' => $fieldType,
            'question_text' => 'Valor de prueba',
            'justification' => 'Prueba',
        ]);

        return [$survey, $question];
    }

    private function payload(Survey $survey, SurveyQuestion $question, mixed $value): array
    {
        return [
            'number_document' => 'DOC-DECIMAL-1',
            'names' => 'Persona Decimal',
            'survey_id' => $survey->id,
            'responses' => [[
                'survey_question_id' => $question->id,
                'response_text' => $value,
            ]],
        ];
    }
}
