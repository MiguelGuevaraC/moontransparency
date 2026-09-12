<?php

namespace App\Services;

use App\Http\Resources\CalculatorParticipationResource;
use App\Models\Survey;
use App\Models\Surveyed;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Co2SurveyDatasetBuilder
{
    public function build(
        int $projectId,
        int $baselineSurveyId,
        ?int $monitoringSurveyId,
        array $householdIds = [],
        int $limit = 20
    ): array {
        $baselineSurvey = Survey::withTrashed()->find($baselineSurveyId);
        $monitoringSurveyId = $monitoringSurveyId ?: $baselineSurvey?->post_survey_id;
        $monitoringSurvey = $monitoringSurveyId ? Survey::withTrashed()->find($monitoringSurveyId) : null;

        $this->validateSurveys($projectId, $baselineSurvey, $monitoringSurvey);

        $participations = Surveyed::query()
            ->whereIn('survey_id', [$baselineSurvey->id, $monitoringSurvey->id])
            ->when($householdIds, fn ($query) => $query->whereIn('household_id', $householdIds))
            ->with($this->relations())
            ->orderBy('id')
            ->get()
            ->map(fn (Surveyed $surveyed) => (new CalculatorParticipationResource($surveyed))->resolve());

        $baseline = $this->indexParticipations($participations->where('survey.id', $baselineSurvey->id));
        $monitoring = $this->indexParticipations($participations->where('survey.id', $monitoringSurvey->id));
        $keys = $baseline->keys()->merge($monitoring->keys())->unique()->sort()->take($limit)->values();
        $warnings = [];
        $families = $keys->map(function (string $key) use ($baseline, $monitoring, &$warnings) {
            $baselineData = $baseline->get($key);
            $monitoringData = $monitoring->get($key);
            $baselineInput = $baselineData['calculator_input'] ?? null;
            $monitoringInput = $monitoringData['calculator_input'] ?? null;
            $members = $baselineInput['household_members'] ?? $monitoringInput['household_members'] ?? [];
            $monitoringSeries = $this->monitoringSeries($monitoringInput);

            if (! $baselineData) {
                $warnings[] = "El hogar {$key} no tiene participación de línea base.";
            }
            if (! $monitoringData) {
                $warnings[] = "El hogar {$key} no tiene participación de monitoreo.";
            }
            foreach ($baselineInput['warnings'] ?? [] as $warning) {
                $warnings[] = "Línea base del hogar {$key}: {$warning}";
            }
            foreach ($monitoringInput['warnings'] ?? [] as $warning) {
                $warnings[] = "Monitoreo del hogar {$key}: {$warning}";
            }

            return [
                'household_id' => $baselineData['household']['id'] ?? $monitoringData['household']['id'] ?? null,
                'household_code' => $baselineData['household']['identifier'] ?? $monitoringData['household']['identifier'] ?? $key,
                'baseline_participation_id' => $baselineData['participation']['id'] ?? null,
                'monitoring_participation_id' => $monitoringData['participation']['id'] ?? null,
                'monitoring_scenario' => $monitoringInput['scenario'] ?? null,
                'members' => $members,
                'baseline_days' => $this->attachMembers(
                    $baselineInput['baseline']['days'] ?? [],
                    $this->membersByDay($baselineData, $members),
                    $members
                ),
                'monitoring_days' => $this->attachMembers(
                    $monitoringSeries,
                    $this->membersByDay($monitoringData, $members),
                    $members
                ),
                'monitoring_components' => [
                    'traditional_days' => $monitoringInput['baseline']['days'] ?? [],
                    'moon_group_days' => $monitoringInput['project']['days'] ?? [],
                ],
            ];
        })->values()->all();

        $totalHouseholds = $baseline->keys()->merge($monitoring->keys())->unique()->count();
        if ($totalHouseholds > $limit) {
            $warnings[] = "Se usaron {$limit} de {$totalHouseholds} hogares por el límite de la muestra RECH.";
        }

        return [
            'project_id' => $projectId,
            'baseline_survey' => $this->surveySummary($baselineSurvey),
            'monitoring_survey' => $this->surveySummary($monitoringSurvey),
            'families' => $families,
            'warnings' => array_values(array_unique($warnings)),
            'available_households' => $totalHouseholds,
            'selected_households' => count($families),
        ];
    }

    private function validateSurveys(int $projectId, ?Survey $baseline, ?Survey $monitoring): void
    {
        $errors = [];
        if (! $baseline || $baseline->trashed() || (int) $baseline->proyect_id !== $projectId) {
            $errors['baseline_survey_id'][] = 'La encuesta de línea base no pertenece al proyecto o no está disponible.';
        } elseif (! $baseline->survey_questions()->where('calculator_key', 'like', 'baseline.%')->exists()) {
            $errors['baseline_survey_id'][] = 'La encuesta no contiene el mapeo KPT de línea base.';
        }

        if (! $monitoring || $monitoring->trashed() || (int) $monitoring->proyect_id !== $projectId) {
            $errors['monitoring_survey_id'][] = 'La encuesta de monitoreo no pertenece al proyecto o no está disponible.';
        } elseif (! $monitoring->survey_questions()->where('calculator_key', 'like', 'monitoring.%')->exists()) {
            $errors['monitoring_survey_id'][] = 'La encuesta no contiene el mapeo KPT de monitoreo.';
        }

        if ($baseline && $monitoring && $baseline->id === $monitoring->id) {
            $errors['monitoring_survey_id'][] = 'Línea base y monitoreo deben ser encuestas diferentes.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function indexParticipations(Collection $participations): Collection
    {
        return $participations
            ->filter(fn (array $item) => ! empty($item['household']['identifier']))
            ->groupBy(fn (array $item) => $this->householdKey($item))
            ->map(function (Collection $duplicates) {
                return $duplicates
                    ->sortByDesc(fn (array $item) => [
                        $item['participation']['status'] === Surveyed::STATUS_FINALIZED ? 1 : 0,
                        $item['participation']['updated_at'] ?? '',
                        $item['participation']['id'],
                    ])
                    ->first();
            });
    }

    private function householdKey(array $participation): string
    {
        $id = $participation['household']['id'] ?? null;

        return $id ? 'ID:'.$id : 'CODE:'.mb_strtoupper(trim((string) $participation['household']['identifier']));
    }

    private function monitoringSeries(?array $input): array
    {
        if (! $input) {
            return [];
        }

        if (($input['scenario'] ?? null) === CalculatorInputMapper::SCENARIO_MONITORING_COMBINED) {
            return $this->combineSeries(
                $input['project']['days'] ?? [],
                $input['baseline']['days'] ?? []
            );
        }

        return $input['project']['days'] ?? [];
    }

    private function combineSeries(array $improvedDays, array $traditionalDays): array
    {
        $improved = collect($improvedDays)->keyBy('day_number');
        $traditional = collect($traditionalDays)->keyBy('day_number');

        return $improved->keys()->merge($traditional->keys())->unique()->sort()->map(function ($dayNumber) use ($improved, $traditional) {
            $parts = collect([$improved->get($dayNumber), $traditional->get($dayNumber)])
                ->filter(fn ($day) => $day && ($day['calculation_ready'] ?? false));

            return [
                'day_number' => (int) $dayNumber,
                'available_weight_kg' => $parts->sum(fn (array $day) => (float) $day['available_weight_kg']),
                'remaining_weight_kg' => $parts->sum(fn (array $day) => (float) $day['remaining_weight_kg']),
                'charcoal_weight_kg' => $parts->sum(fn (array $day) => (float) $day['charcoal_weight_kg']),
                'calculation_ready' => $parts->isNotEmpty(),
            ];
        })->values()->all();
    }

    private function attachMembers(array $days, array $membersByDay, array $fallback): array
    {
        $indexedMembers = collect($membersByDay)->keyBy('day_number');

        return collect($days)->map(function (array $day) use ($indexedMembers, $fallback) {
            $day['members'] = $indexedMembers->get($day['day_number'])['members'] ?? $fallback;

            return $day;
        })->values()->all();
    }

    private function membersByDay(?array $participation, array $fallback): array
    {
        if (! $participation) {
            return [];
        }

        $calculatorKeys = [
            'children_0_14' => 'household.children_0_14',
            'women_over_14' => 'household.women_over_14',
            'men_15_59' => 'household.men_15_59',
            'men_over_59' => 'household.men_over_59',
        ];
        $fieldKeys = collect($participation['fields'] ?? [])
            ->filter(fn (array $field) => ! empty($field['calculator_key']))
            ->keyBy('calculator_key');

        return collect($participation['days'] ?? [])->map(function (array $day) use ($calculatorKeys, $fieldKeys, $fallback) {
            $members = [];
            foreach ($calculatorKeys as $memberKey => $calculatorKey) {
                $fieldKey = $fieldKeys->get($calculatorKey)['key'] ?? null;
                $answer = $fieldKey ? ($day['values'][$fieldKey] ?? null) : null;
                $members[$memberKey] = ($answer['validation_status'] ?? null) === 'valid'
                    && is_numeric($answer['value'] ?? null)
                        ? (float) $answer['value']
                        : ($fallback[$memberKey] ?? null);
            }

            return [
                'day_number' => (int) $day['day_number'],
                'members' => $members,
            ];
        })->values()->all();
    }

    private function surveySummary(Survey $survey): array
    {
        return [
            'id' => $survey->id,
            'code' => $survey->code,
            'name' => $survey->survey_name,
            'type' => $survey->survey_type,
        ];
    }

    private function relations(): array
    {
        return [
            'respondent',
            'household',
            'survey.proyect',
            'survey.survey_questions.survey_questions_options',
            'surveyed_responses.survey_question.survey_questions_options',
            'surveyed_responses.surveyed_responses_options.survey_question_options',
            'surveyed_responses.measurement',
            'measurements.surveyed_responses.survey_question.survey_questions_options',
            'measurements.surveyed_responses.surveyed_responses_options.survey_question_options',
            'measurements.surveyed_responses.measurement',
        ];
    }
}
