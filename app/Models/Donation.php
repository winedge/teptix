<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'donations';

    protected $fillable = [
        'app_user_id',
        'guest_user_id',
        'event_id',
        'amount',
        'transaction_id',
        'payment_intent_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Returns the prefixed donation ID string: don_1, don_2, etc.
     */
    public function getDonationIdAttribute(): string
    {
        return 'don_' . $this->id;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function guestUser()
    {
        return $this->belongsTo(GuestUser::class);
    }

    public function stripeTransaction()
    {
        return $this->hasOne(StripeTransaction::class, 'donation_id', 'id')
            ->whereRaw("donation_id = CONCAT('don_', donations.id)");
    }
}
