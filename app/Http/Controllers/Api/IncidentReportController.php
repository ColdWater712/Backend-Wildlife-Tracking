<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $request->validate([
            'incident_type' => 'required|string|max:50',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'animal_code' => 'nullable|string|max:50',
            'photo_url' => 'nullable|string|max:500',
        ]);

        $data = $request->all();
        
        // Set timestamp manual jika tidak dikirim (menggunakan waktu sekarang)
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