<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingDevice extends Model
{
    public const TYPE_GPS = 'gps';
    public const TYPE_CAMERA = 'camera';
    public const TYPE_AUDIO = 'audio';
    public const TYPES = [self::TYPE_GPS, self::TYPE_CAMERA, self::TYPE_AUDIO];

    public static $snakeAttributes = false;

    protected $table = 'tracking_devices';
    protected $primaryKey = 'device_id';
    public $timestamps = false;

    protected $fillable = [
        'device_type', 'animal_id', 'zone_id', 'model', 'serial_number',
        'latitude', 'longitude', 'installation_date', 'battery_life_months',
        'is_active', 'device_condition', 'battery_level', 'signal_strength',
        'last_recorded_at',
    ];

    protected $attributes = [
        'device_type' => self::TYPE_GPS,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'installation_date' => 'date:Y-m-d',
            'last_recorded_at' => 'datetime',
            'is_active' => 'boolean',
            'battery_level' => 'integer',
            'signal_strength' => 'integer',
            'battery_life_months' => 'integer',
        ];
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function gpsReadings()
    {
        return $this->hasMany(GpsReading::class, 'device_id', 'device_id');
    }

    public function latestReading()
    {
        return $this->hasOne(GpsReading::class, 'device_id', 'device_id')->latestOfMany('recorded_at');
    }

    public function mediaUploads()
    {
        return $this->hasMany(AiMediaUpload::class, 'device_id', 'device_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'device_id', 'device_id');
    }
}
