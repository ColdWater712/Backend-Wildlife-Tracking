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
        $query = TrackingDevice::with(['animal.species', 'latestReading']);

        if ($request->filled('device_condition') && $request->device_condition !== 'all') {
            $query->where('device_condition', $request->device_condition);
        }
        if ($request->has('is_active') && $request->input('is_active') !== 'all') {
            $val = $request->input('is_active');
            $query->where('is_active', in_array(strtolower((string)$val), ['1','true','aktif','active','ya','yes']));
        }
        if ($request->filled('battery_below')) $query->where('battery_level', '<', (int) $request->battery_below);
        if ($request->filled('animal_id'))     $query->where('animal_id', $request->animal_id);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('model', 'like', "%{$s}%")
                ->orWhereHas('animal', fn($aq) => $aq->where('name', 'like', "%{$s}%")));
        }

        $query->orderBy($request->input('orderBy', 'device_id'), $request->input('order', 'asc'));

        // ✅ FE tracking-device.service pakai unwrapData → array
        return $this->list($query->get());
    }

    public function show($id)
    {
        return $this->success(TrackingDevice::with(['animal.species', 'gpsReadings'])->findOrFail($id));
    }

    public function store(StoreTrackingDeviceRequest $request)
    {
        $device = TrackingDevice::create($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'CREATE_DEVICE',
            'details' => json_encode(['device_id' => $device->device_id]), 'timestamp' => now(),
        ]);

        return $this->success($device->load('animal'), 'Perangkat berhasil dibuat', 201);
    }

    public function update(UpdateTrackingDeviceRequest $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update($request->validated());

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_DEVICE',
            'details' => json_encode(['device_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success($device->fresh()->load('animal'), 'Perangkat diperbarui');
    }

    public function assignAnimal(Request $request, $id)
    {
        $request->validate(['animal_id' => 'required|integer|exists:animals,animal_id']);
        $device = TrackingDevice::findOrFail($id);

        $existing = TrackingDevice::where('animal_id', $request->animal_id)->where('device_id', '!=', $id)->first();
        if ($existing) return $this->error('Satwa ini sudah memiliki perangkat lain', 422);

        $device->update(['animal_id' => $request->animal_id]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'ASSIGN_DEVICE',
            'details' => json_encode(['device_id' => $id, 'animal_id' => $request->animal_id]), 'timestamp' => now(),
        ]);

        return $this->success($device->fresh()->load('animal'), 'Perangkat dipasangkan ke satwa');
    }

    public function unassignAnimal($id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update(['animal_id' => null]);

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'UNASSIGN_DEVICE',
            'details' => json_encode(['device_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success($device->fresh(), 'Perangkat dilepas dari satwa');
    }

    public function updateCondition(Request $request, $id)
    {
        $request->validate(['device_condition' => 'required|in:normal,damaged,lost']);
        $device = TrackingDevice::findOrFail($id);
        $device->update(['device_condition' => $request->device_condition]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_DEVICE_CONDITION',
            'details' => json_encode(['device_id' => $id, 'condition' => $request->device_condition]), 'timestamp' => now(),
        ]);

        return $this->success($device->fresh(), 'Kondisi perangkat diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);
        $device = TrackingDevice::findOrFail($id);
        $device->update(['is_active' => $request->boolean('is_active')]);

        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id, 'action' => 'UPDATE_DEVICE_STATUS',
            'details' => json_encode(['device_id' => $id, 'is_active' => $device->is_active]), 'timestamp' => now(),
        ]);

        return $this->success($device->fresh(), 'Status perangkat diperbarui');
    }

    public function latestReading($id)
    {
        $device = TrackingDevice::with('latestReading')->findOrFail($id);
        return $this->success($device->latestReading);
    }

    public function destroy($id)
    {
        TrackingDevice::findOrFail($id)->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'DELETE_DEVICE',
            'details' => json_encode(['device_id' => $id]), 'timestamp' => now(),
        ]);

        return $this->success(null, 'Perangkat dihapus');
    }
}