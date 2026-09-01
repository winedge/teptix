<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'image_for_mobile',
        'image_for_android',

        'status',
        'event_id',
        'banner_type',
        'redirect_url',
        'display_order',
    ];

    protected $table = 'banner'; // corrected table name

    // Define the relationship with the Event model
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

