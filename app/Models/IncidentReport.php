<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentReport extends Model
{
    use SoftDeletes;

    protected $table = 'incident_reports';
    protected $primaryKey = 'report_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'incident_type', 'animal_code', 'location',
        'description', 'timestamp', 'photo_url', 'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function media()
    {
        return $this->hasMany(AiMediaUpload::class, 'report_id', 'report_id');
    }
}