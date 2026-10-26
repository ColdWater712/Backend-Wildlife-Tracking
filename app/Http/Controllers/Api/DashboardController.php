<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\TrackingDevice;
use App\Models\IncidentReport;
use App\Models\GPSReading;
use App\Models\GeoZone;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAnimals = Animal::count();
        $activeAnimals = Animal::where('is_active', true)->count();
        $totalDevices = TrackingDevice::count();
        $activeDevices = TrackingDevice::where('is_active', true)->count();
        $damagedDevices = TrackingDevice::where('device_condition', 'damaged')->count();
        $lostDevices = TrackingDevice::where('device_condition', 'lost')->count();
        $totalIncidents = IncidentReport::count();
        $pendingIncidents = IncidentReport::where('sync_status', 'pending_sync')->count();
        $totalZones = GeoZone::count();
        $totalGpsReadings = GPSReading::count();

        // Ambil 5 GPS reading terakhir
        $latestReadings = GPSReading::with(['device.animal.species'])
            ->orderBy('recorded_at', 'desc')
            ->limit(5)
            ->get();

        // Ambil 5 insiden terakhir
        $latestIncidents = IncidentReport::with('reporter')
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'summary' => [
                    'total_animals' => $totalAnimals,
                    'active_animals' => $activeAnimals,
                    'total_devices' => $totalDevices,
                    'active_devices' => $activeDevices,
                    'damaged_devices' => $damagedDevices,
                    'lost_devices' => $lostDevices,
                    'total_incidents' => $totalIncidents,
                    'pending_incidents' => $pendingIncidents,
                    'total_zones' => $totalZones,
                    'total_gps_readings' => $totalGpsReadings,
                ],
                'latest_readings' => $latestReadings,
                'latest_incidents' => $latestIncidents,
            ],
            'errors' => null
        ], 200);
    }
}