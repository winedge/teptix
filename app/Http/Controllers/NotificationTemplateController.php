<?php

namespace App\Http\Controllers;

use App\Mail\EventNotification;
use App\Models\{AppUser,GuestUser};
use App\Models\NotificationTemplate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Order;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use OneSignal;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('notification_template_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $data = NotificationTemplate::OrderBy('id', 'DESC')->get();
        return view('admin.template.index', compact('data'));
    }

    public function create()
    {
        abort_if(Gate::denies('notification_template_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.template.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'bail|required',
            'subject' => 'bail|required',
        ]);
        $data = $request->all();
        NotificationTemplate::create($data);
        return redirect()->route('notification-template.index')->withStatus(__('Template has added successfully.'));
    }

    public function edit(NotificationTemplate $notificationTemplate)
    {
        abort_if(Gate::denies('notification_template_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return response()->json(['success' => true, 'data' => $notificationTemplate], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subject' => 'bail|required',
        ]);
        $data = $request->all();
        NotificationTemplate::find($id)->update($data);
        return redirect()->route('notification-template.index')->withStatus(__('Template has update successfully.'));
    }


    public function notification()
    {
        $notification = Notification::where('organizer_id',Auth::user()->id)->orderBy('id', 'DESC')->get();
        return view('admin.notification', compact('notification'));
    }
    public function getNotification()
    {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $user = User::get();
        $appuser = AppUser::get();

        // Show events depending on user role:
        // - Admins: see every non-deleted event (all statuses, past and future)
        // - Non-admins: only their pending events that haven't ended yet
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $events = Event::with(['category:id,name'])
                ->where('is_deleted', 0)
                ->get();
        } else {
            $events = Event::with(['category:id,name'])
                ->where([
                    ['is_deleted', 0],
                    ['event_status', 'Pending'],
                    ['end_time', '>', $date->toDateTimeString()],
                ])
                ->whereRaw('FIND_IN_SET(?, user_id)', [Auth::id()])
                ->get();
        }

        return view('admin.getNotification',compact('user','appuser','events'));
    }
    // public function sendNotification(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required',
    //         'description' => 'required',

    //     ]);

    //     $userId = User::whereNotNull('device_token')->pluck('device_token')->all();

    //     if ($request->has('organizer_ids')) {
    //         foreach ($request->organizer_ids as $org) {
    //             $organizer = User::find($org);
    //             if ($organizer) {
    //                 (new AppHelper)->sendOneSignal('organizer', $organizer->device_token, $request->description);
    //             }
    //         }
    //     }

    //     if ($request->has('user_ids')) {
    //         foreach ($request->user_ids as $user) {
    //             $appUser = AppUser::find($user);
    //             if ($appUser) {
    //                 (new AppHelper)->sendOneSignal('organizer', $appUser->device_token, $request->description);
    //             }
    //         }
    //     }
    //     return redirect()->back()->withStatus(__('Notification sent successfully.'));

    //     $data = [
    //         "registration_ids" => $userId,
    //         "notification" => [
    //             "title" => $request->title,
    //             "description" => $request->description,
    //             "image" => $request->image,
    //         ]
    //     ];

    //     try {
    //         OneSignal::sendNotificationToAll(null, $url = null, $data, $buttons = null, $schedule = null);
    //         return redirect()->back()->withStatus(__('Notification sent successfully.'));
    //     } catch (Exception $e) {
    //         return redirect()->back()->withErrors(['message' => 'Error: ' . $e->getMessage()]);
    //     }
    // }
   /**
 * Send notification emails to AppUsers and GuestUsers in batches.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function sendNotification(Request $request)
{
    set_time_limit(0);

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'event_id' => 'required|exists:events,id',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $setting = Setting::first();
    if (!$setting) {
        return response()->json(['error' => 'Mail settings not configured.'], 500);
    }

    if (!$this->isMailServerReachable($setting->mail_host, $setting->mail_port)) {
        return response()->json([
            'error' => 'Mail server is not reachable. Please check SMTP settings or network.'
        ], 500);
    }

    Config::set('mail.mailers.smtp', [
        'transport'  => 'smtp',
        'host'       => $setting->mail_host,
        'port'       => $setting->mail_port,
        'encryption' => $setting->mail_encryption,
        'username'   => $setting->mail_username,
        'password'   => $setting->mail_password,
    ]);
    Config::set('mail.default', 'smtp');
    Config::set('mail.from.address', $setting->mail_from_address ?: 'noreply@example.com');
    Config::set('mail.from.name', $setting->mail_from_name ?: config('app.name'));

    // ✅ Check if event exists and is not deleted
    $event = DB::table('events')
        ->where([
            ['id', $request->event_id],
            ['is_deleted', 0]
        ])
        ->first();

    if (!$event) {
        return response()->json(['error' => 'Event not found or has been deleted.'], 400);
    }

    // ✅ Get ticket IDs for the selected event
    $ticketIds = DB::table('tickets')
        ->where('event_id', $request->event_id)
        ->pluck('id')
        ->toArray();

    if (empty($ticketIds)) {
        return response()->json(['error' => 'No tickets found for the selected event.'], 400);
    }

    // ✅ Get AppUser emails based on orders with matching ticket_id
    $appUserEmails = AppUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('customer_id')
              ->from('orders')
              ->whereNotNull('customer_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('email')
      ->pluck('email')
      ->unique()
      ->filter(function ($email) {
          return filter_var($email, FILTER_VALIDATE_EMAIL);
      });

    // ✅ Get GuestUser emails based on orders with matching ticket_id
    $guestUserEmails = GuestUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('guestuser_id')
              ->from('orders')
              ->whereNotNull('guestuser_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('email')
      ->pluck('email')
      ->unique()
      ->filter(function ($email) {
          return filter_var($email, FILTER_VALIDATE_EMAIL);
      });

    // 🧪 Merge and clean email list
    $emails = $appUserEmails->merge($guestUserEmails)
        ->unique()
        ->values()
        ->toArray();

    if (empty($emails)) {
        return response()->json(['error' => 'No valid emails found for the selected event tickets.'], 400);
    }

    // 🖼️ Handle image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('notifications', 'public');
        $imagePath = Storage::url($path);
    }

    // 📦 Email content
    $data = [
        'title' => $request->title,
        'description' => $request->description,
        'event_id' => $request->event_id,
        'image' => $imagePath,
    ];

    // ✉️ Send emails in chunks
    $chunks = array_chunk($emails, 10);
    $failed = [];

    foreach ($chunks as $index => $batch) {
        foreach ($batch as $email) {
            try {
                Mail::to($email)->send(new EventNotification($data));
            } catch (\Exception $e) {
                $failed[] = [
                    'email' => $email,
                    'error' => $e->getMessage()
                ];
            }
        }

        if ($index < count($chunks) - 1) {
            sleep(10);
        }
    }

    if (count($failed)) {
        return response()->json([
            'status' => 'Partial success: some emails failed to send.',
            'failed' => $failed
        ], 207);
    }

    // 🔥 Send FCM notifications to mobile devices
    $firebaseService = new FirebaseService();

    // 🐛 Debug: Log ticket IDs for debugging
    \Log::info('FCM Debug - Ticket IDs:', $ticketIds);

    // Get AppUser FCM tokens
    $appUserTokens = AppUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('customer_id')
              ->from('orders')
              ->whereNotNull('customer_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->where(function($query) {
        $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '')
              ->orWhere(function($q) {
                  $q->whereNotNull('device_token')->where('device_token', '!=', '');
              });
    })->get()
      ->map(function($user) {
          return $user->fcm_token ?: $user->device_token;
      })
      ->filter()
      ->unique()
      ->values()
      ->toArray();

    // 🐛 Debug: Log found app user tokens
    \Log::info('FCM Debug - App User Tokens Found:', $appUserTokens);

    // Get GuestUser FCM tokens
    $guestUserTokens = GuestUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('guestuser_id')
              ->from('orders')
              ->whereNotNull('guestuser_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('fcm_token')
      ->where('fcm_token', '!=', '')
      ->pluck('fcm_token')
      ->unique()
      ->values()
      ->toArray();

    // 🐛 Debug: Log found guest user tokens
    \Log::info('FCM Debug - Guest User Tokens Found:', $guestUserTokens);

    // 🚨 FALLBACK: If no tokens found for specific tickets, send to ALL users with FCM tokens
    if (empty($appUserTokens) && empty($guestUserTokens)) {
        \Log::warning('FCM Debug - No tokens found for specific tickets, sending to all FCM users');

        // Get ALL app users with FCM tokens
        $allAppUserTokens = AppUser::where(function($query) {
            $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '')
                  ->orWhere(function($q) {
                      $q->whereNotNull('device_token')->where('device_token', '!=', '');
                  });
        })->get()
          ->map(function($user) {
              return $user->fcm_token ?: $user->device_token;
          })
          ->filter()
          ->unique()
          ->values()
          ->toArray();

        // Get ALL guest users with FCM tokens
        $allGuestUserTokens = GuestUser::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        $appUserTokens = $allAppUserTokens;
        $guestUserTokens = $allGuestUserTokens;

        \Log::info('FCM Debug - Fallback App User Tokens:', $appUserTokens);
        \Log::info('FCM Debug - Fallback Guest User Tokens:', $guestUserTokens);
    }

    // Merge all FCM tokens
    $allTokens = array_unique(array_merge($appUserTokens, $guestUserTokens));

    // 🐛 Debug: Log final token list
    \Log::info('FCM Debug - Final Token List:', $allTokens);
    \Log::info('FCM Debug - Total Tokens Count:', [count($allTokens)]);

    $fcmResult = ['success' => 0, 'failure' => 0, 'errors' => []];
    if (!empty($allTokens)) {
        \Log::info('FCM Debug - Sending notifications to ' . count($allTokens) . ' tokens');
        $fcmResult = $firebaseService->sendNotificationToTokens($allTokens, $data);
        \Log::info('FCM Debug - Send Result:', $fcmResult);
    } else {
        \Log::warning('FCM Debug - No FCM tokens found to send notifications');
    }

    return response()->json([
        'status' => 'Notification sent to all users in batches.',
        'email_stats' => [
            'total_emails_found' => count($emails),
            'emails_sent' => count($emails) - count($failed),
            'email_failures' => count($failed)
        ],
        'fcm_stats' => [
            'total_tokens_found' => count($allTokens),
            'tokens_sent' => count($allTokens),
            'fcm_success' => $fcmResult['success'],
            'fcm_failures' => $fcmResult['failure']
        ],
        'summary' => [
            'total_recipients' => count(array_unique(array_merge($emails, $allTokens))),
            'app_users_notified' => count($appUserEmails),
            'guest_users_notified' => count($guestUserEmails)
        ]
    ]);
}

/**
 * Send notification emails to all clients of the organizer across all their events.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function sendToAllClients(Request $request)
{
    set_time_limit(0);

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $setting = Setting::first();
    if (!$setting) {
        return response()->json(['error' => 'Mail settings not configured.'], 500);
    }

    if (!$this->isMailServerReachable($setting->mail_host, $setting->mail_port)) {
        return response()->json([
            'error' => 'Mail server is not reachable. Please check SMTP settings or network.'
        ], 500);
    }

    Config::set('mail.mailers.smtp', [
        'transport'  => 'smtp',
        'host'       => $setting->mail_host,
        'port'       => $setting->mail_port,
        'encryption' => $setting->mail_encryption,
        'username'   => $setting->mail_username,
        'password'   => $setting->mail_password,
    ]);
    Config::set('mail.default', 'smtp');
    Config::set('mail.from.address', $setting->mail_from_address ?: 'noreply@example.com');
    Config::set('mail.from.name', $setting->mail_from_name ?: config('app.name'));

    // ✅ Get all non-deleted events for the current organizer
    $organizerEvents = DB::table('events')
        ->where([
            ['user_id', Auth::id()],
            ['is_deleted', 0]
        ])
        ->pluck('id')
        ->toArray();

    if (empty($organizerEvents)) {
        return response()->json(['error' => 'No events found for this organizer.'], 400);
    }

    // ✅ Get all ticket IDs for organizer's events
    $ticketIds = DB::table('tickets')
        ->whereIn('event_id', $organizerEvents)
        ->pluck('id')
        ->toArray();

    if (empty($ticketIds)) {
        return response()->json(['error' => 'No tickets found for organizer events.'], 400);
    }

    // ✅ Get AppUser emails based on orders with matching ticket_id
    $appUserEmails = AppUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('customer_id')
              ->from('orders')
              ->whereNotNull('customer_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('email')
      ->pluck('email')
      ->unique()
      ->filter(function ($email) {
          return filter_var($email, FILTER_VALIDATE_EMAIL);
      });

    // ✅ Get GuestUser emails based on orders with matching ticket_id
    $guestUserEmails = GuestUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('guestuser_id')
              ->from('orders')
              ->whereNotNull('guestuser_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('email')
      ->pluck('email')
      ->unique()
      ->filter(function ($email) {
          return filter_var($email, FILTER_VALIDATE_EMAIL);
      });

    // 🧪 Merge and clean email list
    $emails = $appUserEmails->merge($guestUserEmails)
        ->unique()
        ->values()
        ->toArray();

    if (empty($emails)) {
        return response()->json(['error' => 'No valid emails found for any organizer events.'], 400);
    }

    // 🖼️ Handle image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('notifications', 'public');
        $imagePath = Storage::url($path);
    }

    // 📦 Email content
    $data = [
        'title' => $request->title,
        'description' => $request->description,
        'event_id' => null, // No specific event for "all clients"
        'image' => $imagePath,
    ];

    // ✉️ Send emails in chunks
    $chunks = array_chunk($emails, 10);
    $failed = [];

    foreach ($chunks as $index => $batch) {
        foreach ($batch as $email) {
            try {
                Mail::to($email)->send(new EventNotification($data));
            } catch (\Exception $e) {
                $failed[] = [
                    'email' => $email,
                    'error' => $e->getMessage()
                ];
            }
        }

        if ($index < count($chunks) - 1) {
            sleep(10);
        }
    }

    if (count($failed)) {
        return response()->json([
            'status' => 'Partial success: some emails failed to send to all clients.',
            'failed' => $failed,
            'total_events' => count($organizerEvents),
            'total_emails_sent' => count($emails) - count($failed)
        ], 207);
    }

    // 🔥 Send FCM notifications to mobile devices for all clients
    $firebaseService = new FirebaseService();

    // Get AppUser FCM tokens for all organizer events
    $appUserTokens = AppUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('customer_id')
              ->from('orders')
              ->whereNotNull('customer_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->where(function($query) {
        $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '')
              ->orWhere(function($q) {
                  $q->whereNotNull('device_token')->where('device_token', '!=', '');
              });
    })->get()
      ->map(function($user) {
          return $user->fcm_token ?: $user->device_token;
      })
      ->filter()
      ->unique()
      ->values()
      ->toArray();

    // Get GuestUser FCM tokens for all organizer events
    $guestUserTokens = GuestUser::whereIn('id', function ($query) use ($ticketIds) {
        $query->select('guestuser_id')
              ->from('orders')
              ->whereNotNull('guestuser_id')
              ->where(function ($q) use ($ticketIds) {
                  foreach ($ticketIds as $id) {
                      $q->orWhere('ticket_id', 'LIKE', '%"' . $id . '"%')
                         ->orWhere('ticket_id', 'LIKE', '%' . $id . '%');
                  }
              });
    })->whereNotNull('fcm_token')
      ->where('fcm_token', '!=', '')
      ->pluck('fcm_token')
      ->unique()
      ->values()
      ->toArray();

    // Merge all FCM tokens
    $allTokens = array_unique(array_merge($appUserTokens, $guestUserTokens));

    $fcmResult = ['success' => 0, 'failure' => 0];
    if (!empty($allTokens)) {
        $fcmResult = $firebaseService->sendNotificationToTokens($allTokens, $data);
    }

    return response()->json([
        'status' => 'Notification sent to all clients across all organizer events.',
        'total_events' => count($organizerEvents),
        'email_stats' => [
            'total_emails_found' => count($emails),
            'emails_sent' => count($emails) - count($failed),
            'email_failures' => count($failed)
        ],
        'fcm_stats' => [
            'total_tokens_found' => count($allTokens),
            'tokens_sent' => count($allTokens),
            'fcm_success' => $fcmResult['success'],
            'fcm_failures' => $fcmResult['failure']
        ],
        'summary' => [
            'total_recipients' => count(array_unique(array_merge($emails, $allTokens))),
            'app_users_notified' => count($appUserEmails),
            'guest_users_notified' => count($guestUserEmails)
        ]
    ]);
}

/**
 * Send notification to specific email addresses.
 * If users exist in AppUser or GuestUser tables, also send push notifications to their mobile devices.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function sendNotificationByEmail(Request $request)
{
    set_time_limit(0);

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'specific_emails' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $setting = Setting::first();
    if (!$setting) {
        return response()->json(['error' => 'Mail settings not configured.'], 500);
    }

    if (!$this->isMailServerReachable($setting->mail_host, $setting->mail_port)) {
        return response()->json([
            'error' => 'Mail server is not reachable. Please check SMTP settings or network.'
        ], 500);
    }

    // Configure mail settings
    Config::set('mail.mailers.smtp', [
        'transport'  => 'smtp',
        'host'       => $setting->mail_host,
        'port'       => $setting->mail_port,
        'encryption' => $setting->mail_encryption,
        'username'   => $setting->mail_username,
        'password'   => $setting->mail_password,
    ]);
    Config::set('mail.default', 'smtp');
    Config::set('mail.from.address', $setting->mail_from_address ?: 'noreply@example.com');
    Config::set('mail.from.name', $setting->mail_from_name ?: config('app.name'));

    // Parse and validate email addresses
    $emailString = $request->specific_emails;
    $emailArray = array_map('trim', explode(',', $emailString));

    // Filter valid emails
    $validEmails = array_filter($emailArray, function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    });

    if (empty($validEmails)) {
        return response()->json(['error' => 'No valid email addresses provided.'], 400);
    }

    // Handle image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('notifications', 'public');
        $imagePath = Storage::url($path);
    }

    // Email content
    $data = [
        'title' => $request->title,
        'description' => $request->description,
        'image' => $imagePath,
    ];

    // Send emails
    $failed = [];
    foreach ($validEmails as $email) {
        try {
            Mail::to($email)->send(new EventNotification($data));
        } catch (\Exception $e) {
            $failed[] = [
                'email' => $email,
                'error' => $e->getMessage()
            ];
        }
    }

    // Find users with these emails and send push notifications
    \Log::info('FCM Debug - Searching for users with emails:', $validEmails);

    $firebaseService = new FirebaseService();
    $mobileTokens = [];
    $appUserCount = 0;
    $guestUserCount = 0;

    // Check AppUsers - users with mobile app accounts
    $appUsers = AppUser::whereIn('email', $validEmails)->get();
    \Log::info('FCM Debug - Total AppUsers found: ' . $appUsers->count());

    foreach ($appUsers as $user) {
        \Log::info('FCM Debug - AppUser Email: ' . $user->email . ', FCM Token: ' . substr($user->fcm_token ?? 'null', 0, 20) . '..., Device Token: ' . substr($user->device_token ?? 'null', 0, 20) . '...');

        $token = $user->fcm_token ?: $user->device_token;
        if ($token && trim($token) != '') {
            $mobileTokens[] = trim($token);
            $appUserCount++;
            \Log::info('FCM Debug - Added token for user: ' . $user->email);
        }
    }

    // Check GuestUsers - guest accounts with FCM tokens
    $guestUsers = GuestUser::whereIn('email', $validEmails)
        ->whereNotNull('fcm_token')
        ->where('fcm_token', '!=', '')
        ->get();

    \Log::info('FCM Debug - Total GuestUsers found: ' . $guestUsers->count());

    foreach ($guestUsers as $guest) {
        if ($guest->fcm_token && trim($guest->fcm_token) != '') {
            $mobileTokens[] = trim($guest->fcm_token);
            $guestUserCount++;
            \Log::info('FCM Debug - Added FCM token for guest: ' . $guest->email);
        }
    }

    // Remove duplicates
    $mobileTokens = array_unique($mobileTokens);
    $mobileTokens = array_values($mobileTokens); // Re-index array

    \Log::info('FCM Debug - Total unique tokens to send: ' . count($mobileTokens));
    \Log::info('FCM Debug - Tokens: ' . json_encode(array_map(function($t) { return substr($t, 0, 30) . '...'; }, $mobileTokens)));

    // Send push notifications via Firebase
    $fcmResult = ['success' => 0, 'failure' => 0, 'errors' => []];
    if (!empty($mobileTokens)) {
        \Log::info('FCM Debug - Sending FCM notifications to ' . count($mobileTokens) . ' token(s)');
        $fcmResult = $firebaseService->sendNotificationToTokens($mobileTokens, $data);
        \Log::info('FCM Debug - FCM Result: ' . json_encode($fcmResult));
    } else {
        \Log::warning('FCM Debug - No FCM tokens found for the provided emails');
    }

    return response()->json([
        'status' => 'Notification sent to specific email addresses.',
        'email_stats' => [
            'total_emails' => count($validEmails),
            'emails_sent' => count($validEmails) - count($failed),
            'email_failures' => count($failed)
        ],
        'mobile_stats' => [
            'users_found' => $appUserCount + $guestUserCount,
            'tokens_found' => count($mobileTokens),
            'push_sent' => $fcmResult['success'],
            'push_failures' => $fcmResult['failure']
        ],
        'failed' => $failed,
        'debug' => [
            'app_users_found' => $appUserCount,
            'guest_users_found' => $guestUserCount,
            'fcm_errors' => $fcmResult['errors'] ?? []
        ]
    ]);
}

private function isMailServerReachable($host, $port = 587, $timeout = 5)
{
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$connection) {
        return false;
    }
    fclose($connection);
    return true;
}



    public function deleteNotification($id)
    {
        $data = Notification::find($id);
        $data->delete();
        return redirect()->back();
    }
    public function markAllAsRead()
    {
        $notification = Notification::where('status', 1)->get();
        if (isset($notification)) {
            DB::table('notification')->update(['status' => 0]);
            return redirect()->back();
        }
    }
}
