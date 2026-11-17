<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsReading extends Model
{
    protected $table = 'gps_readings';
    protected $primaryKey = 'reading_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'latitude', 'longitude', 'recorded_at',
        'altitude_meters', 'accuracy_meters', 'checksum',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'altitude_meters' => 'decimal:2',
            'accuracy_meters' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function device()
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }

    public function weatherData()
    {
        return $this->hasOne(WeatherData::class, 'reading_id', 'reading_id');
    }

    public function zoneVisits()
    {
        return $this->hasMany(AnimalGeoZone::class, 'entry_reading_id', 'reading_id');
    }
}