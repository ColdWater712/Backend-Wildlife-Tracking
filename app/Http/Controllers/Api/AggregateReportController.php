<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAggregateReportRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AggregateReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AggregateReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = AggregateReport::with('generator');

        if ($request->filled('report_type')) $query->where('report_type', $request->report_type);
        if ($request->filled('period_start')) $query->where('period_start', '>=', $request->period_start);
        if ($request->filled('period_end'))   $query->where('period_end', '<=', $request->period_end);

        $query->orderBy('generated_at', 'desc');

        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(AggregateReport::with('generator')->findOrFail($id));
    }

    public function store(StoreAggregateReportRequest $request)
    {
        $validated = $request->validated();
        $validated['generated_by'] = $request->user()->user_id;
        $report = AggregateReport::create($validated);
        return $this->success($report->load('generator'), 'Laporan berhasil dibuat', 201);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:daily,weekly,monthly',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $fileName = "reports/{$request->report_type}_{$request->period_start}_{$request->period_end}.pdf";

        $report = AggregateReport::create([
            'generated_by' => $request->user()->user_id,
            'report_type'  => $request->report_type,
            'period_start' => $request->period_start,
            'period_end'   => $request->period_end,
            'file_path'    => $fileName,
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'GENERATE_REPORT',
            'details' => json_encode(['report_id' => $report->report_id]), 'timestamp' => now(),
        ]);

        return $this->success($report->load('generator'), 'Laporan berhasil digenerate', 201);
    }

    public function update(Request $request, $id)
    {
        $report = AggregateReport::findOrFail($id);
        $request->validate([
            'report_type' => 'sometimes|in:daily,weekly,monthly',
            'period_start' => 'sometimes|date',
            'period_end' => 'sometimes|date|after_or_equal:period_start',
            'file_path' => 'sometimes|nullable|string|max:500',
        ]);
        $report->update($request->validated());
        return $this->success($report->fresh(), 'Laporan diperbarui');
    }

    public function download($id)
    {
        $report = AggregateReport::findOrFail($id);
        if (!$report->file_path || !Storage::disk('public')->exists($report->file_path)) {
            return $this->error('File laporan tidak ditemukan', 404);
        }
        return Storage::disk('public')->download($report->file_path);
    }

    public function destroy($id)
    {
        $report = AggregateReport::findOrFail($id);
        if ($report->file_path && Storage::disk('public')->exists($report->file_path)) {
            Storage::disk('public')->delete($report->file_path);
        }
        $report->delete();
        return $this->success(null, 'Laporan dihapus');
    }
}