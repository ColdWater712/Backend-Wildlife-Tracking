<?php

namespace App\Services\Ai;

use App\Models\ActivityLog;
use App\Models\AiMediaUpload;
use App\Models\AiPrediction;
use App\Models\IncidentReport;
use App\Services\NotificationService;

/**
 * Menindaklanjuti hasil AI: membuat laporan insiden otomatis dan
 * mengirim notifikasi ke petugas bila ada ancaman atau satwa bermasalah.
 */
class ThreatResponder
{
    public function __construct(private NotificationService $notifier) {}

    public function respond(AiPrediction $prediction, AiMediaUpload $media, array $context = []): ?IncidentReport
    {
        $threatRank = AiPrediction::threatRank($prediction->threat_level);
        $animalProblem = in_array($prediction->physical_condition, ['injured', 'sick', 'emaciated'], true);

        if ($threatRank < AiPrediction::threatRank('medium') && ! $animalProblem) {
            return null;
        }

        $place = $media->zone?->zone_name
            ?? ($media->latitude !== null ? sprintf('%.5f, %.5f', $media->latitude, $media->longitude) : 'lokasi tidak diketahui');
        $source = $media->device ? "{$media->device->device_type} #{$media->device_id}" : 'unggahan petugas';
        $refs = [
            'zone_id' => $media->zone_id,
            'device_id' => $media->device_id,
            'animal_id' => $prediction->predicted_animal_id,
            'prediction_id' => $prediction->prediction_id,
        ];

        $incident = null;
        $incidentType = $this->incidentType($prediction);

        if ($incidentType && ! $media->report_id) {
            $incident = IncidentReport::create([
                'user_id' => $media->uploaded_by,
                'animal_id' => $prediction->predicted_animal_id,
                'species_id' => $prediction->predicted_species_id,
                'zone_id' => $media->zone_id,
                'incident_type' => $incidentType,
                'description' => $this->description($prediction, $source),
                'timestamp' => $media->captured_at ?? $media->uploaded_at ?? now(),
                'latitude' => $media->latitude,
                'longitude' => $media->longitude,
                'location' => $media->zone?->zone_name,
                'status' => 'pending',
                'sync_status' => 'synced',
                'source' => $prediction->analysis_type === 'audio' ? 'ai_audio' : 'ai_camera',
            ]);
            $media->update(['report_id' => $incident->report_id]);
            $refs['report_id'] = $incident->report_id;

            ActivityLog::record($media->uploaded_by, 'AI_AUTO_INCIDENT', [
                'prediction_id' => $prediction->prediction_id,
                'report_id' => $incident->report_id,
                'threat_level' => $prediction->threat_level,
            ]);
        }

        if ($threatRank >= AiPrediction::threatRank('medium')) {
            $label = $prediction->analysis_type === 'audio'
                ? ($prediction->sound_label ?? 'suara mencurigakan')
                : 'aktivitas manusia mencurigakan';
            $this->notifier->notifyRoles(
                NotificationService::FIELD_ROLES,
                'risk_zone',
                sprintf('[%s] AI mendeteksi %s di %s (%s). Segera lakukan pengecekan.',
                    strtoupper($prediction->threat_level), $label, $place, $source),
                $refs,
                15,
            );
        }

        if ($animalProblem) {
            $who = $prediction->predictedAnimal?->name_animal
                ?? $prediction->predictedSpecies?->common_name
                ?? 'satwa';
            $condition = AnalysisVocabulary::PHYSICAL_CONDITIONS[$prediction->physical_condition];
            $this->notifier->notifyRoles(
                array_unique(array_merge(NotificationService::FIELD_ROLES, NotificationService::RESEARCH_ROLES)),
                'system',
                "AI mendeteksi {$who} dalam kondisi {$condition} di {$place}. {$prediction->condition_notes}",
                $refs,
                60,
            );
        }

        return $incident;
    }

    private function incidentType(AiPrediction $prediction): ?string
    {
        if ($prediction->analysis_type === 'audio') {
            return AnalysisVocabulary::SOUND_CATEGORIES[$prediction->sound_category ?? 'unknown']['incident'] ?? null;
        }

        $flags = $prediction->raw_response['human_activity'] ?? [];

        return match (true) {
            ($flags['weapons'] ?? false) || ($flags['traps_or_snares'] ?? false) => 'perburuan',
            $flags['logging'] ?? false => 'perambahan',
            $flags['fire_or_smoke'] ?? false => 'kebakaran_hutan',
            in_array($prediction->physical_condition, ['injured', 'sick'], true) => 'kesehatan_satwa',
            AiPrediction::threatRank($prediction->threat_level) >= AiPrediction::threatRank('high') => 'perambahan',
            default => null,
        };
    }

    private function description(AiPrediction $prediction, string $source): string
    {
        $parts = ["[Otomatis oleh AI dari {$source}]"];
        $parts[] = $prediction->ai_summary;

        if ($prediction->sound_label) {
            $parts[] = "Suara terdeteksi: {$prediction->sound_label}.";
        }
        if ($prediction->physical_condition && $prediction->physical_condition !== 'unknown') {
            $parts[] = 'Kondisi satwa: ' . AnalysisVocabulary::PHYSICAL_CONDITIONS[$prediction->physical_condition] . '. ' . $prediction->condition_notes;
        }
        $parts[] = sprintf('Tingkat ancaman: %s, confidence %.0f%%. Perlu verifikasi petugas.',
            $prediction->threat_level, ($prediction->confidence ?? 0) * 100);

        return trim(implode(' ', array_filter($parts)));
    }
}
