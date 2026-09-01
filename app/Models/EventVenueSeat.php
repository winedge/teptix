<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVenueSeat extends Model
{
    use HasFactory;

    const STATUS_AVAILABLE = 'available';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_HELD = 'held';
    const STATUS_BOOKED = 'booked';

    protected $fillable = [
        'event_venue_map_id',
        'event_venue_section_id',
        'event_venue_row_id',
        'venue_map_seat_id',
        'event_id',
        'section_name',
        'row_name',
        'seat_number',
        'seat_label',
        'x_percent',
        'y_percent',
        'radius_percent',
        'ticket_id',
        'pricing_tier',
        'price',
        'status',
        'is_accessible',
        'hold_token',
        'held_by_session_id',
        'held_by_app_user_id',
        'held_by_guest_user_id',
        'held_at',
        'hold_expires_at',
        'booked_order_id',
        'booked_order_child_id',
        'booked_at',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'x_percent' => 'decimal:5',
        'y_percent' => 'decimal:5',
        'radius_percent' => 'decimal:5',
        'price' => 'decimal:2',
        'is_accessible' => 'boolean',
        'held_at' => 'datetime',
        'hold_expires_at' => 'datetime',
        'booked_at' => 'datetime',
    ];

    public function eventVenueMap()
    {
        return $this->belongsTo(EventVenueMap::class);
    }

    public function section()
    {
        return $this->belongsTo(EventVenueSection::class, 'event_venue_section_id');
    }

    public function row()
    {
        return $this->belongsTo(EventVenueRow::class, 'event_venue_row_id');
    }

    public function masterSeat()
    {
        return $this->belongsTo(VenueMapSeat::class, 'venue_map_seat_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function bookedOrder()
    {
        return $this->belongsTo(Order::class, 'booked_order_id');
    }

    public function bookedOrderChild()
    {
        return $this->belongsTo(OrderChild::class, 'booked_order_child_id');
    }

    public function appUser()
    {
        return $this->belongsTo(AppUser::class, 'held_by_app_user_id');
    }

    public function guestUser()
    {
        return $this->belongsTo(GuestUser::class, 'held_by_guest_user_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeExpiredHolds($query)
    {
        return $query
            ->where('status', self::STATUS_HELD)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now());
    }

    public function isHoldExpired()
    {
        return $this->status === self::STATUS_HELD
            && $this->hold_expires_at
            && $this->hold_expires_at->isPast();
    }
}
