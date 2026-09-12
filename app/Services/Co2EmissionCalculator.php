<?php

namespace App\Services;

class Co2EmissionCalculator
{
    public function calculate(array $families, array $overrides = []): array
    {
        $this->assertMethodologyConfiguration();
        $parameters = $this->parameters($overrides);
        $baselineFamilies = [];
        $monitoringFamilies = [];

        foreach ($families as $family) {
            $baseline = $this->familyConsumption($family, 'baseline_days', $parameters['adult_equivalent']);
            $monitoring = $this->familyConsumption($family, 'monitoring_days', $parameters['adult_equivalent']);

            if ($baseline !== null) {
                $baselineFamilies[] = $baseline + $this->familyIdentity($family);
            }
            if ($monitoring !== null) {
                $monitoringFamilies[] = $monitoring + $this->familyIdentity($family);
            }
        }

        $baseline = $this->sampleStatistics($baselineFamilies, 'LOWER');
        $monitoring = $this->sampleStatistics($monitoringFamilies, 'UPPER');
        $cap = $this->baselineCap($baselineFamilies, $baseline, $parameters['per_capita_cap_t_year']);
        $nbpy = $this->operatingDays($parameters['operational_groups']);
        $upy = $this->usageRate($parameters['usage_groups'], $parameters['usage_cap']);
        $emissionMultiplier = $parameters['net_calorific_value_tj_t']
            * (($parameters['co2_emission_factor_t_tj'] * $parameters['non_renewable_biomass_fraction'])
                + $parameters['non_co2_emission_factor_t_tj']);

        $baselineMeanEmissions = $baseline
            ? $this->emissions($nbpy, $upy, $baseline['mean_kg_household_day'] / 1000, $emissionMultiplier, $parameters['years'])
            : null;
        $baselineUnadjusted = $baseline && $cap
            ? $this->emissions($nbpy, $upy, $cap['adjusted_t_household_day'], $emissionMultiplier, $parameters['years'])
            : null;
        $baselineDownwardAdjusted = $baselineUnadjusted === null
            ? null
            : $baselineUnadjusted * (1 - $parameters['downward_adjustment_factor']);
        $baselineFinal = $baselineUnadjusted === null
            ? null
            : min($baselineUnadjusted, $baselineDownwardAdjusted);
        $projectFinal = $monitoring
            ? $this->emissions($nbpy, $upy, $monitoring['adjusted_kg_household_day'] / 1000, $emissionMultiplier, $parameters['years'])
            : null;

        $grossReduction = $baselineFinal !== null && $projectFinal !== null
            ? $baselineFinal - $projectFinal
            : null;
        $embodiedLeakage = $parameters['number_of_stoves']
            * $parameters['manufacturing_emission_t_per_stove']
            / $parameters['stove_lifetime_years'];
        $marketLeakage = $grossReduction === null || $parameters['destruction_evidence']
            ? ($grossReduction === null ? null : 0.0)
            : $grossReduction * $parameters['market_leakage_percentage'];
        $totalLeakage = $marketLeakage === null ? null : $embodiedLeakage + $marketLeakage;
        $netReduction = $grossReduction === null
            ? null
            : ($grossReduction * $parameters['hawthorne_factor']) - $totalLeakage;

        return [
            'contract' => [
                'version' => config('co2.contract_version', '1.0'),
                'methodology' => config('co2.methodology', 'RECH v5.0'),
                'formula_version' => config('co2.formula_version'),
                'factor_source' => config('co2.factor_source'),
                'units' => [
                    'weight' => 'kg',
                    'daily_consumption' => 'kg/hogar/día',
                    'daily_consumption_calculation' => 't/hogar/día',
                    'emissions' => 'tCO2e',
                ],
            ],
            'status' => [
                'calculation_ready' => $baselineFinal !== null && $projectFinal !== null,
                'baseline_ready' => $baselineFinal !== null,
                'monitoring_ready' => $projectFinal !== null,
                'families_received' => count($families),
                'baseline_sample_size' => count($baselineFamilies),
                'monitoring_sample_size' => count($monitoringFamilies),
            ],
            'parameters' => $parameters + [
                'operating_days_nbpy' => $nbpy,
                'weighted_usage_upy' => $upy,
                'emission_multiplier' => $emissionMultiplier,
            ],
            'families' => [
                'baseline' => $baselineFamilies,
                'monitoring' => $monitoringFamilies,
            ],
            'statistics' => [
                'baseline' => $baseline,
                'monitoring' => $monitoring,
                'baseline_cap' => $cap,
            ],
            'emissions' => [
                'baseline_mean_unadjusted' => $baselineMeanEmissions,
                'baseline_before_daf' => $baselineUnadjusted,
                'baseline_after_daf' => $baselineDownwardAdjusted,
                'baseline_final_bey' => $baselineFinal,
                'project_final_aey' => $projectFinal,
                'gross_reduction' => $grossReduction,
                'leakage' => [
                    'embodied' => $grossReduction === null ? null : $embodiedLeakage,
                    'market' => $marketLeakage,
                    'total_ley' => $totalLeakage,
                ],
                'hawthorne_factor' => $parameters['hawthorne_factor'],
                'net_reduction_ery' => $netReduction,
            ],
            'formula' => [
                'baseline' => 'Nbpy × Upy × Pb,adj × NCV × (EFCO2 × fNRB + EFnonCO2) × años',
                'project' => 'Nbpy × Upy × Pp,adj × NCV × (EFCO2 × fNRB + EFnonCO2) × años',
                'final' => 'ERy = ((BEy - AEy) × HEind) - LEy',
            ],
        ];
    }

