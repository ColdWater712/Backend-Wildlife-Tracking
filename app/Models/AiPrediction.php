<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPrediction extends Model
{
    protected $table = 'ai_predictions';
    protected $primaryKey = 'prediction_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'media_id', 'predicted_species_id', 'confidence', 'model_version',
        'processing_status', 'verification_status', 'verified_species_id',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'processed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function media()
    {
        return $this->belongsTo(AiMediaUpload::class, 'media_id', 'media_id');
    }

    public function predictedSpecies()
    {
        return $this->belongsTo(Species::class, 'predicted_species_id', 'species_id');
    }

    public function verifiedSpecies()
    {
        return $this->belongsTo(Species::class, 'verified_species_id', 'species_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}