<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = ['name_role', 'description'];

    /** Alias lama agar klien yang masih membaca `role_name` tetap berfungsi. */
    protected $appends = ['role_name'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->name_role;
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withPivot('role_permission_id');
    }
}
