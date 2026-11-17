<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'log_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'details'];

    protected function casts(): array
    {
        return ['timestamp' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}