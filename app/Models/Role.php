<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    const UPDATED_AT = null; // Hanya ada created_at

    protected $fillable = ['role_name', 'description'];
    protected $casts = ['created_at' => 'datetime'];

    public function permissions() {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }
}