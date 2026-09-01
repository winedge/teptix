<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderChild extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'customer_id',
        'ticket_id',
        'ticket_number',
        'status',
        'ticket_date',
        'checkin',
        'paid',
        'guestuser_id',
        'SeatDetails_id',
        'Book_Seat_Id',
        'seat_id',
        'event_venue_seat_id',
    ];

    protected $table = 'order_child';

    public function getOrderDataAttribute()
    {
        $order  = Order::with(['customer:id,name,last_name,email,image'])->find($this->attributes['tax_id']);
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function seatDetails()
    {
        return $this->belongsTo(Sponsership::class, 'SeatDetails_id');
    }
    public function seatTable()
    {
        return $this->belongsTo(\App\Models\SeatTable::class, 'seat_id');
    }

    public function eventVenueSeat()
    {
        return $this->belongsTo(EventVenueSeat::class, 'event_venue_seat_id');
    }



}
