<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventVenueMap extends Model
{
    use HasFactory, SoftDeletes;

    const SELECTION_MANUAL = 'manual';
    const SELECTION_AUTO = 'auto';

    const STATUS_DRAFT = 'draft';
    const STATUS_LIVE = 'live';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'event_id',
        'venue_id',
        'venue_map_template_id',
        'selection_mode',
        'hold_minutes',
        'status',
        'activated_at',
        'published_at',
    ];

    protected $casts = [
        'hold_minutes' => 'integer',
        'activated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function template()
    {
        return $this->belongsTo(VenueMapTemplate::class, 'venue_map_template_id');
    }

    public function sections()
    {
        return $this->hasMany(EventVenueSection::class);
    }

    public function rows()
    {
        return $this->hasMany(EventVenueRow::class);
    }

    public function seats()
    {
        return $this->hasMany(EventVenueSeat::class);
    }

    public function availableSeats()
    {
        return $this->hasMany(EventVenueSeat::class)
            ->where('status', EventVenueSeat::STATUS_AVAILABLE);
    }
}
