<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsReading extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'gps_readings';
    protected $primaryKey = 'reading_id';
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'latitude', 'longitude', 'recorded_at',
        'altitude_meters', 'accuracy_meters', 'checksum',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude_meters' => 'float',
            'accuracy_meters' => 'float',
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
