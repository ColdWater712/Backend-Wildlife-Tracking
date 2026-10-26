<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GPSReading extends Model
{
    protected $table = 'gps_readings';
    protected $primaryKey = 'reading_id';
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'recorded_at',
        'altitude_meters',
        'accuracy_meters',
        'checksum',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    public function device() {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }
}