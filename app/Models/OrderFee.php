<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'fee_type_id',
        'name',
        'amount',
        'price',
        'amount_type',
    ];

    protected $table = 'order_fees';

    protected $casts = [
        'amount' => 'float',
        'price' => 'float',
    ];
}
