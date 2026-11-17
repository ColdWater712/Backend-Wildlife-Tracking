<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = ['permission_name', 'description'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        );
    }
}