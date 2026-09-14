<?php

namespace Tests\Feature;

use Tests\TestCase;

class CalculatorViewTest extends TestCase
{
    public function test_backend_serves_the_complete_calculator_without_fictitious_families(): void
    {
        $this->get('/calculadora')
            ->assertOk()
            ->assertHeader(
                'Content-Security-Policy',
                "frame-ancestors 'self' https://moongroup-admin.vercel.app https://www.moongroup.com.pe"
            )
            ->assertSee('Reduced Emissions from Cooking and Heating (RECH) v5.0', false)
            ->assertSee('Calculadora sin datos de encuesta', false)
            ->assertSee('id="mainNav"', false)
            ->assertSee('id="mainContent"', false)
            ->assertSee('const CFG = window.APP_CONFIG', false)
            ->assertSee('const SURVEY_FAMILIES = []', false)
            ->assertDontSee('const PRELOADED_FAMILIES', false)
            ->assertDontSee('"id":"H-01"', false)
            ->assertSee('ERy (Reducción Neta Final)', false)
            ->assertDontSee('id="loginButton"', false)
            ->assertDontSee('/calculator/co2', false);
    }

    public function test_embed_view_requires_a_valid_temporary_signature(): void
    {
        $this->get('/calculadora/embed?project_id=1&baseline_survey_id=1')
            ->assertForbidden();
    }
}
