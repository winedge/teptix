<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerPermission extends Model
{
    protected $table = 'manager_permissions';

    protected $fillable = [
        'user_id',
        'permission',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
