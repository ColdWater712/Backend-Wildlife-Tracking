<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Http\Requests\UpdateIncidentReportRequest;
use App\Http\Traits\ApiResponse;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

class IncidentReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = IncidentReport::with('reporter');

        if ($request->user()->role !== 'administrator_sistem') {
            $query->where('user_id', $request->user()->user_id);
        }
        if ($request->filled('incident_type')) $query->where('incident_type', $request->incident_type);
        if ($request->filled('sync_status'))   $query->where('sync_status', $request->sync_status);
        if ($request->filled('animal_code'))   $query->where('animal_code', 'like', '%' . $request->animal_code . '%');
        if ($request->filled('start_date'))    $query->where('timestamp', '>=', $request->start_date);
        if ($request->filled('end_date'))      $query->where('timestamp', '<=', $request->end_date);

        $query->orderBy('timestamp', 'desc');

        // ✅ FE incident-report.service pakai unwrapData → array
        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(IncidentReport::with(['reporter', 'media'])->findOrFail($id));
    }

    public function store(StoreIncidentReportRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id']     = $request->user()->user_id;
        $validated['timestamp']   = now();
        $validated['sync_status'] = $validated['sync_status'] ?? 'pending_sync';

        $report = IncidentReport::create($validated);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'CREATE_INCIDENT',
            'details' => json_encode(['report_id' => $report->report_id]), 'timestamp' => now(),
        ]);

        return $this->success($report->load('reporter'), 'Laporan insiden berhasil dibuat', 201);
    }

    public function update(UpdateIncidentReportRequest $request, $id)
    {
        $report = IncidentReport::findOrFail($id);
        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }
        $report->update($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_INCIDENT',
            'details' => json_encode(['report_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success($report->fresh(), 'Laporan diperbarui');
    }

    public function updateSyncStatus(Request $request, $id)
    {
        $request->validate(['sync_status' => 'required|in:pending_sync,synced,failed']);
        $report = IncidentReport::findOrFail($id);
        $report->update(['sync_status' => $request->sync_status]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_INCIDENT_SYNC',
            'details' => json_encode(['report_id' => $id, 'sync_status' => $request->sync_status]), 'timestamp' => now(),
        ]);

        return $this->success($report->fresh(), 'Status sinkronisasi diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);
        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }
        $report->delete();

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'DELETE_INCIDENT',
            'details' => json_encode(['report_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success(null, 'Laporan dihapus (soft delete)');
    }

    public function media($reportId)
    {
        return $this->list(IncidentReport::findOrFail($reportId)->media);
    }
}