<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueMapTemplateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_map_template_id',
        'numbering_mode',
        'counting_direction',
        'layout_style',
        'show_row_name',
        'seat_shape',
        'focal_x',
        'focal_y',
    ];

    public function template()
    {
        return $this->belongsTo(VenueMapTemplate::class, 'venue_map_template_id');
    }
}
