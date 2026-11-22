<?php

namespace App\Http\Controllers\Api;

use App\Models\AiMediaUpload;
use App\Models\IncidentReport;
use App\Services\AiPredictionService;
use App\Services\GeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentReportController extends ApiController
{
    private const RELATIONS = ['reporter', 'animal', 'species', 'zone'];

    public function __construct(private GeoService $geo) {}

    public function index(Request $request)
    {
        $query = IncidentReport::query()->with(self::RELATIONS);

        foreach (['incident_type', 'status', 'sync_status', 'source'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        foreach (['animal_id', 'species_id', 'zone_id', 'user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }
        if ($request->filled('reported_by')) {
            $query->where('user_id', $request->integer('reported_by'));
        }
        if ($request->filled('animal_code')) {
            $query->whereHas('animal', fn ($q) => $q->where('name_animal', $request->animal_code));
        }
        if ($request->filled('start_date')) {
            $query->where('timestamp', '>=', $request->date('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('timestamp', '<=', $request->date('end_date')->endOfDay());
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('description', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%"));
        }

        $this->applySort($query, $request, ['report_id', 'timestamp', 'incident_type', 'status'], 'timestamp');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(IncidentReport::with([...self::RELATIONS, 'media.predictions'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->user_id;
        $data['zone_id'] ??= $this->geo->resolveZone($data['latitude'] ?? null, $data['longitude'] ?? null, 0)?->zone_id;
        $data['sync_status'] = 'synced';

        $incident = IncidentReport::create($data);

        return $this->success($incident->load(self::RELATIONS), 'Laporan insiden berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $user = $request->user();

        if ((int) $incident->user_id !== (int) $user->user_id && ! $user->hasPermission('delete_data')) {
            return $this->error('Hanya pelapor atau administrator yang dapat mengubah laporan ini.', 403);
        }

        $incident->update($this->validated($request, $incident));

        return $this->success($incident->fresh(self::RELATIONS), 'Laporan insiden diperbarui');
    }

    public function destroy(int $id)
    {
        IncidentReport::findOrFail($id)->delete();

        return $this->success(null, 'Laporan insiden dihapus');
    }

    /** Hanya proses sinkronisasi / admin yang boleh mengubah sync_status. */
    public function updateSyncStatus(Request $request, int $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $incident->update($request->validate(['sync_status' => 'required|in:pending_sync,synced,failed']));

        return $this->success($incident->fresh(self::RELATIONS), 'Status sinkronisasi diperbarui');
    }

    public function media(int $id)
    {
        IncidentReport::findOrFail($id);

        return $this->list(AiMediaUpload::query()->with('predictions')->where('report_id', $id)->orderByDesc('uploaded_at'));
    }

    /** Unggah bukti (foto/video/audio). ?analyze=1 untuk langsung dianalisis AI. */
    public function uploadMedia(Request $request, AiPredictionService $ai, int $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov,webm,wav,mp3,m4a,ogg|max:51200',
            'analyze' => 'sometimes|boolean',
        ]);

        $media = $ai->storeMedia($request->file('file'), [
            'uploaded_by' => $request->user()->user_id,
            'report_id' => $incident->report_id,
            'zone_id' => $incident->zone_id,
            'latitude' => $incident->latitude,
            'longitude' => $incident->longitude,
            'captured_at' => $incident->timestamp,
        ]);

        $prediction = $request->boolean('analyze') ? $ai->process($media) : null;

        return $this->success(
            $prediction ? ['media' => $media, 'prediction' => $prediction] : $media,
            'Media berhasil diunggah',
            201,
        );
    }

    private function validated(Request $request, ?IncidentReport $incident = null): array
    {
        $required = $incident ? 'sometimes' : 'required';

        return $request->validate([
            'incident_type' => "{$required}|string|max:100",
            'description' => "{$required}|string|max:5000",
            'timestamp' => "{$required}|date|before_or_equal:" . now()->addMinutes(5)->toDateTimeString(),
            'latitude' => "{$required}|numeric|between:-90,90",
            'longitude' => "{$required}|numeric|between:-180,180",
            'location' => 'nullable|string|max:255',
            'animal_id' => ['nullable', 'integer', Rule::exists('animals', 'animal_id')->whereNull('deleted_at')],
            'species_id' => ['nullable', 'integer', Rule::exists('species', 'species_id')->whereNull('deleted_at')],
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'photo_url' => 'nullable|url|max:500',
            'status' => 'sometimes|string|max:50',
        ]);
    }
}