    public function defaultParameters(): array
    {
        return $this->parameters([]);
    }

    private function assertMethodologyConfiguration(): void
    {
        $requiredPositive = [
            'per_capita_cap_t_year',
            'net_calorific_value_tj_t',
            'co2_emission_factor_t_tj',
            'stove_lifetime_years',
        ];
        foreach ($requiredPositive as $key) {
            if ((float) config('co2.'.$key, 0) <= 0) {
                throw new \LogicException("El factor RECH {$key} debe ser mayor que cero.");
            }
        }

        $fractions = [
            'precision_threshold',
            'non_renewable_biomass_fraction',
            'usage_cap',
            'market_leakage_percentage',
        ];
        foreach ($fractions as $key) {
            $value = (float) config('co2.'.$key, -1);
            if ($value < 0 || $value > 1) {
                throw new \LogicException("El factor RECH {$key} debe estar entre cero y uno.");
            }
        }

        if (! config('co2.adult_equivalent') || ! config('co2.operational_groups') || ! config('co2.usage_groups')) {
            throw new \LogicException('La configuración RECH debe incluir equivalencias, grupos operativos y grupos de uso.');
        }
    }

    private function familyConsumption(array $family, string $daysKey, array $factors): ?array
    {
        $members = $family['members'] ?? [];
        $daily = [];
        foreach ($family[$daysKey] ?? [] as $day) {
            if (! ($day['calculation_ready'] ?? false)) {
                continue;
            }

            $adultEquivalent = 0.0;
            foreach ($factors as $key => $factor) {
                $value = $day['members'][$key] ?? $members[$key] ?? 0;
                $adultEquivalent += max(0.0, (float) $value) * (float) $factor;
            }

            if ($adultEquivalent <= 0) {
                continue;
            }

            $consumption = (float) $day['available_weight_kg']
                - (float) $day['remaining_weight_kg']
                - (float) $day['charcoal_weight_kg'];

            if ($consumption <= 0) {
                continue;
            }

            $daily[] = [
                'day_number' => (int) $day['day_number'],
                'consumption_kg' => $consumption,
                'adult_equivalent' => $adultEquivalent,
                'per_capita_consumption_kg' => $consumption / $adultEquivalent,
            ];
        }

        if (! $daily) {
            return null;
        }

        return [
            'adult_equivalent' => $this->mean(array_column($daily, 'adult_equivalent')),
            'recorded_days' => count($daily),
            'average_consumption_kg_day' => $this->mean(array_column($daily, 'consumption_kg')),
            'average_per_capita_kg_day' => $this->mean(array_column($daily, 'per_capita_consumption_kg')),
            'days' => $daily,
        ];
    }

