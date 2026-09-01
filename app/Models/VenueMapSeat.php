<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMapSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_map_template_id',
        'venue_map_section_id',
        'venue_map_row_id',
        'seat_number',
        'seat_label',
        'x_percent',
        'y_percent',
        'radius_percent',
        'sort_order',
        'is_accessible',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'x_percent' => 'decimal:5',
        'y_percent' => 'decimal:5',
        'radius_percent' => 'decimal:5',
        'sort_order' => 'integer',
        'is_accessible' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(VenueMapTemplate::class, 'venue_map_template_id');
    }

    public function section()
    {
        return $this->belongsTo(VenueMapSection::class, 'venue_map_section_id');
    }

    public function row()
    {
        return $this->belongsTo(VenueMapRow::class, 'venue_map_row_id');
    }
}
