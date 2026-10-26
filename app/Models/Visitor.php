<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $table = 'visitors';
    protected $primaryKey = 'visitor_id';
    public $timestamps = false; // Hanya ada created_at
    protected $fillable = ['name', 'email', 'phone'];
    protected $casts = ['created_at' => 'datetime'];

    public function bookings() {
        return $this->hasMany(Booking::class, 'visitor_id', 'visitor_id');
    }
}