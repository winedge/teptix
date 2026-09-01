<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMapRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_map_template_id',
        'venue_map_section_id',
        'name',
        'seat_count',
        'start_seat_number',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'seat_count' => 'integer',
        'start_seat_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(VenueMapTemplate::class, 'venue_map_template_id');
    }

    public function section()
    {
        return $this->belongsTo(VenueMapSection::class, 'venue_map_section_id');
    }

    public function seats()
    {
        return $this->hasMany(VenueMapSeat::class);
    }
}
