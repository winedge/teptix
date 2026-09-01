<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'links',
        'files'
    ];

    // Cast the JSON columns to arrays
    protected $casts = [
        'links' => 'array',
        'files' => 'array'
    ];

    // Relationship with Event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
