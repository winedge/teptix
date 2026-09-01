<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMapSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_map_template_id',
        'name',
        'code',
        'color',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(VenueMapTemplate::class, 'venue_map_template_id');
    }

    public function rows()
    {
        return $this->hasMany(VenueMapRow::class);
    }

    public function seats()
    {
        return $this->hasMany(VenueMapSeat::class);
    }
}
