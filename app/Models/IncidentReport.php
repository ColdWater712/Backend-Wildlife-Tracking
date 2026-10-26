<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'incident_type',
        'animal_code',
        'location',
        'description',
        'photo_url',
        'sync_status',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function mediaUploads()
    {
        return $this->hasMany(AIMediaUpload::class, 'report_id', 'report_id');
    }
}