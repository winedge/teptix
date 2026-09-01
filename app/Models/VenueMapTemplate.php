<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VenueMapTemplate extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'venue_id',
        'name',
        'version',
        'expected_seat_count',
        'background_image',
        'background_width',
        'background_height',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'expected_seat_count' => 'integer',
        'background_width' => 'integer',
        'background_height' => 'integer',
        'published_at' => 'datetime',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections()
    {
        return $this->hasMany(VenueMapSection::class);
    }

    public function rows()
    {
        return $this->hasMany(VenueMapRow::class);
    }

    public function seats()
    {
        return $this->hasMany(VenueMapSeat::class);
    }

    public function setting()
    {
        return $this->hasOne(VenueMapTemplateSetting::class, 'venue_map_template_id');
    }

    public function getNumberingModeAttribute()
    {
        return $this->setting ? $this->setting->numbering_mode : 'section';
    }

    public function getCountingDirectionAttribute()
    {
        return $this->setting ? $this->setting->counting_direction : 'left_to_right';
    }

    public function getLayoutStyleAttribute()
    {
        return $this->setting ? ($this->setting->layout_style ?? 'curved') : 'curved';
    }

    public function getShowRowNameAttribute()
    {
        return $this->setting ? (bool) $this->setting->show_row_name : false;
    }

    public function getSeatShapeAttribute()
    {
        return $this->setting ? ($this->setting->seat_shape ?: null) : null;
    }

    public function getFocalXAttribute()
    {
        return $this->setting ? ($this->setting->focal_x ?? 50.0) : 50.0;
    }

    public function getFocalYAttribute()
    {
        return $this->setting ? ($this->setting->focal_y ?? 10.0) : 10.0;
    }

    public function eventVenueMaps()
    {
        return $this->hasMany(EventVenueMap::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isReadyToPublish()
    {
        $plottedSeats = $this->seats()
            ->whereNotNull('x_percent')
            ->whereNotNull('y_percent')
            ->count();

        return $plottedSeats === (int) $this->expected_seat_count;
    }
}
