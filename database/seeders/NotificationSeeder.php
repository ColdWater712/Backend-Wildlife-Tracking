<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\Notification;
use App\Models\TrackingDevice;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $animals = Animal::all();
        $zones = GeoZone::all();
        $devices = TrackingDevice::all();

        $notifications = [
            ['type' => 'risk_zone', 'msg' => 'Satwa memasuki zona perkebunan', 'read' => true],
            ['type' => 'device_alert', 'msg' => 'Baterai perangkat lemah', 'read' => true],
            ['type' => 'system', 'msg' => 'Laporan harian siap diunduh', 'read' => true],
            ['type' => 'risk_zone', 'msg' => 'Satwa terdeteksi di zona lindung', 'read' => false],
            ['type' => 'device_alert', 'msg' => 'Perangkat kehilangan sinyal', 'read' => false],
        ];

        foreach ($notifications as $i => $n) {
            Notification::updateOrCreate(
                ['user_id' => $users[$i % $users->count()]->user_id, 'message' => $n['msg']],
                [
                    'user_id' => $users[$i % $users->count()]->user_id,
                    'notification_type' => $n['type'],
                    'message' => $n['msg'],
                    'animal_id' => $animals[$i % $animals->count()]?->animal_id,
                    'zone_id' => $zones[$i % $zones->count()]?->zone_id,
                    'device_id' => $devices[$i % $devices->count()]?->device_id,
                    'is_read' => $n['read'],
                ]
            );
        }
    }
}