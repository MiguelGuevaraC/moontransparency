<?php

namespace Tests\Feature;

use App\Http\Controllers\GraficosApiController;
use App\Services\GraficoService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class PublicApiUuidConfigurationTest extends TestCase
{
    public function test_dashboard_uses_the_cached_configuration_value_for_uuid_validation(): void
    {
        config(['app.uuid' => 'public-api-test-key']);

        $service = Mockery::mock(GraficoService::class);
        $service->shouldReceive('total_projects')->once()->andReturn(0);
        $service->shouldReceive('sum_beneficiaries')->once()->andReturn(0);
        $service->shouldReceive('sum_budget_estimated')->once()->andReturn(0);
        $service->shouldReceive('calculateProgress')->once()->andReturn([]);
        $service->shouldReceive('getFinancialStatus')->once()->andReturn([]);
        $service->shouldReceive('getDonationsByType')->once()->andReturn([]);

        $request = Request::create('/api/dashboard', 'GET', [], [], [], [
            'HTTP_UUID' => 'public-api-test-key',
        ]);

        $response = (new GraficosApiController($service))->dashboard_resumen($request);

        $this->assertSame(200, $response->status());
    }

    public function test_dashboard_rejects_an_incorrect_uuid(): void
    {
        config(['app.uuid' => 'public-api-test-key']);

        $request = Request::create('/api/dashboard', 'GET', [], [], [], [
            'HTTP_UUID' => 'incorrect-key',
        ]);

        $response = (new GraficosApiController(Mockery::mock(GraficoService::class)))
            ->dashboard_resumen($request);

        $this->assertSame(401, $response->status());
        $this->assertSame(['status' => 'unauthorized'], $response->getData(true));
    }
}
