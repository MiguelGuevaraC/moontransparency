<?php

namespace Tests\Unit;

use App\Services\CalculatorInputMapper;
use PHPUnit\Framework\TestCase;

class CalculatorInputMapperTest extends TestCase
{
    public function test_it_maps_combined_monitoring_into_traditional_and_moon_series(): void
    {
        $fields = collect(array_merge(
            $this->memberFields(),
            $this->seriesFields('monitoring.traditional', 'traditional'),
            $this->seriesFields('monitoring.moon', 'moon'),
            $this->seriesFields('monitoring.exclusive', 'exclusive')
        ));
        $days = collect([
            $this->day(1, [
                'children' => 2, 'women' => 1, 'men' => 1, 'older_men' => 0,
                'traditional_initial' => 8, 'traditional_remaining' => 5, 'traditional_charcoal' => .5,
                'moon_initial' => 10, 'moon_remaining' => 6, 'moon_charcoal' => 1,
            ]),
            $this->day(2, [
                'traditional_additional' => 2, 'traditional_remaining' => 4, 'traditional_charcoal' => .2,
                'moon_additional' => 3, 'moon_remaining' => 4, 'moon_charcoal' => .5,
            ]),
        ]);

        $result = (new CalculatorInputMapper())->map($fields, $days);

        $this->assertTrue($result['supported']);
        $this->assertSame(CalculatorInputMapper::SCENARIO_MONITORING_COMBINED, $result['scenario']);
        $this->assertSame(4.0, $result['household_members']['total']);
        $this->assertSame('TRADITIONAL', $result['baseline']['kitchen']);
        $this->assertSame(7.0, $result['baseline']['days'][1]['available_weight_kg']);
        $this->assertSame('MOON_GROUP', $result['project']['kitchen']);
        $this->assertSame(9.0, $result['project']['days'][1]['available_weight_kg']);
        $this->assertTrue($result['project']['days'][1]['calculation_ready']);
    }

    public function test_it_maps_exclusive_monitoring_only_to_project_series(): void
    {
        $fields = collect(array_merge(
            $this->memberFields(),
            $this->seriesFields('monitoring.traditional', 'traditional'),
            $this->seriesFields('monitoring.moon', 'moon'),
            $this->seriesFields('monitoring.exclusive', 'exclusive')
        ));
        $days = collect([
            $this->day(1, [
                'children' => 1, 'women' => 2, 'men' => 0, 'older_men' => 1,
                'exclusive_initial' => 9,
                'exclusive_remaining' => 5,
                'exclusive_charcoal' => .4,
            ]),
        ]);

        $result = (new CalculatorInputMapper())->map($fields, $days);

        $this->assertTrue($result['supported']);
        $this->assertSame(CalculatorInputMapper::SCENARIO_MONITORING_MOON_ONLY, $result['scenario']);
        $this->assertNull($result['baseline']);
        $this->assertSame('MOON_GROUP', $result['project']['kitchen']);
        $this->assertSame(9.0, $result['project']['days'][0]['available_weight_kg']);
    }

    private function memberFields(): array
    {
        return [
            $this->field('children', 'household.children_0_14'),
            $this->field('women', 'household.women_over_14'),
            $this->field('men', 'household.men_15_59'),
            $this->field('older_men', 'household.men_over_59'),
        ];
    }

    private function seriesFields(string $prefix, string $fieldPrefix): array
    {
        return [
            $this->field($fieldPrefix.'_initial', $prefix.'.initial_wood_kg'),
            $this->field($fieldPrefix.'_additional', $prefix.'.additional_wood_kg'),
            $this->field($fieldPrefix.'_remaining', $prefix.'.remaining_wood_kg'),
            $this->field($fieldPrefix.'_charcoal', $prefix.'.charcoal_kg'),
        ];
    }

    private function field(string $key, string $calculatorKey): array
    {
        return ['key' => $key, 'calculator_key' => $calculatorKey];
    }

    private function day(int $number, array $values): array
    {
        return [
            'day_number' => $number,
            'recorded' => true,
            'values' => collect($values)->map(fn ($value) => [
                'value' => $value,
                'validation_status' => 'valid',
            ])->all(),
        ];
    }
}
