<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false; // Hanya created_at
    
    // is_success adalah Generated Column
    protected $fillable = [
        'booking_id', 'payment_method', 'amount', 
        'payment_status', 'transaction_ref', 'paid_at'
    ];
    protected $casts = [
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function booking() {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}