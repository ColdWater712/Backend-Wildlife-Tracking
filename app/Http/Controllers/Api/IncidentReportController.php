<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Http\Requests\UpdateIncidentReportRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AiMediaUpload;
use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(IncidentReport::with(['reporter', 'media'])->findOrFail($id));
    }

    public function store(StoreIncidentReportRequest $request)
    {
        // ✅ G-2: transaction untuk create + audit log
        $report = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $validated['user_id'] = $request->user()->user_id;
            $validated['timestamp'] = now();
            $validated['sync_status'] = $validated['sync_status'] ?? 'pending_sync';

            $report = IncidentReport::create($validated);

            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => 'CREATE_INCIDENT',
                'details' => json_encode(['report_id' => $report->report_id]),
                'timestamp' => now(),
            ]);

            return $report;
        });

        return $this->success($report->load('reporter'), 'Laporan insiden berhasil dibuat', 201);
    }

    public function update(UpdateIncidentReportRequest $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }

        DB::transaction(function () use ($report, $request) {
            $report->update($request->validated());
            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => 'UPDATE_INCIDENT',
                'details' => json_encode(['report_id' => $report->report_id]),
                'timestamp' => now(),
            ]);
        });

        return $this->success($report->fresh(), 'Laporan diperbarui');
    }

    public function updateSyncStatus(Request $request, $id)
    {
        $request->validate(['sync_status' => 'required|in:pending_sync,synced,failed']);
        $report = IncidentReport::findOrFail($id);
        $report->update(['sync_status' => $request->sync_status]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id,
            'action' => 'UPDATE_INCIDENT_SYNC',
            'details' => json_encode(['report_id' => $id, 'sync_status' => $request->sync_status]),
            'timestamp' => now(),
        ]);

        return $this->success($report->fresh(), 'Status sinkronisasi diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $report = IncidentReport::findOrFail($id);

        if ($report->user_id !== $request->user()->user_id && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses', 403);
        }

        DB::transaction(function () use ($report, $request) {
            $report->delete(); // soft delete
            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => 'DELETE_INCIDENT',
                'details' => json_encode(['report_id' => $report->report_id]),
                'timestamp' => now(),
            ]);
        });

        return $this->success(null, 'Laporan dihapus (soft delete)');
    }

    public function media($reportId)
    {
        $report = IncidentReport::findOrFail($reportId);
        return $this->list($report->media);
    }

    /**
     * ✅ G-1: POST /incidents/{reportId}/media — upload media terkait laporan
     */
    public function uploadMedia(Request $request, $reportId)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,mp4,mov',
            'media_type' => 'nullable|in:image,video',
        ]);

        $report = IncidentReport::findOrFail($reportId);

        if ($report->user_id !== $request->user()->user_id
            && $request->user()->role !== 'administrator_sistem') {
            return $this->error('Tidak memiliki akses ke laporan ini', 403);
        }

        $file = $request->file('file');
        $path = $file->store('ai-uploads', 'public');

        $ext = strtolower($file->getClientOriginalExtension());
        $mediaType = $request->input('media_type')
            ?? (in_array($ext, ['mp4', 'mov']) ? 'video' : 'image');

        $media = DB::transaction(function () use ($report, $path, $mediaType, $request) {
            $media = AiMediaUpload::create([
                'uploaded_by' => $request->user()->user_id,
                'report_id' => $report->report_id,
                'media_type' => $mediaType,
                'file_path' => $path,
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->user_id,
                'action' => 'UPLOAD_INCIDENT_MEDIA',
                'details' => json_encode(['report_id' => $report->report_id, 'media_id' => $media->media_id]),
                'timestamp' => now(),
            ]);

            return $media;
        });

        return $this->success($media, 'Media berhasil diunggah ke laporan', 201);
    }
}