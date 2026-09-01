<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount_type',
        'price',
        'status',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $table = 'fee_types';

    protected $casts = [
        'price' => 'float',
    ];
}
