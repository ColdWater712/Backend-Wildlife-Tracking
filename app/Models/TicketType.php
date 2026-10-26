<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    protected $table = 'ticket_types';
    protected $primaryKey = 'ticket_type_id';
    public $timestamps = false;
    protected $fillable = ['type_name', 'description', 'price', 'is_active'];
    protected $casts = ['created_at' => 'datetime'];
}