<?php

namespace Tests\Feature;

use App\Http\Requests\SurveyQuestionRequest\StoreSurveyQuestionRequest;
use App\Http\Requests\SurveyQuestionRequest\UpdateSurveyQuestionRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Proyect;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Services\GeobosquesSurveyConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GeobosquesSurveyConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_configures_the_forest_pressure_instrument_in_the_dynamic_module(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);

        $this->artisan('survey:configure-geobosques', ['project' => $project->id])
            ->expectsOutputToContain('Encuesta GeoBosques configurada')
            ->expectsOutputToContain('Estado: INACTIVA')
            ->expectsOutputToContain('nueve campos visibles están configurados como obligatorios')
            ->assertSuccessful();

        $survey = $project->surveys()->where(
            'survey_name',
            config('geobosques.survey.name')
        )->firstOrFail();

        $this->assertSame('PRE', $survey->survey_type);
        $this->assertSame('INACTIVA', $survey->status);

        $questions = $survey->survey_questions()
            ->with('survey_questions_options')
            ->orderBy('order')
            ->get();

        $this->assertCount(9, $questions);
        $this->assertSame(range(1, 9), $questions->pluck('order')->map(fn ($order) => (int) $order)->all());
        $this->assertSame([
            'Ubicación',
            'Ubicación',
            'Accesibilidad',
            'Actividad agrícola',
            'Asentamientos humanos',
            'Aprovechamiento forestal',
            'Perturbación del bosque',
            'Coordenadas',
            'Coordenadas',
        ], $questions->pluck('eje')->all());
        $this->assertNotContains(false, $questions->pluck('is_required')->map(fn ($required) => (bool) $required)->all());

        $this->assertQuestion($questions[0], 'Ubicación', 'UBICACION', 'UBICACION', []);
        $this->assertQuestion($questions[1], 'Comunidad / Centro poblado', 'LIBRE', 'CORTO', []);
        $this->assertQuestion(
            $questions[2],
            '¿Cuál es la distancia desde el bosque hasta el acceso más cercano? (carretera, trocha o camino, río navegable, otro acceso)',
            'LIBRE',
            'NUMERICO',
            []
        );
        $this->assertQuestion(
            $questions[3],
            '¿Cuál de las opciones describe mejor la agricultura en el bosque?',
            'OPCIONES',
            'LISTADO',
            [
                'Los cultivos se encuentran a más de 2km del bosque y no tienen mucha expansión.',
                'Los cultivos se encuentran a menos de 2km del bosque y su expansión es limitada.',
                'Los cultivos están dentro o muy cerca al bosque y se observa una expansión.',
            ]
        );
        $this->assertQuestion(
            $questions[4],
            '¿Cuál es la distancia entre el bosque hasta su centro poblado/comunidad?',
            'LIBRE',
            'NUMERICO',
            []
        );
        $this->assertQuestion(
            $questions[5],
            'En una semana habitual ¿cuántos días usted o algun integrante de su hogar extrae productos del bosque para el uso particular de su familia, como leña, madera, frutos o plantas?',
            'OPCIONES',
            'LISTADO',
            [
                '1 - 2 días por semana',
                '3 - 4 días por semana',
                '5 - 7 días por semana',
            ]
        );
        $this->assertQuestion(
            $questions[6],
            '¿Cuál de las siguientes opciones describe mejor el bosque?',
            'OPCIONES',
            'LISTADO',
            [
                'El bosque se encuentra en buen estado de conservación.',
                'Se observan algunos focos de deforestación, incendios o degradación por alguna otra actividad.',
                'Se observan grandes focos de deforestación, incendios recurrentes o degradación por otra actividad.',
            ]
        );
        $this->assertQuestion($questions[7], 'Latitud', 'LIBRE', 'NUMERICO', []);
        $this->assertQuestion($questions[8], 'Longitud', 'LIBRE', 'NUMERICO', []);

        $this->assertSame('Unidad: km.', $questions[2]->justification);
        $this->assertSame('Unidad: km.', $questions[4]->justification);
        $this->assertSame('location.latitude', $questions[7]->calculator_key);
        $this->assertSame('location.longitude', $questions[8]->calculator_key);
        $this->assertSame(9, $questions->sum(fn ($question) => $question->survey_questions_options->count()));

        $resource = (new SurveyResource($survey))->resolve();

        $this->assertTrue($resource['requires_coordinates']);
        $this->assertSame('SURVEY_QUESTIONS', $resource['coordinate_capture']['source']);
        $this->assertSame('AFTER_QUESTIONS', $resource['coordinate_capture']['position']);
        $this->assertTrue($resource['coordinate_capture']['required_on_finalize']);
        $this->assertSame(
            ['latitude', 'longitude'],
            collect($resource['coordinate_capture']['fields'])->pluck('name')->all()
        );
        $this->assertSame(
            ['Latitud', 'Longitud'],
            collect($resource['coordinate_capture']['fields'])->pluck('label')->all()
        );
        $this->assertSame(
            [$questions[7]->id, $questions[8]->id],
            collect($resource['coordinate_capture']['fields'])->pluck('survey_question_id')->all()
        );
    }

    public function test_configuration_is_idempotent_while_the_survey_remains_unpublished(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);
        $configurator = app(GeobosquesSurveyConfigurator::class);

        $first = $configurator->configure($project);
        $firstQuestionIds = $first->survey_questions->pluck('id')->all();
        $firstOptionIds = $first->survey_questions
            ->flatMap(fn ($question) => $question->survey_questions_options)
            ->pluck('id')
            ->all();

        $second = $configurator->configure($project);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($firstQuestionIds, $second->survey_questions->pluck('id')->all());
        $this->assertSame(
            $firstOptionIds,
            $second->survey_questions
                ->flatMap(fn ($question) => $question->survey_questions_options)
                ->pluck('id')
                ->all()
        );
        $this->assertDatabaseCount('surveys', 1);
        $this->assertDatabaseCount('survey_questions', 9);
        $this->assertDatabaseCount('survey_question_options', 9);
    }

    public function test_it_adds_missing_coordinate_questions_to_an_active_survey_without_participations(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);
        $configurator = app(GeobosquesSurveyConfigurator::class);
        $survey = $configurator->configure($project);
        $survey->survey_questions()->whereIn('order', [8, 9])->delete();
        $survey->update(['status' => 'ACTIVA']);

        $this->artisan('survey:configure-geobosques', ['project' => $project->id])
            ->expectsOutputToContain('Estado: ACTIVA (estado conservado)')
            ->expectsOutputToContain('Preguntas dinámicas: 9')
            ->assertSuccessful();

        $this->assertSame('ACTIVA', $survey->fresh()->status);
        $this->assertSame(
            ['Latitud', 'Longitud'],
            $survey->survey_questions()->whereIn('order', [8, 9])->orderBy('order')->pluck('question_text')->all()
        );
    }

    public function test_it_does_not_overwrite_a_survey_with_participations(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);
        $configurator = app(GeobosquesSurveyConfigurator::class);
        $survey = $configurator->configure($project);
        $survey->update(['status' => 'ACTIVA']);
        Surveyed::create([
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
        ]);

        $this->artisan('survey:configure-geobosques', ['project' => $project->id])
            ->expectsOutputToContain('tiene participaciones')
            ->assertFailed();

        $this->assertSame('ACTIVA', $survey->fresh()->status);
        $this->assertCount(9, $survey->survey_questions);
    }

    public function test_degree_is_valid_when_coordinate_questions_are_created_or_edited(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);
        $survey = app(GeobosquesSurveyConfigurator::class)->configure($project);
        $latitude = $survey->survey_questions()->where('calculator_key', 'location.latitude')->firstOrFail();
        $payload = [
            'survey_id' => $survey->id,
            'question_type' => 'LIBRE',
            'type_field' => 'NUMERICO',
            'question_text' => 'Coordenada de prueba',
            'calculator_key' => 'location.test',
            'calculator_value_type' => 'number',
            'calculator_unit' => 'degree',
            'justification' => 'Coordenada decimal.',
        ];
        $storeRequest = StoreSurveyQuestionRequest::create('/api/surveyquestion', 'POST', $payload);

        $this->assertFalse(Validator::make($payload, $storeRequest->rules())->fails());

        $updatePayload = [
            'id' => $latitude->id,
            'survey_id' => $survey->id,
            'calculator_key' => 'location.latitude',
            'calculator_unit' => 'degree',
        ];
        $updateRequest = UpdateSurveyQuestionRequest::create(
            '/api/surveyquestion/'.$latitude->id,
            'PUT',
            $updatePayload
        );

        $this->assertFalse(Validator::make($updatePayload, $updateRequest->rules())->fails());
    }

    private function assertQuestion(
        SurveyQuestion $question,
        string $text,
        string $questionType,
        string $fieldType,
        array $options
    ): void {
        $this->assertSame($text, $question->question_text);
        $this->assertSame($questionType, $question->question_type);
        $this->assertSame($fieldType, $question->type_field);
        $this->assertSame($options, $question->survey_questions_options->pluck('description')->all());
    }
}