    private function familyIdentity(array $family): array
    {
        return [
            'household_id' => $family['household_id'] ?? null,
            'household_code' => $family['household_code'] ?? null,
            'baseline_participation_id' => $family['baseline_participation_id'] ?? null,
            'monitoring_participation_id' => $family['monitoring_participation_id'] ?? null,
            'monitoring_scenario' => $family['monitoring_scenario'] ?? null,
        ];
    }

    private function sampleStatistics(array $families, string $direction): ?array
    {
        if (! $families) {
            return null;
        }

        $values = array_column($families, 'average_consumption_kg_day');
        $sampleSize = count($values);
        $mean = $this->mean($values);
        $standardDeviation = $this->sampleStandardDeviation($values);
        $criticalT = $sampleSize >= 2
            ? $this->inverseStudentT((float) config('co2.precision_probability_two_tails', 0.20), $sampleSize - 1)
            : null;
        $standardError = $sampleSize >= 2 ? $standardDeviation / sqrt($sampleSize) : 0.0;
        $margin = $criticalT === null ? 0.0 : $standardError * $criticalT;
        $relativePrecision = $mean !== 0.0 ? $margin / $mean : null;
        $passesPrecision = $sampleSize < 2
            || ($relativePrecision !== null && $relativePrecision <= (float) config('co2.precision_threshold', 0.10));
        $adjusted = $passesPrecision
            ? $mean
            : ($direction === 'LOWER' ? $mean - $margin : $mean + $margin);

        return [
            'mean_kg_household_day' => $mean,
            'sample_standard_deviation' => $standardDeviation,
            'sample_size' => $sampleSize,
            'critical_t_90_one_tail' => $criticalT,
            'standard_error' => $standardError,
            'margin_of_error' => $margin,
            'relative_precision' => $relativePrecision,
            'precision_threshold' => (float) config('co2.precision_threshold', 0.10),
            'passes_90_10' => $passesPrecision,
            'adjustment_direction' => $direction,
            'adjusted_kg_household_day' => max(0.0, $adjusted),
        ];
    }

    private function baselineCap(array $families, ?array $statistics, float $cap): ?array
    {
        if (! $families || $statistics === null) {
            return null;
        }

        $annualPerCapita = $this->mean(array_column($families, 'average_per_capita_kg_day')) * 365 / 1000;
        $appliedPerCapita = min($annualPerCapita, $cap);
        $averageAdultEquivalent = $this->mean(array_column($families, 'adult_equivalent'));
        $householdCapTDay = $appliedPerCapita * ($averageAdultEquivalent / 365);
        $householdCapKgDay = $householdCapTDay * 1000;
        $adjustedKgDay = min($statistics['adjusted_kg_household_day'], $householdCapKgDay);

        return [
            'measured_per_capita_t_year' => $annualPerCapita,
            'maximum_per_capita_t_year' => $cap,
            'applied_per_capita_t_year' => $appliedPerCapita,
            'average_household_adult_equivalent' => $averageAdultEquivalent,
            'household_cap_t_day' => $householdCapTDay,
            'household_cap_kg_day' => $householdCapKgDay,
            'statistical_kg_household_day' => $statistics['adjusted_kg_household_day'],
            'adjusted_kg_household_day' => $adjustedKgDay,
            'adjusted_t_household_day' => $adjustedKgDay / 1000,
        ];
    }

