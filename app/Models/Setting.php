<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'general_settng';
    protected $appends = ['imagePath'];
    protected $guarded  = [];

    public function getImagePathAttribute()
    {
        return url('images/upload') . '/';
    }

    public function getTimezoneAttribute($value)
    {
        return self::normalizeTimezone($value);
    }

    public function setTimezoneAttribute($value)
    {
        $this->attributes['timezone'] = self::normalizeTimezone($value);
    }

    public static function normalizeTimezone($timezone)
    {
        $timezone = trim((string) $timezone);

        $aliases = [
            'US/Arizona' => 'America/Phoenix',
        ];

        $timezone = $aliases[$timezone] ?? $timezone;

        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            return 'UTC';
        }

        return $timezone;
    }
}
