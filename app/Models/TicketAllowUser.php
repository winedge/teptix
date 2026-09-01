<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAllowUser extends Model
{
    use HasFactory;

    protected $table = 'ticket_allow_users';

    protected $fillable = [
        'ticket_id',
        'allow_to_user',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }
}
