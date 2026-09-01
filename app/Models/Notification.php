<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'organizer_id',
        'order_id',
        'title',
        'message',
    ];

    protected $table = 'notification';
    protected $appends = ['user','event'];

    public function getUserAttribute()
    {
        $order = Order::find($this->attributes['order_id']);
        if($order){
            $user =  AppUser::find($order->customer_id,['id','name','last_name','image']);
            if($user){
                return $user;
            }
            return null;
        }
        return null;
    }

    public function getEventAttribute()
    {
        $order = Order::find($this->attributes['order_id']);
        if (!$order) {
            return null;
        }

        $event = Event::find($order->event_id, ['id', 'name', 'image']);
        if (!$event) {
            return null;
        }

        // Avoid calling makeHidden() on null.
        return $event->makeHidden(['totalTickets', 'soldTickets', 'rate']);
    }

}

