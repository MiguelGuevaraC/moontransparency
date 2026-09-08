<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CalculatorInputMapper
{
    public const SCENARIO_BASELINE = 'BASELINE';

    public const SCENARIO_MONITORING_COMBINED = 'MONITORING_COMBINED';

    public const SCENARIO_MONITORING_MOON_ONLY = 'MONITORING_MOON_ONLY';

    public const SCENARIO_UNDETERMINED = 'UNDETERMINED';

    public const SCENARIO_UNSUPPORTED = 'UNSUPPORTED';

    public function map(Collection $fields, Collection $days): array
    {
        $fieldsByCalculatorKey = $fields
            ->filter(fn (array $field) => ! empty($field['calculator_key']))
            ->keyBy('calculator_key');
        $members = $this->members($fieldsByCalculatorKey, $days);
        $warnings = [];
        $scenario = self::SCENARIO_UNSUPPORTED;
        $baseline = null;
        $project = null;
        $mappingComplete = false;
        $hasBaselineMapping = $fieldsByCalculatorKey->keys()->contains(
            fn (string $key) => str_starts_with($key, 'baseline.')
        );
        $hasMonitoringMapping = $fieldsByCalculatorKey->keys()->contains(
            fn (string $key) => str_starts_with($key, 'monitoring.')
        );

        if ($hasBaselineMapping) {
            $scenario = self::SCENARIO_BASELINE;
            $baseline = $this->series($fieldsByCalculatorKey, $days, 'baseline', 'TRADITIONAL');
            $mappingComplete = $baseline['mapping_complete'];
            $warnings = array_merge($warnings, $baseline['warnings']);
        } elseif ($hasMonitoringMapping) {
            $moon = $this->series($fieldsByCalculatorKey, $days, 'monitoring.moon', 'MOON_GROUP');
            $traditional = $this->series($fieldsByCalculatorKey, $days, 'monitoring.traditional', 'TRADITIONAL');
            $moonOnly = $this->series($fieldsByCalculatorKey, $days, 'monitoring.exclusive', 'MOON_GROUP');
            $hasCombinedData = $moon['has_data'] || $traditional['has_data'];
            $hasMoonOnlyData = $moonOnly['has_data'];

            if ($hasCombinedData && $hasMoonOnlyData) {
                $scenario = self::SCENARIO_UNDETERMINED;
                $warnings[] = 'La participación contiene datos de los dos escenarios de monitoreo.';
            } elseif ($hasCombinedData) {
                $scenario = self::SCENARIO_MONITORING_COMBINED;
                $baseline = $traditional;
                $project = $moon;
                $mappingComplete = $traditional['mapping_complete'] && $moon['mapping_complete'];
                $warnings = array_merge($warnings, $traditional['warnings'], $moon['warnings']);
            } elseif ($hasMoonOnlyData) {
                $scenario = self::SCENARIO_MONITORING_MOON_ONLY;
                $project = $moonOnly;
                $mappingComplete = $moonOnly['mapping_complete'];
                $warnings = array_merge($warnings, $moonOnly['warnings']);
            } else {
                $scenario = self::SCENARIO_UNDETERMINED;
                $warnings[] = 'No hay pesos válidos para identificar el escenario de monitoreo.';
            }
        } else {
            $warnings[] = 'La encuesta no tiene campos KPT vinculados a la calculadora.';
        }

        $missingMemberMappings = collect($members)
            ->except('total')
            ->filter(fn ($value) => $value === null)
            ->keys()
            ->values()
            ->all();

        if ($missingMemberMappings) {
            $warnings[] = 'Faltan datos demográficos: '.implode(', ', $missingMemberMappings).'.';
        }

        return [
            'version' => '1.0',
            'supported' => $scenario !== self::SCENARIO_UNSUPPORTED
                && $scenario !== self::SCENARIO_UNDETERMINED
                && $mappingComplete,
            'scenario' => $scenario,
            'household_members' => $members,
            'baseline' => $baseline ? $this->withoutInternalFields($baseline) : null,
            'project' => $project ? $this->withoutInternalFields($project) : null,
            'warnings' => collect($warnings)->filter()->unique()->values()->all(),
        ];
    }

    private function members(Collection $fields, Collection $days): array
    {
        $members = [
            'children_0_14' => $this->firstNumber($fields, $days, 'household.children_0_14'),
            'women_over_14' => $this->firstNumber($fields, $days, 'household.women_over_14'),
            'men_15_59' => $this->firstNumber($fields, $days, 'household.men_15_59'),
            'men_over_59' => $this->firstNumber($fields, $days, 'household.men_over_59'),
        ];

        $members['total'] = collect($members)->containsStrict(null)
            ? null
            : collect($members)->sum();

        return $members;
    }

    private function firstNumber(Collection $fields, Collection $days, string $calculatorKey): ?float
    {
        $fieldKey = $fields->get($calculatorKey)['key'] ?? null;

        if (! $fieldKey) {
            return null;
        }

        foreach ($days as $day) {
            $value = $this->number($day, $fieldKey);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function series(Collection $fields, Collection $days, string $prefix, string $kitchen): array
    {
        $fieldKeys = [
            'initial' => $fields->get($prefix.'.initial_wood_kg')['key'] ?? null,
            'additional' => $fields->get($prefix.'.additional_wood_kg')['key'] ?? null,
            'remaining' => $fields->get($prefix.'.remaining_wood_kg')['key'] ?? null,
            'charcoal' => $fields->get($prefix.'.charcoal_kg')['key'] ?? null,
        ];
        $missingMappings = collect($fieldKeys)
            ->filter(fn ($value) => $value === null)
            ->keys()
            ->values()
            ->all();
        $warnings = [];

        if ($missingMappings) {
            $warnings[] = 'Faltan campos para '.$prefix.': '.implode(', ', $missingMappings).'.';
        }

        $previousRemaining = null;
        $hasData = false;
        $mappedDays = $days->map(function (array $day) use ($fieldKeys, &$previousRemaining, &$hasData, &$warnings) {
            $dayNumber = (int) $day['day_number'];
            $initial = $this->number($day, $fieldKeys['initial']);
            $additional = $this->number($day, $fieldKeys['additional']);
            $remaining = $this->number($day, $fieldKeys['remaining']);
            $charcoal = $this->number($day, $fieldKeys['charcoal']);
            $hasData = $hasData || collect([$initial, $additional, $remaining, $charcoal])->contains(fn ($value) => $value !== null);

            $available = $dayNumber === 1
                ? $initial
                : ($previousRemaining !== null && $additional !== null ? $previousRemaining + $additional : null);
            $hasRequiredWeights = $available !== null && $remaining !== null && $charcoal !== null;
            $hasNonNegativeWeights = $hasRequiredWeights
                && ($dayNumber === 1 ? $initial >= 0 : $additional >= 0)
                && $available >= 0
                && $remaining >= 0
                && $charcoal >= 0;
            $hasValidBalance = $hasNonNegativeWeights && ($remaining + $charcoal) <= $available;
            $ready = $hasRequiredWeights && $hasNonNegativeWeights && $hasValidBalance;

            if ($day['recorded'] && ! $hasRequiredWeights) {
                $warnings[] = 'El día '.$dayNumber.' no tiene todos los pesos necesarios para calcular consumo.';
            } elseif ($day['recorded'] && ! $hasNonNegativeWeights) {
                $warnings[] = 'El día '.$dayNumber.' contiene pesos negativos y se excluyó del cálculo.';
            } elseif ($day['recorded'] && ! $hasValidBalance) {
                $warnings[] = 'El día '.$dayNumber.' tiene un balance inválido: el peso sobrante más el carbón supera el peso disponible.';
            }

            $previousRemaining = $remaining !== null && $remaining >= 0 ? $remaining : null;

            return [
                'day_number' => $dayNumber,
                'recorded' => (bool) $day['recorded'],
                'initial_weight_kg' => $initial,
                'additional_weight_kg' => $additional,
                'available_weight_kg' => $available,
                'remaining_weight_kg' => $remaining,
                'charcoal_weight_kg' => $charcoal,
                'calculation_ready' => $ready,
            ];
        })->values()->all();

        return [
            'kitchen' => $kitchen,
            'days' => $mappedDays,
            'mapping_complete' => empty($missingMappings),
            'has_data' => $hasData,
            'warnings' => $warnings,
        ];
    }

    private function number(array $day, ?string $fieldKey): ?float
    {
        if (! $fieldKey) {
            return null;
        }

        $answer = $day['values'][$fieldKey] ?? null;

        if (! $answer || ($answer['validation_status'] ?? null) !== 'valid' || ! is_numeric($answer['value'] ?? null)) {
            return null;
        }

        return (float) $answer['value'];
    }

    private function withoutInternalFields(array $series): array
    {
        unset($series['has_data'], $series['warnings']);

        return $series;
    }
}
