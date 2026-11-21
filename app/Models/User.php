<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    public $timestamps = false;

<<<<<<< HEAD
    // GANTI 'role' menjadi 'role_id'
    protected $fillable = ['name', 'email', 'password', 'role_id'];
    protected $hidden = ['password'];
=======
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
    ];
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

<<<<<<< HEAD
    // TAMBAHKAN relasi ke model Role
=======
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }
<<<<<<< HEAD
=======

    public function incidents()
    {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function geoZones()
    {
        return $this->hasMany(GeoZone::class, 'created_by', 'user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function aiMediaUploads()
    {
        return $this->hasMany(AiMediaUpload::class, 'uploaded_by', 'user_id');
    }

    public function aiVerifications()
    {
        return $this->hasMany(AiPrediction::class, 'verified_by', 'user_id');
    }

    public function aggregateReports()
    {
        return $this->hasMany(AggregateReport::class, 'generated_by', 'user_id');
    }
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
}