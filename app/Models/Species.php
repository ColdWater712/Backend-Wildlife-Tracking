<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Species extends Model
{
    use SoftDeletes;

    protected $table = 'species';
    protected $primaryKey = 'species_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = ['common_name', 'scientific_name', 'conservation_status'];

    protected function casts(): array
    {
        return ['deleted_at' => 'datetime'];
    }

    public function animals()
    {
        return $this->hasMany(Animal::class, 'species_id', 'species_id');
    }

    public function predictedIn()
    {
        return $this->hasMany(AiPrediction::class, 'predicted_species_id', 'species_id');
    }

    public function verifiedIn()
    {
        return $this->hasMany(AiPrediction::class, 'verified_species_id', 'species_id');
    }
}