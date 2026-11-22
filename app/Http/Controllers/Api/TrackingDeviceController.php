<?php

namespace App\Http\Controllers\Api;

use App\Models\AiMediaUpload;
use App\Models\GpsReading;
use App\Models\TrackingDevice;
use App\Services\AiPredictionService;
use App\Services\DeviceHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Tiga jenis perangkat: gps (kalung pada satwa), camera (kamera jebak), audio (perekam suara).
 * Kamera & audio dipasang di titik tetap (latitude/longitude/zone_id).
 */
class TrackingDeviceController extends ApiController
{
    public function __construct(private DeviceHealthService $health) {}

    public function index(Request $request)
    {
        $query = TrackingDevice::query()->with(['animal', 'zone']);

        if ($request->filled('device_type')) {
            $query->whereIn('device_type', explode(',', $request->device_type));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('device_condition')) {
            $query->where('device_condition', $request->device_condition);
        }
        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->integer('animal_id'));
        }
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->integer('zone_id'));
        }
        if ($request->boolean('battery_low')) {
            $query->where('battery_level', '<', DeviceHealthService::LOW_BATTERY);
        }
        if ($request->filled('max_battery')) {
            $query->where('battery_level', '<=', $request->integer('max_battery'));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('model', 'like', "%{$s}%")->orWhere('serial_number', 'like', "%{$s}%"));
        }

        $this->applySort($query, $request, ['device_id', 'model', 'battery_level', 'last_recorded_at', 'installation_date'], 'device_id', 'asc');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(TrackingDevice::with(['animal.species', 'zone', 'latestReading'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data += ['is_active' => true, 'device_condition' => 'normal', 'device_type' => 'gps'];

        $device = TrackingDevice::create($data);
        $this->health->evaluate($device);

        return $this->success($device->load(['animal', 'zone']), 'Perangkat berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update($this->validated($request, $device));
        $this->health->evaluate($device);

        return $this->success($device->fresh(['animal', 'zone']), 'Perangkat diperbarui');
    }

    public function destroy(int $id)
    {
        $device = TrackingDevice::withCount(['gpsReadings', 'mediaUploads'])->findOrFail($id);

        if ($device->gps_readings_count > 0 || $device->media_uploads_count > 0) {
            return $this->error(
                'Perangkat memiliki riwayat data (GPS/media) sehingga tidak dapat dihapus. Nonaktifkan perangkat sebagai gantinya.',
                409,
            );
        }

        $device->delete();

        return $this->success(null, 'Perangkat dihapus');
    }

    public function assignAnimal(Request $request, int $id)
    {
        $device = TrackingDevice::findOrFail($id);

        if ($device->device_type !== TrackingDevice::TYPE_GPS) {
            return $this->error('Hanya perangkat GPS (kalung) yang dapat dipasangkan ke satwa.', 422);
        }

        $data = $request->validate([
            'animal_id' => ['required', 'integer', Rule::exists('animals', 'animal_id')->whereNull('deleted_at')],
            'force' => 'sometimes|boolean',
        ]);

        $current = TrackingDevice::where('animal_id', $data['animal_id'])->where('device_id', '!=', $device->device_id)->first();
        if ($current && ! $request->boolean('force')) {
            return $this->error("Satwa sudah terpasang perangkat #{$current->device_id}. Kirim force=true untuk memindahkan.", 409);
        }

        DB::transaction(function () use ($device, $current, $data) {
            $current?->update(['animal_id' => null]);
            $device->update(['animal_id' => $data['animal_id'], 'installation_date' => $device->installation_date ?? now()->toDateString()]);
        });

        return $this->success($device->fresh(['animal', 'zone']), 'Perangkat dipasangkan ke satwa');
    }

    public function unassignAnimal(int $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update(['animal_id' => null]);

        return $this->success($device->fresh(['animal', 'zone']), 'Perangkat dilepas dari satwa');
    }

    public function updateCondition(Request $request, int $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $device->update($request->validate(['device_condition' => 'required|in:normal,damaged,lost']));
        $this->health->evaluate($device);

        return $this->success($device->fresh(['animal', 'zone']), 'Kondisi perangkat diperbarui');
    }

    public function updateStatus(Request $request, int $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $request->validate(['is_active' => 'required|boolean']);
        $device->update(['is_active' => $request->boolean('is_active')]);

        return $this->success($device->fresh(['animal', 'zone']), 'Status perangkat diperbarui');
    }

    /** Heartbeat dari perangkat: baterai & sinyal. */
    public function telemetry(Request $request, int $id)
    {
        $device = TrackingDevice::findOrFail($id);
        $data = $request->validate([
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
            'recorded_at' => 'nullable|date',
        ]);

        $device->update([
            'battery_level' => $data['battery_level'] ?? $device->battery_level,
            'signal_strength' => $data['signal_strength'] ?? $device->signal_strength,
            'last_recorded_at' => $data['recorded_at'] ?? now(),
        ]);
        $this->health->evaluate($device);

        return $this->success($device->fresh(), 'Telemetri perangkat diperbarui');
    }

    public function latestReading(int $id)
    {
        $device = TrackingDevice::findOrFail($id);

        return $this->success($device->gpsReadings()->with('weatherData')->latest('recorded_at')->first());
    }

    public function gpsReadings(Request $request, int $id)
    {
        TrackingDevice::findOrFail($id);
        $query = GpsReading::query()->with('device.animal')->where('device_id', $id);

        if ($request->filled('start_date')) {
            $query->where('recorded_at', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('recorded_at', '<=', $request->date('end_date')->endOfDay());
        }

        return $this->list($query->orderByDesc('recorded_at')->limit(min(5000, $request->integer('limit', 1000))));
    }

    /** Riwayat level baterai terakhir (perangkat hanya menyimpan nilai terkini). */
    public function batteryHistory(int $id)
    {
        $device = TrackingDevice::findOrFail($id);

        return $this->list($device->battery_level === null ? [] : [[
            'recorded_at' => $device->last_recorded_at,
            'battery_level' => $device->battery_level,
            'signal_strength' => $device->signal_strength,
        ]]);
    }

    /** Media yang direkam oleh kamera / perekam audio ini beserta hasil AI-nya. */
    public function captures(int $id)
    {
        TrackingDevice::findOrFail($id);

        return $this->list(AiMediaUpload::query()
            ->with(['predictions.predictedSpecies', 'predictions.predictedAnimal', 'zone'])
            ->where('device_id', $id)
            ->orderByDesc('captured_at'));
    }

    /**
     * Endpoint ingest untuk kamera jebak / perekam audio: unggah rekaman,
     * lalu langsung dianalisis AI dengan konteks lokasi perangkat.
     */
    public function uploadCapture(Request $request, AiPredictionService $ai, int $id)
    {
        $device = TrackingDevice::findOrFail($id);

        if (! in_array($device->device_type, [TrackingDevice::TYPE_CAMERA, TrackingDevice::TYPE_AUDIO], true)) {
            return $this->error('Hanya perangkat kamera atau audio yang dapat mengirim rekaman.', 422);
        }

        $mimes = $device->device_type === TrackingDevice::TYPE_AUDIO
            ? 'mimes:wav,mp3,m4a,ogg,oga,flac,webm,aac|max:25600'
            : 'mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm|max:51200';

        $data = $request->validate([
            'file' => "required|file|{$mimes}",
            'captured_at' => 'nullable|date|before_or_equal:now',
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
        ]);

        $media = $ai->storeMedia($request->file('file'), [
            'uploaded_by' => $request->user()->user_id,
            'device_id' => $device->device_id,
            'captured_at' => $data['captured_at'] ?? now(),
        ]);

        $device->update(array_filter([
            'last_recorded_at' => $media->captured_at,
            'battery_level' => $data['battery_level'] ?? null,
            'signal_strength' => $data['signal_strength'] ?? null,
        ], fn ($v) => $v !== null));

        $prediction = $ai->process($media);

        return $this->success([
            'media' => $media->fresh(['device', 'zone', 'report']),
            'prediction' => $prediction,
        ], 'Rekaman diterima dan dianalisis AI', 201);
    }

    private function validated(Request $request, ?TrackingDevice $device = null): array
    {
        $type = $request->input('device_type', $device?->device_type ?? TrackingDevice::TYPE_GPS);

        $data = $request->validate([
            'device_type' => ['sometimes', Rule::in(TrackingDevice::TYPES)],
            'model' => ($device ? 'sometimes' : 'required') . '|string|max:100',
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('tracking_devices', 'serial_number')->ignore($device?->device_id, 'device_id')],
            'animal_id' => ['nullable', 'integer',
                Rule::exists('animals', 'animal_id')->whereNull('deleted_at'),
                Rule::unique('tracking_devices', 'animal_id')->ignore($device?->device_id, 'device_id'),
                Rule::prohibitedIf($type !== TrackingDevice::TYPE_GPS)],
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
            'installation_date' => 'nullable|date|before_or_equal:today',
            'battery_life_months' => 'nullable|integer|min:0|max:240',
            'is_active' => 'sometimes|boolean',
            'device_condition' => 'sometimes|in:normal,damaged,lost',
            'battery_level' => 'nullable|integer|between:0,100',
            'signal_strength' => 'nullable|integer|between:0,100',
            'last_recorded_at' => 'nullable|date',
        ], [
            'animal_id.prohibited' => 'Perangkat kamera/audio dipasang di lokasi, bukan pada satwa.',
            'animal_id.unique' => 'Satwa ini sudah memiliki perangkat GPS.',
        ]);

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        return $data;
    }
}
