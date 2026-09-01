<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sponsership extends Model
{
    protected $table = 'sponsership';

    protected $fillable = [
        'name',
        'details',
    ];

    // One Sponsership has many SeatTables
    public function seatTables()
    {
        return $this->hasMany(SeatTable::class, 'sponsership_id');
    }
}
