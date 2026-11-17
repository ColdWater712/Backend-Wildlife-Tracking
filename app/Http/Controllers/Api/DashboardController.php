<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AiPrediction;
use App\Models\Animal;
use App\Models\IncidentReport;
use App\Models\Species;
use App\Models\TrackingDevice;
use App\Models\GpsReading;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success([
            'total_animals' => Animal::count(),
            'total_species' => Species::count(),
            'active_devices' => TrackingDevice::where('is_active', true)->count(),
            'total_devices' => TrackingDevice::count(),
            'total_incidents' => IncidentReport::count(),
            'pending_ai_predictions' => AiPrediction::where('verification_status', 'pending')->count(),
            'total_gps_readings' => GpsReading::count(),
            'low_battery_devices' => TrackingDevice::where('battery_level', '<', 20)->count(),
        ]);
    }
}