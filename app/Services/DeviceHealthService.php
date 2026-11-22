<?php

namespace App\Services;

use App\Models\TrackingDevice;

class DeviceHealthService
{
    public const LOW_BATTERY = 20;
    public const WEAK_SIGNAL = 1;

    public function __construct(private NotificationService $notifier) {}

    /** Kirim device_alert bila baterai rendah, sinyal lemah, atau kondisi rusak/hilang. */
    public function evaluate(TrackingDevice $device): void
    {
        $label = "Perangkat #{$device->device_id} ({$device->model})";
        $refs = ['device_id' => $device->device_id, 'animal_id' => $device->animal_id, 'zone_id' => $device->zone_id];
        $roles = NotificationService::FIELD_ROLES;

        if ($device->battery_level !== null && $device->battery_level < self::LOW_BATTERY) {
            $this->notifier->notifyRoles($roles, 'device_alert', "{$label}: baterai rendah ({$device->battery_level}%).", $refs, 720);
        }

        if ($device->signal_strength !== null && $device->signal_strength <= self::WEAK_SIGNAL && $device->is_active) {
            $this->notifier->notifyRoles($roles, 'device_alert', "{$label}: sinyal lemah ({$device->signal_strength}).", $refs, 720);
        }

        if (in_array($device->device_condition, ['damaged', 'lost'], true)) {
            $state = $device->device_condition === 'lost' ? 'hilang' : 'rusak';
            $this->notifier->notifyRoles($roles, 'device_alert', "{$label} dilaporkan {$state}.", $refs, 720);
        }
    }
}
