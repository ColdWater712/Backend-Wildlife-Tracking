<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMediaUpload extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'ai_media_uploads';
    protected $primaryKey = 'media_id';
    public $timestamps = false;

    protected $fillable = [
        'uploaded_by', 'report_id', 'device_id', 'zone_id', 'media_type', 'file_path',
        'mime_type', 'file_size', 'latitude', 'longitude', 'captured_at', 'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'file_size' => 'integer',
            'captured_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function report()
    {
        return $this->belongsTo(IncidentReport::class, 'report_id', 'report_id');
    }

    public function device()
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function predictions()
    {
        return $this->hasMany(AiPrediction::class, 'media_id', 'media_id');
    }

    public function absolutePath(): string
    {
        return storage_path('app/private/' . ltrim($this->file_path, '/'));
    }
}
