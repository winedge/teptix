<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StripeTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaction_stripe';

    protected $fillable = [
        'payment_id',
        'order_id',
        'donation_id',
        'amount',
        'client_secret',
        'currency',
        'latest_charge',
        'txn_id',
        'tax_amount',
        'payment_method_types',
        'status',
        'full_response'
    ];

    protected $casts = [
        'payment_method_types' => 'array',
        'full_response' => 'array',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
