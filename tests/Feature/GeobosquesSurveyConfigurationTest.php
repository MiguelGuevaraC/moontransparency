<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\SurveyQuestion;
use App\Services\GeobosquesSurveyConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSuccessful();

        $survey = $project->surveys()->where(
            'survey_name',
            GeobosquesSurveyConfigurator::SURVEY_NAME
        )->firstOrFail();

        $this->assertSame('PRE', $survey->survey_type);
        $this->assertSame('INACTIVA', $survey->status);

        $questions = $survey->survey_questions()
            ->with('survey_questions_options')
            ->orderBy('order')
            ->get();

        $this->assertCount(7, $questions);
        $this->assertSame(range(1, 7), $questions->pluck('order')->map(fn ($order) => (int) $order)->all());
        $this->assertSame([
            'Ubicación',
            'Ubicación',
            'Accesibilidad',
            'Actividad agrícola',
            'Asentamientos humanos',
            'Aprovechamiento forestal',
            'Perturbación del bosque',
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

        $this->assertSame('Unidad: km.', $questions[2]->justification);
        $this->assertSame('Unidad: km.', $questions[4]->justification);
        $this->assertSame(9, $questions->sum(fn ($question) => $question->survey_questions_options->count()));
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
        $this->assertDatabaseCount('survey_questions', 7);
        $this->assertDatabaseCount('survey_question_options', 9);
    }

    public function test_it_does_not_overwrite_a_published_survey(): void
    {
        $project = Proyect::create(['name' => 'Proyecto GeoBosques']);
        $configurator = app(GeobosquesSurveyConfigurator::class);
        $survey = $configurator->configure($project);
        $survey->update(['status' => 'ACTIVA']);

        $this->artisan('survey:configure-geobosques', ['project' => $project->id])
            ->expectsOutputToContain('ya fue publicada')
            ->assertFailed();

        $this->assertSame('ACTIVA', $survey->fresh()->status);
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
