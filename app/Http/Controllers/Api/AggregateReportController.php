<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AggregateReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AggregateReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = AggregateReport::with(['generatedBy']);

        if ($request->filled('report_type')) $query->where('report_type', $request->report_type);
        if ($request->filled('start_date')) $query->where('period_start', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('period_end', '<=', $request->end_date);

        $query->orderBy('generated_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            AggregateReport::with(['generatedBy'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string',
            'content'     => 'required|string',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'report_type' => 'sometimes|string',
        ]);

        $report = AggregateReport::create([
            'title'        => $request->title,
            'content'      => $request->content,
            'period_start' => $request->period_start,
            'period_end'   => $request->period_end,
            'report_type'  => $request->input('report_type', 'manual'),
            'generated_by' => $request->user()->user_id,
            'generated_at' => now(),
        ]);

        return $this->success($report->load(['generatedBy']), 'Report berhasil dibuat', 201);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'report_type'  => 'sometimes|string',
        ]);

        // Generate report content dari data
        $content = $this->generateReportContent($request->period_start, $request->period_end);

        $report = AggregateReport::create([
            'title'        => "Laporan {$request->report_type} - {$request->period_start} s/d {$request->period_end}",
            'content'      => $content,
            'period_start' => $request->period_start,
            'period_end'   => $request->period_end,
            'report_type'  => $request->input('report_type', 'auto'),
            'generated_by' => $request->user()->user_id,
            'generated_at' => now(),
        ]);

        return $this->success($report->load(['generatedBy']), 'Report berhasil digenerate', 201);
    }

    public function update(Request $request, $id)
    {
        $report = AggregateReport::findOrFail($id);
        $request->validate([
            'title'   => 'sometimes|string',
            'content' => 'sometimes|string',
        ]);

        $report->update($request->only(['title', 'content']));
        return $this->success($report->fresh(['generatedBy']), 'Report diperbarui');
    }

    public function destroy($id)
    {
        AggregateReport::findOrFail($id)->delete();
        return $this->success(null, 'Report dihapus');
    }

    public function download($id)
    {
        $report = AggregateReport::findOrFail($id);
        $content = "Title: {$report->title}\nPeriod: {$report->period_start} to {$report->period_end}\n\n{$report->content}";
        
        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="report_{$id}.txt"');
    }

    protected function generateReportContent(string $start, string $end): string
    {
        $animals = \App\Models\Animal::whereBetween('created_at', [$start, $end])->count();
        $incidents = \App\Models\IncidentReport::whereBetween('timestamp', [$start, $end])->count();
        $readings = \App\Models\GpsReading::whereBetween('recorded_at', [$start, $end])->count();

        return "Laporan Periode {$start} - {$end}\n\n" .
               "Total Animals: {$animals}\n" .
               "Total Incidents: {$incidents}\n" .
               "Total GPS Readings: {$readings}\n";
    }
}