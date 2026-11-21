<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\TrackingDevice;
use App\Models\GpsReading;
use Illuminate\Http\Request;

class TrackingDeviceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = TrackingDevice::with(['animal', 'assignedBy']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('model')) $query->where('model', $request->model);
        if ($request->filled('battery_low')) $query->where('battery_level', '<', 20);
        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('serial_number', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            TrackingDevice::with(['animal', 'assignedBy', 'gpsReadings'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string|unique:tracking_devices,serial_number',
            'model'         => 'required|string',
            'status'        => 'sometimes|string',
            'battery_level' => 'sometimes|numeric|between:0,100',
        ]);

        $device = TrackingDevice::create([
            'serial_number' => $request->serial_number,
            'model'         => $request->model,
            'status'        => $request->input('status', 'active'),
            'battery_level' => $request->input('battery_level', 100),
            'assigned_by'   => $request->user()->user_id,
        ]);

        return $this->success($device->load(['animal', 'assignedBy']), 'Device berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate([
            'serial_number' => 'sometimes|string|unique:tracking_devices,serial_number,' . $device->device_id . ',device_id',
            'model'         => 'sometimes|string',
            'status'        => 'sometimes|string',
            'battery_level' => 'sometimes|numeric|between:0,100',
        ]);

        $device->update($request->only(['serial_number', 'model', 'status', 'battery_level']));
        return $this->success($device->fresh(['animal', 'assignedBy']), 'Device diperbarui');
    }

    public function destroy($id)
    {
        TrackingDevice::findOrFail($id)->delete();
        return $this->success(null, 'Device dihapus');
    }

    public function assignAnimal(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate(['animal_id' => 'required|exists:animals,animal_id']);
        $device->update(['animal_id' => $request->animal_id, 'assigned_at' => now()]);
        return $this->success($device->fresh(['animal']), 'Animal berhasil diassign ke device');
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
        $request->validate(['condition' => 'required|string']);
        $device->update(['condition' => $request->condition]);
        return $this->success($device->fresh(), 'Kondisi device diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate(['status' => 'required|in:active,inactive,maintenance,lost']);
        $device->update(['status' => $request->status]);
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
        $readings = $device->gpsReadings()
            ->whereNotNull('battery_level')
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get(['recorded_at', 'battery_level']);
        return $this->list($readings);
    }
}