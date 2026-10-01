<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ForestPressureDatasetBuilder
{
    public function __construct(private ForestPressureIndexCalculator $calculator)
    {
    }

    /**
     * Arma las zonas evaluadas con las participaciones finalizadas de la
     * Encuesta de Presión sobre el Bosque.
     */
    public function build(): array
    {
        $surveys = $this->surveys();
        $zones = [];
        $warnings = [];
        $incomplete = 0;

        foreach ($surveys as $survey) {
            $questions = $this->questionMap($survey);

            foreach ($questions['missing'] as $label) {
                $warnings[] = "La encuesta \"{$survey->survey_name}\" no tiene la pregunta del factor {$label}.";
            }

            $participations = Surveyed::query()
                ->where('survey_id', $survey->id)
                ->where('status', Surveyed::STATUS_FINALIZED)
                ->with(['surveyed_responses.surveyed_responses_options'])
                ->orderBy('id')
                ->get();

            foreach ($participations as $participation) {
                $zone = $this->zone($participation, $questions);
                if ($zone === null) {
                    $incomplete++;

                    continue;
                }
                $zones[] = $zone;
            }
        }

        if ($incomplete > 0) {
            $warnings[] = "{$incomplete} participación(es) finalizada(s) no tienen los cinco factores y no se incluyen en el IPB.";
        }

        return [
            'project' => $this->project($surveys),
            'surveys' => $surveys->map(fn (Survey $survey) => [
                'id' => $survey->id,
                'name' => $survey->survey_name,
                'project_id' => $survey->proyect_id,
            ])->values()->all(),
            'zones' => $zones,
            'pending_drafts' => $surveys->isEmpty() ? 0 : Surveyed::query()
                ->whereIn('survey_id', $surveys->pluck('id'))
                ->where('status', Surveyed::STATUS_DRAFT)
                ->count(),
            'warnings' => $warnings,
        ];
    }

    private function surveys(): Collection
    {
        $projectId = $this->projectId();

        if ($projectId === null) {
            return collect();
        }

        return Survey::query()
            ->where('proyect_id', $projectId)
            ->where('code', config('forest_pressure.survey_code'))
            ->orderBy('id')
            ->get();
    }

    private function projectId(): ?int
    {
        $configured = config('forest_pressure.project_id');
        if (filled($configured)) {
            return (int) $configured;
        }

        $survey = Survey::query()
            ->where('code', config('forest_pressure.survey_code'))
            ->orderByRaw('status = ? DESC', [Survey::STATUS_ACTIVE])
            ->orderBy('id')
            ->first();

        return $survey?->proyect_id;
    }

    private function project(Collection $surveys): ?array
    {
        $project = $surveys->first()?->proyect;

        return $project ? ['id' => $project->id, 'name' => $project->name] : null;
    }

    /**
     * Ubica las preguntas de ubicación y de cada factor por su eje.
     */
    private function questionMap(Survey $survey): array
    {
        $questions = SurveyQuestion::query()
            ->where('survey_id', $survey->id)
            ->with(['survey_questions_options' => fn ($query) => $query->orderBy('id')])
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        $location = config('forest_pressure.location');
        $byEje = fn (string $eje, ?string $type = null) => $questions->first(
            fn (SurveyQuestion $question) => $this->same($question->eje, $eje)
                && ($type === null || Str::upper((string) $question->question_type) === $type)
        );
        $factors = [];
        $missing = [];

        foreach (config('forest_pressure.factors') as $factor) {
            $question = $byEje($factor['eje']);
            if (! $question) {
                $missing[] = $factor['label'];

                continue;
            }
            $factors[$factor['key']] = [
                'definition' => $factor,
                'question_id' => $question->id,
                'option_positions' => $question->survey_questions_options
                    ->values()
                    ->mapWithKeys(fn ($option, $index) => [$option->id => [
                        'position' => $index + 1,
                        'description' => $option->description,
                    ]])
                    ->all(),
            ];
        }

        return [
            'ubigeo_id' => $byEje($location['ubigeo_eje'], $location['ubigeo_type'])?->id,
            'community_id' => $byEje($location['community_eje'], $location['community_type'])?->id,
            'factors' => $factors,
            'missing' => $missing,
        ];
    }

    private function zone(Surveyed $participation, array $questions): ?array
    {
        $answers = $participation->surveyed_responses->keyBy('survey_question_id');
        $levels = [];
        $factorValues = [];

        foreach ($questions['factors'] as $key => $factor) {
            [$value, $level] = $this->factorAnswer($answers->get($factor['question_id']), $factor);
            $levels[$key] = $level;
            $factorValues[$key] = ['value' => $value, 'level' => $level];
        }

        $result = $this->calculator->evaluate($levels);
        if ($result === null) {
            return null;
        }

        [$district, $province, $department] = $this->ubigeo(
            $answers->get($questions['ubigeo_id'])?->response_text
        );

        return [
            'id' => $participation->id,
            'code' => 'IPB-'.str_pad((string) $participation->id, 4, '0', STR_PAD_LEFT),
            'survey_id' => $participation->survey_id,
            'department' => $department,
            'province' => $province,
            'district' => $district,
            'community' => $this->text($answers->get($questions['community_id'])?->response_text),
            'latitude' => $participation->latitude !== null ? (float) $participation->latitude : null,
            'longitude' => $participation->longitude !== null ? (float) $participation->longitude : null,
            'date' => $participation->completed_at?->toDateString(),
            'factors' => $factorValues,
            'ipb' => $result['ipb'],
            'level' => $result['level'],
            'high_factors' => $result['high_factors'],
        ];
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    private function factorAnswer(?SurveyedResponse $answer, array $factor): array
    {
        if (! $answer) {
            return [null, null];
        }

        if ($factor['definition']['type'] === 'distance') {
            $kilometers = $this->number($answer->response_text);
            if ($kilometers === null) {
                return [null, null];
            }

            return [
                rtrim(rtrim(number_format($kilometers, 2, '.', ''), '0'), '.').' km',
                $this->calculator->distanceLevel($kilometers, $factor['definition']),
            ];
        }

        $selected = $answer->surveyed_responses_options
            ->map(fn ($option) => $factor['option_positions'][$option->survey_question_options_id] ?? null)
            ->filter()
            ->first();

        return $selected
            ? [$selected['description'], $this->calculator->optionLevel($selected['position'])]
            : [null, null];
    }

    /**
     * La respuesta de ubicación se guarda como "Distrito, Provincia, Departamento".
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function ubigeo(?string $text): array
    {
        $parts = array_map('trim', explode(',', (string) $text));
        $unknown = 'Sin ubicación';

        if (count($parts) !== 3 || in_array('', $parts, true)) {
            return [$unknown, $unknown, $unknown];
        }

        return array_map(fn (string $part) => Str::title(Str::lower($part)), $parts);
    }

    private function number(?string $value): ?float
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) && (float) $normalized >= 0 ? (float) $normalized : null;
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function same(?string $left, string $right): bool
    {
        return Str::lower(Str::ascii(trim((string) $left))) === Str::lower(Str::ascii($right));
    }
}
