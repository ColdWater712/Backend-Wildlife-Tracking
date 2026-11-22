<?php

namespace App\Http\Controllers\Api;

use App\Models\GpsReading;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Illuminate\Http\Request;

/** Data cuaca berasal dari provider (ingest / sinkronisasi), bukan diedit manual. */
class WeatherDataController extends ApiController
{
    public function index(Request $request)
    {
        $query = WeatherData::query();

        if ($request->filled('reading_id')) {
            $query->where('reading_id', $request->integer('reading_id'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('start_date')) {
            $query->where('observed_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('observed_at', '<=', $request->date('end_date')->endOfDay());
        }
        if ($request->filled(['min_lat', 'max_lat', 'min_lon', 'max_lon'])) {
            $query->whereBetween('latitude', [$request->float('min_lat'), $request->float('max_lat')])
                ->whereBetween('longitude', [$request->float('min_lon'), $request->float('max_lon')]);
        }

        return $this->list($query->orderByDesc('observed_at'));
    }

    public function show(int $id)
    {
        return $this->success(WeatherData::with('gpsReading')->findOrFail($id));
    }

    public function byReading(int $readingId)
    {
        GpsReading::findOrFail($readingId);

        return $this->success(WeatherData::where('reading_id', $readingId)->first());
    }

    /** Ingest satu objek atau {records: [...]} dari job/provider cuaca. */
    public function ingest(Request $request)
    {
        $records = $request->has('records') ? $request->input('records') : [$request->all()];
        $request->merge(['records' => $records]);

        $data = $request->validate([
            'records' => 'required|array|min:1|max:1000',
            'records.*.reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'records.*.latitude' => 'required|numeric|between:-90,90',
            'records.*.longitude' => 'required|numeric|between:-180,180',
            'records.*.observed_at' => 'required|date',
            'records.*.temperature_c' => 'nullable|numeric|between:-50,70',
            'records.*.rainfall_mm' => 'nullable|numeric|min:0',
            'records.*.humidity_percent' => 'nullable|numeric|between:0,100',
            'records.*.pressure_hpa' => 'nullable|numeric|between:300,1100',
            'records.*.source' => 'nullable|string|max:50',
        ]);

        $saved = collect($data['records'])->map(fn ($r) => WeatherData::create($r + ['source' => 'Open-Meteo']));

        return $this->success($saved->values(), $saved->count() . ' data cuaca disimpan', 201);
    }

    /** Ambil cuaca dari Open-Meteo untuk pembacaan GPS yang belum punya data cuaca. */
    public function sync(Request $request, WeatherService $weather)
    {
        $limit = min(50, $request->integer('limit', 20));
        $readings = GpsReading::query()->doesntHave('weatherData')->latest('recorded_at')->limit($limit)->get();

        $saved = 0;
        $errors = [];
        foreach ($readings as $reading) {
            try {
                $weather->fetchForReading($reading);
                $saved++;
            } catch (\Throwable $e) {
                $errors[] = ['reading_id' => $reading->reading_id, 'error' => $e->getMessage()];
            }
        }

        return $this->success(['synced' => $saved, 'failed' => $errors], "{$saved} data cuaca disinkronkan");
    }
}
