<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeatherDataRequest;
use App\Http\Traits\ApiResponse;
use App\Models\WeatherData;
use Illuminate\Http\Request;

class WeatherDataController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = WeatherData::with('gpsReading');

        if ($request->filled('reading_id')) $query->where('reading_id', $request->reading_id);
        if ($request->filled('source'))     $query->where('source', $request->source);
        if ($request->filled('start_date')) $query->where('observed_at', '>=', $request->start_date);
        if ($request->filled('end_date'))   $query->where('observed_at', '<=', $request->end_date);

        $query->orderBy('observed_at', 'desc');

        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(WeatherData::with('gpsReading')->findOrFail($id));
    }

    public function store(StoreWeatherDataRequest $request)
    {
        $weather = WeatherData::create($request->validated());
        return $this->success($weather, 'Data cuaca berhasil disimpan', 201);
    }

    public function ingest(StoreWeatherDataRequest $request)
    {
        return $this->store($request);
    }

    public function update(Request $request, $id)
    {
        $weather = WeatherData::findOrFail($id);
        $request->validate([
            'temperature_c' => 'sometimes|nullable|numeric|between:-50,60',
            'rainfall_mm' => 'sometimes|nullable|numeric|min:0',
            'humidity_percent' => 'sometimes|nullable|numeric|between:0,100',
            'pressure_hpa' => 'sometimes|nullable|numeric|between:800,1100',
            'source' => 'sometimes|string|max:50',
        ]);
        $weather->update($request->validated());
        return $this->success($weather->fresh(), 'Data cuaca diperbarui');
    }

    public function destroy($id)
    {
        WeatherData::findOrFail($id)->delete();
        return $this->success(null, 'Data cuaca dihapus');
    }

    public function byReading($readingId)
    {
        $weather = WeatherData::where('reading_id', $readingId)->first();
        if (!$weather) return $this->error('Data cuaca tidak ditemukan', 404);
        return $this->success($weather);
    }
}