<?php

namespace Tests\Feature;

use App\Http\Resources\SurveyedResource;
use App\Models\Surveyed;
use App\Services\GeobosquesMapService;
use Tests\TestCase;

class GeobosquesMapTest extends TestCase
{
    public function test_resource_builds_the_official_viewer_url_from_saved_coordinates(): void
    {
        $surveyed = new Surveyed([
            'latitude' => -6.3945400,
            'longitude' => -79.8224030,
        ]);

        $map = (new SurveyedResource($surveyed))->resolve()['geobosques_map'];

        $this->assertTrue($map['available']);
        $this->assertSame('GEOBOSQUES_MINAM', $map['provider']);
        $this->assertSame(-6.39454, $map['latitude']);
        $this->assertSame(-79.822403, $map['longitude']);
        $this->assertSame(
            'https://geobosques.minam.gob.pe/geobosque/visor/index.php?xy=-6.39454,-79.822403',
            $map['viewer_url']
        );
        $this->assertStringEndsWith(
            '/mapa?latitude=-6.39454&longitude=-79.822403',
            $map['embed_url']
        );
        $this->assertTrue($map['marker_supported']);
        $this->assertSame('xy', $map['marker_parameter']);
        $this->assertTrue($map['requires_connection']);
        $this->assertSame('WHEN_ONLINE', $map['load_strategy']);
    }

    public function test_missing_or_invalid_coordinates_do_not_produce_a_misleading_map(): void
    {
        $service = app(GeobosquesMapService::class);

        $missing = $service->build(null, null);
        $incomplete = $service->build(-6.39454, null);
        $invalid = $service->build(91, -79.822403);

        $this->assertFalse($missing['available']);
        $this->assertNull($missing['viewer_url']);
        $this->assertNull($missing['embed_url']);
        $this->assertSame(
            'No hay coordenadas registradas para mostrar el mapa.',
            $missing['message']
        );

        foreach ([$incomplete, $invalid] as $map) {
            $this->assertFalse($map['available']);
            $this->assertNull($map['viewer_url']);
            $this->assertSame(
                'Las coordenadas registradas no son válidas para mostrar el mapa.',
                $map['message']
            );
        }
    }

    public function test_map_page_defers_the_external_viewer_until_the_browser_is_online(): void
    {
        $response = $this->get('/mapa?latitude=-6.39454&longitude=-79.822403');

        $response
            ->assertOk()
            ->assertSee('data-viewer-url="https://geobosques.minam.gob.pe/geobosque/visor/index.php?xy=-6.39454,-79.822403"', false)
            ->assertSee('if (! navigator.onLine)', false)
            ->assertSee('frame.src = viewerUrl', false)
            ->assertSee("frame.removeAttribute('src')", false)
            ->assertDontSee('src="https://geobosques.minam.gob.pe', false)
            ->assertDontSee('-8.3799965384869', false);
    }

    public function test_map_page_shows_a_clear_message_when_coordinates_are_unavailable(): void
    {
        $this->get('/mapa')
            ->assertOk()
            ->assertSee('No hay coordenadas registradas para mostrar el mapa.')
            ->assertDontSee('<iframe', false)
            ->assertDontSee(GeobosquesMapService::VIEWER_BASE_URL, false);

        $this->get('/mapa?latitude=not-a-number&longitude=-79.822403')
            ->assertOk()
            ->assertSee('Las coordenadas registradas no son válidas para mostrar el mapa.')
            ->assertDontSee('<iframe', false);
    }
}
