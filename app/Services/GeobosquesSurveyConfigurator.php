<?php

namespace App\Services;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use DomainException;
use Illuminate\Support\Facades\DB;

class GeobosquesSurveyConfigurator
{
    public const SURVEY_NAME = 'Encuesta de Presión sobre el Bosque';

    /**
     * Configura el instrumento recibido en el módulo dinámico de encuestas.
     *
     * La encuesta permanece inactiva hasta que el responsable funcional
     * confirme la obligatoriedad y autorice su publicación.
     */
    public function configure(Proyect $project): Survey
    {
        return DB::transaction(function () use ($project) {
            $survey = Survey::withTrashed()
                ->where('proyect_id', $project->id)
                ->where('survey_name', self::SURVEY_NAME)
                ->lockForUpdate()
                ->first();

            if ($survey && ($survey->status === 'ACTIVA' || Surveyed::withTrashed()->where('survey_id', $survey->id)->exists())) {
                throw new DomainException(
                    'La encuesta GeoBosques ya fue publicada o tiene participaciones; no se puede resincronizar automáticamente.'
                );
            }

            if (! $survey) {
                $survey = new Survey();
            } elseif ($survey->trashed()) {
                $survey->restore();
            }

            $survey->fill([
                'proyect_id' => $project->id,
                'survey_name' => self::SURVEY_NAME,
                'survey_type' => 'PRE',
                'description' => 'Instrumento GeoBosques para registrar factores de presión y el estado de conservación del bosque.',
                'status' => 'INACTIVA',
                'post_survey_id' => null,
            ])->save();

            $this->synchronizeQuestions($survey);

            return $survey->fresh([
                'proyect',
                'survey_questions' => fn ($query) => $query->orderBy('order')->orderBy('id'),
                'survey_questions.survey_questions_options' => fn ($query) => $query->orderBy('id'),
            ]);
        });
    }

    /**
     * El control UBICACION existente representa los tres campos jerárquicos
     * Departamento, Provincia y Distrito. Comunidad se conserva como campo
     * independiente, tal como ocurre en los instrumentos actuales.
     */
    public static function questions(): array
    {
        return [
            [
                'order' => 1,
                'eje' => 'Ubicación',
                'question_text' => 'Ubicación',
                'question_type' => 'UBICACION',
                'type_field' => 'UBICACION',
                'is_required' => true,
                'justification' => 'Departamento, provincia y distrito.',
                'options' => [],
            ],
            [
                'order' => 2,
                'eje' => 'Ubicación',
                'question_text' => 'Comunidad / Centro poblado',
                'question_type' => 'LIBRE',
                'type_field' => 'CORTO',
                'is_required' => true,
                'justification' => '-',
                'options' => [],
            ],
            [
                'order' => 3,
                'eje' => 'Accesibilidad',
                'question_text' => '¿Cuál es la distancia desde el bosque hasta el acceso más cercano? (carretera, trocha o camino, río navegable, otro acceso)',
                'question_type' => 'LIBRE',
                'type_field' => 'NUMERICO',
                'is_required' => true,
                'justification' => 'Unidad: km.',
                'options' => [],
            ],
            [
                'order' => 4,
                'eje' => 'Actividad agrícola',
                'question_text' => '¿Cuál de las opciones describe mejor la agricultura en el bosque?',
                'question_type' => 'OPCIONES',
                'type_field' => 'LISTADO',
                'is_required' => true,
                'justification' => '-',
                'options' => [
                    'Los cultivos se encuentran a más de 2km del bosque y no tienen mucha expansión.',
                    'Los cultivos se encuentran a menos de 2km del bosque y su expansión es limitada.',
                    'Los cultivos están dentro o muy cerca al bosque y se observa una expansión.',
                ],
            ],
            [
                'order' => 5,
                'eje' => 'Asentamientos humanos',
                'question_text' => '¿Cuál es la distancia entre el bosque hasta su centro poblado/comunidad?',
                'question_type' => 'LIBRE',
                'type_field' => 'NUMERICO',
                'is_required' => true,
                'justification' => 'Unidad: km.',
                'options' => [],
            ],
            [
                'order' => 6,
                'eje' => 'Aprovechamiento forestal',
                'question_text' => 'En una semana habitual ¿cuántos días usted o algun integrante de su hogar extrae productos del bosque para el uso particular de su familia, como leña, madera, frutos o plantas?',
                'question_type' => 'OPCIONES',
                'type_field' => 'LISTADO',
                'is_required' => true,
                'justification' => '-',
                'options' => [
                    '1 - 2 días por semana',
                    '3 - 4 días por semana',
                    '5 - 7 días por semana',
                ],
            ],
            [
                'order' => 7,
                'eje' => 'Perturbación del bosque',
                'question_text' => '¿Cuál de las siguientes opciones describe mejor el bosque?',
                'question_type' => 'OPCIONES',
                'type_field' => 'LISTADO',
                'is_required' => true,
                'justification' => '-',
                'options' => [
                    'El bosque se encuentra en buen estado de conservación.',
                    'Se observan algunos focos de deforestación, incendios o degradación por alguna otra actividad.',
                    'Se observan grandes focos de deforestación, incendios recurrentes o degradación por otra actividad.',
                ],
            ],
        ];
    }

    private function synchronizeQuestions(Survey $survey): void
    {
        $expectedOrders = [];

        foreach (self::questions() as $definition) {
            $expectedOrders[] = $definition['order'];
            $options = $definition['options'];
            unset($definition['options']);

            $questionsAtOrder = SurveyQuestion::withTrashed()
                ->where('survey_id', $survey->id)
                ->where('order', $definition['order'])
                ->orderBy('id')
                ->get();

            $question = $questionsAtOrder->shift() ?? new SurveyQuestion();

            foreach ($questionsAtOrder as $duplicate) {
                SurveyQuestionOption::where('survey_question_id', $duplicate->id)->delete();
                $duplicate->delete();
            }

            if ($question->trashed()) {
                $question->restore();
            }

            $question->fill($definition + ['survey_id' => $survey->id])->save();
            $this->synchronizeOptions($question, $options);
        }

        $obsoleteQuestions = SurveyQuestion::where('survey_id', $survey->id)
            ->whereNotIn('order', $expectedOrders)
            ->get();

        foreach ($obsoleteQuestions as $question) {
            SurveyQuestionOption::where('survey_question_id', $question->id)->delete();
            $question->delete();
        }
    }

    private function synchronizeOptions(SurveyQuestion $question, array $descriptions): void
    {
        $keptIds = [];

        foreach ($descriptions as $description) {
            $option = SurveyQuestionOption::withTrashed()
                ->where('survey_question_id', $question->id)
                ->where('description', $description)
                ->orderBy('id')
                ->first();

            if (! $option) {
                $option = new SurveyQuestionOption();
            } elseif ($option->trashed()) {
                $option->restore();
            }

            $option->fill([
                'survey_question_id' => $question->id,
                'description' => $description,
            ])->save();

            $keptIds[] = $option->id;
        }

        $obsolete = SurveyQuestionOption::where('survey_question_id', $question->id);

        if ($keptIds) {
            $obsolete->whereNotIn('id', $keptIds);
        }

        $obsolete->delete();
    }
}
