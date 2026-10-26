<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $table = 'species';
    protected $primaryKey = 'species_id';
    public $timestamps = false; // No created_at or updated_at

    protected $fillable = [
        'common_name',
        'scientific_name',
        'conservation_status',
    ];

    public function animals() {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }
}