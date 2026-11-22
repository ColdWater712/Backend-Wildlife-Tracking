<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use SoftDeletes;

    public static $snakeAttributes = false;

    protected $table = 'animals';
    protected $primaryKey = 'animal_id';

    protected $fillable = [
        'species_id', 'name_animal', 'birth_date', 'gender', 'distinctive_marks', 'is_active',
    ];

    /** `name` dipertahankan sebagai alias baca dari `name_animal` untuk frontend. */
    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->name_animal;
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice()
    {
        return $this->hasOne(TrackingDevice::class, 'animal_id', 'animal_id');
    }

    public function gpsReadings()
    {
        return $this->hasManyThrough(
            GpsReading::class, TrackingDevice::class,
            'animal_id', 'device_id', 'animal_id', 'device_id',
        );
    }

    public function zoneVisits()
    {
        return $this->hasMany(AnimalGeoZone::class, 'animal_id', 'animal_id');
    }

    public function incidents()
    {
        return $this->hasMany(IncidentReport::class, 'animal_id', 'animal_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'animal_id', 'animal_id');
    }

    public function aiObservations()
    {
        return $this->hasMany(AiPrediction::class, 'predicted_animal_id', 'animal_id');
    }
}
