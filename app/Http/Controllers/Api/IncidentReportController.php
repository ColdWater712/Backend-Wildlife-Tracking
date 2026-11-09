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
            'photo_url' => [
                'nullable',
                'url',
                'regex:/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i'
            ],
        ], [
            'photo_url.regex' => 'URL foto harus berakhiran dengan ekstensi gambar (.jpg, .jpeg, .png, .gif, .webp, .bmp, .svg).',
            'photo_url.url' => 'Format URL foto tidak valid.',
        ]);

        $data = $request->all();
        
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