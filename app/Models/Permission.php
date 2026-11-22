<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public static $snakeAttributes = false;

    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    public $timestamps = false;

    protected $fillable = ['name_permission', 'description'];

    protected $appends = ['permission_name'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function getPermissionNameAttribute(): ?string
    {
        return $this->name_permission;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withPivot('role_permission_id');
    }
}
