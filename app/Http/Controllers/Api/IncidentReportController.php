<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\IncidentReport;
use App\Models\AiMediaUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidentReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = IncidentReport::with(['reporter', 'animal', 'species']);

        if ($request->filled('incident_type')) $query->where('incident_type', $request->incident_type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('animal_id')) $query->where('animal_id', $request->animal_id);
        if ($request->filled('species_id')) $query->where('species_id', $request->species_id);
        if ($request->filled('reporter_id')) $query->where('reported_by', $request->reporter_id);
        if ($request->filled('start_date')) $query->where('timestamp', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->where('timestamp', '<=', $request->end_date);
        if ($request->filled('search')) {
            $query->where('description', 'like', "%" . $request->search . "%");
        }

        $query->orderBy('timestamp', 'desc');
        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(IncidentReport::with(['reporter', 'animal', 'species', 'media'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'incident_type' => 'required|string',
            'description'   => 'required|string',
            'timestamp'     => 'required|date',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'location'      => 'nullable|string',
            'animal_id'     => 'nullable|exists:animals,animal_id',
            'species_id'    => 'nullable|exists:species,species_id',
            'status'        => 'sometimes|string',
        ]);

        $incident = IncidentReport::create([
            'incident_type' => $request->incident_type,
            'description'   => $request->description,
            'timestamp'     => $request->timestamp,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'location'      => $request->input('location', 'Lokasi Tidak Diketahui'), // WAJIB ADA
            'animal_id'     => $request->animal_id,
            'species_id'    => $request->species_id,
            'reported_by'   => $request->user()->user_id,
            'status'        => $request->input('status', 'pending'),
        ]);

        return $this->success($incident->load(['reporter', 'animal', 'species']), 'Incident berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $request->validate([
            'incident_type' => 'sometimes|string',
            'description'   => 'sometimes|string',
            'timestamp'     => 'sometimes|date',
            'latitude'      => 'sometimes|numeric',
            'longitude'     => 'sometimes|numeric',
            'location'      => 'nullable|string',
            'animal_id'     => 'nullable|exists:animals,animal_id',
            'species_id'    => 'nullable|exists:species,species_id',
            'status'        => 'sometimes|string',
        ]);

        $incident->update($request->only([
            'incident_type', 'description', 'timestamp', 'latitude', 'longitude',
            'location', 'animal_id', 'species_id', 'status'
        ]));

        return $this->success($incident->fresh(['reporter', 'animal', 'species']), 'Incident diperbarui');
    }

    public function destroy($id)
    {
        IncidentReport::findOrFail($id)->delete();
        return $this->success(null, 'Incident dihapus');
    }

    public function updateSyncStatus(Request $request, $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $request->validate(['sync_status' => 'required|in:synced,pending,failed']);
        $incident->update(['sync_status' => $request->sync_status]);
        return $this->success($incident->fresh(), 'Sync status diperbarui');
    }

    public function media($id)
    {
        $incident = IncidentReport::findOrFail($id);
        $media = AiMediaUpload::where('report_id', $incident->getKey())->get();
        return $this->list($media);
    }

    public function uploadMedia(Request $request, $id)
    {
        $incident = IncidentReport::findOrFail($id);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png|max:10240']);

        $file = $request->file('file');
        $path = $file->store('incident-media', 'public');

        $media = AiMediaUpload::create([
            'uploaded_by' => $request->user()->user_id,
            'report_id'   => $incident->getKey(),
            'media_type'  => 'image',
            'file_path'   => $path,
        ]);

        return $this->success($media, 'Media berhasil diupload', 201);
    }

    public function byAnimal($animalId)
    {
        $incidents = IncidentReport::where('animal_id', $animalId)
            ->with(['reporter', 'species'])
            ->orderBy('timestamp', 'desc')
            ->get();
        return $this->list($incidents);
    }
}