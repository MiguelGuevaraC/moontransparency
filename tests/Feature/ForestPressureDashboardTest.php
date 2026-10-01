<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Services\ForestPressureDatasetBuilder;
use App\Services\GeobosquesSurveyConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ForestPressureDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dataset_calculates_the_index_of_each_finalized_participation(): void
    {
        $survey = $this->forestSurvey();
        $first = $this->participation($survey, [
            'ubigeo' => 'San Juan de Lurigancho, Lima, Lima',
            'community' => 'Horacio Zeballos Gamez',
            'access_km' => '0,8',
            'agriculture' => 1,
            'settlement_km' => '3',
            'forest_use' => 2,
            'disturbance' => 1,
        ], -11.961914, -76.991916);
        $this->participation($survey, [
            'ubigeo' => 'Iquitos, Maynas, Loreto',
            'community' => 'Independencia',
            'access_km' => '0.5',
            'agriculture' => 3,
            'settlement_km' => '0.2',
            'forest_use' => 3,
            'disturbance' => 2,
        ], -3.742603, -73.245233);
        $this->participation($survey, ['ubigeo' => 'Iquitos, Maynas, Loreto'], null, null, Surveyed::STATUS_DRAFT);

        $dataset = app(ForestPressureDatasetBuilder::class)->build();

        $this->assertCount(2, $dataset['zones']);
        $this->assertSame(1, $dataset['pending_drafts']);
        $zone = $dataset['zones'][0];
        $this->assertSame('IPB-'.str_pad((string) $first->id, 4, '0', STR_PAD_LEFT), $zone['code']);
        $this->assertSame(
            ['Lima', 'Lima', 'San Juan De Lurigancho'],
            [$zone['department'], $zone['province'], $zone['district']]
        );
        $this->assertSame('Horacio Zeballos Gamez', $zone['community']);
        $this->assertSame(3, $zone['factors']['accessibility']['level']);
        $this->assertSame('0.8 km', $zone['factors']['accessibility']['value']);
        $this->assertSame(2, $zone['factors']['settlements']['level']);
        $this->assertSame('3 - 4 días por semana', $zone['factors']['forest_use']['value']);
        $this->assertSame(1.8, $zone['ipb']);
        $this->assertSame('MEDIA', $zone['level']);
        $this->assertSame(-11.961914, $zone['latitude']);
        $this->assertSame(2.8, $dataset['zones'][1]['ipb']);
        $this->assertSame('ALTA', $dataset['zones'][1]['level']);
    }

    public function test_dashboard_only_uses_the_configured_project(): void
    {
        $complete = [
            'ubigeo' => 'Iquitos, Maynas, Loreto',
            'access_km' => '7',
            'agriculture' => 1,
            'settlement_km' => '7',
            'forest_use' => 1,
            'disturbance' => 1,
        ];
        $first = $this->forestSurvey();
        $second = $this->forestSurvey();
        $second->update(['status' => Survey::STATUS_ACTIVE]);
        $this->participation($first, $complete);
        $this->participation($second, $complete);
        $this->participation($second, $complete);

        $dataset = app(ForestPressureDatasetBuilder::class)->build();

        $this->assertSame($second->proyect_id, $dataset['project']['id']);
        $this->assertCount(2, $dataset['zones']);

        config(['forest_pressure.project_id' => $first->proyect_id]);
        $dataset = app(ForestPressureDatasetBuilder::class)->build();

        $this->assertSame($first->proyect_id, $dataset['project']['id']);
        $this->assertCount(1, $dataset['zones']);
    }

    public function test_incomplete_finalized_participations_are_reported_and_excluded(): void
    {
        $survey = $this->forestSurvey();
        $this->participation($survey, ['ubigeo' => 'Iquitos, Maynas, Loreto', 'access_km' => '2']);

        $dataset = app(ForestPressureDatasetBuilder::class)->build();

        $this->assertSame([], $dataset['zones']);
        $this->assertStringContainsString('no tienen los cinco factores', $dataset['warnings'][0]);
    }

    public function test_public_portal_gets_a_temporary_link_to_the_dashboard(): void
    {
        config([
            'app.uuid' => 'public-platform-key',
            'forest_pressure.public_url' => 'https://develop.garzasoft.com/moontransparency/public',
        ]);
        $survey = $this->forestSurvey();
        $this->participation($survey, [
            'ubigeo' => 'Iquitos, Maynas, Loreto',
            'community' => 'Independencia </script>',
            'access_km' => '7',
            'agriculture' => 2,
            'settlement_km' => '2',
            'forest_use' => 2,
            'disturbance' => 1,
        ], -3.742603, -73.245233);

        $this->postJson('/api/forest-pressure/embed-link')->assertUnauthorized();

        $link = $this->withHeader('UUID', 'public-platform-key')
            ->postJson('/api/forest-pressure/embed-link')
            ->assertOk()
            ->assertJsonPath('data.methodology', 'IPB V2')
            ->json('data.iframe_url');

        $this->assertStringStartsWith(
            'https://develop.garzasoft.com/moontransparency/public/presion-bosque/embed?expires=',
            $link
        );

        $this->get(str_replace('https://develop.garzasoft.com/moontransparency/public', '', $link))
            ->assertOk()
            ->assertHeader(
                'Content-Security-Policy',
                "frame-ancestors 'self' https://moongroup-admin.vercel.app https://www.moongroup.com.pe"
            )
            ->assertSee('Dashboard de Índice de Presión sobre el Bosque', false)
            ->assertSee('geobosques.minam.gob.pe', false)
            ->assertSee('"ipb":1.6', false)
            ->assertSee('"level":"MEDIA"', false)
            ->assertDontSee('Independencia </script>', false);
    }

    public function test_dashboard_requires_a_valid_signature(): void
    {
        $this->get('/presion-bosque/embed')->assertForbidden();
        $this->get(URL::temporarySignedRoute('forest-pressure.embed', now()->addMinute(), [], false))
            ->assertOk()
            ->assertSee('No hay respuestas finalizadas para los filtros seleccionados.', false);
    }

    private function forestSurvey(): Survey
    {
        return app(GeobosquesSurveyConfigurator::class)
            ->configure(Proyect::create(['name' => 'Proyecto GeoBosques']));
    }

    private function participation(
        Survey $survey,
        array $answers,
        ?float $latitude = null,
        ?float $longitude = null,
        string $status = Surveyed::STATUS_FINALIZED
    ): Surveyed {
        $respondent = Respondent::create([
            'number_document' => (string) random_int(10000000, 99999999),
            'names' => 'Persona encuestada',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => $status,
            'completed_at' => $status === Surveyed::STATUS_FINALIZED ? now() : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        $questions = $survey->survey_questions()
            ->with(['survey_questions_options' => fn ($query) => $query->orderBy('id')])
            ->get()
            ->keyBy(fn ($question) => (int) $question->order);
        $texts = ['ubigeo' => 1, 'community' => 2, 'access_km' => 3, 'settlement_km' => 5];
        $options = ['agriculture' => 4, 'forest_use' => 6, 'disturbance' => 7];

        foreach ($texts as $key => $order) {
            if (isset($answers[$key])) {
                $this->answer($surveyed, $questions[$order]->id, $answers[$key]);
            }
        }

        foreach ($options as $key => $order) {
            if (! isset($answers[$key])) {
                continue;
            }
            $response = $this->answer($surveyed, $questions[$order]->id, null);
            SurveyedResponseOption::create([
                'surveyed_response_id' => $response->id,
                'survey_question_options_id' => $questions[$order]->survey_questions_options[$answers[$key] - 1]->id,
                'surveyed_id' => $surveyed->id,
                'respondent_id' => $respondent->id,
            ]);
        }

        return $surveyed;
    }

    private function answer(Surveyed $surveyed, int $questionId, ?string $text): SurveyedResponse
    {
        return SurveyedResponse::create([
            'surveyed_id' => $surveyed->id,
            'survey_question_id' => $questionId,
            'respondent_id' => $surveyed->respondent_id,
            'response_text' => $text,
        ]);
    }
}
