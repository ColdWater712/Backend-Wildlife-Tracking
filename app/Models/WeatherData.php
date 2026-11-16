<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    protected $table = 'weather_data';
    protected $primaryKey = 'weather_id';
    public $timestamps = false; 

    protected $fillable = [
        'reading_id', 'latitude', 'longitude', 'observed_at', 
        'temperature_c', 'rainfall_mm', 'humidity_percent', 'pressure_hpa', 'source'
    ];
    protected $casts = ['observed_at' => 'datetime'];
}