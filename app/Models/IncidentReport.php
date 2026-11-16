<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = true;
    public $timestamps = false;


    protected $fillable = [
        'incident_type',
        'location',
        'description',
        'animal_code',
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}