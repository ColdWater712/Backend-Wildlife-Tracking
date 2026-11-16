<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    protected $table = 'animals';
    protected $primaryKey = 'animal_id';
    public $incrementing = true;
    public $timestamps = false;

    // ✅ HANYA field yang BOLEH diisi user
    protected $fillable = [
        'species_id',
        'name',
        'birth_date',
        'gender',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function trackingDevice()
    {
        return $this->hasOne(TrackingDevice::class, 'animal_id', 'animal_id');
    }
}