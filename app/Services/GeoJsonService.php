<?php

namespace App\Services;

class GeoJsonService
{
    public function normalize(?array $geojson): ?array
    {
        if (! $geojson) {
            return $geojson;
        }

        if (($geojson['type'] ?? null) === 'FeatureCollection') {
            $geojson['features'] = collect($geojson['features'] ?? [])
                ->map(fn (mixed $feature): mixed => is_array($feature) ? $this->normalize($feature) : $feature)
                ->values()
                ->all();

            return $geojson;
        }

        if (($geojson['type'] ?? null) === 'Feature' && is_array($geojson['geometry'] ?? null)) {
            $geojson['geometry'] = $this->normalize($geojson['geometry']);

            return $geojson;
        }

        if (($geojson['type'] ?? null) === 'Polygon') {
            $geojson['coordinates'] = collect($geojson['coordinates'] ?? [])
                ->map(fn (mixed $ring): mixed => is_array($ring) ? $this->closeRing($ring) : $ring)
                ->values()
                ->all();

            return $geojson;
        }

        if (($geojson['type'] ?? null) === 'MultiPolygon') {
            $geojson['coordinates'] = collect($geojson['coordinates'] ?? [])
                ->map(function (mixed $polygon): mixed {
                    if (! is_array($polygon)) {
                        return $polygon;
                    }

                    return collect($polygon)
                        ->map(fn (mixed $ring): mixed => is_array($ring) ? $this->closeRing($ring) : $ring)
                        ->values()
                        ->all();
                })
                ->values()
                ->all();
        }

        return $geojson;
    }

    private function closeRing(array $ring): array
    {
        if (count($ring) < 3 || ! is_array($ring[0] ?? null)) {
            return $ring;
        }

        $first = $ring[0];
        $last = $ring[array_key_last($ring)];
        if ($first !== $last) {
            $ring[] = $first;
        }

        return array_values($ring);
    }
}
