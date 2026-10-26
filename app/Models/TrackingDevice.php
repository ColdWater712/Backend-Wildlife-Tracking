<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingDevice extends Model
{
    protected $table = 'tracking_devices';
    protected $primaryKey = 'device_id';
    public $timestamps = false;

    protected $fillable = [
        'animal_id',
        'model',
        'installation_date',
        'battery_life_months',
        'is_active',
        'device_condition',
        'battery_level',
        'signal_strength',
        'last_recorded_at',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_recorded_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function animal() {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function gpsReadings() {
        return $this->hasMany(GPSReading::class, 'device_id', 'device_id');
    }
}