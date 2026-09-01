<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminActivityLog extends Model
{
    use HasFactory;

    public const EVENT_CREATED = 'event_created';
    public const EVENT_DELETED = 'event_deleted';
    public const PROMOTION_CREATED = 'promotion_created';
    public const TICKET_CREATED = 'ticket_created';
    public const TICKET_DELETED = 'ticket_deleted';
    public const MANAGER_CREATED = 'manager_created';
    public const SCANNER_CREATED = 'scanner_created';

    protected $fillable = [
        'actor_user_id',
        'activity_type',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function record(string $activityType, ?Model $subject, string $title, ?string $description = null, array $details = [], ?Request $request = null): void
    {
        if (!Schema::hasTable('admin_activity_logs')) {
            return;
        }

        try {
            self::create([
                'actor_user_id' => Auth::id(),
                'activity_type' => $activityType,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->getKey() : null,
                'title' => $title,
                'description' => $description,
                'details' => $details,
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request ? (string) $request->userAgent() : null,
            ]);
        } catch (Throwable $th) {
            Log::warning('Unable to record admin activity log.', [
                'activity_type' => $activityType,
                'subject_id' => $subject ? $subject->getKey() : null,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
