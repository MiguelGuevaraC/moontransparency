<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorViewTest extends TestCase
{
    public function test_calculator_can_authenticate_and_load_a_participation_from_the_api(): void
    {
        $this->get('/calculadora')
            ->assertOk()
            ->assertSee('id="btnCalculatorLogin"', false)
            ->assertSee('id="calculatorParticipationId"', false)
            ->assertSee('id="btnLoadParticipation"', false)
            ->assertSee('/surveyed/${participationId}/calculator', false)
            ->assertSee('Authorization', false)
            ->assertSee('calculator_input', false);
    }
}
