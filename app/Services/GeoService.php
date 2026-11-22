<?php

namespace App\Services;

use App\Models\GeoZone;
use Illuminate\Support\Collection;

class GeoService
{
    /** Jarak dua titik WGS84 dalam kilometer (haversine). */
    public function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function zoneContains(GeoZone $zone, float $lat, float $lon): bool
    {
        $polygon = $zone->polygon_geojson;

        if (is_array($polygon) && $polygon !== []) {
            return $this->geoJsonContains($polygon, $lat, $lon);
        }

        if ($zone->center_lat !== null && $zone->center_lon !== null && $zone->radius_km !== null) {
            return $this->distanceKm($lat, $lon, (float) $zone->center_lat, (float) $zone->center_lon)
                <= (float) $zone->radius_km;
        }

        return false;
    }

    /** @return Collection<int, GeoZone> */
    public function zonesContaining(float $lat, float $lon): Collection
    {
        return GeoZone::query()->get()
            ->filter(fn (GeoZone $zone) => $this->zoneContains($zone, $lat, $lon))
            ->values();
    }

    /** Zona yang memuat titik; jika tidak ada, zona dengan pusat terdekat dalam $maxKm. */
    public function resolveZone(?float $lat, ?float $lon, float $maxKm = 5.0): ?GeoZone
    {
        if ($lat === null || $lon === null) {
            return null;
        }

        $containing = $this->zonesContaining($lat, $lon);
        if ($containing->isNotEmpty()) {
            return $containing->first();
        }

        return GeoZone::query()
            ->whereNotNull('center_lat')->whereNotNull('center_lon')
            ->get()
            ->map(fn (GeoZone $z) => [$z, $this->distanceKm($lat, $lon, (float) $z->center_lat, (float) $z->center_lon)])
            ->filter(fn ($pair) => $pair[1] <= $maxKm)
            ->sortBy(fn ($pair) => $pair[1])
            ->map(fn ($pair) => $pair[0])
            ->first();
    }

    /** Validasi struktur GeoJSON Polygon / MultiPolygon / Feature. */
    public function isValidPolygon(mixed $geojson): bool
    {
        if (! is_array($geojson)) {
            return false;
        }

        if (($geojson['type'] ?? null) === 'Feature') {
            return $this->isValidPolygon($geojson['geometry'] ?? null);
        }

        $rings = match ($geojson['type'] ?? null) {
            'Polygon' => [$geojson['coordinates'] ?? null],
            'MultiPolygon' => $geojson['coordinates'] ?? null,
            default => null,
        };

        if (! is_array($rings) || $rings === []) {
            return false;
        }

        foreach ($rings as $polygon) {
            if (! is_array($polygon) || $polygon === []) {
                return false;
            }
            foreach ($polygon as $ring) {
                if (! is_array($ring) || count($ring) < 4) {
                    return false;
                }
                foreach ($ring as $point) {
                    if (! is_array($point) || count($point) < 2
                        || ! is_numeric($point[0]) || ! is_numeric($point[1])
                        || abs((float) $point[0]) > 180 || abs((float) $point[1]) > 90) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function geoJsonContains(array $geojson, float $lat, float $lon): bool
    {
        if (($geojson['type'] ?? null) === 'Feature') {
            return $this->geoJsonContains($geojson['geometry'] ?? [], $lat, $lon);
        }

        $polygons = match ($geojson['type'] ?? null) {
            'Polygon' => [$geojson['coordinates'] ?? []],
            'MultiPolygon' => $geojson['coordinates'] ?? [],
            default => [],
        };

        foreach ($polygons as $polygon) {
            $outer = $polygon[0] ?? [];
            if (! $this->ringContains($outer, $lat, $lon)) {
                continue;
            }
            $inHole = false;
            foreach (array_slice($polygon, 1) as $hole) {
                if ($this->ringContains($hole, $lat, $lon)) {
                    $inHole = true;
                    break;
                }
            }
            if (! $inHole) {
                return true;
            }
        }

        return false;
    }

    /** Ray casting. Koordinat GeoJSON berurutan [lon, lat]. */
    private function ringContains(array $ring, float $lat, float $lon): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = [(float) $ring[$i][0], (float) $ring[$i][1]];
            [$xj, $yj] = [(float) $ring[$j][0], (float) $ring[$j][1]];

            if ((($yi > $lat) !== ($yj > $lat))
                && ($lon < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
