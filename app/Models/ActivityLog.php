<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'activity_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'timestamp', 'details'];

    protected function casts(): array
    {
        return ['timestamp' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Catat aktivitas penting (audit trail). Tidak pernah melempar exception. */
    public static function record(?int $userId, string $action, array $details = []): void
    {
        try {
            static::create([
                'user_id' => $userId,
                'action' => $action,
                'timestamp' => now(),
                'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
