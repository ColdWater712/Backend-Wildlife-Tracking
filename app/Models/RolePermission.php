<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';
    public $incrementing = false; // Karena PK adalah composite (role_id + permission_id)
    public $timestamps = false;   // Tidak ada timestamp sama sekali

    protected $fillable = ['role_id', 'permission_id'];
}