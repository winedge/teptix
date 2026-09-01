<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'customer_id',
        'organization_id',
        'event_id',
        'ticket_id',
        'coupon_id',
        'quantity',
        'coupon_discount',
        'payment',
        'tax',
        'org_commission',
        'payment_type',
        'payment_status',
        'payment_token',
        'order_status',
        'org_pay_status',
        'ticket_price',
        'ticket_date',
        'checkins_count',
        'seat_details',
        'book_seats',
        'guestuser_id',
        'admin_revenue',
        'org_revenue',
        'tax_option',
        'tax_custom_amount',
        'tax_data',
    ];

    protected $table = 'orders';

    protected $appends = ['review'];

    public function event()
    {
        return $this->hasOne('App\Models\Event', 'id', 'event_id');
    }
    public function getTicketIdsAttribute()
    {
        // Convert comma-separated string to array of IDs
        return explode(',', $this->ticket_id);
    }

    // Define a method to retrieve related Ticket models
    public function tickets()
    {
        // Retrieve tickets based on ticket_ids
        return Ticket::whereIn('id', $this->getTicketIdsAttribute())->get();
    }
    public function ticket()
    {
        return $this->hasOne('App\Models\Ticket', 'id', 'ticket_id');
    }
    public function customer()
    {
        return $this->guestuser_id ? $this->guestUser() : $this->appUser();

        // return $this->hasOne('App\Models\AppUser', 'id', 'customer_id');
    }
    public function organization()
    {
        return $this->hasOne('App\Models\User', 'id', 'organization_id');
    }

    public function orderChild()
    {
        return $this->hasMany('App\Models\OrderChild', 'order_id', 'id');
    }

    public function ordertax()
    {
        return $this->hasOne('App\Models\OrderTax', 'id', 'organization_id');
    }

    public function getReviewAttribute()
    {
        return Review::where('order_id',$this->attributes['id'])->first();
    }

    // Define the AppUser relationship
    public function appUser()
    {
        return $this->hasOne('App\Models\AppUser', 'id', 'customer_id');
    }

    // Define the GuestUser relationship
    public function guestUser()
    {
        return $this->hasOne('App\Models\GuestUser', 'id', 'guestuser_id');
    }



    // Keep this for backward compatibility if needed elsewhere
    public function allorderchild()
    {
        return $this->hasMany(OrderChild::class, 'order_id', 'id');
    }

    // Stripe Transaction relationship
    public function stripeTransaction()
    {
        return $this->hasOne('App\Models\StripeTransaction', 'order_id', 'id');
    }

    protected $casts = [
        'ticket_id' => 'array',
        'quantity' => 'array',
        'admin_revenue' => 'float',
        'org_revenue' => 'float',
        'tax' => 'float',
        'payment' => 'float',
        'org_commission' => 'float',
    ];



}
