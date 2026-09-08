<?php

namespace App\Services;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GeobosquesSurveyConfigurator
{
    /**
     * Configura el instrumento versionado en config/geobosques.php.
     *
     * La encuesta permanece inactiva hasta que un usuario autorizado
     * revise la configuración y la publique desde el módulo dinámico.
     */
    public function configure(Proyect $project): Survey
    {
        return DB::transaction(function () use ($project) {
            $definition = config('geobosques.survey');
            $survey = Survey::withTrashed()
                ->where('proyect_id', $project->id)
                ->where('code', $definition['code'])
                ->lockForUpdate()
                ->first();

            if ($survey && ($survey->status === Survey::STATUS_ACTIVE || Surveyed::withTrashed()->where('survey_id', $survey->id)->exists())) {
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
                'code' => $definition['code'],
                'proyect_id' => $project->id,
                'survey_name' => $definition['name'],
                'survey_type' => $definition['type'],
                'description' => $definition['description'],
                'status' => Survey::STATUS_INACTIVE,
                'requires_coordinates' => $definition['requires_coordinates'],
                'expected_days' => null,
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

    public static function questions(): array
    {
        $questions = config('geobosques.questions');

        if (! is_array($questions) || $questions === []) {
            throw new RuntimeException('No se encontró la definición versionada del instrumento GeoBosques.');
        }

        return $questions;
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
