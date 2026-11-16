<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentReport::with('reporter');

        if ($request->user()->role !== 'administrator_sistem') {
            $query->where('user_id', $request->user()->user_id);
        }

        $reports = $query->orderBy('timestamp', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ], 200);
    }

    public function store(StoreIncidentReportRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->user_id;
        $validated['timestamp'] = now();
        $validated['sync_status'] = 'pending_sync';

        $report = IncidentReport::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Laporan insiden berhasil dibuat',
            'data' => $report,
        ], 201);
    }

    public function show($id)
    {
        $report = IncidentReport::with('reporter')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus laporan ini.',
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dihapus',
        ], 200);
    }
}