    private function parameters(array $overrides): array
    {
        $method = strtoupper((string) ($overrides['monitoring_method'] ?? config('co2.monitoring_method', 'MANUAL')));
        $year = (int) ($overrides['monitoring_year'] ?? config('co2.monitoring_year', 2026));
        $dafRows = config('co2.downward_adjustment_factors', []);
        $daf = array_key_exists('downward_adjustment_factor', $overrides)
            ? (float) $overrides['downward_adjustment_factor']
            : (float) ($dafRows[$year] ?? config('co2.default_downward_adjustment_factor', 0.02));
        $adultEquivalent = array_replace(
            config('co2.adult_equivalent', []),
            $overrides['adult_equivalent'] ?? []
        );
        $hawthorne = array_key_exists('hawthorne_factor', $overrides)
            ? (float) $overrides['hawthorne_factor']
            : (float) (config('co2.hawthorne_factors.'.$method) ?? config('co2.hawthorne_factors.MANUAL', 0.9));

        return [
            'monitoring_year' => $year,
            'years' => (float) ($overrides['years'] ?? config('co2.years', 1)),
            'adult_equivalent' => array_map('floatval', $adultEquivalent),
            'per_capita_cap_t_year' => (float) ($overrides['per_capita_cap_t_year'] ?? config('co2.per_capita_cap_t_year', 1.25)),
            'net_calorific_value_tj_t' => (float) ($overrides['net_calorific_value_tj_t'] ?? config('co2.net_calorific_value_tj_t', 0.0156)),
            'co2_emission_factor_t_tj' => (float) ($overrides['co2_emission_factor_t_tj'] ?? config('co2.co2_emission_factor_t_tj', 112)),
            'non_renewable_biomass_fraction' => (float) ($overrides['non_renewable_biomass_fraction'] ?? config('co2.non_renewable_biomass_fraction', 0.8)),
            'non_co2_emission_factor_t_tj' => (float) ($overrides['non_co2_emission_factor_t_tj'] ?? config('co2.non_co2_emission_factor_t_tj', 9.49)),
            'operational_groups' => array_values($overrides['operational_groups'] ?? config('co2.operational_groups', [])),
            'usage_cap' => (float) ($overrides['usage_cap'] ?? config('co2.usage_cap', 0.75)),
            'usage_groups' => array_values($overrides['usage_groups'] ?? config('co2.usage_groups', [])),
            'downward_adjustment_factor' => $daf,
            'number_of_stoves' => (float) ($overrides['number_of_stoves'] ?? config('co2.number_of_stoves', 3)),
            'manufacturing_emission_t_per_stove' => (float) ($overrides['manufacturing_emission_t_per_stove'] ?? config('co2.manufacturing_emission_t_per_stove', 0.0017)),
            'stove_lifetime_years' => (float) ($overrides['stove_lifetime_years'] ?? config('co2.stove_lifetime_years', 5)),
            'destruction_evidence' => (bool) ($overrides['destruction_evidence'] ?? config('co2.destruction_evidence', false)),
            'market_leakage_percentage' => (float) ($overrides['market_leakage_percentage'] ?? config('co2.market_leakage_percentage', 0.02)),
            'monitoring_method' => $method,
            'hawthorne_factor' => $hawthorne,
        ];
    }

    private function operatingDays(array $rows): float
    {
        return array_reduce($rows, static function (float $total, array $row): float {
            if (! ($row['operational'] ?? false)) {
                return $total;
            }

            return $total + ((float) ($row['quantity'] ?? 0) * (float) ($row['months'] ?? 0) * (365 / 12));
        }, 0.0);
    }

    private function usageRate(array $rows, float $cap): float
    {
        $total = array_sum(array_map(static fn (array $row): float => (float) ($row['quantity'] ?? 0), $rows));
        if ($total <= 0) {
            return 0.0;
        }

        $weighted = array_reduce($rows, static function (float $sum, array $row) use ($cap): float {
            return $sum + ((float) ($row['quantity'] ?? 0) * min((float) ($row['percentage'] ?? 0), $cap));
        }, 0.0);

        return $weighted / $total;
    }

