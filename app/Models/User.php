<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Konfigurasi Tabel & Primary Key
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Tabel ini hanya punya created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // --- Relasi (Sesuai Dokumen) ---
    public function incidentReports() {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function geoZones() {
        return $this->hasMany(GeoZone::class, 'created_by', 'user_id');
    }

    public function activityLogs() {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }
}