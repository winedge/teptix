<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'amount_type',
        'price',
        'status',
        'allow_all_bill',
        'is_default',
        'created_by',
        'updated_by',
        'approval_status',
        'rejection_reason',
    ];

    protected $table = 'tax';

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function isPending()   { return $this->approval_status === 'pending'; }
    public function isApproved()  { return $this->approval_status === 'approved'; }
    public function isRejected()  { return $this->approval_status === 'rejected'; }
}
