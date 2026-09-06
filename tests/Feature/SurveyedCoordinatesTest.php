<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Services\GeobosquesSurveyConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyedCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_manual_coordinates_as_separate_participation_fields(): void
    {
        $survey = $this->createGeobosquesSurvey();

        $response = $this->postJson('/api/response-survey', $this->payload($survey, [
            'latitude' => -6.3945400,
            'longitude' => -79.8224030,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.latitude', -6.39454)
            ->assertJsonPath('data.longitude', -79.822403)
            ->assertJsonPath('data.geobosques_map.available', true)
            ->assertJsonPath(
                'data.geobosques_map.viewer_url',
                'https://geobosques.minam.gob.pe/geobosque/visor/index.php?xy=-6.39454,-79.822403'
            )
            ->assertJsonPath('data.geobosques_map.marker_parameter', 'xy')
            ->assertJsonPath('data.geobosques_map.load_strategy', 'WHEN_ONLINE');

        $this->assertDatabaseHas('surveyeds', [
            'id' => $response->json('data.id'),
            'latitude' => '-6.3945400',
            'longitude' => '-79.8224030',
            'status' => Surveyed::STATUS_DRAFT,
        ]);
    }

    public function test_it_rejects_coordinates_outside_the_allowed_ranges(): void
    {
        $survey = $this->createGeobosquesSurvey();

        $this->postJson('/api/response-survey', $this->payload($survey, [
            'latitude' => -90.0000001,
            'longitude' => -79.822403,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La latitud debe estar entre -90 y 90.');

        $this->postJson('/api/response-survey', $this->payload($survey, [
            'latitude' => -6.39454,
            'longitude' => 180.0000001,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La longitud debe estar entre -180 y 180.');

        $this->assertDatabaseCount('surveyeds', 0);
    }

    public function test_it_rejects_an_incomplete_coordinate_pair(): void
    {
        $survey = $this->createGeobosquesSurvey();

        $this->postJson('/api/response-survey', $this->payload($survey, [
            'latitude' => -6.39454,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La latitud y la longitud deben enviarse juntas.');

        $this->postJson('/api/response-survey', $this->payload($survey, [
            'longitude' => -79.822403,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La latitud y la longitud deben enviarse juntas.');

        $this->assertDatabaseCount('surveyeds', 0);
    }

    public function test_a_draft_may_omit_coordinates_but_geobosques_requires_them_when_finalizing(): void
    {
        $survey = $this->createGeobosquesSurveyWithOneRequiredLocation();
        $locationQuestion = $survey->survey_questions()->firstOrFail();
        $payload = $this->payload($survey, [
            'responses' => [[
                'survey_question_id' => $locationQuestion->id,
                'response_text' => 'Chiclayo, Chiclayo, Lambayeque',
            ]],
        ]);

        $draft = $this->postJson('/api/response-survey', $payload)
            ->assertOk()
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null)
            ->assertJsonPath('data.geobosques_map.available', false)
            ->assertJsonPath(
                'data.geobosques_map.message',
                'No hay coordenadas registradas para mostrar el mapa.'
            );

        $surveyedId = $draft->json('data.id');

        $this->postJson("/api/response-survey/$surveyedId/finalize", $payload)
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'La latitud y la longitud son obligatorias para finalizar la encuesta GeoBosques.'
            );

        $this->postJson("/api/response-survey/$surveyedId/finalize", $payload + [
            'latitude' => -6.39454,
            'longitude' => -79.822403,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Surveyed::STATUS_FINALIZED)
            ->assertJsonPath('data.latitude', -6.39454)
            ->assertJsonPath('data.longitude', -79.822403);
    }

    public function test_it_updates_both_coordinates_without_losing_them_on_later_partial_saves(): void
    {
        $survey = $this->createGeobosquesSurvey();
        $created = $this->postJson('/api/response-survey', $this->payload($survey, [
            'latitude' => -6.39454,
            'longitude' => -79.822403,
        ]))->assertOk();
        $surveyedId = $created->json('data.id');

        $this->postJson("/api/response-survey/$surveyedId", $this->payload($survey, [
            'latitude' => -8.3799965,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La latitud y la longitud deben enviarse juntas.');

        $this->postJson("/api/response-survey/$surveyedId", $this->payload($survey, [
            'latitude' => -8.3799965,
            'longitude' => -75.1142120,
        ]))
            ->assertOk()
            ->assertJsonPath('data.latitude', -8.3799965)
            ->assertJsonPath('data.longitude', -75.114212);

        $this->postJson(
            "/api/response-survey/$surveyedId",
            $this->payload($survey)
        )
            ->assertOk()
            ->assertJsonPath('data.latitude', -8.3799965)
            ->assertJsonPath('data.longitude', -75.114212);
    }

    private function createGeobosquesSurvey(): Survey
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);

        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => GeobosquesSurveyConfigurator::SURVEY_NAME,
            'survey_type' => 'PRE',
            'status' => 'ACTIVA',
        ]);
    }

    private function createGeobosquesSurveyWithOneRequiredLocation(): Survey
    {
        $survey = $this->createGeobosquesSurvey();
        SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Ubicación',
            'question_type' => 'UBICACION',
            'type_field' => 'UBICACION',
            'order' => 1,
            'is_required' => true,
        ]);

        return $survey;
    }

    private function payload(Survey $survey, array $overrides = []): array
    {
        return $overrides + [
            'number_document' => 'DOC-GEO-001',
            'names' => 'Persona GeoBosques',
            'survey_id' => $survey->id,
            'responses' => [],
        ];
    }
}
