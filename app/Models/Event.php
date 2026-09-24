<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'user_id',
        'type',
        'address',
        'category_id',
        'start_time',
        'end_time',
        'event_logo',
        'event_logos',
        'image',
        'thumbnail',
        'image_2',
        'gallery',
        'people',
        'lat',
        'lang',
        'description',
        'security',
        'status',
        'event_status',
        'is_deleted',
        'is_featured',
        'meta_pixel_id',
        'scanner_id',
        'tags',
        'url',
    ];

    protected $table = 'events';
    protected $dates = ['start_time', 'end_time'];
    protected $appends = ['imagePath', 'rate', 'totalTickets', 'soldTickets'];

    public function category()
    {
        return $this->hasOne('App\Models\Category', 'id', 'category_id');
    }

    public function organization()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function getOrganizationsAttribute()
    {
        $ids = array_filter(array_map('trim', explode(',', (string)($this->user_id ?? ''))));
        if (empty($ids)) {
            return collect();
        }
        return \App\Models\User::whereIn('id', $ids)->get();
    }
    public function ticket()
    {
        return $this->hasMany('App\Models\Ticket', 'event_id', 'id');
    }

    public function venueMaps()
    {
        return $this->hasMany(EventVenueMap::class, 'event_id', 'id');
    }

    public function liveVenueMap()
    {
        return $this->hasOne(EventVenueMap::class, 'event_id', 'id')
            ->where('status', EventVenueMap::STATUS_LIVE);
    }

    public function venueSeats()
    {
        return $this->hasMany(EventVenueSeat::class, 'event_id', 'id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function video()
    {
        return $this->hasOne(Video::class);
    }

    public function getImagePathAttribute()
    {
        return url('images/upload') . '/';
    }

    public function getTotalTicketsAttribute()
    {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        return intval(Ticket::where([['event_id', $this->attributes['id']], ['is_deleted', 0], ['status', 1], ['end_time', '>=', $date->format('Y-m-d H:i:s')], ['start_time', '<=', $date->format('Y-m-d H:i:s')]])->sum('quantity'));
    }



    public function getSoldTicketsAttribute()
{
    $orders = Order::where('event_id', $this->attributes['id'])
        ->whereNotNull('quantity')
        ->where('quantity', '!=', '')
        ->get();

    return $orders->sum(function ($order) {
        // Remove everything except numbers (digits)
        $cleaned = preg_replace('/\D/', '', $order->quantity);
        return intval($cleaned);
    });
}



    public function getRateAttribute()
    {
        $review =  Review::where('event_id', $this->attributes['id'])->get(['rate']);
        if (count($review) > 0) {
            $totalRate = 0;
            foreach ($review as $r) {
                $totalRate = $totalRate + $r->rate;
            }
            return  round($totalRate / count($review));
        } else {
            return 0;
        }
    }

    public function scopeDurationData($query, $start, $end)
    {
        $data =  $query->whereBetween('start_time', [$start,  $end]);
        return $data;
    }
}
