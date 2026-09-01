<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_name',
        'first_name',
        'last_name',
        'email',
        'password',
        'status',
        'phone',
        'image',
        'device_token',
        'org_id',
        'bio',
        'country',
        'language',
        'is_verify',
        'onboarding_completed_at',
        'deleted_softaccount',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];
    protected $appends = ['followers', 'imagePath'];

    public function getFollowersAttribute()
    {
        if (!isset($this->attributes['id'])) {
            return [];
        }
        $appuser = AppUser::get();
        $followers = array();
        foreach ($appuser as $user) {
            if (in_array($this->attributes['id'], array_filter(explode(',', (string) ($user->following ?? ''))))) {
                array_push($followers, $user->id);
            }
        }
        return $followers;
    }

    public function getImagePathAttribute()
    {
        return url('images/upload') . '/' . ($this->attributes['image'] ?? '');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'org_id');
    }


    public function getFullNameAttribute()
    {
        return ucfirst($this->first_name) . ' ' . ucfirst($this->last_name);
    }

    public function managerPermissions()
    {
        return $this->hasMany(\App\Models\ManagerPermission::class, 'user_id');
    }

    public function hasManagerPermission($permission)
    {
        if ($this->hasRole('admin') || $this->hasRole('Organizer')) {
            return true;
        }
        if (!$this->hasRole('Manager')) {
            return false;
        }
        return \DB::table('manager_permissions')
            ->where('user_id', $this->id)
            ->where('permission', $permission)
            ->exists();
    }

}
