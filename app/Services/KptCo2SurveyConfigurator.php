<?php

namespace App\Services;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class KptCo2SurveyConfigurator
{
    public function inspect(Proyect $project): array
    {
        $definitions = $this->definitions();

        return collect($definitions)->map(function (array $definition, string $kind) use ($project) {
            $survey = $this->findSurvey($project, $definition);
            $participations = $survey
                ? DB::table('surveyeds')->where('survey_id', $survey->id)->whereNull('deleted_at')
                : null;

            return [
                'kind' => $kind,
                'survey_id' => $survey?->id,
                'name' => $definition['name'],
                'status' => $survey?->status,
                'questions_current' => $survey?->survey_questions()->count() ?? 0,
                'questions_expected' => count($definition['questions']),
                'drafts' => $participations
                    ? (clone $participations)->where('status', 'BORRADOR')->count()
                    : 0,
                'finalized' => $participations
                    ? (clone $participations)->where(function ($query) {
                        $query->where('status', 'FINALIZADA')->orWhereNotNull('completed_at');
                    })->count()
                    : 0,
                'participations' => $participations ? (clone $participations)->count() : 0,
            ];
        })->values()->all();
    }

    public function configure(Proyect $project): array
    {
        return DB::transaction(function () use ($project) {
            $definitions = $this->definitions();
            $baseline = $this->synchronizeSurvey($project, $definitions['baseline']);
            $monitoring = $this->synchronizeSurvey($project, $definitions['monitoring']);

            $baseline->update(['post_survey_id' => $monitoring->id]);
            if ($monitoring->post_survey_id !== null) {
                $monitoring->update(['post_survey_id' => null]);
            }

            return [
                'baseline' => $baseline->fresh($this->relations()),
                'monitoring' => $monitoring->fresh($this->relations()),
            ];
        }, 3);
    }

    public function findConfiguredSurveys(Proyect $project): array
    {
        return collect($this->definitions())
            ->map(fn (array $definition) => $this->findSurvey($project, $definition))
            ->filter()
            ->values()
            ->all();
    }

    private function synchronizeSurvey(Proyect $project, array $definition): Survey
    {
        $survey = $this->findSurvey($project, $definition, true);

        if ($survey && DB::table('surveyeds')
            ->where('survey_id', $survey->id)
            ->whereNull('deleted_at')
            ->exists()) {
            throw new DomainException(
                "La encuesta {$survey->survey_name} todavía tiene participaciones. Ejecute primero la limpieza respaldada."
            );
        }

        $status = $survey?->status ?? Survey::STATUS_INACTIVE;

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
            'status' => $status,
            'requires_coordinates' => false,
            'expected_days' => $definition['expected_days'],
        ])->save();

        $this->synchronizeQuestions($survey, $definition['questions']);

        return $survey;
    }

    private function findSurvey(Proyect $project, array $definition, bool $lock = false): ?Survey
    {
        $query = Survey::withTrashed()
            ->where('proyect_id', $project->id)
            ->where(function ($query) use ($definition) {
                $query->where('code', $definition['code'])
                    ->orWhereIn('survey_name', $definition['legacy_names']);
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$definition['code']])
            ->orderBy('id');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function synchronizeQuestions(Survey $survey, array $definitions): void
    {
        $existing = SurveyQuestion::withTrashed()
            ->where('survey_id', $survey->id)
            ->orderBy('id')
            ->get();
        $keptIds = [];

        foreach ($definitions as $definition) {
            $options = $definition['options'] ?? [];
            unset($definition['options']);

            $question = $this->matchingQuestion($existing, $definition);
            if (! $question) {
                $question = new SurveyQuestion();
            } elseif ($question->trashed()) {
                $question->restore();
            }

            $question->fill($definition + ['survey_id' => $survey->id])->save();
            $keptIds[] = $question->id;
            $this->synchronizeOptions($question, $options);
        }

        foreach ($existing->whereNotIn('id', $keptIds)->whereNull('deleted_at') as $obsolete) {
            SurveyQuestionOption::where('survey_question_id', $obsolete->id)->delete();
            $obsolete->delete();
        }
    }

    private function matchingQuestion($existing, array $definition): ?SurveyQuestion
    {
        $question = $existing->firstWhere('instrument_key', $definition['instrument_key']);

        if (! $question && ! empty($definition['calculator_key'])) {
            $question = $existing->firstWhere('calculator_key', $definition['calculator_key']);
        }

        if (! $question) {
            $normalizedText = $this->normalize($definition['question_text']);
            $question = $existing->first(
                fn (SurveyQuestion $candidate) => $this->normalize($candidate->question_text) === $normalizedText
            );
        }

        if ($question) {
            $existing->forget($existing->search(fn (SurveyQuestion $item) => $item->id === $question->id));
        }

        return $question;
    }

    private function synchronizeOptions(SurveyQuestion $question, array $descriptions): void
    {
        $keptIds = [];

        foreach ($descriptions as $description) {
            $option = SurveyQuestionOption::withTrashed()
                ->where('survey_question_id', $question->id)
                ->where('description', $description)
                ->orderBy('id')
                ->first() ?? new SurveyQuestionOption();

            if ($option->exists && $option->trashed()) {
                $option->restore();
            }

            $option->fill([
                'survey_question_id' => $question->id,
                'description' => $description,
            ])->save();
            $keptIds[] = $option->id;
        }

        $obsolete = SurveyQuestionOption::where('survey_question_id', $question->id);
        if ($keptIds !== []) {
            $obsolete->whereNotIn('id', $keptIds);
        }
        $obsolete->delete();
    }

    private function definitions(): array
    {
        $definitions = config('kpt_co2.surveys');

        if (! is_array($definitions) || ! isset($definitions['baseline'], $definitions['monitoring'])) {
            throw new RuntimeException('No se encontró la definición versionada de las encuestas KPT CO2.');
        }

        return $definitions;
    }

    private function normalize(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function relations(): array
    {
        return [
            'survey_questions' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'survey_questions.survey_questions_options' => fn ($query) => $query->orderBy('id'),
        ];
    }
}
