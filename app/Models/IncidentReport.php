<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'incident_type', 'description', 'timestamp', 'latitude', 'longitude',
        'location', 'animal_id', 'species_id', 'reported_by', 'status', 'sync_status',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id', 'species_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function media()
    {
        return $this->hasMany(AiMediaUpload::class, 'report_id', 'report_id');
    }
}
