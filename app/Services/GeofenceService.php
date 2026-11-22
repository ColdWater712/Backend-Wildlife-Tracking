<?php

namespace App\Services;

use App\Models\AnimalGeoZone;
use App\Models\GpsReading;
use App\Models\TrackingDevice;

/**
 * Memproses pembacaan GPS baru: memperbarui status perangkat, mencatat satwa
 * masuk/keluar zona (animal_geo_zones), dan mengirim peringatan zona berisiko.
 */
class GeofenceService
{
    /** Tipe zona yang dianggap rawan konflik manusia-satwa. */
    public const RISK_ZONE_TYPES = ['permukiman', 'perkebunan', 'jalan_raya'];

    public function __construct(
        private GeoService $geo,
        private NotificationService $notifier,
    ) {}

    public function handleReading(GpsReading $reading): array
    {
        $device = $reading->device ?? TrackingDevice::find($reading->device_id);
        $events = ['entered' => [], 'exited' => []];

        if ($device && ($device->last_recorded_at === null || $reading->recorded_at->gt($device->last_recorded_at))) {
            $device->forceFill(['last_recorded_at' => $reading->recorded_at])->save();
        }

        $animal = $device?->animal;
        if (! $animal) {
            return $events;
        }

        $inside = $this->geo->zonesContaining((float) $reading->latitude, (float) $reading->longitude)
            ->keyBy('zone_id');

        $openVisits = AnimalGeoZone::query()
            ->where('animal_id', $animal->animal_id)
            ->whereNull('exited_at')
            ->get();

        foreach ($openVisits as $visit) {
            if (! $inside->has($visit->zone_id) && $reading->recorded_at->gte($visit->entered_at)) {
                $visit->update(['exited_at' => $reading->recorded_at]);
                $events['exited'][] = $visit->zone_id;
            }
        }

        $openZoneIds = $openVisits->whereNull('exited_at')->pluck('zone_id')->all();

        foreach ($inside as $zoneId => $zone) {
            if (in_array($zoneId, $openZoneIds, true)) {
                continue;
            }

            AnimalGeoZone::create([
                'animal_id' => $animal->animal_id,
                'zone_id' => $zoneId,
                'entry_reading_id' => $reading->reading_id,
                'entered_at' => $reading->recorded_at,
            ]);
            $events['entered'][] = $zoneId;

            if (in_array($zone->zone_type, self::RISK_ZONE_TYPES, true)) {
                $this->notifier->notifyRoles(
                    array_merge(NotificationService::FIELD_ROLES, ['peneliti_ekologi']),
                    'risk_zone',
                    "Satwa {$animal->name_animal} memasuki {$zone->zone_name} ({$zone->zone_type}). Potensi konflik dengan manusia.",
                    ['animal_id' => $animal->animal_id, 'zone_id' => $zoneId, 'device_id' => $device->device_id],
                );
            }
        }

        return $events;
    }
}
