<?php

namespace App\Services;

class RechCalculatorViewDataMapper
{
    public function families(array $families, int $limit): array
    {
        return collect($families)
            ->take($limit)
            ->map(fn (array $family) => $this->family($family))
            ->values()
            ->all();
    }

    public function configuration(array $parameters): array
    {
        $operationalRows = collect($parameters['operational_groups'] ?? [])
            ->map(fn (array $row) => [
                'id' => '',
                'cantidad' => (float) ($row['quantity'] ?? 0),
                'operativa' => (bool) ($row['operational'] ?? false),
                'fechaInicio' => '',
                'meses' => (float) ($row['months'] ?? 0),
            ])
            ->values();

        while ($operationalRows->count() < 20) {
            $operationalRows->push([
                'id' => '',
                'cantidad' => 0,
                'operativa' => false,
                'fechaInicio' => '',
                'meses' => 0,
            ]);
        }

        $usageRows = collect($parameters['usage_groups'] ?? [])
            ->values()
            ->map(fn (array $row, int $index) => [
                'rango' => 'Grupo '.($index + 1),
                'cantidad' => (float) ($row['quantity'] ?? 0),
                'porcentaje' => (float) ($row['percentage'] ?? 0),
            ])
            ->all();

        $adultEquivalent = $parameters['adult_equivalent'] ?? [];

        return [
            'maxFamilias' => (int) config('co2.sample_limit', 20),
            'pesoInicialLBdefault' => null,
            'pesoInicialMejDefault' => null,
            'pesoInicialTradDefault' => null,
            'fChild' => (float) ($adultEquivalent['children_0_14'] ?? 0.5),
            'fWoman' => (float) ($adultEquivalent['women_over_14'] ?? 0.8),
            'fMan1559' => (float) ($adultEquivalent['men_15_59'] ?? 1),
            'fMan60plus' => (float) ($adultEquivalent['men_over_59'] ?? 0.8),
            'pcapTope' => (float) ($parameters['per_capita_cap_t_year'] ?? 1.25),
            'NCVbfuel' => (float) ($parameters['net_calorific_value_tj_t'] ?? 0.0156),
            'EFbfCO2' => (float) ($parameters['co2_emission_factor_t_tj'] ?? 112),
            'fNRBby' => (float) ($parameters['non_renewable_biomass_fraction'] ?? 0.8),
            'EFbfNonCO2' => (float) ($parameters['non_co2_emission_factor_t_tj'] ?? 9.49),
            'nYears' => (float) ($parameters['years'] ?? 1),
            'nbpyRows' => $operationalRows->take(20)->all(),
            'upyCap' => (float) ($parameters['usage_cap'] ?? 0.75),
            'upyRows' => $usageRows,
            'monitoringYear' => (int) ($parameters['monitoring_year'] ?? now()->year),
            'dafRows' => collect(config('co2.downward_adjustment_factors', []))
                ->map(fn ($percentage, $year) => [
                    'year' => (int) $year,
                    'pct' => (float) $percentage,
                ])->values()->all(),
            'dafDefault' => (float) ($parameters['downward_adjustment_factor'] ?? 0.02),
            'numCocinas' => (float) ($parameters['number_of_stoves'] ?? 0),
            'emisionFabCocina' => (float) ($parameters['manufacturing_emission_t_per_stove'] ?? 0),
            'aniosVidaCocina' => (float) ($parameters['stove_lifetime_years'] ?? 1),
            'evidenciaDestruccion' => (bool) ($parameters['destruction_evidence'] ?? false),
            'leMarketPct' => (float) ($parameters['market_leakage_percentage'] ?? 0),
            'HEind' => (float) ($parameters['hawthorne_factor'] ?? 0.9),
        ];
    }

    private function family(array $family): array
    {
        $baselineDays = $this->daysByNumber($family['baseline_days'] ?? []);
        $moonDays = $this->daysByNumber($family['monitoring_components']['moon_group_days'] ?? []);
        $traditionalDays = $this->daysByNumber($family['monitoring_components']['traditional_days'] ?? []);
        $monitoringDays = $this->daysByNumber($family['monitoring_days'] ?? []);
        $fallbackMembers = $family['members'] ?? [];

        return [
            'id' => (string) ($family['household_code'] ?? ''),
            'lb' => [
                'comp' => $this->composition($baselineDays, $fallbackMembers),
                'pesoInicial' => $this->firstValue($baselineDays, 'available_weight_kg'),
                'pf' => $this->series($baselineDays, 'remaining_weight_kg'),
                'carbon' => $this->series($baselineDays, 'charcoal_weight_kg'),
            ],
            'mon' => [
                'comp' => $this->composition($monitoringDays, $fallbackMembers),
                'pesoInicialMej' => $this->firstValue($moonDays, 'available_weight_kg'),
                'pesoInicialTrad' => $this->firstValue($traditionalDays, 'available_weight_kg'),
                'pfMej' => $this->series($moonDays, 'remaining_weight_kg'),
                'carbMej' => $this->series($moonDays, 'charcoal_weight_kg'),
                'pfTrad' => $this->series($traditionalDays, 'remaining_weight_kg'),
                'carbTrad' => $this->series($traditionalDays, 'charcoal_weight_kg'),
            ],
        ];
    }

    private function daysByNumber(array $days): array
    {
        return collect($days)
            ->filter(fn (array $day) => isset($day['day_number']))
            ->keyBy(fn (array $day) => (int) $day['day_number'])
            ->all();
    }

    private function composition(array $days, array $fallback): array
    {
        $keys = [
            'ni' => 'children_0_14',
            'mu' => 'women_over_14',
            'h1' => 'men_15_59',
            'h2' => 'men_over_59',
        ];

        return collect($keys)->mapWithKeys(function (string $memberKey, string $targetKey) use ($days, $fallback) {
            $values = collect(range(1, 7))->map(function (int $day) use ($days, $fallback, $memberKey) {
                return $this->numberOrNull($days[$day]['members'][$memberKey] ?? $fallback[$memberKey] ?? null);
            })->all();

            return [$targetKey => $values];
        })->all();
    }

    private function series(array $days, string $key): array
    {
        return collect(range(1, 7))
            ->map(fn (int $day) => $this->numberOrNull($days[$day][$key] ?? null))
            ->all();
    }

    private function firstValue(array $days, string $key): ?float
    {
        foreach (range(1, 7) as $day) {
            $value = $this->numberOrNull($days[$day][$key] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function numberOrNull($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
