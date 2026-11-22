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

    public static $snakeAttributes = false;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role_id', 'username', 'full_name', 'email', 'password',
        'address', 'phone', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Bentuk agregat: user_id, role_id, username, name_role. */
    protected $appends = ['name_role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameRoleAttribute(): ?string
    {
        return $this->role?->name_role;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /** Daftar nama permission milik role user (di-cache per instance). */
    public function permissionNames(): array
    {
        return once(fn () => $this->role
            ? $this->role->permissions()->pluck('name_permission')->all()
            : []);
    }

    public function hasPermission(string ...$permissions): bool
    {
        $owned = $this->permissionNames();

        foreach ($permissions as $permission) {
            if (in_array($permission, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role?->name_role, $roles, true);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class, 'user_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}
