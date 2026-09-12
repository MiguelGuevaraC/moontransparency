<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorViewTest extends TestCase
{
    public function test_calculator_can_authenticate_and_run_the_official_rech_api(): void
    {
        $this->get('/calculadora')
            ->assertOk()
            ->assertSee('Metodología RECH v5.0', false)
            ->assertSee('id="loginButton"', false)
            ->assertSee('/calculator/co2/configuration', false)
            ->assertSee('/calculator/co2', false)
            ->assertSee('Authorization', false)
            ->assertSee('net_reduction_ery', false);
    }
}
