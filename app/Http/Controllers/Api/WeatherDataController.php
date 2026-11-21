<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\WeatherData;
use Illuminate\Http\Request;

class WeatherDataController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = WeatherData::orderBy('observed_at', 'desc');

        if ($request->filled('start_date')) $query->where('observed_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('observed_at', '<=', $request->end_date);
        if ($request->filled('min_temp')) $query->where('temperature_c', '>=', $request->min_temp);
        if ($request->filled('max_temp')) $query->where('temperature_c', '<=', $request->max_temp);

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(WeatherData::findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'observed_at'      => 'required|date',
            'temperature_c'    => 'required|numeric',
            'humidity_percent' => 'required|numeric|between:0,100',
            'rainfall_mm'      => 'nullable|numeric',
            'wind_speed'       => 'nullable|numeric',
            'pressure'         => 'nullable|numeric',
        ]);

        $weather = WeatherData::create($request->only([
            'observed_at', 'temperature_c', 'humidity_percent',
            'rainfall_mm', 'wind_speed', 'pressure'
        ]));

        return $this->success($weather, 'Weather data berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $weather = WeatherData::findOrFail($id);
        $request->validate([
            'temperature_c'    => 'sometimes|numeric',
            'humidity_percent' => 'sometimes|numeric|between:0,100',
            'rainfall_mm'      => 'nullable|numeric',
            'wind_speed'       => 'nullable|numeric',
            'pressure'         => 'nullable|numeric',
        ]);

        $weather->update($request->only([
            'temperature_c', 'humidity_percent', 'rainfall_mm', 'wind_speed', 'pressure'
        ]));

        return $this->success($weather->fresh(), 'Weather data diperbarui');
    }

    public function destroy($id)
    {
        WeatherData::findOrFail($id)->delete();
        return $this->success(null, 'Weather data dihapus');
    }

    public function ingest(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.observed_at' => 'required|date',
            'data.*.temperature_c' => 'required|numeric',
            'data.*.humidity_percent' => 'required|numeric',
        ]);

        $created = [];
        foreach ($request->data as $item) {
            $created[] = WeatherData::create($item);
        }

        return $this->success($created, count($created) . ' weather records diingest', 201);
    }

    public function byReading($readingId)
    {
        $reading = \App\Models\GpsReading::findOrFail($readingId);
        $weather = WeatherData::where('observed_at', '<=', $reading->recorded_at)
            ->orderBy('observed_at', 'desc')
            ->first();
        return $this->success($weather);
    }
}