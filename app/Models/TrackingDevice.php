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
        'animal_id', 'model', 'installation_date', 'battery_life_months',
        'is_active', 'device_condition', 'battery_level', 'signal_strength',
        'last_recorded_at',
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

    public function latestReading()
    {
        return $this->hasOne(GpsReading::class, 'device_id', 'device_id')
            ->latestOfMany('recorded_at');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'device_id', 'device_id');
    }
}