    private function emissions(float $nbpy, float $upy, float $consumptionTDay, float $multiplier, float $years): float
    {
        return $nbpy * $upy * $consumptionTDay * $multiplier * $years;
    }

    private function mean(array $values): float
    {
        return $values ? array_sum($values) / count($values) : 0.0;
    }

    private function sampleStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = $this->mean($values);
        $squares = array_sum(array_map(static fn (float $value): float => ($value - $mean) ** 2, $values));

        return sqrt($squares / (count($values) - 1));
    }

    private function inverseStudentT(float $probability, int $degreesOfFreedom): float
    {
        $low = 0.0;
        $high = 1000.0;
        for ($i = 0; $i < 200; $i++) {
            $middle = ($low + $high) / 2;
            $probabilityAtMiddle = 2 * (1 - $this->studentCdf($middle, $degreesOfFreedom));
            if ($probabilityAtMiddle > $probability) {
                $low = $middle;
            } else {
                $high = $middle;
            }
        }

        return ($low + $high) / 2;
    }

    private function studentCdf(float $t, int $degreesOfFreedom): float
    {
        $x = $degreesOfFreedom / ($degreesOfFreedom + ($t * $t));
        $probability = 0.5 * $this->incompleteBeta($degreesOfFreedom / 2, 0.5, $x);

        return $t > 0 ? 1 - $probability : $probability;
    }

    private function incompleteBeta(float $a, float $b, float $x): float
    {
        if ($x <= 0) {
            return 0.0;
        }
        if ($x >= 1) {
            return 1.0;
        }

        $bt = exp($this->logGamma($a + $b) - $this->logGamma($a) - $this->logGamma($b) + ($a * log($x)) + ($b * log(1 - $x)));
        if ($x < ($a + 1) / ($a + $b + 2)) {
            return $bt * $this->betaContinuedFraction($x, $a, $b) / $a;
        }

        return 1 - ($bt * $this->betaContinuedFraction(1 - $x, $b, $a) / $b);
    }

    private function betaContinuedFraction(float $x, float $a, float $b): float
    {
        $qab = $a + $b;
        $qap = $a + 1;
        $qam = $a - 1;
        $c = 1.0;
        $d = 1 - ($qab * $x / $qap);
        $d = abs($d) < 1.0e-30 ? 1.0e-30 : $d;
        $d = 1 / $d;
        $h = $d;

        for ($m = 1; $m <= 200; $m++) {
            $m2 = 2 * $m;
            $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
            $d = 1 + ($aa * $d);
            $d = abs($d) < 1.0e-30 ? 1.0e-30 : $d;
            $c = 1 + ($aa / $c);
            $c = abs($c) < 1.0e-30 ? 1.0e-30 : $c;
            $d = 1 / $d;
            $h *= $d * $c;
            $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
            $d = 1 + ($aa * $d);
            $d = abs($d) < 1.0e-30 ? 1.0e-30 : $d;
            $c = 1 + ($aa / $c);
            $c = abs($c) < 1.0e-30 ? 1.0e-30 : $c;
            $d = 1 / $d;
            $delta = $d * $c;
            $h *= $delta;
            if (abs($delta - 1) < 3.0e-9) {
                break;
            }
        }

        return $h;
    }

    private function logGamma(float $x): float
    {
        $coefficients = [
            0.99999999999980993,
            676.5203681218851,
            -1259.1392167224028,
            771.32342877765313,
            -176.61502916214059,
            12.507343278686905,
            -0.13857109526572012,
            9.9843695780195716e-6,
            1.5056327351493116e-7,
        ];

        if ($x < 0.5) {
            return log(M_PI / sin(M_PI * $x)) - $this->logGamma(1 - $x);
        }

        $x -= 1;
        $sum = $coefficients[0];
        $t = $x + 7.5;
        for ($i = 1; $i < 9; $i++) {
            $sum += $coefficients[$i] / ($x + $i);
        }

        return 0.5 * log(2 * M_PI) + (($x + 0.5) * log($t)) - $t + log($sum);
    }
}
