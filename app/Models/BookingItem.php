<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $table = 'booking_items';
    protected $primaryKey = 'item_id';
    public $timestamps = false;
    
    // subtotal adalah Generated Column, jangan di-fillable
    protected $fillable = ['booking_id', 'ticket_type_id', 'quantity', 'unit_price'];

    public function ticketType() {
        return $this->belongsTo(TicketType::class, 'ticket_type_id', 'ticket_type_id');
    }
}