<?php

namespace App\Services\Ai;

use App\Models\Animal;
use App\Models\AnimalGeoZone;
use App\Models\GpsReading;
use App\Models\Species;
use App\Services\GeoService;
use Carbon\CarbonInterface;

/**
 * Menebak individu satwa mana yang tertangkap kamera/perekam, berdasarkan:
 *  - kedekatan posisi GPS kalung satwa dengan lokasi perangkat pada waktu pengambilan,
 *  - satwa yang tercatat berada di zona yang sama (animal_geo_zones),
 *  - kecocokan ciri fisik pembeda (animals.distinctive_marks),
 *  - satu-satunya individu aktif dari spesies tersebut.
 */
class AnimalIdentifier
{
    private const GPS_WINDOW_HOURS = 6;
    private const GPS_DISTANCE_SCALE_KM = 1.5;
    private const MIN_SCORE = 0.35;
    private const MIN_MARGIN = 0.1;

    public function __construct(private GeoService $geo) {}

    /**
     * @return array{animal: ?Animal, confidence: ?float, method: ?string, candidates: array}
     */
    public function identify(?Species $species, ?float $lat, ?float $lon, ?int $zoneId, CarbonInterface $at, ?string $features = null): array
    {
        $empty = ['animal' => null, 'confidence' => null, 'method' => null, 'candidates' => []];

        if (! $species) {
            return $empty;
        }

        $candidates = Animal::query()
            ->with('trackingDevice')
            ->where('species_id', $species->species_id)
            ->where('is_active', true)
            ->get();

        if ($candidates->isEmpty()) {
            return $empty;
        }

        $scored = $candidates->map(function (Animal $animal) use ($lat, $lon, $zoneId, $at, $features, $candidates) {
            $evidence = [];
            $score = 0.0;

            if ($candidates->count() === 1) {
                $score += 0.3;
                $evidence['single_candidate'] = 0.3;
            }

            if ($lat !== null && $lon !== null && $animal->trackingDevice) {
                $reading = $this->nearestReading($animal->trackingDevice->device_id, $at);
                if ($reading) {
                    $distance = $this->geo->distanceKm($lat, $lon, (float) $reading->latitude, (float) $reading->longitude);
                    $hours = abs($reading->recorded_at->diffInMinutes($at)) / 60;
                    $gps = 0.6 * exp(-$distance / self::GPS_DISTANCE_SCALE_KM) * (1 - 0.5 * min(1, $hours / self::GPS_WINDOW_HOURS));
                    $score += $gps;
                    $evidence['gps_proximity'] = round($gps, 3);
                    $evidence['gps_distance_km'] = round($distance, 3);
                    $evidence['gps_time_gap_hours'] = round($hours, 2);
                }
            }

            if ($zoneId !== null) {
                $zone = $this->zoneScore($animal->animal_id, $zoneId, $at);
                if ($zone > 0) {
                    $score += $zone;
                    $evidence['zone_presence'] = $zone;
                }
            }

            if ($features && $animal->distinctive_marks) {
                $marks = $this->tokenOverlap($features, $animal->distinctive_marks);
                if ($marks > 0) {
                    $score += 0.25 * $marks;
                    $evidence['distinctive_marks'] = round(0.25 * $marks, 3);
                }
            }

            return [
                'animal' => $animal,
                'score' => round(min(0.99, $score), 4),
                'evidence' => $evidence,
            ];
        })->sortByDesc('score')->values();

        $best = $scored->first();
        $second = $scored->get(1);
        $summary = $scored->map(fn ($c) => [
            'animal_id' => $c['animal']->animal_id,
            'name_animal' => $c['animal']->name_animal,
            'score' => $c['score'],
            'evidence' => $c['evidence'],
        ])->take(5)->all();

        $margin = $best['score'] - ($second['score'] ?? 0);
        if ($best['score'] < self::MIN_SCORE || ($second && $margin < self::MIN_MARGIN)) {
            return ['animal' => null, 'confidence' => null, 'method' => null, 'candidates' => $summary];
        }

        $evidence = array_intersect_key($best['evidence'], array_flip(['gps_proximity', 'zone_presence', 'distinctive_marks', 'single_candidate']));
        arsort($evidence);

        return [
            'animal' => $best['animal'],
            'confidence' => $best['score'],
            'method' => array_key_first($evidence),
            'candidates' => $summary,
        ];
    }

    private function nearestReading(int $deviceId, CarbonInterface $at): ?GpsReading
    {
        $from = $at->copy()->subHours(self::GPS_WINDOW_HOURS);
        $to = $at->copy()->addHours(self::GPS_WINDOW_HOURS);

        return GpsReading::query()
            ->where('device_id', $deviceId)
            ->whereBetween('recorded_at', [$from, $to])
            ->get()
            ->sortBy(fn (GpsReading $r) => abs($r->recorded_at->diffInSeconds($at)))
            ->first();
    }

    /** 0.3 jika satwa tercatat di zona tsb saat itu, 0.1 jika pernah dalam 30 hari terakhir. */
    private function zoneScore(int $animalId, int $zoneId, CarbonInterface $at): float
    {
        $visits = AnimalGeoZone::query()
            ->where('animal_id', $animalId)
            ->where('zone_id', $zoneId)
            ->where('entered_at', '>=', $at->copy()->subDays(30))
            ->get();

        $presentNow = $visits->contains(fn (AnimalGeoZone $v) => $v->entered_at->lte($at)
            && ($v->exited_at === null || $v->exited_at->gte($at)));

        return $presentNow ? 0.3 : ($visits->isNotEmpty() ? 0.1 : 0.0);
    }

    private function tokenOverlap(string $a, string $b): float
    {
        $tokenize = fn (string $s) => collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($s)))
            ->filter(fn ($t) => mb_strlen($t) >= 4)
            ->unique();

        $ta = $tokenize($a);
        $tb = $tokenize($b);

        if ($ta->isEmpty() || $tb->isEmpty()) {
            return 0.0;
        }

        return $ta->intersect($tb)->count() / min($ta->count(), $tb->count());
    }
}
