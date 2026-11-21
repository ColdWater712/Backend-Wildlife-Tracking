<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    const UPDATED_AT = null; 

    // GANTI 'role' menjadi 'role_id'
    protected $fillable = ['name', 'email', 'password', 'role_id'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    // TAMBAHKAN relasi ke model Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }
}