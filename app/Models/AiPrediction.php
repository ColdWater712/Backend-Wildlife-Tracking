<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrediction extends Model
{
    public const THREAT_LEVELS = ['none', 'low', 'medium', 'high', 'critical'];

    public static $snakeAttributes = false;

    protected $table = 'ai_predictions';
    protected $primaryKey = 'prediction_id';
    public $timestamps = false;

    protected $fillable = [
        'media_id', 'analysis_type', 'predicted_species_id', 'confidence', 'detected_label',
        'individual_count', 'predicted_animal_id', 'animal_match_confidence', 'animal_match_method',
        'physical_condition', 'condition_notes', 'behavior', 'behavior_notes',
        'sound_category', 'sound_label', 'threat_level', 'detected_objects', 'ai_summary',
        'raw_response', 'model_version', 'processing_status', 'processed_at',
        'verification_status', 'verified_species_id', 'verified_animal_id', 'verified_by', 'verified_at',
    ];

    protected $hidden = ['raw_response'];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'animal_match_confidence' => 'float',
            'individual_count' => 'integer',
            'detected_objects' => 'array',
            'raw_response' => 'array',
            'processed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public static function threatRank(?string $level): int
    {
        $rank = array_search($level, self::THREAT_LEVELS, true);

        return $rank === false ? 0 : $rank;
    }

    public function media()
    {
        return $this->belongsTo(AiMediaUpload::class, 'media_id', 'media_id');
    }

    public function predictedSpecies()
    {
        return $this->belongsTo(Species::class, 'predicted_species_id', 'species_id');
    }

    public function predictedAnimal()
    {
        return $this->belongsTo(Animal::class, 'predicted_animal_id', 'animal_id');
    }

    public function verifiedSpecies()
    {
        return $this->belongsTo(Species::class, 'verified_species_id', 'species_id');
    }

    public function verifiedAnimal()
    {
        return $this->belongsTo(Animal::class, 'verified_animal_id', 'animal_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}
