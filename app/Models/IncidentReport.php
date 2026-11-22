<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentReport extends Model
{
    use SoftDeletes;

    public static $snakeAttributes = false;

    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'user_id', 'animal_id', 'species_id', 'zone_id', 'incident_type', 'description',
        'timestamp', 'latitude', 'longitude', 'location', 'photo_url',
        'status', 'sync_status', 'source',
    ];

    /** `reported_by` adalah alias baca dari `user_id` (pelapor) untuk frontend. */
    protected $appends = ['reported_by'];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getReportedByAttribute(): ?int
    {
        return $this->user_id;
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function media()
    {
        return $this->hasMany(AiMediaUpload::class, 'report_id', 'report_id');
    }
}
