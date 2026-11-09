<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GeoZone extends Model
{
    protected $table = 'geo_zones';
    protected $primaryKey = 'zone_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'zone_name', 'zone_type', 'center_lat', 'center_lon', 
        'radius_km', 'polygon_geojson', 'created_by'
    ];
    protected $casts = [
        'polygon_geojson' => 'array', // Agar JSON otomatis jadi array PHP
        'created_at' => 'datetime',
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by', 'user_id'); }
}