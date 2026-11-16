<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingDevice extends Model
{
    protected $table = 'tracking_devices';
    protected $primaryKey = 'device_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'animal_id',
        'model',
        'installation_date',
        'battery_life_months',
        'battery_level',
        'signal_strength',
        'device_condition',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'last_recorded_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function gpsReadings()
    {
        return $this->hasMany(GpsReading::class, 'device_id', 'device_id');
    }
}