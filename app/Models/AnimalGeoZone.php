<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalGeoZone extends Model
{
    protected $table = 'animal_geo_zones';
    protected $primaryKey = 'visit_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'animal_id', 'zone_id', 'entry_reading_id', 'entered_at', 'exited_at',
    ];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
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

    public function entryReading()
    {
        return $this->belongsTo(GpsReading::class, 'entry_reading_id', 'reading_id');
    }
}