<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatTable extends Model
{
    protected $table = 'seat_table';

    protected $fillable = [
        'name_of_table',
        'number_seat',
        'sponsership_id',
        'prefixname',
        'user_id',
    ];

    // One SeatTable belongs to one Sponsership
    public function sponsership()
    {
        return $this->belongsTo(Sponsership::class, 'sponsership_id');
    }
    
}
