<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'notifications';
    protected $primaryKey = 'notif_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'notification_type', 'message', 'animal_id', 'zone_id',
        'device_id', 'report_id', 'prediction_id', 'is_read', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'animal_id');
    }

    public function zone()
    {
        return $this->belongsTo(GeoZone::class, 'zone_id', 'zone_id');
    }

    public function device()
    {
        return $this->belongsTo(TrackingDevice::class, 'device_id', 'device_id');
    }

    public function report()
    {
        return $this->belongsTo(IncidentReport::class, 'report_id', 'report_id');
    }

    public function prediction()
    {
        return $this->belongsTo(AiPrediction::class, 'prediction_id', 'prediction_id');
    }
}
