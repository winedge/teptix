<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVenueRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_venue_map_id',
        'event_venue_section_id',
        'venue_map_row_id',
        'name',
        'seat_count',
        'start_seat_number',
        'sort_order',
        'ticket_id',
        'pricing_tier',
        'price',
        'status',
    ];

    protected $casts = [
        'seat_count' => 'integer',
        'start_seat_number' => 'integer',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    public function eventVenueMap()
    {
        return $this->belongsTo(EventVenueMap::class);
    }

    public function section()
    {
        return $this->belongsTo(EventVenueSection::class, 'event_venue_section_id');
    }

    public function masterRow()
    {
        return $this->belongsTo(VenueMapRow::class, 'venue_map_row_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function seats()
    {
        return $this->hasMany(EventVenueSeat::class);
    }
}
