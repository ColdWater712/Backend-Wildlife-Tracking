<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    const UPDATED_AT = null;

    protected $fillable = ['permission_name', 'description'];
    protected $casts = ['created_at' => 'datetime'];
}