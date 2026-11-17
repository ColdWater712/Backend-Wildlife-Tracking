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

        if ($request->filled('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }
        if ($request->filled('sync_status')) {
            $query->where('sync_status', $request->sync_status);
        }
        if ($request->filled('animal_code')) {
            $query->where('animal_code', 'like', '%' . $request->animal_code . '%');
        }
        if ($request->filled('start_date')) {
            $query->where('timestamp', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('timestamp', '<=', $request->end_date);
        }

        $query->orderBy('timestamp', 'desc');

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        $report = IncidentReport::with(['reporter', 'media'])->findOrFail($id);
        return $this->success($report);
    }

    public function store(StoreIncidentReportRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->user_id;
        $validated['timestamp'] = now();
        $validated['sync_status'] = $validated['sync_status'] ?? 'pending_sync';

        $report = IncidentReport::create($validated);
        return $this->success($report->load('reporter'), 'Laporan insiden berhasil dibuat', 201);
    }

    public function update(UpdateIncidentReportRequest $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }

        $report->update($request->validated());
        return $this->success($report->fresh(), 'Laporan diperbarui');
    }

    public function updateSyncStatus(Request $request, $id)
    {
        $request->validate(['sync_status' => 'required|in:pending_sync,synced,failed']);
        $report = IncidentReport::findOrFail($id);
        $report->update(['sync_status' => $request->sync_status]);
        return $this->success($report->fresh(), 'Status sinkronisasi diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }

        $report->delete();
        return $this->success(null, 'Laporan dihapus (soft delete)');
    }

    public function media($reportId)
    {
        $report = IncidentReport::findOrFail($reportId);
        return $this->success($report->media);
    }
}