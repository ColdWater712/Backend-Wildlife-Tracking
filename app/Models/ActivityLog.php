<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'log_id';
    const CREATED_AT = 'timestamp';
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'details'];
    protected $casts = ['timestamp' => 'datetime'];
}