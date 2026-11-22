<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'weather_data';
    protected $primaryKey = 'weather_id';
    public $timestamps = false;

    protected $fillable = [
        'reading_id', 'latitude', 'longitude', 'observed_at', 'temperature_c',
        'rainfall_mm', 'humidity_percent', 'pressure_hpa', 'source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'temperature_c' => 'float',
            'rainfall_mm' => 'float',
            'humidity_percent' => 'float',
            'pressure_hpa' => 'float',
            'observed_at' => 'datetime',
        ];
    }

    public function gpsReading()
    {
        return $this->belongsTo(GpsReading::class, 'reading_id', 'reading_id');
    }
}
