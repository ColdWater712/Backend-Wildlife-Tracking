<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Animal;
use App\Models\GpsReading;
use App\Models\IncidentReport;
use App\Models\TrackingDevice;
use App\Models\Species;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $stats = [
            'total_animals'      => Animal::count(),
            'active_animals'     => Animal::where('is_active', true)->count(),
            'total_species'      => Species::count(),
            'total_devices'      => TrackingDevice::count(),
            'active_devices'     => TrackingDevice::where('status', 'active')->count(),
            'total_incidents'    => IncidentReport::count(),
            'critical_incidents' => IncidentReport::whereIn('incident_type', ['konflik', 'perambahan'])->count(),
            'total_readings'     => GpsReading::count(),
        ];

        return $this->success($stats);
    }

    public function stats(Request $request)
    {
        $period = $request->input('period', '7'); // days
        $startDate = now()->subDays($period);

        $stats = [
            'new_animals'   => Animal::where('created_at', '>=', $startDate)->count(),
            'new_incidents' => IncidentReport::where('timestamp', '>=', $startDate)->count(),
            'new_readings'  => GpsReading::where('recorded_at', '>=', $startDate)->count(),
            'low_battery'   => TrackingDevice::where('battery_level', '<', 20)->count(),
        ];

        return $this->success($stats);
    }

    public function charts(Request $request)
    {
        $period = $request->input('period', '30');
        $startDate = now()->subDays($period);

        // Incidents per day
        $incidentsByDay = IncidentReport::where('timestamp', '>=', $startDate)
            ->selectRaw('DATE(timestamp) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Readings per day
        $readingsByDay = GpsReading::where('recorded_at', '>=', $startDate)
            ->selectRaw('DATE(recorded_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'incidents' => $incidentsByDay,
            'readings'  => $readingsByDay,
        ]);
    }
}