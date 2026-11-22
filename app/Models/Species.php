<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Species extends Model
{
    use SoftDeletes;

    public static $snakeAttributes = false;

    protected $table = 'species';
    protected $primaryKey = 'species_id';

    protected $fillable = ['common_name', 'scientific_name', 'conservation_status'];

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
