<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    protected $table = 'weather_data';
    protected $primaryKey = 'weather_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'reading_id', 'latitude', 'longitude', 'observed_at',
        'temperature_c', 'rainfall_mm', 'humidity_percent',
        'pressure_hpa', 'source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'temperature_c' => 'decimal:2',
            'rainfall_mm' => 'decimal:2',
            'humidity_percent' => 'decimal:2',
            'pressure_hpa' => 'decimal:2',
            'observed_at' => 'datetime',
        ];
    }

    public function gpsReading()
    {
        return $this->belongsTo(GpsReading::class, 'reading_id', 'reading_id');
    }
}