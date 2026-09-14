<?php

namespace Tests\Unit;

use App\Services\RechCalculatorViewDataMapper;
use Tests\TestCase;

class RechCalculatorViewDataMapperTest extends TestCase
{
    public function test_it_maps_database_dataset_to_the_delivered_html_model(): void
    {
        $families = app(RechCalculatorViewDataMapper::class)->families([[
            'household_code' => 'HOG-00000009',
            'members' => [
                'children_0_14' => 2,
                'women_over_14' => 1,
                'men_15_59' => 1,
                'men_over_59' => 0,
            ],
            'baseline_days' => [[
                'day_number' => 1,
                'available_weight_kg' => 20,
                'remaining_weight_kg' => 6,
                'charcoal_weight_kg' => 0.5,
            ]],
            'monitoring_days' => [[
                'day_number' => 1,
                'members' => [
                    'children_0_14' => 3,
                    'women_over_14' => 1,
                    'men_15_59' => 1,
                    'men_over_59' => 0,
                ],
            ]],
            'monitoring_components' => [
                'moon_group_days' => [[
                    'day_number' => 1,
                    'available_weight_kg' => 20,
                    'remaining_weight_kg' => 12,
                    'charcoal_weight_kg' => 0.3,
                ]],
                'traditional_days' => [[
                    'day_number' => 1,
                    'available_weight_kg' => 15,
                    'remaining_weight_kg' => 8,
                    'charcoal_weight_kg' => 0.5,
                ]],
            ],
        ]], 20);

        $this->assertSame('HOG-00000009', $families[0]['id']);
        $this->assertSame(20.0, $families[0]['lb']['pesoInicial']);
        $this->assertSame([6.0, null, null, null, null, null, null], $families[0]['lb']['pf']);
        $this->assertSame([2.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0], $families[0]['lb']['comp']['ni']);
        $this->assertSame([3.0, 2.0, 2.0, 2.0, 2.0, 2.0, 2.0], $families[0]['mon']['comp']['ni']);
        $this->assertSame(20.0, $families[0]['mon']['pesoInicialMej']);
        $this->assertSame(15.0, $families[0]['mon']['pesoInicialTrad']);
        $this->assertSame([12.0, null, null, null, null, null, null], $families[0]['mon']['pfMej']);
        $this->assertSame([8.0, null, null, null, null, null, null], $families[0]['mon']['pfTrad']);
    }
}
