<?php

namespace App\Http\Controllers\Api;

use App\Models\AggregateReport;
use App\Services\MonitoringStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Laporan agregat dihasilkan sistem (generate) — tidak diedit manual. */
class AggregateReportController extends ApiController
{
    private const DISK = 'local';

    public function index(Request $request)
    {
        $query = AggregateReport::query()->with('generator');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->report_type);
        }
        if ($request->filled('period_start') || $request->filled('start_date')) {
            $query->where('period_start', '>=', $request->input('period_start', $request->input('start_date')));
        }
        if ($request->filled('period_end') || $request->filled('end_date')) {
            $query->where('period_end', '<=', $request->input('period_end', $request->input('end_date')));
        }

        return $this->list($query->orderByDesc('generated_at'));
    }

    public function show(int $id)
    {
        return $this->success(AggregateReport::with('generator')->findOrFail($id));
    }

    public function generate(Request $request, MonitoringStatsService $stats)
    {
        $data = $request->validate([
            'report_type' => 'required|in:daily,weekly,monthly',
            'period_start' => 'nullable|date|before_or_equal:today',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'overwrite' => 'sometimes|boolean',
        ]);

        [$start, $end] = $this->period($data['report_type'], $data['period_start'] ?? null, $data['period_end'] ?? null);

        $existing = AggregateReport::query()
            ->where('report_type', $data['report_type'])
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->first();

        if ($existing && ! $request->boolean('overwrite')) {
            return $this->error('Laporan untuk periode ini sudah ada. Kirim overwrite=true untuk membuat ulang.', 409, ['report_id' => [$existing->report_id]]);
        }

        $reportData = $stats->collect($start, $end);
        $path = "reports/{$data['report_type']}_{$start}_{$end}.csv";
        Storage::disk(self::DISK)->put($path, $this->toCsv($data['report_type'], $start, $end, $reportData));

        $report = AggregateReport::updateOrCreate(
            ['report_type' => $data['report_type'], 'period_start' => $start, 'period_end' => $end],
            ['generated_by' => $request->user()->user_id, 'report_data' => $reportData, 'generated_at' => now(), 'file_path' => $path],
        );

        return $this->success($report->load('generator'), 'Laporan berhasil dibuat', 201);
    }

    public function download(int $id)
    {
        $report = AggregateReport::findOrFail($id);
        $disk = Storage::disk(self::DISK);

        if (! $report->file_path || ! $disk->exists($report->file_path)) {
            return $this->error('Berkas laporan tidak ditemukan. Silakan generate ulang.', 404);
        }

        return $disk->download($report->file_path, basename($report->file_path), ['Content-Type' => 'text/csv']);
    }

    public function destroy(int $id)
    {
        $report = AggregateReport::findOrFail($id);

        if ($report->file_path) {
            Storage::disk(self::DISK)->delete($report->file_path);
        }
        $report->delete();

        return $this->success(null, 'Laporan dihapus');
    }

    /** Periode default: kemarin (daily), 7 hari terakhir (weekly), bulan lalu (monthly). */
    private function period(string $type, ?string $start, ?string $end): array
    {
        if ($start) {
            $from = Carbon::parse($start);
            $to = $end ? Carbon::parse($end) : match ($type) {
                'daily' => $from->copy(),
                'weekly' => $from->copy()->addDays(6),
                'monthly' => $from->copy()->endOfMonth(),
            };

            return [$from->toDateString(), $to->toDateString()];
        }

        return match ($type) {
            'daily' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'weekly' => [now()->subDays(7)->toDateString(), now()->subDay()->toDateString()],
            'monthly' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        };
    }

    private function toCsv(string $type, string $start, string $end, array $data): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Laporan Agregat Wildlife Tracking']);
        fputcsv($handle, ['Tipe', $type]);
        fputcsv($handle, ['Periode', "{$start} s/d {$end}"]);
        fputcsv($handle, ['Dibuat', now()->toDateTimeString()]);
        fputcsv($handle, []);
        fputcsv($handle, ['Statistik', 'Nilai']);
        foreach ($data['statistics'] as $key => $value) {
            fputcsv($handle, [$key, $value]);
        }
        fputcsv($handle, []);
        fputcsv($handle, ['Spesies', 'Nama Ilmiah', 'Jumlah Individu', 'Status Konservasi']);
        foreach ($data['species_breakdown'] as $row) {
            fputcsv($handle, [$row['common_name'], $row['scientific_name'], $row['count'], $row['status']]);
        }
        fputcsv($handle, []);
        fputcsv($handle, ['Jenis Insiden', 'Jumlah']);
        foreach ($data['incidents_by_type'] as $typeName => $count) {
            fputcsv($handle, [$typeName, $count]);
        }
        foreach ($data['ai_insights'] as $section => $rows) {
            fputcsv($handle, []);
            fputcsv($handle, ["AI: {$section}", 'Jumlah']);
            foreach ($rows as $key => $count) {
                fputcsv($handle, [$key, $count]);
            }
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF" . $csv;
    }
}
