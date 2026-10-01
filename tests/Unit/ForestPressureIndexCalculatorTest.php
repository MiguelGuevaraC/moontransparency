<?php

namespace Tests\Unit;

use App\Services\ForestPressureIndexCalculator;
use Tests\TestCase;

class ForestPressureIndexCalculatorTest extends TestCase
{
    public function test_it_reproduces_the_example_of_the_ipb_v2_workbook(): void
    {
        $calculator = new ForestPressureIndexCalculator();
        $distance = config('forest_pressure.factors.0');

        // 03_Evaluacion: 1 km, Media, 1 km, Ocasional, Gran perturbación.
        $result = $calculator->evaluate([
            'accessibility' => $calculator->distanceLevel(1, $distance),
            'agriculture' => 2,
            'settlements' => $calculator->distanceLevel(1, $distance),
            'forest_use' => 2,
            'disturbance' => 3,
        ]);

        $this->assertSame(2.6, $result['ipb']);
        $this->assertSame('ALTA', $result['level']);
        $this->assertSame(
            ['Accesibilidad', 'Asentamientos humanos', 'Perturbación del bosque'],
            $result['high_factors']
        );
    }

    public function test_distance_levels_follow_the_workbook_thresholds(): void
    {
        $calculator = new ForestPressureIndexCalculator();
        $factor = config('forest_pressure.factors.0');

        $this->assertSame(3, $calculator->distanceLevel(0.4, $factor));
        $this->assertSame(3, $calculator->distanceLevel(1, $factor));
        $this->assertSame(2, $calculator->distanceLevel(1.01, $factor));
        $this->assertSame(2, $calculator->distanceLevel(5, $factor));
        $this->assertSame(1, $calculator->distanceLevel(5.5, $factor));
    }

    public function test_pressure_levels_follow_the_interpretation_ranges(): void
    {
        $calculator = new ForestPressureIndexCalculator();

        $this->assertSame('BAJA', $calculator->pressureLevel(1.0));
        $this->assertSame('BAJA', $calculator->pressureLevel(1.5));
        $this->assertSame('MEDIA', $calculator->pressureLevel(1.6));
        $this->assertSame('MEDIA', $calculator->pressureLevel(2.2));
        $this->assertSame('ALTA', $calculator->pressureLevel(2.4));
        $this->assertSame('ALTA', $calculator->pressureLevel(3.0));
    }

    public function test_an_incomplete_evaluation_has_no_index(): void
    {
        $this->assertNull((new ForestPressureIndexCalculator())->evaluate([
            'accessibility' => 3,
            'agriculture' => 1,
            'settlements' => 2,
            'forest_use' => null,
            'disturbance' => 1,
        ]));
    }
}
