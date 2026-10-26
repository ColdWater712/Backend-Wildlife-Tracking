<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    protected $table = 'animals';
    protected $primaryKey = 'animal_id';
    public $timestamps = false;

    protected $fillable = [
        'species_id',
        'name',
        'birth_date',
        'gender',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function species() {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice() {
        return $this->hasOne(TrackingDevice::class, 'animal_id', 'animal_id');
    }

    public function geoZoneVisits() {
        return $this->hasMany(AnimalGeoZone::class, 'animal_id', 'animal_id');
    }
}