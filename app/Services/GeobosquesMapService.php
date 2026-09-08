<?php

namespace App\Services;

class GeobosquesMapService
{
    /**
     * Build the GeoBosques viewer contract for a coordinate pair.
     */
    public function build($latitude, $longitude): array
    {
        if ($this->isMissing($latitude) && $this->isMissing($longitude)) {
            return $this->unavailable(
                'No hay coordenadas registradas para mostrar el mapa.'
            );
        }

        if (! $this->isValidLatitude($latitude) || ! $this->isValidLongitude($longitude)) {
            return $this->unavailable(
                'Las coordenadas registradas no son válidas para mostrar el mapa.'
            );
        }

        $normalizedLatitude = $this->normalize((float) $latitude);
        $normalizedLongitude = $this->normalize((float) $longitude);
        $markerParameter = (string) config('geobosques.viewer.marker_parameter');

        return [
            'available' => true,
            'provider' => config('geobosques.viewer.provider'),
            'latitude' => (float) $normalizedLatitude,
            'longitude' => (float) $normalizedLongitude,
            'viewer_url' => rtrim((string) config('geobosques.viewer.base_url'), '?').'?'.$markerParameter.'='.$normalizedLatitude.','.$normalizedLongitude,
            'embed_url' => route('geobosques.map', [
                'latitude' => $normalizedLatitude,
                'longitude' => $normalizedLongitude,
            ]),
            'marker_supported' => true,
            'marker_parameter' => $markerParameter,
            'requires_connection' => true,
            'load_strategy' => 'WHEN_ONLINE',
            'message' => null,
        ];
    }

    private function unavailable(string $message): array
    {
        return [
            'available' => false,
            'provider' => config('geobosques.viewer.provider'),
            'latitude' => null,
            'longitude' => null,
            'viewer_url' => null,
            'embed_url' => null,
            'marker_supported' => true,
            'marker_parameter' => config('geobosques.viewer.marker_parameter'),
            'requires_connection' => true,
            'load_strategy' => 'WHEN_ONLINE',
            'message' => $message,
        ];
    }

    private function isMissing($value): bool
    {
        return $value === null || $value === '';
    }

    private function isValidLatitude($latitude): bool
    {
        return ! $this->isMissing($latitude)
            && is_numeric($latitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90;
    }

    private function isValidLongitude($longitude): bool
    {
        return ! $this->isMissing($longitude)
            && is_numeric($longitude)
            && (float) $longitude >= -180
            && (float) $longitude <= 180;
    }

    private function normalize(float $coordinate): string
    {
        $normalized = rtrim(rtrim(number_format($coordinate, 7, '.', ''), '0'), '.');

        return $normalized === '-0' ? '0' : $normalized;
    }
}
