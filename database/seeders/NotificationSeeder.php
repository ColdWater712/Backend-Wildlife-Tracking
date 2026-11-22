<?php

namespace Database\Seeders;

use App\Models\TrackingDevice;
use App\Services\DeviceHealthService;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(DeviceHealthService $health, NotificationService $notifier): void
    {
        TrackingDevice::all()->each(fn (TrackingDevice $d) => $health->evaluate($d));

        $notifier->notifyRoles(
            ['administrator_sistem', 'peneliti_ekologi', 'pengambil_kebijakan'],
            'system',
            'Laporan harian siap diunduh.',
        );
    }
}
