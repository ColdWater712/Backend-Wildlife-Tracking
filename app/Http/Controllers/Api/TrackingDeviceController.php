<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingDeviceRequest;
use App\Http\Requests\UpdateTrackingDeviceRequest;
use App\Http\Traits\ApiResponse;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = TrackingDevice::with(['animal.species']);

        if ($request->filled('device_condition') && $request->device_condition !== 'all') {
            $query->where('device_condition', $request->device_condition);
        }

        if ($request->has('is_active') && $request->input('is_active') !== 'all') {
            $val = $request->input('is_active');
            $isActive = in_array(strtolower((string)$val), ['1', 'true', 'aktif', 'active', 'ya', 'yes']);
            $query->where('is_active', $isActive);
        }

        if ($request->filled('battery_below')) {
            $query->where('battery_level', '<', (int) $request->battery_below);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('model', 'like', "%{$s}%")
                ->orWhereHas('animal', fn($aq) => $aq->where('name', 'like', "%{$s}%")));
        }

        $sort = $request->input('orderBy', 'device_id');
        $dir = $request->input('order', 'asc');
        $query->orderBy($sort, $dir);

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        $device = TrackingDevice::with(['animal.species', 'gpsReadings'])->findOrFail($id);
        return $this->success($device);
    }

    public function store(StoreTrackingDeviceRequest $request)
    {
        $device = TrackingDevice::create($request->validated());
        return $this->success($device->load('animal'), 'Perangkat berhasil dibuat', 201);
    }

    public function update(UpdateTrackingDeviceRequest $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update($request->validated());
        return $this->success($device->fresh()->load('animal'), 'Perangkat diperbarui');
    }

    public function assignAnimal(Request $request, $id)
    {
        $request->validate(['animal_id' => 'required|integer|exists:animals,animal_id']);
        $device = TrackingDevice::findOrFail($id);

        $existing = TrackingDevice::where('animal_id', $request->animal_id)->where('device_id', '!=', $id)->first();
        if ($existing) {
            return $this->error('Satwa ini sudah memiliki perangkat lain', 422);
        }

        $device->update(['animal_id' => $request->animal_id]);
        return $this->success($device->fresh()->load('animal'), 'Perangkat dipasangkan ke satwa');
    }

    public function unassignAnimal($id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update(['animal_id' => null]);
        return $this->success($device->fresh(), 'Perangkat dilepas dari satwa');
    }

    public function updateCondition(Request $request, $id)
    {
        $request->validate(['device_condition' => 'required|in:normal,damaged,lost']);
        $device = TrackingDevice::findOrFail($id);
        $device->update(['device_condition' => $request->device_condition]);
        return $this->success($device->fresh(), 'Kondisi perangkat diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $device = TrackingDevice::findOrFail($id);
        $device->update(['is_active' => $request->boolean('is_active')]);
        return $this->success($device->fresh(), 'Status perangkat diperbarui');
    }

    public function latestReading($id)
    {
        $device = TrackingDevice::with(['animal.species', 'latestReading'])->findOrFail($id);
        return $this->success($device->latestReading);
    }

    public function destroy($id)
    {
        TrackingDevice::findOrFail($id)->delete();
        return $this->success(null, 'Perangkat dihapus');
    }
}