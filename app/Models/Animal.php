<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use SoftDeletes;

    protected $table = 'animals';
    protected $primaryKey = 'animal_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = ['species_id', 'name', 'birth_date', 'gender', 'is_active'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice()
    {
        return $this->hasOne(TrackingDevice::class, 'animal_id', 'animal_id');
    }

    public function geoZoneVisits()
    {
        return $this->hasMany(AnimalGeoZone::class, 'animal_id', 'animal_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'animal_id', 'animal_id');
    }
}