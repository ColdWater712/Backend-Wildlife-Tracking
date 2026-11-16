<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\TrackingDevice;
use App\Models\IncidentReport;
use App\Models\AiPrediction;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_animals' => Animal::count(),
            'active_devices' => TrackingDevice::where('is_active', true)->count(),
            'total_incidents' => IncidentReport::count(),
            'pending_ai_predictions' => AiPrediction::where('verification_status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }
}