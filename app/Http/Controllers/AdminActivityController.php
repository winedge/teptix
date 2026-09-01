<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AdminActivityController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user() || (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('Organizer'))) {
            abort(403, 'Unauthorized action.');
        }

        $activityTypes = [
            AdminActivityLog::EVENT_CREATED => __('Event Created'),
            AdminActivityLog::EVENT_DELETED => __('Event Deleted'),
            AdminActivityLog::PROMOTION_CREATED => __('Promotion Created'),
            AdminActivityLog::TICKET_CREATED => __('Ticket Created'),
            AdminActivityLog::TICKET_DELETED => __('Ticket Deleted'),
        ];

        $activities = collect();

        if (Schema::hasTable('admin_activity_logs')) {
            $activityQuery = AdminActivityLog::with('actor')->orderBy('created_at', 'DESC');

            if (Auth::user()->hasRole('Organizer')) {
                $activityQuery->where('actor_user_id', Auth::id());
            }

            if ($request->filled('activity_type')) {
                $activityQuery->where('activity_type', $request->activity_type);
            }

            $activities = $activityQuery->paginate(25, ['*'], 'activity_page')->appends($request->query());
        }

        return view('admin.activity.index', compact('activities', 'activityTypes'));
    }
}
