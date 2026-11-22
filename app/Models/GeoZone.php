<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeoZone extends Model
{
    use SoftDeletes;

    public static $snakeAttributes = false;

    protected $table = 'geo_zones';
    protected $primaryKey = 'zone_id';

    protected $fillable = [
        'zone_name', 'zone_type', 'center_lat', 'center_lon',
        'radius_km', 'polygon_geojson', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'center_lat' => 'float',
            'center_lon' => 'float',
            'radius_km' => 'float',
            'polygon_geojson' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function visits()
    {
        return $this->hasMany(AnimalGeoZone::class, 'zone_id', 'zone_id');
    }

    public function devices()
    {
        return $this->hasMany(TrackingDevice::class, 'zone_id', 'zone_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'zone_id', 'zone_id');
    }
}
