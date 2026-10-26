<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';
    // Booking punya created_at DAN updated_at
    protected $fillable = [
        'booking_code', 'visitor_id', 'visit_date', 
        'total_amount', 'booking_status', 'expires_at'
    ];
    protected $casts = [
        'visit_date' => 'date',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visitor() {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }

    public function items() {
        return $this->hasMany(BookingItem::class, 'booking_id', 'booking_id');
    }

    public function payments() {
        return $this->hasMany(Payment::class, 'booking_id', 'booking_id');
    }
}