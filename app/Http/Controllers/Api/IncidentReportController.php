<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    // ... (method index dan show yang sudah ada biarkan saja) ...

    public function store(StoreIncidentReportRequest $request)
    {
        // Data user_id diambil dari user yang sedang login (token)
        $data = $request->validated();
        $data['user_id'] = $request->user()->user_id;
        $data['timestamp'] = now();

        $report = IncidentReport::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Laporan insiden berhasil dibuat.',
            'data' => $report,
            'errors' => null
        ], 201);
    }

    public function update(StoreIncidentReportRequest $request, $id)
    {
        $report = IncidentReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
                'data' => null,
                'errors' => null
            ], 404);
        }

        $report->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Laporan insiden berhasil diperbarui.',
            'data' => $report,
            'errors' => null
        ], 200);
    }

    public function destroy($id)
    {
        $report = IncidentReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
                'data' => null,
                'errors' => null
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan insiden berhasil dihapus.',
            'data' => null,
            'errors' => null
        ], 200);
    }
}