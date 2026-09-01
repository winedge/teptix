<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVenueSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_venue_map_id',
        'venue_map_section_id',
        'name',
        'code',
        'color',
        'sort_order',
        'ticket_id',
        'pricing_tier',
        'price',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    public function eventVenueMap()
    {
        return $this->belongsTo(EventVenueMap::class);
    }

    public function masterSection()
    {
        return $this->belongsTo(VenueMapSection::class, 'venue_map_section_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function rows()
    {
        return $this->hasMany(EventVenueRow::class);
    }

    public function seats()
    {
        return $this->hasMany(EventVenueSeat::class);
    }
}
