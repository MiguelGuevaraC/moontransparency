<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorViewTest extends TestCase
{
    public function test_backend_serves_the_complete_delivered_rech_calculator(): void
    {
        $this->get('/calculadora')
            ->assertOk()
            ->assertSee('Reduced Emissions from Cooking and Heating (RECH) v5.0', false)
            ->assertSee('id="mainNav"', false)
            ->assertSee('id="mainContent"', false)
            ->assertSee('window.APP_CONFIG', false)
            ->assertSee('const PRELOADED_FAMILIES', false)
            ->assertSee('co2calc_state_v3_parameter_tables_kpt_complete_v2', false)
            ->assertSee('ERy (Reducción Neta Final)', false)
            ->assertDontSee('id="loginButton"', false)
            ->assertDontSee('/calculator/co2', false);
    }
}
