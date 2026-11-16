<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Models\IncidentReport;

class IncidentReportController extends Controller
{
    public function index()
    {
        $reports = IncidentReport::with('reporter')
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports
        ], 200);
    }

    public function store(StoreIncidentReportRequest $request)
    {
        $data = $request->validated();
        
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = now();
        }

        $report = IncidentReport::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Laporan insiden berhasil dibuat',
            'data' => $report
        ], 201);
    }
}