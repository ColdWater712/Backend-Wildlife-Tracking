<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoZone extends Model
{
    protected $table = 'geo_zones';
    protected $primaryKey = 'zone_id';
    public $timestamps = false;

    protected $fillable = [
        'zone_name',
        'zone_type',
        'center_lat',
        'center_lon',
        'radius_km',
        'polygon_geojson',
        'created_by',
    ];

    protected $casts = [
        'center_lat' => 'decimal:6',
        'center_lon' => 'decimal:6',
        'polygon_geojson' => 'array',
        'created_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function animalVisits()
    {
        return $this->hasMany(AnimalGeoZone::class, 'zone_id', 'zone_id');
    }
}