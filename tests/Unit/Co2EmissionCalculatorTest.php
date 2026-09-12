<?php

namespace Tests\Unit;

use App\Services\Co2EmissionCalculator;
use Tests\TestCase;

class Co2EmissionCalculatorTest extends TestCase
{
    public function test_it_reproduces_the_reference_result_from_the_supplied_html_and_excel(): void
    {
        $result = app(Co2EmissionCalculator::class)->calculate($this->referenceFamilies());

        $this->assertSame(20, $result['status']['baseline_sample_size']);
        $this->assertSame(20, $result['status']['monitoring_sample_size']);
        $this->assertEqualsWithDelta(16.257142857142856, $result['statistics']['baseline']['mean_kg_household_day'], 1.0e-10);
        $this->assertEqualsWithDelta(1.3277282090267981, $result['statistics']['baseline']['critical_t_90_one_tail'], 1.0e-9);
        $this->assertEqualsWithDelta(0.011986301369863015, $result['statistics']['baseline_cap']['adjusted_t_household_day'], 1.0e-12);
        $this->assertEqualsWithDelta(11.258571428571427, $result['statistics']['monitoring']['mean_kg_household_day'], 1.0e-10);
        $this->assertEqualsWithDelta(912.5, $result['parameters']['operating_days_nbpy'], 1.0e-10);
        $this->assertEqualsWithDelta(0.74, $result['parameters']['weighted_usage_upy'], 1.0e-12);
        $this->assertEqualsWithDelta(12.2611241025, $result['emissions']['baseline_final_bey'], 1.0e-9);
        $this->assertEqualsWithDelta(11.751743591472856, $result['emissions']['project_final_aey'], 1.0e-9);
        $this->assertEqualsWithDelta(0.011207610220542888, $result['emissions']['leakage']['total_ley'], 1.0e-9);
        $this->assertEqualsWithDelta(0.447234849703887, $result['emissions']['net_reduction_ery'], 1.0e-9);
    }

    public function test_it_excludes_incomplete_families_instead_of_converting_missing_data_to_zero(): void
    {
        $families = [[
            'household_code' => 'HOG-1',
            'members' => ['children_0_14' => 2, 'women_over_14' => 1, 'men_15_59' => 1, 'men_over_59' => 0],
            'baseline_days' => [],
            'monitoring_days' => [$this->day(1, 20, 10, 0.5)],
        ]];

        $result = app(Co2EmissionCalculator::class)->calculate($families);

        $this->assertFalse($result['status']['calculation_ready']);
        $this->assertSame(0, $result['status']['baseline_sample_size']);
        $this->assertSame(1, $result['status']['monitoring_sample_size']);
        $this->assertNull($result['emissions']['baseline_final_bey']);
        $this->assertNotNull($result['emissions']['project_final_aey']);
        $this->assertNull($result['emissions']['net_reduction_ery']);
    }

    public function test_sensor_monitoring_uses_a_hawthorne_factor_of_one(): void
    {
        $result = app(Co2EmissionCalculator::class)->calculate($this->referenceFamilies(), [
            'monitoring_method' => 'SENSORS',
        ]);

        $this->assertSame(1.0, $result['parameters']['hawthorne_factor']);
        $expected = $result['emissions']['gross_reduction'] - $result['emissions']['leakage']['total_ley'];
        $this->assertEqualsWithDelta($expected, $result['emissions']['net_reduction_ery'], 1.0e-12);
    }

    public function test_it_uses_the_household_composition_recorded_for_each_day(): void
    {
        $result = app(Co2EmissionCalculator::class)->calculate([[
            'household_code' => 'H-DYNAMIC',
            'members' => ['children_0_14' => 0, 'women_over_14' => 0, 'men_15_59' => 2, 'men_over_59' => 0],
            'baseline_days' => [
                $this->day(1, 20, 10, 0) + ['members' => ['children_0_14' => 2, 'women_over_14' => 0, 'men_15_59' => 0, 'men_over_59' => 0]],
                $this->day(2, 20, 10, 0) + ['members' => ['children_0_14' => 0, 'women_over_14' => 0, 'men_15_59' => 2, 'men_over_59' => 0]],
            ],
            'monitoring_days' => [],
        ]]);

        $family = $result['families']['baseline'][0];
        $this->assertEqualsWithDelta(1.5, $family['adult_equivalent'], 1.0e-12);
        $this->assertEqualsWithDelta(7.5, $family['average_per_capita_kg_day'], 1.0e-12);
    }

    private function referenceFamilies(): array
    {
        $baselineFinal = array_fill(0, 20, [5, 4, 2, 2, 2, 1, 2]);
        $baselineFinal[2] = [10, 5, 2, 2, 9, 1, 9];
        $baselineFinal[3] = [10, 5, 6, 2, 8, 1, 7];
        $baselineFinal[4] = [15, 4, 2, 2, 2, 12, 2];
        $baselineFinal[6] = [10, 15, 2, 10, 8, 1, 2];

        $monitoringFinal = array_fill(0, 20, [12, 11, 8, 9, 10, 9, 11]);
        $monitoringFinal[7] = [12, 6, 8, 9, 8, 9, 11];
        $monitoringFinal[10] = [12, 11, 11, 9, 5, 9, 11];
        $monitoringFinal[12] = [12, 15, 8, 9, 10, 10, 11];
        $monitoringFinal[14] = [12, 10, 8, 8, 10, 7, 11];
        $monitoringFinal[17] = [12, 10, 15, 9, 10, 9, 11];

        $baselineCharcoal = [0.5, 0.6, 0.5, 0.5, 0.5, 0.5, 0.5];
        $improvedCharcoal = [0.3, 0.2, 0.15, 0.3, 0.2, 0.15, 0.15];
        $traditionalFinal = [8, 7, 6, 5, 7, 8, 7];
        $traditionalCharcoal = [0.5, 0.2, 0.5, 0.4, 1.8, 2, 0.8];
        $mixedFamilies = [0, 3, 4, 5];
        $families = [];

        for ($index = 0; $index < 20; $index++) {
            $baselineDays = [];
            $monitoringDays = [];
            for ($day = 0; $day < 7; $day++) {
                $baselineDays[] = $this->day($day + 1, 20, $baselineFinal[$index][$day], $baselineCharcoal[$day]);
                $available = 20;
                $remaining = $monitoringFinal[$index][$day];
                $charcoal = $improvedCharcoal[$day];
                if (in_array($index, $mixedFamilies, true)) {
                    $available += 15;
                    $remaining += $traditionalFinal[$day];
                    $charcoal += $traditionalCharcoal[$day];
                }
                $monitoringDays[] = $this->day($day + 1, $available, $remaining, $charcoal);
            }

            $families[] = [
                'household_code' => sprintf('H-%02d', $index + 1),
                'members' => [
                    'children_0_14' => 3,
                    'women_over_14' => 0,
                    'men_15_59' => 2,
                    'men_over_59' => 0,
                ],
                'baseline_days' => $baselineDays,
                'monitoring_days' => $monitoringDays,
            ];
        }

        return $families;
    }

    private function day(int $number, float $available, float $remaining, float $charcoal): array
    {
        return [
            'day_number' => $number,
            'available_weight_kg' => $available,
            'remaining_weight_kg' => $remaining,
            'charcoal_weight_kg' => $charcoal,
            'calculation_ready' => true,
        ];
    }
}
