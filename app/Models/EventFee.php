<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'fee_type_id',
    ];

    protected $table = 'event_fees';

    public function feeType()
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id');
    }
}
