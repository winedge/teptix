<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizerTax extends Model
{
    use HasFactory;

    protected $table = 'organizer_taxes';

    protected $fillable = [
        'user_id',
        'ticket_id',
        'name',
        'amount_type',
        'price',
        'status',
        'allow_all_bill',
        'approval_status',
        'rejection_reason',
        'tax_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
