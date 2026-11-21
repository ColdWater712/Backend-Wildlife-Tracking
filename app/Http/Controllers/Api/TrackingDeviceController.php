<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\TrackingDevice;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = TrackingDevice::with('animal');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('device_condition')) {
            $query->where('device_condition', $request->device_condition);
        }
        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }
        if ($request->filled('battery_low')) {
            $query->where('battery_level', '<', 20);
        }
        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('model', 'like', "%{$search}%");
        }

        $query->orderBy('device_id', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            TrackingDevice::with(['animal', 'gpsReadings'])->findOrFail($id),
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'model' => 'required|string|max:100',
            'animal_id' => 'nullable|exists:animals,animal_id|unique:tracking_devices,animal_id',
            'installation_date' => 'nullable|date',
            'battery_life_months' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
            'device_condition' => 'in:normal,damaged,lost',
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
            'last_recorded_at' => 'nullable|date',
        ]);

        $data = $request->only([
            'model',
            'animal_id',
            'installation_date',
            'battery_life_months',
            'battery_level',
            'signal_strength',
            'last_recorded_at',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['device_condition'] = $request->input('device_condition', 'normal');

        $device = TrackingDevice::create($data);

        return $this->success($device->load('animal'), 'Device berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate([
            'model' => 'sometimes|string|max:100',
            'animal_id' => 'nullable|exists:animals,animal_id|unique:tracking_devices,animal_id,' . $device->device_id . ',device_id',
            'installation_date' => 'nullable|date',
            'battery_life_months' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
            'device_condition' => 'in:normal,damaged,lost',
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
            'last_recorded_at' => 'nullable|date',
        ]);

        $data = $request->only([
            'model',
            'animal_id',
            'installation_date',
            'battery_life_months',
            'battery_level',
            'signal_strength',
            'last_recorded_at',
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if ($request->has('device_condition')) {
            $data['device_condition'] = $request->device_condition;
        }

        $device->update($data);

        return $this->success($device->fresh('animal'), 'Device diperbarui');
    }

    public function destroy($id)
    {
        TrackingDevice::findOrFail($id)->delete();

        return $this->success(null, 'Device dihapus');
    }

    public function assignAnimal(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate([
            'animal_id' => 'required|exists:animals,animal_id|unique:tracking_devices,animal_id,' . $device->device_id . ',device_id',
        ]);
        $device->update(['animal_id' => $request->animal_id]);

        return $this->success($device->fresh('animal'), 'Animal berhasil diassign ke device');
    }

    public function unassignAnimal($id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update(['animal_id' => null]);

        return $this->success($device->fresh(), 'Animal di-unassign dari device');
    }

    public function updateCondition(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate(['device_condition' => 'required|in:normal,damaged,lost']);
        $device->update(['device_condition' => $request->device_condition]);

        return $this->success($device->fresh(), 'Kondisi device diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate(['is_active' => 'required|boolean']);
        $device->update(['is_active' => $request->boolean('is_active')]);

        return $this->success($device->fresh(), 'Status device diperbarui');
    }

    public function latestReading($id)
    {
        $device = TrackingDevice::findOrFail($id);
        $reading = $device->gpsReadings()->latest('recorded_at')->first();

        return $this->success($reading);
    }

    public function batteryHistory($id)
    {
        $device = TrackingDevice::findOrFail($id);

        if ($device->battery_level === null && $device->last_recorded_at === null) {
            return $this->list([]);
        }

        return $this->list([[
            'recorded_at' => $device->last_recorded_at,
            'battery_level' => $device->battery_level,
        ]]);
    }
}
