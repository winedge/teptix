<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'location',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'lat',
        'lng',
        'status',
        'created_by',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mapTemplates()
    {
        return $this->hasMany(VenueMapTemplate::class);
    }

    public function publishedMapTemplates()
    {
        return $this->hasMany(VenueMapTemplate::class)
            ->where('status', VenueMapTemplate::STATUS_PUBLISHED);
    }

    public function eventVenueMaps()
    {
        return $this->hasMany(EventVenueMap::class);
    }
}
