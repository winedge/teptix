<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\AppUser;
use App\Models\User;
use App\Models\Event;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderTax;
use App\Models\Tax;
use App\Models\Review;
use App\Models\OrderChild;
use App\Http\Controllers\AppHelper;
use App\Models\PaymentSetting;
use App\Models\Ticket;
use App\Models\Currency;
use App\Models\Setting;
use App\Mail\ResetPassword;
use App\Mail\TicketBook;
use App\Mail\TicketBookOrg;
use App\Models\CouponUsageHistory;
use App\Models\NotificationTemplate;
use App\Models\Notification;
use App\Models\EventReport;
use App\Models\EventVenueMap;
use App\Models\EventVenueSeat;
use App\Models\EventVenueRow;
use App\Models\EventVenueSection;
use App\Models\Module;
use App\Models\Banner;
use App\Models\GuestUser;
use App\Models\GuestOtp;
use App\Models\StripeTransaction;
use App\Services\FirebaseService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Twilio\Rest\Client as Clients;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Stripe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class ApiController extends Controller
{
    public function __construct()
    {
        (new AppHelper)->mailConfig();
    }

    /**
     * Save base64 encoded image
     */
    private function saveBase64Image($base64String)
    {
        try {
            // Remove data:image prefix if exists
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
            } else {
                $type = 'png'; // default
            }

            $base64String = str_replace(' ', '+', $base64String);
            $imageData = base64_decode($base64String);

            if ($imageData === false) {
                throw new \Exception('Base64 decode failed');
            }

            $fileName = uniqid() . '.' . $type;
            $filePath = public_path('images/upload') . '/' . $fileName;

            // Create directory if not exists
            if (!file_exists(public_path('images/upload'))) {
                mkdir(public_path('images/upload'), 0755, true);
            }

            file_put_contents($filePath, $imageData);

            return $fileName;
        } catch (\Exception $e) {
            Log::error('Base64 image save failed: ' . $e->getMessage());
            return 'defaultuser.png';
        }
    }

    public function userLogin(Request $request)
    {

        $request->validate([
            'email' => 'bail|required|email',
            'provider' => 'bail|required',
            'password' => 'bail|required',
            'device_token' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);
        if ($request->provider == "LOCAL") {
            $userdata = array('email' => $request->email, 'status' => 1, 'password' => $request->password);
            if (Auth::guard('appuser')->attempt($userdata)) {
                $user = Auth::guard('appuser')->user();
                if ($user->is_verify == 0 &&  Setting::first()->user_verify == 1) {
                    if (Setting::first()->verify_by == 'email' && Setting::first()->mail_host != NULL) {
                        $details = [
                            'id' => $user->id,
                        ];
                        $setting = Setting::first();
                        $config = array(
                            'driver'     => $setting->mail_mailer,
                            'host'       => $setting->mail_host,
                            'port'       => $setting->mail_port,
                            'encryption' => $setting->mail_encryption,
                            'username'   => $setting->mail_username,
                            'password'   => $setting->mail_password
                        );
                        Config::set('mail', $config);
                        $details = [
                            'url' => url('user/VerificationConfirm/' .  $user->id)
                        ];
                        Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
                        return response()->json(['msg' => 'Verification link has been sent to your email. Please visit that link to complete the verification.', 'data' => $user, 'success' => true], 200);
                    }
                    if (Setting::first()->verify_by != 'email') {
                        $setting = Setting::first();
                        $otp = rand(100000, 999999);
                        $to = $user->phone;
                        $message = "Your phone verification code is $otp for $setting->app_name.";
                        $twilio_sid = $setting->twilio_account_id;
                        $twilio_token = $setting->twilio_auth_token;
                        $twilio_phone_number = $setting->twilio_phone_number;
                        $twilio = new Clients($twilio_sid, $twilio_token);
                        $twilio->messages->create(
                            $to,
                            [
                                'from' => $twilio_phone_number,
                                'body' => $message,
                            ]
                        );
                        $user = AppUser::find($user->id);
                        $user->otp = $otp;
                        $user->update();
                        return response()->json(['msg' => 'Phone verification code sent via SMS.', 'data' => $user, 'success' => true, 'otp' => $otp], 200);
                    }
                } else {
                    // Update both device_token and fcm_token
                    $updateData = [];
                    if ($request->has('device_token')) {
                        $updateData['device_token'] = $request->device_token;
                    }
                    if ($request->has('fcm_token')) {
                        $updateData['fcm_token'] = $request->fcm_token;
                    }
                    if (!empty($updateData)) {
                        AppUser::find($user->id)->update($updateData);
                    }
                    $user['token'] = $user->createToken('eventRight')->accessToken;
                    return response()->json(['msg' => 'Login successfully', 'data' => $user, 'success' => true], 200);
                }
            } else {
                return response()->json(['msg' => 'Invalid Username or password', 'data' => null, 'success' => false]);
            }
        }
    }

    public function userRegister(Request $request)
    {
        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:app_user|unique:users',
            'password' => 'bail|required|min:6',
            'phone' => 'bail|required',
            'Countrycode' => 'bail|required',
            'image' => 'bail|nullable|string', // Changed to accept base64 string
        ]);

        // Validate base64 image size (max 2MB = 2048KB)
        if ($request->has('image') && !empty($request->image)) {
            $imageSize = strlen(base64_decode($request->image));
            $maxSizeInBytes = 2048 * 1024; // 2MB in bytes

            if ($imageSize > $maxSizeInBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image size must not exceed 2MB. Current size: ' . round($imageSize / 1024) . 'KB',
                    'errors' => ['image' => ['The image must not be greater than 2048 kilobytes.']]
                ], 422);
            }
        }

        $data = $request->all();
        $verify = Setting::first()->user_verify == 1 ? 0 : 1;
        $data['password'] =  Hash::make($request->password);

        // Handle image upload (file or base64)
        if ($request->hasFile('image')) {
            $image_name = (new AppHelper)->saveApiImage($request);
            $data['image'] = $image_name;
        } elseif ($request->has('image') && !empty($request->image)) {
            // Handle base64 image
            $image_name = $this->saveBase64Image($request->image);
            $data['image'] = $image_name;
        } else {
            $data['image'] = "defaultuser.png";
        }

        $data['status'] = 1;
        $data['name'] = $request->first_name;
        $data['provider'] = "LOCAL";
        $data['language'] = Setting::first()->language;
        $data['is_verify'] = $verify;
        $data['phone'] = $request->Countrycode . $request->phone;
        $data['is_verify'] = $verify;
        $user = AppUser::create($data);
        if ($user->is_verify == 0) {
            if (Setting::first()->verify_by == 'email' && Setting::first()->mail_host != NULL) {
                $details = [
                    'id' => $user->id,
                ];
                $setting = Setting::first();
                $config = array(
                    'driver'     => $setting->mail_mailer,
                    'host'       => $setting->mail_host,
                    'port'       => $setting->mail_port,
                    'encryption' => $setting->mail_encryption,
                    'username'   => $setting->mail_username,
                    'password'   => $setting->mail_password
                );
                Config::set('mail', $config);

                $details = [
                    'url' => url('user/VerificationConfirm/' .  $user->id)
                ];
                Mail::to($user->email)->send(new \App\Mail\VerifyMail($details));
                return response()->json(['msg' => 'Verification link has been sent to your email. Please visit that link to complete the verification.', 'data' => $user, 'success' => true], 200);
            }
            if (Setting::first()->verify_by == 'phone') {
                $setting = Setting::first();
                $otp = rand(100000, 999999);
                $to = $user->phone;
                $message = "Your phone verification code is $otp for $setting->app_name.";
                if ($setting->enable_twillio == 1) {
                    $twilio_sid = $setting->twilio_account_id;
                    $twilio_token = $setting->twilio_auth_token;
                    $twilio_phone_number = $setting->twilio_phone_number;
                    try {
                        $twilio = new Clients($twilio_sid, $twilio_token);
                        $twilio->messages->create(
                            $to,
                            [
                                'from' => $twilio_phone_number,
                                'body' => $message,
                            ]
                        );
                    } catch (\Throwable $th) {
                        return redirect()->back()->with('error', 'Somthing Went Wrong');
                    }
                }
                if ($setting->enable_vonage == 1) {
                    $apiKey = $setting->vonege_api_key;
                    $apiSecret = $setting->vonage_account_secret;
                    $virtualNumber = $setting->vonage_sender_number;
                    $response = Http::post('https://rest.nexmo.com/sms/json', [
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                        'to' => $to,
                        'from' => $virtualNumber,
                        'text' => $message,
                    ]);
                }
                $user = AppUser::find($user->id);
                $user->otp = $otp;
                $user->update();
                return response()->json(['msg' => 'Phone verification code sent via SMS.', 'data' => $user, 'success' => true, 'otp' => $otp,], 200);
            }
        } else {
            $user['token'] = $user->createToken('eventRight')->accessToken;
        }
        return response()->json(['msg' => 'Registered successfully', 'data' => $user, 'success' => true], 200);
    }

    public function organization()
    {
        $users = User::role('Organizer')->where('status', 1)->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);

        foreach ($users as $value) {
            if (Auth::check()) {
                if (in_array(Auth::user()->id, $value->followers)) {
                    $value->isFollow = true;
                } else {
                    $value->isFollow = false;
                }
            } else {
                $value->isFollow = false;
            }
        }
        return response()->json(['msg' => null, 'data' => $users, 'success' => true], 200);
    }

    protected function resolveEventDateRange($dateInput, $timezone = null)
    {
        if (empty($dateInput) || strcasecmp($dateInput, 'All') === 0) {
            return null;
        }

        $tz = $timezone ?: (Setting::find(1)->timezone ?? 'UTC');
        $clean = trim(strtolower($dateInput));

        if ($clean === 'today') {
            $start = Carbon::now($tz)->format('Y-m-d') . ' 00:00:00';
            $end = Carbon::now($tz)->format('Y-m-d') . ' 23:59:59';
            return [$start, $end];
        }

        if ($clean === 'tomorrow' || $clean === 'tommorow') {
            $start = Carbon::tomorrow($tz)->format('Y-m-d') . ' 00:00:00';
            $end = Carbon::tomorrow($tz)->format('Y-m-d') . ' 23:59:59';
            return [$start, $end];
        }

        if (in_array($clean, ['this week', 'thisweek', 'this_week', 'week'])) {
            $start = Carbon::now($tz)->startOfWeek()->format('Y-m-d') . ' 00:00:00';
            $end = Carbon::now($tz)->endOfWeek()->format('Y-m-d') . ' 23:59:59';
            return [$start, $end];
        }

        try {
            $parsed = Carbon::parse($dateInput, $tz);
            $start = $parsed->format('Y-m-d') . ' 00:00:00';
            $end = $parsed->format('Y-m-d') . ' 23:59:59';
            return [$start, $end];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function events(Request $request)
    {
        $timezone = Setting::findOrFail(1)->timezone;
        $date = Carbon::now($timezone);

        $dateFilter = $request->query('date')
            ?? $request->query('searchDate')
            ?? $request->query('duration')
            ?? $request->query('filter_date')
            ?? $request->date
            ?? $request->searchDate
            ?? $request->duration
            ?? $request->filter_date;
        $dateRange = $this->resolveEventDateRange($dateFilter, $timezone);

        $query = Event::with(['ticket'])
            ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending']])
            ->where('id', '!=', 19);

        if ($dateRange) {
            $query->where(function ($q) use ($dateRange) {
                $q->whereBetween('start_time', [$dateRange[0], $dateRange[1]])
                  ->orWhere(function ($sub) use ($dateRange) {
                      $sub->where('start_time', '<=', $dateRange[1])
                          ->where('end_time', '>=', $dateRange[0]);
                  });
            });
        } else {
            $query->where('end_time', '>', $date->format('Y-m-d H:i:s'));
        }

        $search = $request->query('searchString') ?? $request->searchString;
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('address', 'LIKE', "%$search%")
                  ->orWhere('name', 'LIKE', "%$search%")
                  ->orWhere('description', 'LIKE', "%$search%");
            });
        }

        $events = $query->orderBy('start_time', 'ASC')->get()->makeHidden(['created_at', 'updated_at']);

        foreach ($events as $value) {
            $value->description = str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
            $value->share_url = url('/event/' . $value->id . '/' . str_replace(' ', '', $value->name));
            if (Auth::guard('userApi')->check()) {
                if (in_array($value->id, array_filter(explode(',', Auth::guard('userApi')->user()->favorite ?? '')))) {
                    $value->isLike = true;
                } else {
                    $value->isLike = false;
                }
            } else {
                $value->isLike = false;
            }
        }

        $data['events'] = $events;
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function EventFrmCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'bail|required',
        ]);
        $timezone = Setting::findOrFail(1)->timezone;
        $date = Carbon::now($timezone);

        $data =  Event::where([['status', 1], ['is_deleted', 0], ['category_id', $request->category_id], ['start_time', '>=', $date->format('Y-m-d H:i:s')]])->get();
        return $data;
        if ($request->lat != null &&  $request->lang != null) {
            $lat = $request->lat;
            $lang = $request->lang;
            $event = array();
            $radius = 50;
            $results = DB::select(DB::raw('SELECT id,name, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lang ) - radians(' . $lang . ') ) + sin( radians(' . $lat . ') ) * sin( radians(lat) ) ) ) AS distance FROM events HAVING distance < ' . $radius . '  ORDER BY distance'));
            if (count($results) > 0) {
                foreach ($results as $q) {
                    array_push($event, $q->id);
                }
            }
            $data = $data->whereIn('id', $event);
        }

        $data = $data->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->description =  str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
            if (Auth::guard('userApi')->check()) {
                if (in_array($value->id, array_filter(explode(',', Auth::guard('userApi')->user()->favorite)))) {
                    $value->isLike = true;
                } else {
                    $value->isLike = false;
                }
            } else {
                $value->isLike = false;
            }
        }
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function searchFreeEvent(Request $request)
    {
        $request->validate([
            'free_event' => 'bail|required|numeric',
            'category_id' => 'bail|required|numeric',
        ]);
        $timezone = Setting::findOrFail(1)->timezone;
        $date = Carbon::now($timezone);
        $data = Event::where([['status', 1], ['is_deleted', 0], ['category_id', $request->category_id], ['start_time', '>=', $date->format('Y-m-d H:i:s')]]);
        if ($request->free_event == 1) {
            $ar_event = array();
            $ar = Event::where([['status', 1], ['is_deleted', 0], ['start_time', '>=', $date->format('Y-m-d H:i:s')]])->get();
            foreach ($ar as $value) {
                $ticket = Ticket::where([['status', 1], ['is_deleted', 0], ['event_id', $value->id], ['type', 'free']])->get();
                if (count($ticket) > 0) {
                    array_push($ar_event, $value->id);
                }
            }
            $data = $data->whereIn('id', $ar_event);
        }
        if ($request->lat != null &&  $request->lang != null) {
            $lat = $request->lat;
            $lang = $request->lang;
            $event = array();
            $radius = 50;
            $results = DB::select(DB::raw('SELECT id,name, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lang ) - radians(' . $lang . ') ) + sin( radians(' . $lat . ') ) * sin( radians(lat) ) ) ) AS distance FROM events HAVING distance < ' . $radius . '  ORDER BY distance'));
            if (count($results) > 0) {
                foreach ($results as $q) {
                    array_push($event, $q->id);
                }
            }
            $data = $data->whereIn('id', $event);
        }
        $data = $data->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->description =  str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
            if (Auth::guard('userApi')->check()) {
                if (in_array($value->id, array_filter(explode(',', Auth::guard('userApi')->user()->favorite)))) {
                    $value->isLike = true;
                } else {
                    $value->isLike = false;
                }
            } else {
                $value->isLike = false;
            }
        }
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function category()
    {
        $data = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function organizationDetail($id)
    {
        $data = User::findOrFail($id);
        $data->event = Event::where([['user_id', $id], ['is_deleted', 0], ['status', 1]])->orderBy('id', 'DESC')->get();
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function eventDetail($id)
    {
        $data = Event::with(['ticket', 'organization:id,first_name,last_name,image,email', 'videos'])->findOrFail($id);

        $data->hasTag = explode(',', $data->tags);
        $data->descriptionHTML = str_replace("&nbsp;", " ", $data->getOriginal('description'));
        $data->description = str_replace("&nbsp;", " ", strip_tags($data->description));

        $now = Carbon::now();

        // Recent (live/upcoming) events: start_time >= now (exclude current event)
        $data->recent_event = Event::where([
            ['category_id', $data->category_id],
            ['is_deleted', 0],
            ['status', 1],
            ['start_time', '>=', $now],
            ['id', '!=', $id]
        ])->orderBy('start_time', 'ASC')->get();

        // Previous (past) events: start_time < now (exclude current event)
        $data->previous_event = Event::where([
            ['category_id', $data->category_id],
            ['is_deleted', 0],
            ['status', 1],
            ['start_time', '<', $now],
            ['id', '!=', $id]
        ])->orderBy('start_time', 'ASC')->get();

        $data->date = $data->start_time->format('d F Y');
        $data->endDate = $data->end_time->format('d F Y');
        $data->startTime = $data->start_time->format('h:i a');
        $data->gallery = array_filter(explode(',', $data->gallery));
        $data->endTime = $data->end_time->format('h:i a');
        $data->share_url = url('/event/' . $data->id . '/' . str_replace(' ', '', $data->name));

        foreach ($data->recent_event as $value) {
            $value->time = $value->start_time->format('d F Y h:i a');
            if (Auth::guard('userApi')->check()) {
                $value->isLike = in_array(
                    $value->id,
                    array_filter(explode(',', Auth::guard('userApi')->user()->favorite))
                );
            } else {
                $value->isLike = false;
            }
        }

        if (Auth::guard('userApi')->check()) {
            $data->organization->isFollow = in_array(
                Auth::guard('userApi')->user()->id,
                $data->organization->followers
            );

            $data->isLike = in_array(
                $id,
                array_filter(explode(',', Auth::guard('userApi')->user()->favorite))
            );
        } else {
            $data->organization->isFollow = false;
            $data->isLike = false;
        }

        // sold_out logic
        $all_ticket = Ticket::where([['event_id', $id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
        $use_ticket = Order::where('event_id', $id)->sum('quantity');
        $data->sold_out = $all_ticket > 0 && $all_ticket == $use_ticket;

        // Also return ticket availability/sale status (like /user/event-tickets/{id})
        $event = $data; // alias for readability
        $dataTicket['event_name'] = $event->name;
        $dataTicket['organization'] = optional($event->organization)->full_name;

        $timezone = Setting::findOrFail(1)->timezone;
        $date = Carbon::now($timezone);

        $isOrganizerOrAdmin = Auth::check() && (
            (method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('organizer'))) ||
            (int) Auth::id() === (int) $event->user_id
        );

        $tickets = Ticket::with('allowUser')
            ->where([['event_id', $id], ['is_deleted', 0], ['status', 1]])
            ->orderBy('id', 'DESC')
            ->get();

        if (!$isOrganizerOrAdmin) {
            $tickets = $tickets->filter(function ($t) {
                return (int) $t->allow_to_user === 1;
            })->values();
        }

        $totalBooked = Order::where('event_id', $id)->sum('quantity');
        $remainingCapacity = $event->people > 0 ? max(0, $event->people - $totalBooked) : null;
        $eventCompleted = Carbon::parse($event->end_time)->lt($date);

        foreach ($tickets as $value) {
            $bookedForTicket = 0;
            $ordersForEvent = Order::where('event_id', $id)->get();

            foreach ($ordersForEvent as $ord) {
                $ticketIds = array_filter(array_map('trim', explode(',', $ord->ticket_id)));
                $quantities = array_filter(array_map('trim', explode(',', $ord->quantity)));

                foreach ($ticketIds as $index => $tid) {
                    if ($tid == $value->id) {
                        $bookedForTicket += isset($quantities[$index]) ? (int) $quantities[$index] : 0;
                    }
                }
            }

            $value->use_ticket = $bookedForTicket;
            $value->startTime = $value->start_time->format('Y-m-d h:i a');
            $value->endTime = $value->end_time->format('Y-m-d h:i a');

            if (is_null($remainingCapacity)) {
                $value->available = max(0, $value->quantity - $bookedForTicket);
            } else {
                $value->available = max(0, min($value->quantity - $bookedForTicket, $remainingCapacity));
            }

            if ((int) $id === 46) {
                $value->available = 0;
            }

            $value->sold_out = $value->available == 0;

            $now = Carbon::now();
            $value->is_sale_started = Carbon::parse($value->start_time)->lte($now);
            $value->is_sale_ended = Carbon::parse($value->end_time)->lt($now);

            if ($eventCompleted || $value->is_sale_ended) {
                $value->sale_status = 'Sales Ended';
            } elseif (!$value->is_sale_started) {
                $value->sale_status = 'not_started';
            } else {
                $value->sale_status = 'ongoing';
            }
        }

        $dataTicket['ticket'] = $tickets;
        $dataTicket['module'] = Module::where('module', 'Seatmap')->first();

        $data->tickets_detail = $dataTicket;

        // Donation link only for event id 46
        if ((int) $id === 46) {
            $data->donation_link = 'https://donate.stripe.com/00w14oflc2Xt9POcbU77O02';
        }

        if ((int) $id === 54) {
            $data->seat_map_image = 'https://teptix.com/images/seatmaps.jpg';
        }

        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function ticketDetail($id)
    {
        $data = Ticket::findOrFail($id)->makeHidden(['created_at', 'updated_at']);
        $event = Event::findOrFail($data->event_id);
        $data->event_name = $event->name;
        $data->organization = User::findOrFail($event->user_id)->name;
        $data->use_ticket = (int)Order::where('ticket_id', $id)->sum('quantity');
        $data->startTime = $data->start_time->format('Y-m-d h:i a');
        $data->endTime = $data->end_time->format('Y-m-d h:i a');

        // Sale state flags
        $now = Carbon::now();
        $data->is_sale_started = Carbon::parse($data->start_time)->lte($now);
        $data->is_sale_ended = Carbon::parse($data->end_time)->lt($now);

        // Human-readable sale status: 'ended', 'not_started', 'ongoing'
        if ($data->is_sale_ended) {
            $data->sale_status = 'ended';
        } elseif (!$data->is_sale_started) {
            $data->sale_status = 'not_started';
        } else {
            $data->sale_status = 'ongoing';
        }

        $data->sold_out = ($data->quantity <= $data->use_ticket);
        $venueSeatMap = $this->buildVenueSeatMapPayload($event, [(int) $data->id]);
        $usesTicketRows = $venueSeatMap['has_seat_map'] && ($venueSeatMap['uses_ticket_rows'] ?? false);
        $data->has_venue_seat_map = $venueSeatMap['has_seat_map'];
        $data->seat_map_connected = $venueSeatMap['has_seat_map'] && (
            ! $usesTicketRows || collect($venueSeatMap['seats'])->contains('ticket_id', (int) $data->id)
        );
        $data->venue_seat_map = $venueSeatMap['has_seat_map'] ? $venueSeatMap : null;

        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function eventVenueSeatMap(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $selectedTicketIds = $this->expandTicketSelectionFromRequest($request);
        $selectedTicketQuantity = $this->requestedSeatSelectionLimit($request);
        $selectedVenueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('seat_ids', []))
        );
        $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));

        $payload = $this->buildVenueSeatMapPayload($event, $selectedTicketIds, $selectedVenueSeatIds, $guestHoldOwner, $selectedTicketQuantity);

        if (! $payload['has_seat_map']) {
            return response()->json([
                'success' => false,
                'msg' => 'Venue seat map is not available for this event.',
                'data' => $payload,
            ], 404);
        }

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => null,
            'data' => $payload,
        ], 200));
    }

    public function eventVenueSeatMapHtml(Request $request)
    {
        $eventId = (int) $request->input('event_id', $request->input('event', 0));

        if (! $eventId) {
            $eventId = (int) EventVenueMap::where('status', EventVenueMap::STATUS_LIVE)
                ->orderByDesc('id')
                ->value('event_id');
        }

        abort_if(! $eventId, 404, 'Venue seat map is not available.');

        $event = Event::findOrFail($eventId);
        $selectedTicketIds = $this->expandTicketSelectionFromRequest($request);
        $selectedTicketQuantity = $this->requestedSeatSelectionLimit($request);
        $selectedVenueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('seat_ids', []))
        );
        $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));

        $payload = $this->buildVenueSeatMapPayload($event, $selectedTicketIds, $selectedVenueSeatIds, $guestHoldOwner, $selectedTicketQuantity);

        abort_if(! $payload['has_seat_map'], 404, 'Venue seat map is not available for this event.');

        return $this->noStoreResponse(response()
            ->view('api.event-seat-map-stage', [
                'event' => $event,
                'payload' => $payload,
                'primaryColor' => Setting::value('primary_color') ?: '#047857',
                'stage' => $this->buildVenueSeatMapStageOnlyPayload($payload)['stage'],
            ])
            ->header('Content-Type', 'text/html'));
    }

    public function eventVenueSeatMapFromBody(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'ticket_id' => 'nullable',
            'ticket_ids' => 'nullable',
            'tickets' => 'nullable',
            'quantity' => 'nullable',
            'venue_seat_ids' => 'nullable',
            'seat_ids' => 'nullable',
            'stage_only' => 'nullable|boolean',
        ]);

        $event = Event::findOrFail($request->input('event_id'));
        $selectedTicketIds = $this->expandTicketSelectionFromRequest($request);
        $selectedTicketQuantity = $this->requestedSeatSelectionLimit($request);
        $selectedVenueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('seat_ids', []))
        );
        $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));
        $payload = $this->buildVenueSeatMapPayload($event, $selectedTicketIds, $selectedVenueSeatIds, $guestHoldOwner, $selectedTicketQuantity);

        if (! $payload['has_seat_map']) {
            return response()->json([
                'success' => false,
                'msg' => 'Venue seat map is not available for this event.',
                'data' => $payload,
            ], 404);
        }

        if ($request->boolean('stage_only')) {
            $payload = $this->buildVenueSeatMapStageOnlyPayload($payload);
        }

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => null,
            'data' => $payload,
        ], 200));
    }

    public function holdEventVenueSeats(Request $request)
    {
        $event = Event::findOrFail($request->input('event_id'));
        $selectedTicketIds = $this->expandTicketSelectionFromRequest($request);
        $venueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('seat_ids', []))
        );
        $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));

        if (empty($selectedTicketIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Please select at least one ticket.',
                'data' => null,
            ], 422);
        }

        $seatHoldingTicketIds = $this->venueSeatRequiredTicketIds($event, $selectedTicketIds);
        $venueSeatIds = $this->filterVenueSeatIdsForRequiredTickets($event, $venueSeatIds, $seatHoldingTicketIds);

        if (count($seatHoldingTicketIds) !== count($venueSeatIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Selected seat count must match selected ticket quantity.',
                'data' => [
                    'ticket_quantity' => count($seatHoldingTicketIds),
                    'selected_ticket_quantity' => count($selectedTicketIds),
                    'seat_quantity' => count($venueSeatIds),
                ],
            ], 422);
        }

        $liveVenueMap = $event->liveVenueMap()->first();

        if (! $liveVenueMap) {
            return response()->json([
                'success' => false,
                'msg' => 'Venue seat map is not available for this event.',
                'data' => null,
            ], 404);
        }

        $selectedTicketIdsUnique = array_values(array_unique($selectedTicketIds));
        $validTicketCount = Ticket::where('event_id', $event->id)
            ->whereIn('id', $selectedTicketIdsUnique)
            ->count();

        if ($validTicketCount !== count($selectedTicketIdsUnique)) {
            return response()->json([
                'success' => false,
                'msg' => 'Selected ticket does not belong to this event.',
                'data' => null,
            ], 422);
        }

        $userId = $this->currentApiUserId();
        $holdExpiresAt = null;

        if (! $userId) {
            return response()->json([
                'success' => false,
                'msg' => 'User authentication is required to hold seats.',
                'data' => null,
            ], 401);
        }

        $venueMapUsesTicketRows = $liveVenueMap->seats()->whereNotNull('ticket_id')->exists()
            || EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)->whereNotNull('ticket_id')->exists()
            || EventVenueSection::where('event_venue_map_id', $liveVenueMap->id)->whereNotNull('ticket_id')->exists();
        $selectedTicketLookup = array_flip(array_values(array_unique($seatHoldingTicketIds)));

        $holdResult = DB::transaction(function () use ($event, $liveVenueMap, $venueSeatIds, $userId, $guestHoldOwner, $venueMapUsesTicketRows, $selectedTicketLookup) {
            $this->releaseExpiredVenueSeatHoldsForApi($event->id);

            EventVenueSeat::where('event_id', $event->id)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_app_user_id', $userId)
                ->whereNotIn('id', $venueSeatIds)
                ->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

            if (empty($venueSeatIds)) {
                return ['success' => true, 'hold_expires_at' => null];
            }

            $lockedSeats = EventVenueSeat::where('event_id', $event->id)
                ->where('event_venue_map_id', $liveVenueMap->id)
                ->whereIn('id', $venueSeatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedSeats->count() !== count($venueSeatIds)) {
                return ['success' => false, 'message' => 'One or more selected seats were not found.'];
            }

            if ($venueMapUsesTicketRows) {
                $invalidTicketSeat = $lockedSeats->first(function ($seat) use ($selectedTicketLookup) {
                    return ! $this->seatMatchesRequiredTicket($seat, $selectedTicketLookup);
                });

                if ($invalidTicketSeat) {
                    return ['success' => false, 'message' => 'One or more selected seats are not available for the selected ticket.'];
                }
            }

            $unavailableSeat = $lockedSeats->first(function ($seat) use ($userId, $guestHoldOwner) {
                if ($seat->status === EventVenueSeat::STATUS_AVAILABLE) {
                    return false;
                }

                $heldByCurrentUser = (
                    $seat->status === EventVenueSeat::STATUS_HELD
                    && (int) $seat->held_by_app_user_id === (int) $userId
                    && $seat->hold_expires_at
                    && $seat->hold_expires_at->isFuture()
                );

                $heldBySameGuest = (
                    $seat->status === EventVenueSeat::STATUS_HELD
                    && $guestHoldOwner
                    && $seat->held_by_session_id === $guestHoldOwner
                    && $seat->hold_expires_at
                    && $seat->hold_expires_at->isFuture()
                );

                return ! ($heldByCurrentUser || $heldBySameGuest);
            });

            if ($unavailableSeat) {
                return ['success' => false, 'message' => 'One or more selected seats are no longer available.'];
            }

            $expiresAt = now()->addMinutes((int) ($liveVenueMap->hold_minutes ?: 10));

            EventVenueSeat::whereIn('id', $venueSeatIds)->update([
                'status' => EventVenueSeat::STATUS_HELD,
                'hold_token' => Str::random(40),
                'held_by_session_id' => null,
                'held_by_app_user_id' => $userId,
                'held_by_guest_user_id' => null,
                'held_at' => now(),
                'hold_expires_at' => $expiresAt,
            ]);

            return ['success' => true, 'hold_expires_at' => $expiresAt->toIso8601String()];
        });

        if (! $holdResult['success']) {
            return response()->json([
                'success' => false,
                'msg' => $holdResult['message'],
                'data' => null,
            ], 409);
        }

        $holdExpiresAt = $holdResult['hold_expires_at'];
        $payload = $this->buildVenueSeatMapPayload($event, $selectedTicketIds, $venueSeatIds);
        $payload['hold_expires_at'] = $holdExpiresAt;
        $payload['seats'] = $payload['selected_seats'];

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => 'Seats held successfully.',
            'data' => $payload,
        ], 200));
    }

    public function holdGuestEventVenueSeats(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'ticket_id' => 'nullable',
            'ticket_ids' => 'nullable',
            'tickets' => 'nullable',
            'quantity' => 'nullable',
            'venue_seat_ids' => 'nullable',
            'seat_ids' => 'nullable',
            'guest_hold_key' => 'nullable|string|max:120',
            'preserve_all_ticket_rows' => 'nullable|boolean',
        ]);

        $event = Event::findOrFail($request->input('event_id'));
        $selectedTicketIds = $this->expandTicketSelectionFromRequest($request);
        $venueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('seat_ids', []))
        );
        $guestHoldKey = $request->input('guest_hold_key') ?: Str::random(48);
        $guestHoldOwner = $this->apiGuestHoldOwner($guestHoldKey);

        if (empty($selectedTicketIds) && !empty($venueSeatIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Please select at least one ticket.',
                'data' => null,
            ], 422);
        }

        $seatHoldingTicketIds = $this->venueSeatRequiredTicketIds($event, $selectedTicketIds);
        $venueSeatIds = $this->filterVenueSeatIdsForRequiredTickets($event, $venueSeatIds, $seatHoldingTicketIds);

        if (!empty($venueSeatIds) && count($venueSeatIds) > count($seatHoldingTicketIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Selected seat count cannot be more than selected ticket quantity.',
                'data' => [
                    'ticket_quantity' => count($seatHoldingTicketIds),
                    'selected_ticket_quantity' => count($selectedTicketIds),
                    'seat_quantity' => count($venueSeatIds),
                ],
            ], 422);
        }

        $liveVenueMap = $event->liveVenueMap()->first();

        if (! $liveVenueMap) {
            return response()->json([
                'success' => false,
                'msg' => 'Venue seat map is not available for this event.',
                'data' => null,
            ], 404);
        }

        $selectedTicketIdsUnique = array_values(array_unique($selectedTicketIds));
        if (!empty($selectedTicketIdsUnique)) {
            $validTicketCount = Ticket::where('event_id', $event->id)
                ->whereIn('id', $selectedTicketIdsUnique)
                ->count();

            if ($validTicketCount !== count($selectedTicketIdsUnique)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Selected ticket does not belong to this event.',
                    'data' => null,
                ], 422);
            }
        }

        $venueMapUsesTicketRows = $liveVenueMap->seats()->whereNotNull('ticket_id')->exists()
            || EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)->whereNotNull('ticket_id')->exists()
            || EventVenueSection::where('event_venue_map_id', $liveVenueMap->id)->whereNotNull('ticket_id')->exists();
        $selectedTicketLookup = array_flip(array_values(array_unique($seatHoldingTicketIds)));

        $holdResult = DB::transaction(function () use ($event, $liveVenueMap, $venueSeatIds, $guestHoldOwner, $venueMapUsesTicketRows, $selectedTicketLookup) {
            $this->releaseExpiredVenueSeatHoldsForApi($event->id);

            EventVenueSeat::where('event_id', $event->id)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $guestHoldOwner)
                ->whereNotIn('id', $venueSeatIds ?: [0])
                ->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

            if (empty($venueSeatIds)) {
                return ['success' => true, 'hold_expires_at' => null];
            }

            $lockedSeats = EventVenueSeat::where('event_id', $event->id)
                ->where('event_venue_map_id', $liveVenueMap->id)
                ->whereIn('id', $venueSeatIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedSeats->count() !== count($venueSeatIds)) {
                return ['success' => false, 'message' => 'One or more selected seats were not found.'];
            }

            if ($venueMapUsesTicketRows) {
                $invalidTicketSeat = $lockedSeats->first(function ($seat) use ($selectedTicketLookup) {
                    return ! $this->seatMatchesRequiredTicket($seat, $selectedTicketLookup);
                });

                if ($invalidTicketSeat) {
                    return ['success' => false, 'message' => 'One or more selected seats are not available for the selected ticket.'];
                }
            }

            $unavailableSeat = $lockedSeats->first(function ($seat) use ($guestHoldOwner) {
                if ($seat->status === EventVenueSeat::STATUS_AVAILABLE) {
                    return false;
                }

                return ! (
                    $seat->status === EventVenueSeat::STATUS_HELD
                    && $seat->held_by_session_id === $guestHoldOwner
                    && $seat->hold_expires_at
                    && $seat->hold_expires_at->isFuture()
                );
            });

            if ($unavailableSeat) {
                return ['success' => false, 'message' => 'One or more selected seats are no longer available.'];
            }

            $expiresAt = now()->addMinutes(10);

            EventVenueSeat::whereIn('id', $venueSeatIds)->update([
                'status' => EventVenueSeat::STATUS_HELD,
                'hold_token' => Str::random(40),
                'held_by_session_id' => $guestHoldOwner,
                'held_by_app_user_id' => null,
                'held_by_guest_user_id' => null,
                'held_at' => now(),
                'hold_expires_at' => $expiresAt,
            ]);

            return ['success' => true, 'hold_expires_at' => $expiresAt->toIso8601String()];
        });

        if (! $holdResult['success']) {
            return response()->json([
                'success' => false,
                'msg' => $holdResult['message'],
                'data' => null,
            ], 409);
        }

        $payload = $this->buildVenueSeatMapPayload(
            $event,
            $selectedTicketIds,
            $venueSeatIds,
            $guestHoldOwner,
            null,
            $request->boolean('preserve_all_ticket_rows')
        );
        $payload['hold_expires_at'] = $holdResult['hold_expires_at'];
        $payload['guest_hold_key'] = $guestHoldKey;

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => empty($venueSeatIds) ? 'Seat hold released.' : 'Seats held successfully.',
            'data' => $payload,
        ], 200));
    }

    public function releaseGuestEventVenueSeatHold(Request $request)
    {
        $seatIds = $this->normalizeApiIds(
            $request->input('seat_id', $request->input('seat_ids', $request->input('venue_seat_ids', [])))
        );
        $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));

        if (empty($seatIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Please provide seat_id.',
                'data' => null,
            ], 422);
        }

        if (! $guestHoldOwner) {
            return response()->json([
                'success' => false,
                'msg' => 'Please provide guest_hold_key.',
                'data' => null,
            ], 422);
        }

        $this->releaseExpiredVenueSeatHoldsForApi();

        $releaseResult = DB::transaction(function () use ($seatIds, $guestHoldOwner) {
            $seats = EventVenueSeat::whereIn('id', $seatIds)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'One or more selected seats were not found.',
                ];
            }

            $unreleasableSeat = $seats->first(function ($seat) {
                $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;

                return ! in_array($status, [
                    EventVenueSeat::STATUS_AVAILABLE,
                    EventVenueSeat::STATUS_HELD,
                ], true);
            });

            if ($unreleasableSeat) {
                return [
                    'success' => false,
                    'status' => 409,
                    'message' => 'Seat is not currently held and cannot be released.',
                ];
            }

            $heldByOther = $seats->first(function ($seat) use ($guestHoldOwner) {
                return ($seat->status ?: EventVenueSeat::STATUS_AVAILABLE) === EventVenueSeat::STATUS_HELD
                    && $seat->held_by_session_id !== $guestHoldOwner;
            });

            if ($heldByOther) {
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => 'You can only release seats held by this guest hold key.',
                ];
            }

            $releasableSeatIds = $seats
                ->filter(function ($seat) use ($guestHoldOwner) {
                    return ($seat->status ?: EventVenueSeat::STATUS_AVAILABLE) === EventVenueSeat::STATUS_HELD
                        && $seat->held_by_session_id === $guestHoldOwner;
                })
                ->pluck('id')
                ->map(fn ($seatId) => (int) $seatId)
                ->values()
                ->all();

            if (! empty($releasableSeatIds)) {
                EventVenueSeat::whereIn('id', $releasableSeatIds)->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);
            }

            return [
                'success' => true,
                'released_count' => count($releasableSeatIds),
            ];
        });

        if (! $releaseResult['success']) {
            return response()->json([
                'success' => false,
                'msg' => $releaseResult['message'],
                'data' => null,
            ], $releaseResult['status']);
        }

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => $releaseResult['released_count'] > 0
                ? 'Seat hold released successfully.'
                : 'Seat hold already released.',
            'data' => [
                'seat_id' => count($seatIds) === 1 ? $seatIds[0] : null,
                'seat_ids' => $seatIds,
                'released_count' => $releaseResult['released_count'],
                'status' => EventVenueSeat::STATUS_AVAILABLE,
            ],
        ], 200));
    }

    public function releaseEventVenueSeatHold(Request $request)
    {
        $seatIds = $this->normalizeApiIds(
            $request->input('seat_id', $request->input('seat_ids', $request->input('venue_seat_ids', [])))
        );

        if (empty($seatIds)) {
            return response()->json([
                'success' => false,
                'msg' => 'Please provide seat_id.',
                'data' => null,
            ], 422);
        }

        $userId = $this->currentApiUserId();

        if (! $userId) {
            return response()->json([
                'success' => false,
                'msg' => 'User authentication is required to release a seat hold.',
                'data' => null,
            ], 401);
        }

        $this->releaseExpiredVenueSeatHoldsForApi();

        $releaseResult = DB::transaction(function () use ($seatIds, $userId) {
            $seats = EventVenueSeat::whereIn('id', $seatIds)
                ->lockForUpdate()
                ->get();

            if ($seats->count() !== count($seatIds)) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'One or more selected seats were not found.',
                ];
            }

            $unreleasableSeat = $seats->first(function ($seat) {
                $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;

                return ! in_array($status, [
                    EventVenueSeat::STATUS_AVAILABLE,
                    EventVenueSeat::STATUS_HELD,
                ], true);
            });

            if ($unreleasableSeat) {
                return [
                    'success' => false,
                    'status' => 409,
                    'message' => 'Seat is not currently held and cannot be released.',
                ];
            }

            $heldByOther = $seats->first(function ($seat) use ($userId) {
                return ($seat->status ?: EventVenueSeat::STATUS_AVAILABLE) === EventVenueSeat::STATUS_HELD
                    && (int) $seat->held_by_app_user_id !== (int) $userId;
            });

            if ($heldByOther) {
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => 'You can only release seats held by your account.',
                ];
            }

            $releasableSeatIds = $seats
                ->filter(function ($seat) use ($userId) {
                    return ($seat->status ?: EventVenueSeat::STATUS_AVAILABLE) === EventVenueSeat::STATUS_HELD
                        && (int) $seat->held_by_app_user_id === (int) $userId;
                })
                ->pluck('id')
                ->map(fn ($seatId) => (int) $seatId)
                ->values()
                ->all();

            if (! empty($releasableSeatIds)) {
                EventVenueSeat::whereIn('id', $releasableSeatIds)->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);
            }

            return [
                'success' => true,
                'released_count' => count($releasableSeatIds),
            ];
        });

        if (! $releaseResult['success']) {
            return response()->json([
                'success' => false,
                'msg' => $releaseResult['message'],
                'data' => null,
            ], $releaseResult['status']);
        }

        return $this->noStoreResponse(response()->json([
            'success' => true,
            'msg' => $releaseResult['released_count'] > 0
                ? 'Seat hold released successfully.'
                : 'Seat hold already released.',
            'data' => [
                'seat_id' => count($seatIds) === 1 ? $seatIds[0] : null,
                'seat_ids' => $seatIds,
                'released_count' => $releaseResult['released_count'],
                'status' => EventVenueSeat::STATUS_AVAILABLE,
            ],
        ], 200));
    }

    protected function seatMatchesRequiredTicket($seat, array $selectedTicketLookup): bool
    {
        $seatTicketId = (int) ($seat->ticket_id ?? 0);

        if ($seatTicketId <= 0) {
            if ($seat->relationLoaded('row') && $seat->row) {
                $seatTicketId = (int) ($seat->row->ticket_id ?? 0);
            } elseif ($seat->event_venue_row_id) {
                $row = EventVenueRow::find($seat->event_venue_row_id);
                if ($row) {
                    $seatTicketId = (int) ($row->ticket_id ?? 0);
                }
            }
        }

        if ($seatTicketId <= 0) {
            if ($seat->relationLoaded('section') && $seat->section) {
                $seatTicketId = (int) ($seat->section->ticket_id ?? 0);
            } elseif ($seat->event_venue_section_id) {
                $section = EventVenueSection::find($seat->event_venue_section_id);
                if ($section) {
                    $seatTicketId = (int) ($section->ticket_id ?? 0);
                }
            }
        }

        return $seatTicketId > 0 && isset($selectedTicketLookup[$seatTicketId]);
    }

    public function eventTickets($id)
    {
        $event = Event::findOrFail($id);
        $data['event_name'] = $event->name;

        $data['organization'] = User::findOrFail($event->user_id)->full_name;
        $timezone = Setting::findOrFail(1)->timezone;
        $date = Carbon::now($timezone);

        $isOrganizerOrAdmin = Auth::check() && (
            (method_exists(Auth::user(), 'hasRole') && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('organizer'))) ||
            (int) Auth::id() === (int) $event->user_id
        );

        $data['ticket'] = Ticket::with('allowUser')
            ->where([['event_id', $id], ['is_deleted', 0], ['status', 1]])
            ->orderBy('id', 'DESC')->get();

        if (!$isOrganizerOrAdmin) {
            $data['ticket'] = $data['ticket']->filter(function ($t) {
                return (int) $t->allow_to_user === 1;
            })->values();
        }

        // Calculate overall booked tickets for the event and remaining capacity
        $totalBooked = Order::where('event_id', $id)->sum('quantity');
        $remainingCapacity = $event->people > 0 ? max(0, $event->people - $totalBooked) : null;

        $eventCompleted = Carbon::parse($event->end_time)->lt($date);
        $venueSeatMap = $this->buildVenueSeatMapPayload($event);
        $usesTicketRows = $venueSeatMap['has_seat_map'] && ($venueSeatMap['uses_ticket_rows'] ?? false);
        $seatMapTicketIds = $venueSeatMap['has_seat_map']
            ? collect($venueSeatMap['seats'])
                ->pluck('ticket_id')
                ->filter()
                ->map(fn ($ticketId) => (int) $ticketId)
                ->unique()
                ->values()
            : collect();

        foreach ($data['ticket'] as $value) {
            // Calculate booked quantity for this specific ticket by parsing orders for the event
            $bookedForTicket = 0;
            $ordersForEvent = Order::where('event_id', $id)->get();
            foreach ($ordersForEvent as $ord) {
                $ticketIds = array_filter(array_map('trim', explode(',', $ord->ticket_id)));
                $quantities = array_filter(array_map('trim', explode(',', $ord->quantity)));
                foreach ($ticketIds as $index => $tid) {
                    if ($tid == $value->id) {
                        $bookedForTicket += isset($quantities[$index]) ? (int) $quantities[$index] : 0;
                    }
                }
            }

            $value->use_ticket = $bookedForTicket;
            $value->startTime = $value->start_time->format('Y-m-d h:i a');
            $value->endTime = $value->end_time->format('Y-m-d h:i a');

            // Available tickets should not exceed remaining event capacity (if set)
            if (is_null($remainingCapacity)) {
                $value->available = max(0, $value->quantity - $bookedForTicket);
            } else {
                $value->available = max(0, min($value->quantity - $bookedForTicket, $remainingCapacity));
            }

            if ((int) $id === 46) {
                // Force event 46 to display all tickets as sold out
                $value->available = 0;
            }

            $value->sold_out = $value->available == 0 ? true : false;

            // Sale state flags
            $now = Carbon::now();
            $value->is_sale_started = Carbon::parse($value->start_time)->lte($now);
            $value->is_sale_ended = Carbon::parse($value->end_time)->lt($now);

            // Human-readable sale status: 'ended', 'not_started', 'ongoing'
            if ($eventCompleted || $value->is_sale_ended) {
                $value->sale_status = 'Sales Ended';
            } elseif (!$value->is_sale_started) {
                $value->sale_status = 'not_started';
            } else {
                $value->sale_status = 'ongoing';
            }

            $value->has_venue_seat_map = $venueSeatMap['has_seat_map'];
            $value->seat_map_connected = $venueSeatMap['has_seat_map'] && (
                ! $usesTicketRows || $seatMapTicketIds->contains((int) $value->id)
            );
            $value->seat_map_status = $value->seat_map_connected ? 'connected' : 'not_connected';
        }
        $data['has_venue_seat_map'] = $venueSeatMap['has_seat_map'];
        $data['uses_ticket_rows'] = $usesTicketRows;
        $data['seat_map_image'] = (int) $id === 54
            ? 'https://teptix.com/images/seatmaps.jpg'
            : null;
        $data['module'] = Module::where('module', 'Seatmap')->first();

        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }



    public function reportEvent(Request $request)
    {
        $request->validate([
            'event_id' => 'bail|required',
            'email' => 'bail|required|email',
            'reason' => 'bail|required',
            'message' => 'bail|required',
        ]);
        $report = EventReport::create($request->all());
        return response()->json(['msg' => null, 'data' => $report, 'success' => true], 200);
    }

    public function categoryEvent()
    {
        $data = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->events = Event::where([['status', 1], ['is_deleted', 0], ['category_id', $value->id]])->orderBy('id', 'DESC')->get();
        }
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function userProfile()
    {
        (new AppHelper)->eventStatusChange();
        $data = Auth::user();
        $data->likeCount = count(array_filter(explode(',', $data->favorite)));
        $data->totalTicket = Order::where('customer_id', $data->id)->count();
        $data->followingCount = count(array_filter(explode(',', $data->following)));
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function userLikes()
    {

        $data = Event::whereIn('id', array_filter(explode(',', Auth::user()->favorite)))->where([['status', 1], ['is_deleted', 0]])->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->description =  str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
        }
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function userFollowing()
    {
        $data = User::whereIn('id', array_filter(explode(',', Auth::user()->following)))->with('events')->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            foreach ($value->events as $event) {
                $event->description =  str_replace("&nbsp;", " ", strip_tags($event->description));
                $event->time = $event->start_time->format('d F Y h:i a');
                $event->share_url = url('/event/' . $event->id. '/' . str_replace(' ', '', $event->name));
                if (Auth::guard('userApi')->check()) {
                    if (in_array($event->id, array_filter(explode(',', Auth::guard('userApi')->user()->favorite)))) {
                        $event->isLike = true;
                    } else {
                        $event->isLike = false;
                    }
                } else {
                    $event->isLike = false;
                }
            }
            if (Auth::check()) {
                if (in_array(Auth::user()->id, $value->followers)) {
                    $value->isFollow = true;
                } else {
                    $value->isFollow = false;
                }
            } else {
                $value->isFollow = false;
            }
        }
        return response()->json(['msg' => null, 'data' => $data, 'success' => true], 200);
    }

    public function editUserProfile(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'last_name' => 'bail|required',
        ]);
        AppUser::findOrFail(Auth::user()->id)->update($request->all());
        $user = AppUser::findOrFail(Auth::user()->id);
        return response()->json(['msg' => 'Profile Update successfully', 'data' => $user, 'success' => true], 200);
    }

    public function editImage(Request $request)
    {
        $request->validate([
            'image' => 'bail|required',
        ]);

        if (isset($request->image)) {
            $image_name = (new AppHelper)->saveApiImage($request);
            AppUser::findOrFail(Auth::user()->id)->update(['image' => $image_name]);
            return response()->json(['msg' => null, 'data' => null, 'success' => true], 200);
        } else {
            return response()->json(['msg' => null, 'data' => null, 'success' => false], 200);
        }
    }


    public function addFavorite(Request $request)
    {
        $request->validate([
            'event_id' => 'bail|required',
        ]);
        $users = AppUser::findOrFail(Auth::user()->id);
        $likes = array_filter(explode(',', $users->favorite));
        if (count(array_keys($likes, $request->event_id)) > 0) {
            if (($key = array_search($request->event_id, $likes)) !== false) {
                unset($likes[$key]);
            }
            $msg = "Remove from Bookmark!";
        } else {
            array_push($likes, $request->event_id);
            $msg = "Add in Bookmark!";
        }
        $client = AppUser::findOrFail(Auth::user()->id);
        $client->favorite = implode(',', $likes);
        $client->update();

        return response()->json(['msg' => $msg, 'data' => null, 'success' => true], 200);
    }

    public function addFollowing(Request $request)
    {
        $request->validate([
            'user_id' => 'bail|required',
        ]);
        $users = AppUser::findOrFail(Auth::user()->id);
        $likes = array_filter(explode(',', $users->following));
        if (count(array_keys($likes, $request->user_id)) > 0) {
            if (($key = array_search($request->user_id, $likes)) !== false) {
                unset($likes[$key]);
            }
            $msg = "Remove from following list!";
        } else {
            array_push($likes, $request->user_id);
            $msg = "Add in following!";
        }
        $client = AppUser::findOrFail(Auth::user()->id);
        $client->following = implode(',', $likes);
        $client->update();
        return response()->json(['msg' => $msg, 'data' => null, 'success' => true], 200);
    }

    public function checkCode(Request $request)
    {
        $request->validate([
            'coupon_code' => 'bail|required',
            'event_id' => 'bail|required',
            'amount' => 'bail|required',
        ]);
        //New Code
        $total = $request->amount;
        $date = Carbon::now()->format('Y-m-d');
        $coupon = Coupon::where([['coupon_code', $request->coupon_code], ['status', 1], ['event_id', $request->event_id]])->first();
        if ($coupon) {
            $couponHistory = CouponUsageHistory::where([['coupon_id', $coupon->id], ['appuser_id', Auth::guard('userApi')->user()->id]])->get();
            if (count($couponHistory) >= $coupon->max_use_per_user ) {
                return response([
                    'success' => false,
                    'message' => 'This coupon is reached max use!'
                ]);
            }
            if (Carbon::parse($date)->between(Carbon::parse($coupon->start_date), Carbon::parse($coupon->end_date))) {
                if ($coupon->max_use > $coupon->use_count) {
                    if ($total > $coupon->minimum_amount) {

                        if ($coupon->discount_type == 0) {
                            $discount = $total * ($coupon->discount / 100);
                        } else {
                            $discount = $coupon->discount;
                        }
                        if ($discount > $coupon->maximum_discount) {
                            $discount = $coupon->maximum_discount;
                        }
                        $subtotal = $total - $discount;

                        return response([
                            'success' => true,
                            'data' => [
                                'total_price' => $subtotal,
                                'total' => $total,
                                'discount' => $coupon->discount,
                                'applied_discount' => $discount,
                                'coupon_id' => $coupon->id,
                                'coupon_type' => $coupon->discount_type
                            ],
                            'message' => 'coupon apply successful.'
                        ]);
                    } else {
                        return response([
                            'success' => false,
                            'message' => 'Invalid amount.'
                        ]);
                    }
                } else {
                    return response([
                        'success' => false,
                        'message' => 'This coupon is reached max use!'
                    ]);
                }
            } else {
                return response([
                    'success' => false,
                    'message' => 'This coupon is expire!'
                ]);
            }
        } else {
            return response([
                'success' => false,
                'message' => 'Invalid Coupon code for this event!'
            ]);
        }
    }

    public function  allCoupon($id)
    {
        $now = Carbon::now();

        $data = Coupon::where('status', 1)
            ->where('event_id', $id)
            ->whereHas('event', function ($q) use ($now) {
                $q->where('end_time', '>=', $now);
            })
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }


    public function userNotification(Request $request)
    {
        (new AppHelper)->eventStatusChange();

        $eventId = $request->input('event_id');
        if ($eventId === '' || $eventId === null) {
            $eventId = null;
        } else {
            $eventId = (int) $eventId;
        }

        $dataQuery = Notification::where('user_id', Auth::user()->id);

        if (!is_null($eventId)) {
            // Filter notifications based on related order's event_id
            $dataQuery->whereIn('order_id', function ($q) use ($eventId) {
                $q->select('id')
                    ->from('orders')
                    ->where('event_id', $eventId);
            });
        }

        $data = $dataQuery->orderBy('id', 'DESC')->get();

        foreach ($data as $value) {
            $order = Order::find($value->order_id);
            if ($order) {
                $event = $order->event_id;
                $eventData = Event::find($event);
                if ($eventData) {
                    $value->event_image = url('images/upload') . '/' . $eventData->image;
                } else {
                    $value->event_image = null;
                }
            } else {
                $value->event_image = null;
            }
        }

        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }


    public function addReview(Request $request)
    {
        $request->validate([
            'event_id' => 'bail|required',
            'order_id' => 'bail|required',
            'message' => 'bail|required',
            'rate' => 'bail|required|numeric',
        ]);
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $data['organization_id'] = Event::findOrFail($request->event_id)->user_id;
        $data['status'] = 0;
        $review = Review::create($data);
        return response()->json(['success' => true, 'msg' => null, 'data' => $review], 200);
    }


    public function orderTax($id)
    {
        $organizer = Event::findOrFail($id)->user_id;
        $data = Tax::where(function($query) use ($organizer) {
                $query->where('user_id', 1)
                      ->orWhere('user_id', $organizer);
            })
            ->where('status', 1)
            ->where('allow_all_bill', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->makeHidden(['created_at', 'updated_at']);
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }




public function ticketTax(Request $request)
{
    $ids = $request->input('id');

    // Support both array and comma-separated string
    if (is_string($ids)) {
        $ids = explode(',', $ids);
    }

    $ids = is_array($ids) ? $ids : [$ids];
    $result = [];

    foreach ($ids as $id) {
        $ticket = Ticket::find($id);
        if (!$ticket) {
            $result[$id] = ['error' => 'Ticket not found'];
            continue;
        }

        // Get all tax IDs from the ticket
        $taxIds = array_filter(explode(',', $ticket->tax_id));

        // Calculate total tax for this ticket
        $totalTax = 0;
        $ticketPrice = $ticket->price ?? 0;
        $taxDetails = [];

        if (! $this->ticketIsFree($ticket) && (float) $ticketPrice > 0) {
            // Process each tax ID individually to match FrontendController logic
            foreach ($taxIds as $taxId) {
                $tax = Tax::where('status', 1)
                         ->where('allow_all_bill', 1)
                         ->where('id', trim($taxId))
                         ->first();

                if ($tax) {
                    $taxAmount = 0;
                    if ($tax->amount_type === 'percentage') {
                        // Calculate percentage of ticket price
                        $taxAmount = round(($ticketPrice * $tax->price) / 100, 2);
                    } else {
                        // Fixed price amount
                        $taxAmount = round($tax->price, 2);
                    }

                    $totalTax += $taxAmount;
                    $taxDetails[] = [
                        'tax_id' => $tax->id,
                        'name' => $tax->name,
                        'amount_type' => $tax->amount_type,
                        'rate' => $tax->price,
                        'calculated_amount' => $taxAmount
                    ];
                }
            }
        }

        // Fetch all taxes for response (to maintain backward compatibility)
        $taxes = (! $this->ticketIsFree($ticket) && (float) $ticketPrice > 0)
            ? Tax::where('status', 1)
                ->where('allow_all_bill', 1)
                ->whereIn('id', $taxIds)
                ->orderBy('id', 'DESC')
                ->get()
            : collect();

        // Return taxes with calculated total
        $result[$id] = [
            'taxes' => $taxes,
            'tax_details' => $taxDetails,
            'ticket_price' => $ticketPrice,
            'total_tax' => round($totalTax, 2),
            'final_price' => round($ticketPrice + $totalTax, 2)
        ];
    }

    return response()->json(['success' => true, 'msg' => null, 'data' => $result], 200);
}

private function expandTicketSelectionFromRequest(Request $request): array
{
    if ($request->has('tickets') || $request->has('ticket_ids')) {
        return $this->normalizeApiIds($request->input('tickets', $request->input('ticket_ids')), false);
    }

    $ticketIdInput = $request->input('ticket_id', []);
    $quantityInput = $this->seatMapQuantityInput($request);

    if (is_string($ticketIdInput) && ! $this->hasSeatMapQuantityInput($request) && preg_match('/^(\d+)\$quantity=(\d+)$/', $ticketIdInput, $matches)) {
        $ticketIdInput = $matches[1];
        $quantityInput = $matches[2];
    }

    $ticketIds = $this->normalizeApiIds($ticketIdInput, false);
    $quantities = $this->normalizeApiIds($quantityInput, false);

    if (empty($ticketIds) || empty($quantities)) {
        return $ticketIds;
    }

    if (count($ticketIds) > 1 && count($quantities) === 1) {
        $totalQuantity = max(1, (int) $quantities[0]);
        $expandedTicketIds = [];

        for ($i = 0; $i < $totalQuantity; $i++) {
            $expandedTicketIds[] = (int) $ticketIds[$i % count($ticketIds)];
        }

        return $expandedTicketIds;
    }

    $expandedTicketIds = [];
    foreach ($ticketIds as $index => $ticketId) {
        $quantity = max(1, (int) ($quantities[$index] ?? 1));
        for ($i = 0; $i < $quantity; $i++) {
            $expandedTicketIds[] = (int) $ticketId;
        }
    }

    return $expandedTicketIds;
}

private function requestedSeatSelectionLimit(Request $request): ?int
{
    if (! $this->hasSeatMapQuantityInput($request)) {
        return null;
    }

    $ticketIds = $this->normalizeApiIds($request->input('ticket_id', []), false);
    $quantities = $this->normalizeApiIds($this->seatMapQuantityInput($request), false);

    if (count($ticketIds) > 1 && count($quantities) === 1) {
        return max(1, (int) $quantities[0]);
    }

    return null;
}

private function venueSeatRequiredTicketIds(Event $event, array $ticketIds): array
{
    $ticketIds = $this->normalizeApiIds($ticketIds, false);

    if (empty($ticketIds)) {
        return [];
    }

    $venueMapUsesTicketRows = EventVenueSeat::where('event_id', $event->id)
        ->whereNotNull('ticket_id')
        ->exists();

    if (! $venueMapUsesTicketRows) {
        return $ticketIds;
    }

    $seatTicketIds = EventVenueSeat::where('event_id', $event->id)
        ->whereIn('ticket_id', array_values(array_unique($ticketIds)))
        ->distinct()
        ->pluck('ticket_id')
        ->map(fn ($ticketId) => (int) $ticketId)
        ->all();
    $seatTicketLookup = array_flip($seatTicketIds);

    return array_values(array_filter($ticketIds, fn ($ticketId) => isset($seatTicketLookup[(int) $ticketId])));
}

private function venueSeatRequiredQuantity(Event $event, array $ticketIds, array $quantities): int
{
    $ticketIds = $this->normalizeApiIds($ticketIds, false);
    $quantities = $this->normalizeApiIds($quantities, false);

    if (empty($ticketIds)) {
        return 0;
    }

    $seatRequiredTicketLookup = array_flip(array_values(array_unique(
        $this->venueSeatRequiredTicketIds($event, $ticketIds)
    )));

    $quantity = 0;

    foreach ($ticketIds as $index => $ticketId) {
        if (! isset($seatRequiredTicketLookup[(int) $ticketId])) {
            continue;
        }

        $quantity += max(1, (int) ($quantities[$index] ?? 1));
    }

    return $quantity;
}

private function filterVenueSeatIdsForRequiredTickets(Event $event, array $venueSeatIds, array $seatHoldingTicketIds): array
{
    $venueSeatIds = $this->normalizeApiIds($venueSeatIds);
    $seatHoldingTicketIds = $this->normalizeApiIds($seatHoldingTicketIds);

    if (empty($venueSeatIds)) {
        return [];
    }

    $venueMapUsesTicketRows = EventVenueSeat::where('event_id', $event->id)
        ->whereNotNull('ticket_id')
        ->exists();

    if (! $venueMapUsesTicketRows) {
        return $venueSeatIds;
    }

    if (empty($seatHoldingTicketIds)) {
        return [];
    }

    $requiredTicketLookup = array_flip(array_values(array_unique($seatHoldingTicketIds)));
    $allowedSeatIds = EventVenueSeat::where('event_id', $event->id)
        ->whereIn('id', $venueSeatIds)
        ->whereIn('ticket_id', array_keys($requiredTicketLookup))
        ->pluck('id')
        ->map(fn ($seatId) => (int) $seatId)
        ->all();
    $allowedSeatLookup = array_flip($allowedSeatIds);

    return array_values(array_filter($venueSeatIds, fn ($seatId) => isset($allowedSeatLookup[(int) $seatId])));
}

private function ticketIsFree(?Ticket $ticket): bool
{
    return $ticket && strtolower(trim((string) $ticket->type)) === 'free';
}

private function hasSeatMapQuantityInput(Request $request): bool
{
    return $request->has('quantity') || $request->has('qauntity');
}

private function seatMapQuantityInput(Request $request)
{
    return $request->input('quantity', $request->input('qauntity', []));
}

private function normalizeApiIds($value, bool $unique = true): array
{
    $ids = [];
    $collect = function ($item) use (&$ids, &$collect) {
        if (is_array($item)) {
            foreach ($item as $child) {
                $collect($child);
            }
            return;
        }

        if (is_string($item)) {
            $trimmed = trim($item);
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $collect($decoded);
                return;
            }

            if (strpos($trimmed, ',') !== false) {
                foreach (explode(',', $trimmed) as $part) {
                    $collect($part);
                }
                return;
            }

            $item = $trimmed;
        }

        if (is_numeric($item) && (int) $item > 0) {
            $ids[] = (int) $item;
        }
    };

    $collect($value);

    if ($unique) {
        $ids = array_values(array_unique($ids));
    }

    return array_values($ids);
}

private function currentApiUserId(): ?int
{
    return Auth::guard('userApi')->id() ?: Auth::id();
}

private function apiGuestHoldOwner($guestHoldKey): ?string
{
    $guestHoldKey = is_string($guestHoldKey) ? trim($guestHoldKey) : '';

    if ($guestHoldKey === '') {
        return null;
    }

    return 'api_guest:' . hash('sha256', $guestHoldKey);
}

private function releaseExpiredVenueSeatHoldsForApi($eventId = null): void
{
    $query = EventVenueSeat::expiredHolds();

    if ($eventId) {
        $query->where('event_id', $eventId);
    }

    $query->update([
        'status' => EventVenueSeat::STATUS_AVAILABLE,
        'hold_token' => null,
        'held_by_session_id' => null,
        'held_by_app_user_id' => null,
        'held_by_guest_user_id' => null,
        'held_at' => null,
        'hold_expires_at' => null,
    ]);
}

private function noStoreResponse($response)
{
    return $response
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
}

private function buildVenueSeatMapPayload(Event $event, array $selectedTicketIds = [], array $selectedVenueSeatIds = [], ?string $guestHoldOwner = null, ?int $selectedTicketQuantityOverride = null, bool $includeAllTicketRows = false): array
{
    $liveVenueMap = $event->liveVenueMap()
        ->with(['venue', 'template', 'sections.rows'])
        ->first();

    if (! $liveVenueMap || ! $liveVenueMap->template) {
        return [
            'has_seat_map' => false,
            'event_id' => (int) $event->id,
            'map' => null,
            'sections' => [],
            'tickets' => [],
            'seats' => [],
            'selected_seats' => [],
            'selected_ticket_quantity' => $selectedTicketQuantityOverride ?? count($selectedTicketIds),
            'selected_seat_quantity' => count($selectedVenueSeatIds),
            'totals' => $this->buildVenueSeatTotals($selectedTicketIds, collect()),
        ];
    }

    $this->releaseExpiredVenueSeatHoldsForApi($event->id);

    $selectedTicketIds = $this->normalizeApiIds($selectedTicketIds, false);
    $selectedVenueSeatIds = $this->normalizeApiIds($selectedVenueSeatIds);
    $selectedTicketIdsUnique = array_values(array_unique($selectedTicketIds));
    $seatHoldingTicketIds = $this->venueSeatRequiredTicketIds($event, $selectedTicketIds);
    $selectedSeatLookup = array_flip($selectedVenueSeatIds);
    $currentUserId = $this->currentApiUserId();

    $seats = $liveVenueMap->seats()
        ->with(['ticket' => function ($query) {
            $query->select('id', 'name', 'type', 'price', 'tax_id')->with('allowUser');
        }])
        ->select([
            'id',
            'event_venue_map_id',
            'event_venue_section_id',
            'event_venue_row_id',
            'event_id',
            'section_name',
            'row_name',
            'seat_number',
            'seat_label',
            'x_percent',
            'y_percent',
            'radius_percent',
            'is_accessible',
            'ticket_id',
            'price',
            'status',
            'held_by_app_user_id',
            'held_by_session_id',
            'hold_expires_at',
        ])
        ->orderBy('event_venue_section_id')
        ->orderBy('event_venue_row_id')
        ->orderBy('seat_number')
        ->get();

    $venueMapUsesTicketRows = $seats->contains(fn ($seat) => ! empty($seat->ticket_id));
    if ($venueMapUsesTicketRows && (empty($selectedTicketIdsUnique) || $includeAllTicketRows)) {
        $ticketRowIds = $seats
            ->pluck('ticket_id')
            ->filter()
            ->map(fn ($ticketId) => (int) $ticketId)
            ->unique()
            ->values()
            ->all();

        $selectedTicketIdsUnique = array_values(array_unique(array_merge($selectedTicketIdsUnique, $ticketRowIds)));
    }
    $selectedTicketLookup = array_flip($selectedTicketIdsUnique);
    $isTheatreCategory = (int) $event->category_id === 8;

    $formattedSeats = $seats->map(function ($seat) use ($selectedSeatLookup, $currentUserId, $guestHoldOwner, $venueMapUsesTicketRows, $selectedTicketLookup, $event, $isTheatreCategory) {
        $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;
        $ticket = $seat->ticket;
        if ($ticket && (int) $ticket->allow_to_user === 0) {
            $status = EventVenueSeat::STATUS_BOOKED;
        }
        $seatTicketId = (int) ($seat->ticket_id ?? 0);

        // Theatre category (category_id=8):
        // - Seats with no ticket_id => red (booked class) - not connected to any ticket row
        // - Held seats by others    => grey (held class, CSS overrides to grey on theatre map)
        // Other categories: original behaviour unchanged
        $isUnconnectedSeat = $isTheatreCategory && $seatTicketId === 0;

        $isSelectedByRequest = isset($selectedSeatLookup[$seat->id]);
        $isHeldByCurrentUser = $status === EventVenueSeat::STATUS_HELD
            && $currentUserId
            && (int) $seat->held_by_app_user_id === (int) $currentUserId
            && $seat->hold_expires_at
            && $seat->hold_expires_at->isFuture();
        $isHeldByCurrentGuest = $status === EventVenueSeat::STATUS_HELD
            && $guestHoldOwner
            && $seat->held_by_session_id === $guestHoldOwner
            && $seat->hold_expires_at
            && $seat->hold_expires_at->isFuture();
        $isAvailable = ($status === EventVenueSeat::STATUS_AVAILABLE) && ! $isUnconnectedSeat;
        $isAvailableForCurrentUser = $isAvailable || $isHeldByCurrentUser || $isHeldByCurrentGuest;
        $matchesSelectedTicket = ! $venueMapUsesTicketRows || ($seatTicketId > 0 && isset($selectedTicketLookup[$seatTicketId]));
        $isSelectable = $isAvailableForCurrentUser && $matchesSelectedTicket && ! $isUnconnectedSeat;
        $isSelected = $matchesSelectedTicket && ($isSelectedByRequest || $isHeldByCurrentUser || $isHeldByCurrentGuest) && ! $isUnconnectedSeat;

        if ($isTheatreCategory) {
            // Theatre: unconnected => booked (red)
            //          held by current user/guest => selected
            //          held by others => 'held' (CSS overrides to grey)
            //          available but wrong ticket row => blocked (grey)
            $classStatus = $isUnconnectedSeat
                ? EventVenueSeat::STATUS_BOOKED
                : ($isSelected
                    ? 'selected'
                    : (($isAvailableForCurrentUser && ! $matchesSelectedTicket) ? EventVenueSeat::STATUS_BLOCKED : $status));
        } else {
            // Original logic for all other categories
            $classStatus = $isSelected
                ? 'selected'
                : (($isAvailableForCurrentUser && ! $matchesSelectedTicket) ? EventVenueSeat::STATUS_BLOCKED : $status);
        }
        $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);
        $ticket = $seat->ticket;
        $price = $this->resolveVenueSeatUnitPrice($seat->price, $ticket?->price);

        return [
            'id' => (int) $seat->id,
            'seat_label' => $seatLabel,
            'section_name' => $seat->section_name,
            'row_name' => $seat->row_name,
            'seat_number' => (int) $seat->seat_number,
            'x_percent' => (float) $seat->x_percent,
            'y_percent' => (float) $seat->y_percent,
            'radius_percent' => $seat->radius_percent !== null ? (float) $seat->radius_percent : null,
            'is_accessible' => (bool) $seat->is_accessible,
            'ticket_id' => $seatTicketId ?: null,
            'ticket_name' => $ticket->name ?? null,
            'ticket_type' => $ticket->type ?? null,
            'price' => $price,
            'status' => $status,
            'class_status' => $classStatus,
            'selectable' => $isSelectable,
            'disabled' => ! $isSelectable,
            'selected' => $isSelected,
            'matches_selected_ticket' => $matchesSelectedTicket,
            'held_by_current_user' => $isHeldByCurrentUser || $isHeldByCurrentGuest,
            'hold_expires_at' => $seat->hold_expires_at ? $seat->hold_expires_at->toIso8601String() : null,
            'color' => $isTheatreCategory
                ? $this->venueSeatStatusColorTheatre($classStatus)   // theatre-specific colors
                : $this->venueSeatStatusColor($classStatus),
            'title' => trim($seatLabel . ' - ' . ucfirst($classStatus)),
        ];
    })->values();

    $selectedSeats = $formattedSeats
        ->filter(fn ($seat) => ! empty($seat['selected']))
        ->values();

    $selectedTicketCounts = array_count_values($selectedTicketIds);
    $selectedTickets = Ticket::where('event_id', $event->id)
        ->whereIn('id', $selectedTicketIdsUnique ?: [0])
        ->get()
        ->keyBy('id');

    return [
        'has_seat_map' => true,
        'event_id' => (int) $event->id,
        'is_theatre' => $isTheatreCategory,
        'server_time' => now()->toIso8601String(),
        'map' => [
            'id' => (int) $liveVenueMap->id,
            'venue_id' => (int) $liveVenueMap->venue_id,
            'venue_name' => optional($liveVenueMap->venue)->name,
            'template_id' => (int) $liveVenueMap->venue_map_template_id,
            'selection_mode' => $liveVenueMap->selection_mode,
            'hold_minutes' => (int) ($liveVenueMap->hold_minutes ?: 10),
            'seat_shape' => optional($liveVenueMap->template)->seat_shape,
            'show_row_name' => optional($liveVenueMap->template)->show_row_name,
            'layout_style' => optional($liveVenueMap->template)->layout_style,
            'focal_x' => optional($liveVenueMap->template)->focal_x,
            'focal_y' => optional($liveVenueMap->template)->focal_y,
            'background_image' => optional($liveVenueMap->template)->background_image,
            'background_image_url' => optional($liveVenueMap->template)->background_image
                ? url('images/upload/' . $liveVenueMap->template->background_image)
                : null,
            'background_width' => optional($liveVenueMap->template)->background_width,
            'background_height' => optional($liveVenueMap->template)->background_height,
            'status' => $liveVenueMap->status,
        ],
        'legend' => $isTheatreCategory
            ? [
                // Theatre: held = grey, unconnected/booked = red
                'selected'  => ['label' => 'Selected by current user',      'color' => $this->venueSeatStatusColor('selected')],
                'held'      => ['label' => 'Hold / Blocked',                 'color' => '#9ca3af'],
                'booked'    => ['label' => 'Booked / Unavailable',           'color' => '#ef4444'],
                'blocked'   => ['label' => 'Blocked for selected ticket',    'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_BLOCKED)],
                'available' => ['label' => 'Available',                      'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_AVAILABLE)],
            ]
            : [
                'selected'  => ['label' => 'Selected by current user',       'color' => $this->venueSeatStatusColor('selected')],
                'held'      => ['label' => 'Temporarily locked',             'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_HELD)],
                'booked'    => ['label' => 'Booked',                         'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_BOOKED)],
                'blocked'   => ['label' => 'Blocked for selected ticket',    'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_BLOCKED)],
                'available' => ['label' => 'Available',                      'color' => $this->venueSeatStatusColor(EventVenueSeat::STATUS_AVAILABLE)],
            ],
        'sections' => $liveVenueMap->sections
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->map(function ($section) {
                return [
                    'id' => (int) $section->id,
                    'name' => $section->name,
                    'code' => $section->code,
                    'ticket_id' => $section->ticket_id ? (int) $section->ticket_id : null,
                    'price' => $section->price !== null ? (float) $section->price : null,
                    'rows' => $section->rows
                        ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                        ->map(function ($row) {
                            return [
                                'id' => (int) $row->id,
                                'name' => $row->name,
                                'seat_count' => (int) $row->seat_count,
                                'ticket_id' => $row->ticket_id ? (int) $row->ticket_id : null,
                                'price' => $row->price !== null ? (float) $row->price : null,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values(),
        'tickets' => $selectedTickets
            ->map(function ($ticket) use ($selectedTicketCounts) {
                return [
                    'id' => (int) $ticket->id,
                    'name' => $ticket->name,
                    'type' => $ticket->type,
                    'price' => (float) $ticket->price,
                    'selected_quantity' => (int) ($selectedTicketCounts[$ticket->id] ?? 0),
                ];
            })
            ->values(),
        'seats' => $formattedSeats,
        'selected_seats' => $selectedSeats,
        'selected_ticket_quantity' => $selectedTicketQuantityOverride ?? count($selectedTicketIds),
        'selected_seat_quantity' => count($selectedVenueSeatIds),
        'totals' => $this->buildVenueSeatTotals($selectedTicketIds, $selectedSeats),
        'uses_ticket_rows' => $venueMapUsesTicketRows,
    ];
}

private function buildVenueSeatMapStageOnlyPayload(array $payload): array
{
    return [
        'event_id' => $payload['event_id'],
        'server_time' => $payload['server_time'] ?? null,
        'stage' => [
            'map_id' => $payload['map']['id'] ?? null,
            'venue_name' => $payload['map']['venue_name'] ?? null,
            'seat_shape' => $payload['map']['seat_shape'] ?? null,
            'show_row_name' => $payload['map']['show_row_name'] ?? null,
            'layout_style' => $payload['map']['layout_style'] ?? null,
            'focal_x' => $payload['map']['focal_x'] ?? null,
            'focal_y' => $payload['map']['focal_y'] ?? null,
            'background_image_url' => $payload['map']['background_image_url'] ?? null,
            'background_width' => $payload['map']['background_width'] ?? null,
            'background_height' => $payload['map']['background_height'] ?? null,
            'selection_mode' => $payload['map']['selection_mode'] ?? null,
            'hold_minutes' => $payload['map']['hold_minutes'] ?? null,
            'legend' => $payload['legend'] ?? [],
            'seats' => $payload['seats'] ?? [],
            'selected_seats' => $payload['selected_seats'] ?? [],
        ],
        'tickets' => $payload['tickets'] ?? [],
        'uses_ticket_rows' => $payload['uses_ticket_rows'] ?? false,
        'selected_ticket_quantity' => $payload['selected_ticket_quantity'] ?? 0,
        'selected_seat_quantity' => $payload['selected_seat_quantity'] ?? 0,
        'totals' => $payload['totals'] ?? [],
    ];
}

private function venueSeatStatusColor(string $status): string
{
    return match ($status) {
        'selected' => '#ffffff',
        EventVenueSeat::STATUS_HELD => '#facc15',
        EventVenueSeat::STATUS_BOOKED => '#ef4444',
        EventVenueSeat::STATUS_BLOCKED => '#9ca3af',
        default => '#22c55e',
    };
}

/** Theatre category (category_id=8) color overrides:
 *  held  => grey  (same as blocked - not selectable)
 *  booked => red  (same as standard booked / unconnected seats)
 */
private function venueSeatStatusColorTheatre(string $status): string
{
    return match ($status) {
        'selected'                      => '#ffffff',
        EventVenueSeat::STATUS_HELD     => '#9ca3af',   // grey - not available
        EventVenueSeat::STATUS_BOOKED   => '#ef4444',   // red
        EventVenueSeat::STATUS_BLOCKED  => '#9ca3af',   // grey
        default                         => '#22c55e',   // available = green
    };
}

private function buildVenueSeatTotals(array $selectedTicketIds, $selectedSeats): array
{
    $selectedTicketIds = $this->normalizeApiIds($selectedTicketIds, false);
    $selectedSeatTicketIds = $selectedSeats instanceof \Illuminate\Support\Collection
        ? $selectedSeats
            ->pluck('ticket_id')
            ->filter()
            ->map(fn ($ticketId) => (int) $ticketId)
            ->all()
        : [];
    $ticketIds = array_values(array_unique(array_merge($selectedTicketIds, $selectedSeatTicketIds)));
    $tickets = Ticket::whereIn('id', $ticketIds ?: [0])
        ->get()
        ->keyBy('id');
    $lineItems = [];
    $taxDetails = [];
    $subtotal = 0;
    $taxTotal = 0;
    $currency = optional(Setting::first())->currency;

    if ($selectedSeats instanceof \Illuminate\Support\Collection && $selectedSeats->count() > 0) {
        foreach ($selectedSeats->values() as $index => $seat) {
            $seat = (array) $seat;
            $ticketId = (int) (($seat['ticket_id'] ?? 0) ?: ($selectedTicketIds[$index] ?? 0));
            $ticket = $tickets->get($ticketId);

            if (! $ticket) {
                continue;
            }

            $unitPrice = $this->resolveVenueSeatUnitPrice($seat['price'] ?? null, $ticket->price) ?? 0.0;
            $tax = $this->calculateTicketTaxForApi($ticket, $unitPrice, 1);
            $subtotal += $unitPrice;
            $taxTotal += $tax['total_tax'];
            $taxDetails = array_merge($taxDetails, $tax['tax_details']);
            $lineItems[] = [
                'ticket_id' => (int) $ticket->id,
                'ticket_name' => $ticket->name,
                'ticket_type' => $ticket->type,
                'quantity' => 1,
                'unit_price' => round($unitPrice, 2),
                'seat_id' => (int) ($seat['id'] ?? 0),
                'seat_label' => $seat['seat_label'] ?? null,
                'line_subtotal' => round($unitPrice, 2),
                'line_tax' => round($tax['total_tax'], 2),
                'line_total' => round($unitPrice + $tax['total_tax'], 2),
            ];
        }
    } else {
        foreach (array_count_values($selectedTicketIds) as $ticketId => $quantity) {
            $ticket = $tickets->get((int) $ticketId);

            if (! $ticket) {
                continue;
            }

            $unitPrice = (float) $ticket->price;
            $lineSubtotal = $unitPrice * (int) $quantity;
            $tax = $this->calculateTicketTaxForApi($ticket, $unitPrice, (int) $quantity);
            $subtotal += $lineSubtotal;
            $taxTotal += $tax['total_tax'];
            $taxDetails = array_merge($taxDetails, $tax['tax_details']);
            $lineItems[] = [
                'ticket_id' => (int) $ticket->id,
                'ticket_name' => $ticket->name,
                'ticket_type' => $ticket->type,
                'quantity' => (int) $quantity,
                'unit_price' => round($unitPrice, 2),
                'line_subtotal' => round($lineSubtotal, 2),
                'line_tax' => round($tax['total_tax'], 2),
                'line_total' => round($lineSubtotal + $tax['total_tax'], 2),
            ];
        }
    }

    return [
        'currency' => $currency,
        'quantity' => array_sum(array_column($lineItems, 'quantity')),
        'subtotal' => round($subtotal, 2),
        'tax' => round($taxTotal, 2),
        'total' => round($subtotal + $taxTotal, 2),
        'tax_details' => $taxDetails,
        'line_items' => $lineItems,
    ];
}

private function calculateTicketTaxForApi(Ticket $ticket, float $unitPrice, int $quantity = 1): array
{
    $taxDetails = [];
    $totalTax = 0;

    if ($this->ticketIsFree($ticket) || $unitPrice <= 0 || $quantity <= 0) {
        return [
            'total_tax' => 0,
            'tax_details' => [],
        ];
    }

    $taxIds = array_filter(array_map('trim', explode(',', (string) $ticket->tax_id)));
    $taxes = Tax::where('status', 1)
        ->where('allow_all_bill', 1)
        ->when(
            ! empty($taxIds),
            fn ($query) => $query->whereIn('id', $taxIds),
            fn ($query) => $query->where('is_default', 1)->limit(1)
        )
        ->orderBy('id', 'DESC')
        ->get();

    foreach ($taxes as $tax) {
        $unitTax = $tax->amount_type === 'percentage'
            ? round(($unitPrice * $tax->price) / 100, 2)
            : round((float) $tax->price, 2);
        $calculatedAmount = round($unitTax * $quantity, 2);
        $totalTax += $calculatedAmount;
        $taxDetails[] = [
            'ticket_id' => (int) $ticket->id,
            'tax_id' => (int) $tax->id,
            'name' => $tax->name,
            'amount_type' => $tax->amount_type,
            'rate' => (float) $tax->price,
            'quantity' => $quantity,
            'calculated_amount' => $calculatedAmount,
        ];
    }

    return [
        'total_tax' => round($totalTax, 2),
        'tax_details' => $taxDetails,
    ];
}

private function resolveVenueSeatUnitPrice($seatPrice, $ticketPrice): ?float
{
    $seatPrice = $seatPrice !== null && $seatPrice !== '' ? (float) $seatPrice : null;
    $ticketPrice = $ticketPrice !== null && $ticketPrice !== '' ? (float) $ticketPrice : null;

    if ($seatPrice !== null && $seatPrice > 0) {
        return $seatPrice;
    }

    if ($ticketPrice !== null && $ticketPrice > 0) {
        return $ticketPrice;
    }

    return $seatPrice ?? $ticketPrice;
}

private function attachApiVenueSeatsToOrder(Order $order, Event $event, array $venueSeatIds): void
{
    if (empty($venueSeatIds)) {
        return;
    }

    $this->releaseExpiredVenueSeatHoldsForApi($event->id);

    $userId = $this->currentApiUserId();
    $orderTicketIds = $this->normalizeApiIds($order->ticket_id, false);
    $requiredOrderTicketIds = array_values(array_unique($this->venueSeatRequiredTicketIds($event, $orderTicketIds)));
    $orderChildren = OrderChild::where('order_id', $order->id)
        ->when(! empty($requiredOrderTicketIds), fn ($query) => $query->whereIn('ticket_id', $requiredOrderTicketIds))
        ->orderBy('id')
        ->get()
        ->values();
    $orderChildrenByTicket = $orderChildren->groupBy('ticket_id')->map(fn ($children) => $children->values());
    $orderChildCursors = [];
    $fallbackOrderChildIndex = 0;

    foreach ($venueSeatIds as $index => $venueSeatId) {
        $seat = EventVenueSeat::where('event_id', $event->id)
            ->where('id', $venueSeatId)
            ->lockForUpdate()
            ->first();

        if (! $seat || $seat->status !== EventVenueSeat::STATUS_HELD) {
            throw ValidationException::withMessages([
                'venue_seat_ids' => 'One or more selected seats are no longer held.',
            ]);
        }

        if ((int) $seat->held_by_app_user_id !== (int) $userId || $seat->isHoldExpired()) {
            throw ValidationException::withMessages([
                'venue_seat_ids' => 'Your seat hold has expired. Please select seats again.',
            ]);
        }

        $seatTicketId = (int) ($seat->ticket_id ?? 0);
        $orderChild = null;

        if ($seatTicketId > 0 && $orderChildrenByTicket->has($seatTicketId)) {
            $ticketChildIndex = $orderChildCursors[$seatTicketId] ?? 0;
            $orderChild = $orderChildrenByTicket->get($seatTicketId)->get($ticketChildIndex);
            $orderChildCursors[$seatTicketId] = $ticketChildIndex + 1;
        }

        if (! $orderChild) {
            $orderChild = $orderChildren->get($fallbackOrderChildIndex);
            $fallbackOrderChildIndex++;
        }

        $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);

        $seat->update([
            'status' => EventVenueSeat::STATUS_BOOKED,
            'booked_order_id' => $order->id,
            'booked_order_child_id' => $orderChild ? $orderChild->id : null,
            'booked_at' => now(),
            'hold_token' => null,
            'held_by_session_id' => null,
            'held_by_app_user_id' => null,
            'held_by_guest_user_id' => null,
            'held_at' => null,
            'hold_expires_at' => null,
        ]);

        if ($orderChild && Schema::hasColumn('order_child', 'event_venue_seat_id')) {
            $orderChild->update([
                'event_venue_seat_id' => $seat->id,
                'Book_Seat_Id' => $seatLabel,
            ]);
        }
    }
}







private function attachApiGuestVenueSeatsToOrder(Order $order, Event $event, array $venueSeatIds, ?string $guestHoldOwner): void
{
    if (empty($venueSeatIds)) {
        return;
    }

    $this->releaseExpiredVenueSeatHoldsForApi($event->id);

    $orderTicketIds = $this->normalizeApiIds($order->ticket_id, false);
    $requiredOrderTicketIds = array_values(array_unique($this->venueSeatRequiredTicketIds($event, $orderTicketIds)));
    $orderChildren = OrderChild::where('order_id', $order->id)
        ->when(! empty($requiredOrderTicketIds), fn ($query) => $query->whereIn('ticket_id', $requiredOrderTicketIds))
        ->orderBy('id')
        ->get()
        ->values();
    $orderChildrenByTicket = $orderChildren->groupBy('ticket_id')->map(fn ($children) => $children->values());
    $orderChildCursors = [];
    $fallbackOrderChildIndex = 0;

    foreach ($venueSeatIds as $index => $venueSeatId) {
        $seat = EventVenueSeat::where('event_id', $event->id)
            ->where('id', $venueSeatId)
            ->lockForUpdate()
            ->first();

        if (! $seat) {
            throw ValidationException::withMessages([
                'venue_seat_ids' => 'One or more selected seats were not found.',
            ]);
        }

        $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;
        $isAvailable = $status === EventVenueSeat::STATUS_AVAILABLE;
        $isHeldByCurrentGuest = $status === EventVenueSeat::STATUS_HELD
            && $guestHoldOwner
            && $seat->held_by_session_id === $guestHoldOwner
            && ! $seat->isHoldExpired();

        if (! $isAvailable && ! $isHeldByCurrentGuest) {
            throw ValidationException::withMessages([
                'venue_seat_ids' => 'One or more selected seats are no longer available.',
            ]);
        }

        $seatTicketId = (int) ($seat->ticket_id ?? 0);
        $orderChild = null;

        if ($seatTicketId > 0 && $orderChildrenByTicket->has($seatTicketId)) {
            $ticketChildIndex = $orderChildCursors[$seatTicketId] ?? 0;
            $orderChild = $orderChildrenByTicket->get($seatTicketId)->get($ticketChildIndex);
            $orderChildCursors[$seatTicketId] = $ticketChildIndex + 1;
        }

        if (! $orderChild) {
            $orderChild = $orderChildren->get($fallbackOrderChildIndex);
            $fallbackOrderChildIndex++;
        }

        $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);

        $seat->update([
            'status' => EventVenueSeat::STATUS_BOOKED,
            'booked_order_id' => $order->id,
            'booked_order_child_id' => $orderChild ? $orderChild->id : null,
            'booked_at' => now(),
            'hold_token' => null,
            'held_by_session_id' => null,
            'held_by_app_user_id' => null,
            'held_by_guest_user_id' => null,
            'held_at' => null,
            'hold_expires_at' => null,
        ]);

        if ($orderChild) {
            $orderChildUpdate = ['Book_Seat_Id' => $seatLabel];

            if (Schema::hasColumn('order_child', 'event_venue_seat_id')) {
                $orderChildUpdate['event_venue_seat_id'] = $seat->id;
            }

            $orderChild->update($orderChildUpdate);
        }
    }
}

public function createOrder(Request $request)
{
        DB::beginTransaction();

        // Log full request body (mask sensitive tokens)
        try {
            $loggedRequest = $request->all();
            if (isset($loggedRequest['payment_token'])) {
                $loggedRequest['payment_token'] = '***';
            }
            if (isset($loggedRequest['paymentToken'])) {
                $loggedRequest['paymentToken'] = '***';
            }
            Log::info('=== createOrder request started ===', $loggedRequest);
        } catch (\Throwable $e) {
            Log::warning('createOrder: could not log request: ' . $e->getMessage());
        }

        try {

        // Handle tax_data from string or array
        if (is_string($request->tax_data)) {
            $request->merge(['tax_data' => json_decode($request->tax_data, true)]);
        }

        // Convert empty coupon_id to null
        if ($request->coupon_id === '' || $request->coupon_id === ' ') {
            $request->merge(['coupon_id' => null]);
        }

        // Convert "null" string to null for payment_token when payment_type is LOCAL
        if ($request->payment_type === 'LOCAL' && $request->payment_token === 'null') {
            $request->merge(['payment_token' => null]);
        }
        if (is_string($request->ticket_id)) {
            $ticketIds = array_filter(explode(',', $request->ticket_id));
            $request->merge(['ticket_id' => $ticketIds]);
        }

        // Convert comma-separated quantity string to array
        if (is_string($request->quantity)) {
            $quantities = array_filter(explode(',', $request->quantity));
            $request->merge(['quantity' => $quantities]);
        }

        $venueSeatIds = $this->normalizeApiIds(
            $request->input('venue_seat_ids', $request->input('selectedVenueSeatIds', $request->input('seat_ids', [])))
        );

        if (! empty($venueSeatIds)) {
            $request->merge(['venue_seat_ids' => $venueSeatIds]);
        }

        $validated = $request->validate([
            'event_id' => 'bail|required|exists:events,id',
            'ticket_id' => 'bail|required|array|min:1',
            'ticket_id.*' => 'exists:tickets,id',
            'quantity' => 'bail|required|array|min:1',
            'quantity.*' => 'integer|min:1',
            'coupon_discount' => 'bail|required|numeric|min:0',
            'payment' => 'bail|required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_type' => 'bail|required|in:LOCAL,STRIPE,PAYPAL,RAZOR,WALLET,FREE',
            'payment_token' => 'nullable|required_if:payment_type,STRIPE,PAYPAL,RAZOR',
            'coupon_id' => 'nullable|sometimes|exists:coupon,id',
            'tax_data' => 'nullable|array',
            'tax_data.*.tax_id' => 'required|exists:tax,id',
            'tax_data.*.price' => 'required|numeric|min:0',
            'venue_seat_ids' => 'nullable|array',
            'venue_seat_ids.*' => 'integer|exists:event_venue_seats,id',
        ], [
            'ticket_id.required' => 'At least one ticket is required',
            'quantity.required' => 'Quantity is required for each ticket',
            'quantity.*.min' => 'Quantity must be at least 1',
            'payment_token.required_if' => 'Payment token is required for this payment method',
        ]);

        // Match count of ticket_id and quantity
        if (count($request->ticket_id) !== count($request->quantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'The number of tickets and quantities must match'
            ]);
        }

        // Validate tax_data manually
        $taxData = $request->tax_data ?? [];
        foreach ($taxData as $tax) {
            if (!isset($tax['tax_id']) || !isset($tax['price'])) {
                throw ValidationException::withMessages([
                    'tax_data' => 'Each tax item must have tax_id and price'
                ]);
            }
        }

        $totalQuantity = array_sum($request->quantity);
        $event = Event::findOrFail($request->event_id);
        $seatRequiredQuantity = $this->venueSeatRequiredQuantity($event, $request->ticket_id, $request->quantity);

        if (! empty($venueSeatIds) && count($venueSeatIds) !== (int) $seatRequiredQuantity) {
            throw ValidationException::withMessages([
                'venue_seat_ids' => 'Selected seat count must match selected ticket quantity.',
            ]);
        }

        // Prepare order data
        $orderData = [
            'order_id' => '#' . rand(9999, 100000),
            'organization_id' => $event->user_id,
            'customer_id' => Auth::id(),
            'event_id' => $request->event_id,
            'payment_status' => $request->payment_type == "LOCAL" ? 0 : 1,
            'order_status' => $request->payment_type == "LOCAL" ? 'Pending' : 'Complete',
            'quantity' => $totalQuantity, // do not encode
            'ticket_id' => is_array($request->ticket_id) ? implode(',', $request->ticket_id) : $request->ticket_id,
            'book_seats' => is_array($request->book_seats) ? json_encode($request->book_seats) : $request->book_seats,
            'seat_details' => is_array($request->seat_details) ? json_encode($request->seat_details) : $request->seat_details,
            'coupon_discount' => $request->coupon_discount,
            'payment' => $request->payment,
            'tax' => $request->tax ?? 0,
            'payment_type' => $request->payment_type,
            'payment_token' => $request->payment_token
        ];

        // Wallet Payment
        if ($request->payment_type == 'WALLET') {
            $user = AppUser::findOrFail(Auth::id());
            if ($user->balance < $request->payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ], 400);
            }
            $user->withdraw($request->payment, ['event_id' => $request->event_id]);
        }

        // Commission Calculation
        $com = Setting::first(['org_commission_type', 'org_commission']);
        $orderData['org_commission'] = $request->payment_type == "FREE" ? 0 : (
            $com->org_commission_type == "percentage"
                ? ($request->payment - $request->tax) * $com->org_commission / 100
                : $com->org_commission
        );

        // Coupon
        if ($request->coupon_id) {
            $coupon = Coupon::findOrFail($request->coupon_id);
            $coupon->increment('use_count');

            CouponUsageHistory::create([
                'coupon_id' => $coupon->id,
                'appuser_id' => Auth::id()
            ]);

            $orderData['coupon_discount'] = $coupon->discount;
            $orderData['coupon_id'] = $coupon->id;
        }

        // Stripe Payment
        if ($request->payment_type == "STRIPE") {
            $currency = Setting::first()->currency;
            $stripeAmount = in_array($currency, ["USD", "EUR", "INR"])
                ? $request->payment * 100
                : $request->payment;

            Stripe\Stripe::setApiKey(PaymentSetting::first()->stripeSecretKey);

            // Check if payment_token is a Stripe Token (tok_xxx) or Payment Intent (pi_xxx)
            if ($request->payment_token && strpos($request->payment_token, 'tok_') === 0) {
                // It's a Stripe Token - need to create Payment Intent with this token
                \Log::info("Received Stripe Token: {$request->payment_token}, creating Payment Intent");

                $paymentIntent = Stripe\PaymentIntent::create([
                    "amount" => $stripeAmount,
                    "currency" => $currency,
                    "payment_method_data" => [
                        "type" => "card",
                        "card" => ["token" => $request->payment_token]
                    ],
                    "confirm" => true, // Auto-confirm the payment
                    "automatic_payment_methods" => ["enabled" => true, "allow_redirects" => "never"]
                ]);

                $orderData['payment_token'] = $paymentIntent->id;
                \Log::info("Payment Intent created from token: {$paymentIntent->id}");

            } elseif ($request->payment_token && strpos($request->payment_token, 'pi_') === 0) {
                // It's already a Payment Intent ID - use it directly
                \Log::info("Received Payment Intent: {$request->payment_token}");
                $orderData['payment_token'] = $request->payment_token;

            } else {
                // No token provided - create new payment intent
                \Log::info("No payment token provided, creating new Payment Intent");

                $paymentIntent = Stripe\PaymentIntent::create([
                    "amount" => $stripeAmount,
                    "currency" => $currency,
                ]);

                $orderData['payment_token'] = $paymentIntent->id;
            }
        }

        // Create Order
        $order = Order::create($orderData);
        \Log::info("Order created successfully: #{$order->order_id}, payment_type: {$request->payment_type}, payment_token: " . ($order->payment_token ?? 'null'));

        // Seatmap handling
        if (Module::where('module', 'seatmap')->where('is_enable', 1)->exists() && $request->book_seats) {
            \Modules\Seatmap\Entities\Seats::whereIn('id', explode(',', $request->book_seats))
                ->update(['type' => 'occupied']);
        }

        // Order children
        foreach ($request->ticket_id as $index => $ticketId) {
            $ticket = Ticket::findOrFail($ticketId);
            $quantity = $request->quantity[$index];

            OrderChild::insert(
                array_fill(0, $quantity, [
                    'ticket_number' => uniqid(),
                    'ticket_id' => $ticketId,
                    'order_id' => $order->id,
                    'customer_id' => Auth::id(),
                    'checkin' => $ticket->maximum_checkins,
                    'paid' => $request->payment_type == 'LOCAL' ? 0 : 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->attachApiVenueSeatsToOrder($order, $event, $venueSeatIds);

        // Order Taxes
        if (!empty($taxData)) {
            $orderTaxes = array_map(function ($tax) use ($order) {
                return [
                    'order_id' => $order->id,
                    'tax_id' => $tax['tax_id'],
                    'price' => $tax['price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $taxData);

            OrderTax::insert($orderTaxes);
        }
        $this->createOrderFees($order);

        // Send notifications
        $this->sendOrderNotifications($order, array_sum($request->quantity));

        DB::commit();

        // Store Stripe Transaction AFTER commit - so it won't rollback if there's an error
        if ($request->payment_type == 'STRIPE' && $order->payment_token) {
            \Log::info("Attempting to store Stripe transaction for order #{$order->order_id}");
            $this->retrieveAndStoreStripeTransaction($order);
        }

        $response = response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);

        Log::info('=== createOrder response (success) ===', $response->getData(true));

        // Log after sending notifications as well (final confirmation)
        Log::info('=== createOrder final response returning ===', $response->getData(true));

        return $response;

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
            'request_data' => $request->all()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Order creation failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Order creation failed',
            'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}

protected function sendOrderNotifications($order, $totalQuantity)
{
    try {
        // Get settings
        $setting = Setting::first();

        if (!$setting->mail_notification) {
            \Log::info("Mail notifications disabled, skipping order notification");
            return;
        }

        // Get notification template for ticket booking
        $template = NotificationTemplate::where('title', 'Book Ticket')->first();

        if (!$template) {
            \Log::error('Book Ticket notification template not found');
            return;
        }

        // Get authenticated user
        $user = AppUser::find(Auth::id());

        if (!$user) {
            \Log::error('User not found for order notification');
            return;
        }

        // Load order with relationships
        $order = Order::with(['event', 'organization', 'customer'])->find($order->id);
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

        // Generate individual ticket PNGs
        $tempDirectory = storage_path('temp_qrcodes');
        if (!file_exists($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $ticketFiles = [];
        $ticketCounter = 1;

        foreach ($order->tickets() as $ticketData) {
            $orderchildren = OrderChild::where('ticket_id', $ticketData->id)
                ->where('order_id', $order->id)
                ->get();

            foreach ($orderchildren as $orderChild) {
                // Build single ticket HTML inline
                $singleTicketHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
                $singleTicketHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: transparent; }';
                $singleTicketHtml .= '.ticket { width: 320px; border: 2px solid #d32f2f; border-radius: 10px; background-color: white; overflow: hidden; }';
                $singleTicketHtml .= '.ticket-header { background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold; }';
                $singleTicketHtml .= '.ticket-body { padding: 15px; }';
                $singleTicketHtml .= '.event-info { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; }';
                $singleTicketHtml .= '.event-info img { width: 60px; height: 40px; border-radius: 5px; object-fit: cover; }';
                $singleTicketHtml .= '.event-details { flex-grow: 1; }';
                $singleTicketHtml .= '.event-name { font-size: 16px; font-weight: bold; margin-bottom: 3px; }';
                $singleTicketHtml .= '.event-meta { font-size: 12px; color: #666; margin-bottom: 2px; }';
                $singleTicketHtml .= '.ticket-divider { border-top: 1px dashed #d32f2f; padding-top: 10px; margin-top: 10px; }';
                $singleTicketHtml .= '.ticket-type { font-size: 14px; color: #d32f2f; font-weight: bold; margin-bottom: 5px; }';
                $singleTicketHtml .= '.ticket-info { font-size: 16px; font-weight: bold; margin: 5px 0; }';
                $singleTicketHtml .= '.ticket-info span { font-weight: 400; }';
                $singleTicketHtml .= '.qr-code-section { text-align: center; margin-top: 10px; }';
                $singleTicketHtml .= '.qr-code-section img { width: 200px; height: 200px; }';
                $singleTicketHtml .= '.ticket-number { text-align: center; margin-top: 5px; font-size: 12px; color: #666; }';
                $singleTicketHtml .= '.ticket-footer { background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: #666; }';
                $singleTicketHtml .= '</style></head><body><div class="ticket">';
                $singleTicketHtml .= '<div class="ticket-header"><span>The Event Palette</span></div>';
                $singleTicketHtml .= '<div class="ticket-body"><div class="event-info">';

                // Add event image as base64
                $imagePath = public_path('images/upload/' . $order->event->image);
                if (file_exists($imagePath)) {
                    $imageContent = file_get_contents($imagePath);
                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                    $imageData = 'data:image/' . $imageType . ';base64,' . base64_encode($imageContent);
                    $singleTicketHtml .= '<img src="' . $imageData . '" alt="Event Poster">';
                }

                $singleTicketHtml .= '<div class="event-details">';
                $singleTicketHtml .= '<div class="event-name">' . htmlspecialchars($order->event->name) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . htmlspecialchars($order->organization->organization_name) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . ($order->event->type == 'online' ? 'Online Event' : htmlspecialchars($order->event->address)) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . $order->event->start_time->format('F d Y') . ' | ' . $order->event->start_time->format('h:i a') . '</div>';
                $singleTicketHtml .= '</div></div>';
                $singleTicketHtml .= '<div class="ticket-divider">';
                $singleTicketHtml .= '<div class="ticket-type">' . htmlspecialchars($ticketData->name) . '</div>';
                $singleTicketHtml .= '<div class="ticket-info">Ticket: <span>' . htmlspecialchars($ticketData->type) . '</span></div>';
                if (!empty($orderChild->Book_Seat_Id)) {
                    $singleTicketHtml .= '<div class="ticket-info">Seat Number: <span>' . htmlspecialchars($orderChild->Book_Seat_Id) . '</span></div>';
                }
                $singleTicketHtml .= '</div><div class="qr-code-section">';

                // Generate QR code
                $qrCode = QrCode::format('png')->size(200)->generate($orderChild->ticket_number);
                $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);

                $singleTicketHtml .= '<img src="' . $base64QrCode . '" alt="QR Code">';
                $singleTicketHtml .= '</div><div class="ticket-number">#' . htmlspecialchars($orderChild->ticket_number) . '</div>';
                $singleTicketHtml .= '</div><div class="ticket-footer">All Sales Are Final! No Refunds!</div>';
                $singleTicketHtml .= '</div></body></html>';

                // Generate PDF
                $customPaper = array(0, 0, 360, 650);
                $pdf = Pdf::loadHTML($singleTicketHtml)->setPaper($customPaper, 'portrait');
                $pdfOutput = $pdf->output();

                $ticketFileName = "ticket_with_qr_{$ticketCounter}.pdf";
                $ticketFilePath = "{$tempDirectory}/{$ticketFileName}";
                file_put_contents($ticketFilePath, $pdfOutput);

                $ticketFiles[] = [
                    'path' => $ticketFilePath,
                    'name' => $ticketFileName
                ];

                $ticketCounter++;
            }
        }

        // Generate invoice PDF
        $invoicePaper = array(0, 0, 720, 1440);
        $invoicePdf = Pdf::loadView('ticketmail', compact('order'))
            ->setPaper($invoicePaper, 'portrait');
        $invoicePdfOutput = $invoicePdf->output();

        // Prepare email details
        $details = [
            'user_name' => "{$user->name} {$user->last_name}",
            'quantity' => $totalQuantity,
            'event_name' => $order->event->name,
            'date' => $order->event->start_time->format('d F Y h:i a'),
            'app_name' => $setting->app_name,
        ];

        // ✅ Store notification in DB (so userNotification endpoint shows it)
        $messageTemplate = $template->message_content;
        $message1 = str_replace(
            ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"],
            $details,
            $messageTemplate
        );

        Notification::create([
            'organizer_id' => null,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'title' => 'Ticket Booked',
            'message' => $message1,
        ]);

        // Send ticket booking notification email
        $qrcode = $order->order_id;
        Mail::to($user->email)->send(new TicketBook($template->mail_content, $details, $template->subject, $qrcode));


        // Send tickets and invoice as attachments
        $emailData = [
            'email' => $user->email,
            'title' => 'Your Event Tickets & Invoice | ' . $setting->app_name,
            'body' => 'Thank you for your order! Please find your event tickets with QR codes and invoice attached.',
        ];

        Mail::send('mail', $emailData, function ($message) use ($emailData, $ticketFiles, $invoicePdfOutput, $setting) {
            $message->from($setting->sender_email, $setting->app_name)
                ->to($emailData['email'])
                ->subject($emailData['title']);

            // Attach each individual ticket
            foreach ($ticketFiles as $ticketFile) {
                $message->attachData(file_get_contents($ticketFile['path']), $ticketFile['name'], ['mime' => 'application/pdf']);
            }

            // Attach invoice
            $message->attachData($invoicePdfOutput, 'invoice.pdf', ['mime' => 'application/pdf']);
        });

        // Clean up temp ticket files
        foreach ($ticketFiles as $ticketFile) {
            if (file_exists($ticketFile['path'])) {
                unlink($ticketFile['path']);
            }
        }

        \Log::info("Order notification sent successfully for order #{$order->order_id}");

    } catch (\Exception $e) {
        \Log::error('Failed to send order notifications: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
    }
}

/**
 * Send email notifications for guest orders with ticket and invoice
 */
protected function sendGuestOrderNotifications($orderInput, $guest, $totalQuantity)
{
    try {
        // Get settings
        $setting = Setting::first();

        if (!$setting->mail_notification) {
            \Log::info("Mail notifications disabled, skipping guest order notification");
            return;
        }

        // Get notification template for guest ticket booking
        $template = NotificationTemplate::where('title', 'Book Ticket')->first();

        if (!$template) {
            \Log::error('Book Ticket notification template not found');
            return;
        }

        // Load order with relationships
        $order = Order::with(['event', 'organization', 'guestUser'])->find($orderInput->id);
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

        // Generate individual ticket PNGs
        $tempDirectory = storage_path('temp_qrcodes');
        if (!file_exists($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $ticketFiles = [];
        $ticketCounter = 1;

        foreach ($order->tickets() as $ticketData) {
            $orderchildren = OrderChild::where('ticket_id', $ticketData->id)
                ->where('order_id', $order->id)
                ->get();

            foreach ($orderchildren as $orderChild) {
                // Build single ticket HTML inline
                $singleTicketHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>';
                $singleTicketHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: transparent; }';
                $singleTicketHtml .= '.ticket { width: 320px; border: 2px solid #d32f2f; border-radius: 10px; background-color: white; overflow: hidden; }';
                $singleTicketHtml .= '.ticket-header { background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold; }';
                $singleTicketHtml .= '.ticket-body { padding: 15px; }';
                $singleTicketHtml .= '.event-info { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; }';
                $singleTicketHtml .= '.event-info img { width: 60px; height: 40px; border-radius: 5px; object-fit: cover; }';
                $singleTicketHtml .= '.event-details { flex-grow: 1; }';
                $singleTicketHtml .= '.event-name { font-size: 16px; font-weight: bold; margin-bottom: 3px; }';
                $singleTicketHtml .= '.event-meta { font-size: 12px; color: #666; margin-bottom: 2px; }';
                $singleTicketHtml .= '.ticket-divider { border-top: 1px dashed #d32f2f; padding-top: 10px; margin-top: 10px; }';
                $singleTicketHtml .= '.ticket-type { font-size: 14px; color: #d32f2f; font-weight: bold; margin-bottom: 5px; }';
                $singleTicketHtml .= '.ticket-info { font-size: 16px; font-weight: bold; margin: 5px 0; }';
                $singleTicketHtml .= '.ticket-info span { font-weight: 400; }';
                $singleTicketHtml .= '.qr-code-section { text-align: center; margin-top: 10px; }';
                $singleTicketHtml .= '.qr-code-section img { width: 200px; height: 200px; }';
                $singleTicketHtml .= '.ticket-number { text-align: center; margin-top: 5px; font-size: 12px; color: #666; }';
                $singleTicketHtml .= '.ticket-footer { background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: #666; }';
                $singleTicketHtml .= '</style></head><body><div class="ticket">';
                $singleTicketHtml .= '<div class="ticket-header"><span>The Event Palette</span></div>';
                $singleTicketHtml .= '<div class="ticket-body"><div class="event-info">';

                // Add event image as base64
                $imagePath = public_path('images/upload/' . $order->event->image);
                if (file_exists($imagePath)) {
                    $imageContent = file_get_contents($imagePath);
                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                    $imageData = 'data:image/' . $imageType . ';base64,' . base64_encode($imageContent);
                    $singleTicketHtml .= '<img src="' . $imageData . '" alt="Event Poster">';
                }

                $singleTicketHtml .= '<div class="event-details">';
                $singleTicketHtml .= '<div class="event-name">' . htmlspecialchars($order->event->name) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . htmlspecialchars($order->organization->organization_name) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . ($order->event->type == 'online' ? 'Online Event' : htmlspecialchars($order->event->address)) . '</div>';
                $singleTicketHtml .= '<div class="event-meta">' . $order->event->start_time->format('F d Y') . ' | ' . $order->event->start_time->format('h:i a') . '</div>';
                $singleTicketHtml .= '</div></div>';
                $singleTicketHtml .= '<div class="ticket-divider">';
                $singleTicketHtml .= '<div class="ticket-type">' . htmlspecialchars($ticketData->name) . '</div>';
                $singleTicketHtml .= '<div class="ticket-info">Ticket: <span>' . htmlspecialchars($ticketData->type) . '</span></div>';
                if (!empty($orderChild->Book_Seat_Id)) {
                    $singleTicketHtml .= '<div class="ticket-info">Seat Number: <span>' . htmlspecialchars($orderChild->Book_Seat_Id) . '</span></div>';
                }
                $singleTicketHtml .= '</div><div class="qr-code-section">';

                // Generate QR code
                $qrCode = QrCode::format('png')->size(200)->generate($orderChild->ticket_number);
                $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);

                $singleTicketHtml .= '<img src="' . $base64QrCode . '" alt="QR Code">';
                $singleTicketHtml .= '</div><div class="ticket-number">#' . htmlspecialchars($orderChild->ticket_number) . '</div>';
                $singleTicketHtml .= '</div><div class="ticket-footer">All Sales Are Final! No Refunds!</div>';
                $singleTicketHtml .= '</div></body></html>';

                // Generate PDF
                $customPaper = array(0, 0, 360, 650);
                $pdf = Pdf::loadHTML($singleTicketHtml)->setPaper($customPaper, 'portrait');
                $pdfOutput = $pdf->output();

                $ticketFileName = "ticket_with_qr_{$ticketCounter}.pdf";
                $ticketFilePath = "{$tempDirectory}/{$ticketFileName}";
                file_put_contents($ticketFilePath, $pdfOutput);

                $ticketFiles[] = [
                    'path' => $ticketFilePath,
                    'name' => $ticketFileName
                ];

                $ticketCounter++;
            }
        }

        // Generate invoice PDF
        $invoicePaper = array(0, 0, 720, 1440);
        $invoicePdf = Pdf::loadView('ticketmail', compact('order'))
            ->setPaper($invoicePaper, 'portrait');
        $invoicePdfOutput = $invoicePdf->output();

        // Prepare email details
        $details = [
            'user_name' => "{$guest->name} {$guest->last_name}",
            'quantity' => $totalQuantity,
            'event_name' => $order->event->name,
            'date' => $order->event->start_time->format('d F Y h:i a'),
            'app_name' => $setting->app_name,
        ];

        // Send ticket booking notification email
        $qrcode = $order->order_id;
        Mail::to($guest->email)->send(new TicketBook($template->mail_content, $details, $template->subject, $qrcode));

        // Send tickets and invoice as attachments
        $emailData = [
            'email' => $guest->email,
            'title' => 'Your Event Tickets & Invoice | ' . $setting->app_name,
            'body' => 'Thank you for your order! Please find your event tickets with QR codes and invoice attached.',
        ];

        Mail::send('mail', $emailData, function ($message) use ($emailData, $ticketFiles, $invoicePdfOutput, $setting) {
            $message->from($setting->sender_email, $setting->app_name)
                ->to($emailData['email'])
                ->subject($emailData['title']);

            // Attach each individual ticket
            foreach ($ticketFiles as $ticketFile) {
                $message->attachData(file_get_contents($ticketFile['path']), $ticketFile['name'], ['mime' => 'application/pdf']);
            }

            // Attach invoice
            $message->attachData($invoicePdfOutput, 'invoice.pdf', ['mime' => 'application/pdf']);
        });

        // Clean up temp ticket files
        foreach ($ticketFiles as $ticketFile) {
            if (file_exists($ticketFile['path'])) {
                unlink($ticketFile['path']);
            }
        }

        \Log::info("Guest order notification sent successfully for order #{$order->order_id}");

    } catch (\Exception $e) {
        \Log::error('Failed to send guest order notifications: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
    }
}

protected function storeStripeTransaction($order)
{
    try {
        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            \Log::error('Stripe secret key not configured');
            return;
        }

        $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
        $paymentIntent = $stripe->paymentIntents->retrieve($order->payment_token, []);

        // Initialize txn_id and tax_amount
        $txnId = null;
        $taxAmount = null;

        // If latest_charge exists, retrieve charge and balance transaction
        if ($paymentIntent->latest_charge) {
            try {
                $charge = $stripe->charges->retrieve($paymentIntent->latest_charge, []);

                if ($charge->balance_transaction) {
                    $balanceTransaction = $stripe->balanceTransactions->retrieve($charge->balance_transaction, []);
                    $txnId = $balanceTransaction->id;
                    $taxAmount = $balanceTransaction->fee / 100; // Convert from cents to dollars
                }
            } catch (\Exception $e) {
                \Log::warning("Could not retrieve charge/balance transaction: " . $e->getMessage());
            }
        }

        $existingTransaction = StripeTransaction::where('payment_id', $paymentIntent->id)->first();

        StripeTransaction::updateOrCreate(
            ['payment_id' => $paymentIntent->id],
            [
                'order_id' => $order->id,
                'donation_id' => null,
                'amount' => $paymentIntent->amount / 100,
                'client_secret' => $paymentIntent->client_secret,
                'currency' => $paymentIntent->currency,
                'latest_charge' => $paymentIntent->latest_charge ?? ($existingTransaction->latest_charge ?? null),
                'txn_id' => $txnId ?? ($existingTransaction->txn_id ?? null),
                'tax_amount' => $taxAmount ?? ($existingTransaction->tax_amount ?? null),
                'payment_method_types' => $paymentIntent->payment_method_types,
                'status' => $paymentIntent->status,
                'full_response' => $paymentIntent->toArray()
            ]
        );

        \Log::info("Stripe transaction stored for order #{$order->order_id}");
    } catch (\Exception $e) {
        \Log::error('Failed to store Stripe transaction: ' . $e->getMessage());
    }
}

/**
 * Retrieve Stripe transaction info from Stripe API and store in database
 * Uses live/test secret key from payment_settings table
 */
protected function retrieveAndStoreStripeTransaction($order)
{
    try {
        // Get Stripe secret key from payment_settings table
        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            \Log::error('Stripe secret key not configured in payment_settings table');
            return false;
        }

        if (!$order->payment_token) {
            \Log::error("No payment_token found for order #{$order->order_id}");
            return false;
        }

        \Log::info("Retrieving Stripe transaction for order #{$order->order_id}, payment_token: {$order->payment_token}");

        // Initialize Stripe client with live/test key from database
        $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);

        // Retrieve payment intent from Stripe API
        $paymentIntent = $stripe->paymentIntents->retrieve($order->payment_token, []);

        \Log::info("Payment Intent retrieved - ID: {$paymentIntent->id}, Status: {$paymentIntent->status}, Amount: {$paymentIntent->amount}");

        // Check if transaction already exists
        $existingTransaction = StripeTransaction::where('payment_id', $paymentIntent->id)
            ->orWhere('order_id', $order->id)
            ->first();

        if ($existingTransaction) {
            \Log::info("Stripe transaction already exists for order #{$order->order_id}; refreshing Stripe fee details");
        }

        // Initialize txn_id and tax_amount
        $txnId = null;
        $taxAmount = null;

        // If latest_charge exists, retrieve charge and balance transaction for fee/tax info
        if (isset($paymentIntent->latest_charge) && $paymentIntent->latest_charge) {
            try {
                // Wait a moment for Stripe to fully process the charge
                sleep(2);

                $charge = $stripe->charges->retrieve($paymentIntent->latest_charge, []);
                \Log::info("Charge retrieved: {$charge->id}, Balance Transaction: " . ($charge->balance_transaction ?? 'null'));

                if (isset($charge->balance_transaction) && $charge->balance_transaction) {
                    $balanceTransaction = $stripe->balanceTransactions->retrieve($charge->balance_transaction, []);
                    $txnId = $balanceTransaction->id;
                    $taxAmount = $balanceTransaction->fee / 100; // Convert from cents to dollars

                    \Log::info("Balance Transaction retrieved - txn_id: {$txnId}, fee: {$balanceTransaction->fee}, tax_amount: {$taxAmount}");
                } else {
                    \Log::warning("No balance_transaction found in charge. Charge may still be processing.");
                }
            } catch (\Exception $e) {
                \Log::warning("Could not retrieve charge/balance transaction: " . $e->getMessage());
                \Log::warning("Stack trace: " . $e->getTraceAsString());
            }
        } else {
            \Log::warning("No latest_charge in payment intent. Payment may not be confirmed yet. Status: {$paymentIntent->status}");
        }

        // Prepare transaction data
        $transactionData = [
            'payment_id' => $paymentIntent->id,
            'order_id' => $order->id,
            'donation_id' => null,
            'amount' => $paymentIntent->amount / 100, // Convert from cents to dollars
            'client_secret' => $paymentIntent->client_secret ?? null,
            'currency' => $paymentIntent->currency,
            'latest_charge' => $paymentIntent->latest_charge ?? ($existingTransaction->latest_charge ?? null),
            'txn_id' => $txnId ?? ($existingTransaction->txn_id ?? null),
            'tax_amount' => $taxAmount ?? ($existingTransaction->tax_amount ?? null),
            'payment_method_types' => $paymentIntent->payment_method_types ?? [],
            'status' => $paymentIntent->status,
            'full_response' => json_decode(json_encode($paymentIntent), true)
        ];

        \Log::info("Storing Stripe transaction in database: " . json_encode([
            'payment_id' => $transactionData['payment_id'],
            'order_id' => $transactionData['order_id'],
            'amount' => $transactionData['amount'],
            'currency' => $transactionData['currency'],
            'status' => $transactionData['status']
        ]));

        // Store or refresh transaction in transaction_stripe table
        $transaction = StripeTransaction::updateOrCreate(
            ['payment_id' => $paymentIntent->id],
            $transactionData
        );

        \Log::info("Stripe transaction stored successfully - ID: {$transaction->id}, Order: #{$order->order_id}, Payment: {$paymentIntent->id}");

        return $transaction;

    } catch (\Stripe\Exception\InvalidRequestException $e) {
        \Log::error("Stripe API Error for order #{$order->order_id}: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        \Log::error("Failed to retrieve and store Stripe transaction for order #{$order->order_id}: " . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return false;
    }
}

protected function storeStripeTransactionDirect($order, $paymentIntent)
{
    try {
        \Log::info("Starting storeStripeTransactionDirect for order #{$order->order_id}, payment_id: {$paymentIntent->id}");

        // Check if transaction already exists
        $exists = StripeTransaction::where('payment_id', $paymentIntent->id)->first();
        if ($exists) {
            \Log::info("Stripe transaction already exists for order #{$order->order_id}; refreshing Stripe fee details");
        }

        // Initialize txn_id and tax_amount
        $txnId = null;
        $taxAmount = null;

        \Log::info("Payment Intent Status: {$paymentIntent->status}, Latest Charge: " . ($paymentIntent->latest_charge ?? 'null'));

        // If latest_charge exists, retrieve charge and balance transaction
        if (isset($paymentIntent->latest_charge) && $paymentIntent->latest_charge) {
            try {
                $paymentSetting = PaymentSetting::first();
                if ($paymentSetting && $paymentSetting->stripeSecretKey) {
                    // Wait a moment for Stripe to fully process the charge
                    sleep(2);

                    $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
                    $charge = $stripe->charges->retrieve($paymentIntent->latest_charge, []);

                    \Log::info("Charge retrieved: {$charge->id}, Balance Transaction: " . ($charge->balance_transaction ?? 'null'));

                    if (isset($charge->balance_transaction) && $charge->balance_transaction) {
                        $balanceTransaction = $stripe->balanceTransactions->retrieve($charge->balance_transaction, []);
                        $txnId = $balanceTransaction->id;
                        $taxAmount = $balanceTransaction->fee / 100; // Convert from cents to dollars

                        \Log::info("Balance Transaction retrieved - txn_id: {$txnId}, fee: {$balanceTransaction->fee}, tax_amount: {$taxAmount}");
                    } else {
                        \Log::warning("No balance_transaction found in charge. Charge may still be processing.");
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Could not retrieve charge/balance transaction: " . $e->getMessage());
                \Log::warning("Stack trace: " . $e->getTraceAsString());
            }
        } else {
            \Log::warning("No latest_charge found in payment intent. Payment may not be confirmed yet. Status: {$paymentIntent->status}");
        }

        $transactionData = [
            'payment_id' => $paymentIntent->id,
            'order_id' => $order->id,
            'donation_id' => null,
            'amount' => $paymentIntent->amount / 100,
            'client_secret' => $paymentIntent->client_secret,
            'currency' => $paymentIntent->currency,
            'latest_charge' => $paymentIntent->latest_charge ?? ($exists->latest_charge ?? null),
            'txn_id' => $txnId ?? ($exists->txn_id ?? null),
            'tax_amount' => $taxAmount ?? ($exists->tax_amount ?? null),
            'payment_method_types' => $paymentIntent->payment_method_types ?? [],
            'status' => $paymentIntent->status,
            'full_response' => json_decode(json_encode($paymentIntent), true)
        ];

        \Log::info("Creating StripeTransaction with data: " . json_encode($transactionData));

        $transaction = StripeTransaction::updateOrCreate(
            ['payment_id' => $paymentIntent->id],
            $transactionData
        );

        \Log::info("Stripe transaction stored successfully for order #{$order->order_id}, transaction_id: {$transaction->id}");

        return $transaction;
    } catch (\Exception $e) {
        \Log::error('Failed to store Stripe transaction directly: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
    }
}





//     public function createOrder(Request $request)
// {
//     $request->validate([
//     'event_id' => 'required|integer|exists:events,id',
//     'tickets' => 'required|array|min:1',
//     'tickets.*.ticket_id' => 'required|integer|exists:tickets,id',
//     'tickets.*.quantity' => 'required|integer|min:1',
//     'tickets.*.tax' => 'required|numeric|min:0',
//     'tickets.*.tax_data' => 'nullable|array',
//     'tickets.*.tax_data.*.tax_id' => 'required_with:tickets.*.tax_data|integer|exists:taxes,id',
//     'tickets.*.tax_data.*.price' => 'required_with:tickets.*.tax_data|numeric|min:0',
//     'coupon_discount' => 'required|numeric|min:0',
//     'payment' => 'required|numeric|min:0',
//     'payment_type' => 'required|string|in:LOCAL,WALLET,FREE,STRIPE,PAYPAL,RAZOR',
//     'payment_token' => 'required_if:payment_type,STRIPE,PAYPAL,RAZOR',
//     'book_seats' => 'nullable|string',
//     'seat_details' => 'nullable|string',
//     'coupon_id' => 'nullable|integer|exists:coupons,id'
// ]);


//     $totalTax = collect($request->tickets)->sum('tax');
//     $totalQuantity = collect($request->tickets)->sum('quantity');
//     $data = $request->only([
//         'event_id', 'coupon_discount', 'payment', 'payment_type', 'payment_token', 'book_seats', 'seat_details'
//     ]);

//     $data['tax'] = $totalTax;
//     $data['order_id'] = '#' . rand(9999, 100000);
//     $data['organization_id'] = Event::findOrFail($request->event_id)->user_id;
//     $data['customer_id'] = Auth::user()->id;

//     $data['payment_status'] = $request->payment_type === "LOCAL" ? 0 : 1;
//     $data['order_status'] = $request->payment_type === "LOCAL" ? 'Pending' : 'Complete';

//     if ($request->payment_type === 'WALLET') {
//         $user = AppUser::find(Auth::id());
//         if ($user->balance >= $request->payment) {
//             $user->withdraw($request->payment, ['event_id' => $request->event_id]);
//         } else {
//             return response()->json(['success' => false, 'message' => 'Insufficient balance']);
//         }
//     }

//     $com = Setting::findOrFail(1, ['org_commission_type', 'org_commission']);
//     $p = $request->payment - $totalTax;
//     $data['org_commission'] = $request->payment_type === "FREE" ? 0 :
//         ($com->org_commission_type === "percentage"
//             ? $p * $com->org_commission / 100
//             : $com->org_commission);

//     if ($request->coupon_id) {
//         $coupon = Coupon::find($request->coupon_id);
//         $coupon->increment('use_count');
//         CouponUsageHistory::create([
//             'coupon_id' => $coupon->id,
//             'appuser_id' => Auth::guard('userApi')->user()->id
//         ]);
//         $data['coupon_discount'] = $coupon->discount;
//         $data['coupon_id'] = $coupon->id;
//     }

//     if ($request->payment_type === "STRIPE") {
//         $currency_code = Setting::first()->currency;
//         $stripe_payment = in_array($currency_code, ['USD', 'EUR', 'INR']) ? $request->payment * 100 : $request->payment;
//         \Stripe\Stripe::setApiKey(PaymentSetting::find(1)->stripeSecretKey);
//         $stripeDetail = \Stripe\PaymentIntent::create([
//             "amount" => $stripe_payment,
//             "currency" => $currency_code,
//         ]);
//         $data['payment_token'] = $stripeDetail->id;
//     }

//     $order = Order::create($data);

//     // Update booked seats
//     if ($request->book_seats) {
//         $seats = explode(',', $request->book_seats);
//         $module = Module::where('module', 'seatmap')->first();
//         if ($module && $module->is_enable == 1) {
//             foreach ($seats as $seat_id) {
//                 \Modules\Seatmap\Entities\Seats::where('id', $seat_id)->update(['type' => 'occupied']);
//             }
//         }
//     }

//     // Loop through tickets
//     foreach ($request->tickets as $ticketItem) {
//         $ticket = Ticket::find($ticketItem['ticket_id']);
//         for ($i = 1; $i <= $ticketItem['quantity']; $i++) {
//             OrderChild::create([
//                 'ticket_number' => uniqid(),
//                 'ticket_id' => $ticketItem['ticket_id'],
//                 'order_id' => $order->id,
//                 'customer_id' => Auth::id(),
//                 'checkin' => $ticket->maximum_checkins ?? null,
//                 'paid' => $request->payment_type === 'LOCAL' ? 0 : 1,
//             ]);
//         }

//         if (!empty($ticketItem['tax_data'])) {
//             foreach ($ticketItem['tax_data'] as $tax) {
//                 OrderTax::create([
//                     'order_id' => $order->id,
//                     'tax_id' => $tax['tax_id'],
//                     'price' => $tax['price'],
//                 ]);
//             }
//         }
//     }

//     $user = AppUser::find($order->customer_id);
//     $setting = Setting::first();

//     // User Notification
//     $message_template = NotificationTemplate::where('title', 'Book Ticket')->first();
//     $detail = [
//         'user_name' => $user->name,
//         'quantity' => $totalQuantity,
//         'event_name' => Event::find($request->event_id)->name,
//         'date' => Event::find($request->event_id)->start_time->format('d F Y h:i a'),
//         'app_name' => $setting->app_name
//     ];
//     $message1 = str_replace(["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"], $detail, $message_template->message_content);

//     Notification::create([
//         'organizer_id' => null,
//         'user_id' => $user->id,
//         'order_id' => $order->id,
//         'title' => 'Ticket Booked',
//         'message' => $message1
//     ]);
//     if ($setting->push_notification == 1) {
//         (new AppHelper)->sendOneSignal('user', $user->device_token, $message1);
//     }

//     // Email to User
//     if ($setting->mail_notification == 1) {
//         try {
//             Config::set('mail', [
//                 'driver'     => $setting->mail_mailer,
//                 'host'       => $setting->mail_host,
//                 'port'       => $setting->mail_port,
//                 'encryption' => $setting->mail_encryption,
//                 'username'   => $setting->mail_username,
//                 'password'   => $setting->mail_password
//             ]);
//             Mail::to($user->email)->send(new TicketBook($message_template->mail_content, $detail, $message_template->subject, $order->order_id));
//         } catch (\Throwable $th) {
//             Log::info($th->getMessage());
//         }
//     }

//     // Organizer Notification
//     $org = User::find($order->organization_id);
//     $org_message_template = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
//     $org_detail = [
//         'organizer_name' => $org->first_name . ' ' . $org->last_name,
//         'user_name' => $user->name . ' ' . $user->last_name,
//         'quantity' => $totalQuantity,
//         'event_name' => Event::find($request->event_id)->name,
//         'date' => Event::find($request->event_id)->start_time->format('d F Y h:i a'),
//         'app_name' => $setting->app_name
//     ];
//     $org_message1 = str_replace(["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"], $org_detail, $org_message_template->message_content);

//     Notification::create([
//         'organizer_id' => $order->organization_id,
//         'user_id' => null,
//         'order_id' => $order->id,
//         'title' => 'New Ticket Booked',
//         'message' => $org_message1
//     ]);

//     if ($setting->mail_notification == 1) {
//         try {
//             Mail::to($org->email)->send(new TicketBookOrg($org_message_template->mail_content, $org_detail, $org_message_template->subject));
//         } catch (\Throwable $th) {
//             Log::info($th->getMessage());
//         }
//     }

//     return response()->json(['success' => true, 'msg' => null, 'data' => $order], 200);
// }



    public function viewUserOrder()
    {
        (new AppHelper)->eventStatusChange();
        $data = Order::with(['event', 'ticket', 'organization'])->where('customer_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }

    public function viewSingleOrder($id)
    {
        (new AppHelper)->eventStatusChange();

        // Main order with event, ticket, and organization
        $data = Order::with(['event', 'ticket', 'organization'])->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'msg' => 'Order not found',
                'data' => null
            ], 404);
        }

        // Order children with ticket info
        $orderChildren = OrderChild::with('ticket')->where('order_id', $data->id)->get();

        // Append ticket name, seat id, and qr code to each child
        $data['order_child'] = $orderChildren->map(function ($child) {
            $seatId = !empty($child->Book_Seat_Id) ? $child->Book_Seat_Id : (!empty($child->Book_seat_id) ? $child->Book_seat_id : null);

            $qrCode = null;
            $qrCodeDataUrl = null;
            if (!empty($child->ticket_number)) {
                try {
                    $qrCode = base64_encode(QrCode::format('png')->size(150)->generate($child->ticket_number));
                    $qrCodeDataUrl = 'data:image/png;base64,' . $qrCode;
                } catch (\Exception $e) {
                    $qrCode = null;
                    $qrCodeDataUrl = null;
                }
            }

            return [
                'id' => $child->id,
                'ticket_id' => $child->ticket_id,
                'ticket_name' => optional($child->ticket)->name,
                'ticket_number' => $child->ticket_number,
                'qr_code' => $qrCode,
                'qrcode' => $qrCodeDataUrl,
                'Book_seat_id' => $seatId,
                'Book_Seat_Id' => $seatId,
                'checkin' => $child->checkin,
                'paid' => $child->paid,
                'created_at' => $child->created_at,
                'updated_at' => $child->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'msg' => null,
            'data' => $data
        ], 200);
    }


    public function allSetting()
    {
        $general = Setting::find(1, ['app_name', 'app_version', 'logo', 'currency', 'onesignal_app_id', 'onesignal_project_number', 'help_center', 'privacy_policy', 'cookie_policy', 'terms_services', 'acknowledgement', 'currency_sybmol']);
        if (auth('userApi')->check()) {
            $paymentSettings = PaymentSetting::find(1);
            $general->stripe = $paymentSettings->stripe;
            $general->cod = $paymentSettings->cod;
            $general->paypal = $paymentSettings->paypal;
            $general->razor = $paymentSettings->razor;
            $general->flutterwave = $paymentSettings->flutterwave;
            $general->stripeSecretKey = $paymentSettings->stripeSecretKey;
            $general->stripePublicKey = $paymentSettings->stripePublicKey;
            $general->paypalClientId = $paymentSettings->paypalClientId;
            $general->paypalSecret = $paymentSettings->paypalSecret;
            $general->razorPublishKey = $paymentSettings->razorPublishKey;
            $general->razorSecretKey = $paymentSettings->razorSecretKey;
            $general->ravePublicKey = $paymentSettings->ravePublicKey;
            $general->raveSecretKey = $paymentSettings->raveSecretKey;
            $general->flutterDebugMode = $paymentSettings->flutterDebugMode;
            $general->wallet = $paymentSettings->wallet;
        }
        return response()->json(['success' => true, 'msg' => null, 'data' => $general], 200);
    }

    public function userOrder(Request $request)
    {
        // If event_id is provided and not empty, filter orders by it.
        $eventId = $request->input('event_id');
        if ($eventId === '' || $eventId === null) {
            $eventId = null;
        } else {
            $eventId = (int) $eventId;
        }

        $upcomingQuery = Order::with(['event', 'ticket'])
            ->where('customer_id', Auth::id())
            ->whereHas('event', function ($query) {
                $query->where('end_time', '>=', now());
            });

        if (!is_null($eventId)) {
            $upcomingQuery->where('event_id', $eventId);
        }

        $data['upcoming'] = $upcomingQuery->orderBy('id', 'DESC')->get();

        $pastQuery = Order::with(['event', 'ticket'])
            ->where('customer_id', Auth::id())
            ->where(function ($q) {
                $q->where('order_status', 'Complete')
                    ->orWhere('order_status', 'Cancel');
            });

        if (!is_null($eventId)) {
            $pastQuery->where('event_id', $eventId);
        }

        $data['past'] = $pastQuery->orderBy('id', 'DESC')->get();

        foreach ($data['upcoming'] as $upcoming) {
            $upcoming['order_child'] = OrderChild::where('order_id', $upcoming->id)->get();
        }

        foreach ($data['past'] as $past) {
            $past['order_child'] = OrderChild::where('order_id', $past->id)->get();
        }

        $response = response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);

        try {
            Log::info('=== user/user-order response ===', (array) $response->getData(true));
        } catch (\Throwable $e) {
            Log::warning('user/user-order: failed to log response payload: ' . $e->getMessage());
        }

        return $response;
    }


    public function singleOrder($id)
    {
        (new AppHelper)->eventStatusChange();
        $data = Order::with(['event', 'ticket'])->find($id);
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }

    public function searchEvent(Request $request)
    {
        $timezone = Setting::find(1)->timezone ?? 'UTC';
        $date = Carbon::now($timezone);
        $data = Event::where([['status', 1], ['is_deleted', 0]]);

        $dateFilter = $request->query('date')
            ?? $request->query('duration')
            ?? $request->query('searchDate')
            ?? $request->query('filter_date')
            ?? $request->date
            ?? $request->duration
            ?? $request->searchDate
            ?? $request->filter_date;
        $dateRange = $this->resolveEventDateRange($dateFilter, $timezone);

        if ($dateRange) {
            $data = $data->where(function ($q) use ($dateRange) {
                $q->whereBetween('start_time', [$dateRange[0], $dateRange[1]])
                  ->orWhere(function ($sub) use ($dateRange) {
                      $sub->where('start_time', '<=', $dateRange[1])
                          ->where('end_time', '>=', $dateRange[0]);
                  });
            });
        } else {
            $data = $data->where('start_time', '>=', $date->format('Y-m-d'));
        }

        $lat = $request->query('lat') ?? $request->lat;
        $lang = $request->query('lang') ?? $request->lang;
        if ($lat != null && $lang != null) {
            $event = array();
            $radius = 50;
            $results = DB::select(DB::raw('SELECT id,name, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lang ) - radians(' . $lang . ') ) + sin( radians(' . $lat . ') ) * sin( radians(lat) ) ) ) AS distance FROM events HAVING distance < ' . $radius . '  ORDER BY distance'));
            if (count($results) > 0) {
                foreach ($results as $q) {
                    array_push($event, $q->id);
                }
            }
            $data = $data->whereIn('id', $event);
        }

        $category = $request->query('category') ?? $request->category;
        if (!empty($category) && $category != "All") {
            $data = $data->where('category_id', $category);
        }

        $data = $data->orderBy('start_time', 'ASC')->get();
        foreach ($data as $value) {
            $value->description =  str_replace("&nbsp;", " ", strip_tags($value->description));
            $value->time = $value->start_time->format('d F Y h:i a');
            if (Auth::guard('userApi')->check()) {
                if (in_array($value->id, array_filter(explode(',', Auth::guard('userApi')->user()->favorite ?? '')))) {
                    $value->isLike = true;
                } else {
                    $value->isLike = false;
                }
            } else {
                $value->isLike = false;
            }
        }
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'bail|required',
            'password' => 'bail|required|min:6',
            'password_confirmation' => 'bail|required|same:password|min:6'
        ]);
        if (Hash::check($request->old_password, Auth::user()->password)) {
            AppUser::find(Auth::user()->id)->update(['password' => Hash::make($request->password)]);
            return response()->json(['success' => true, 'msg' => 'Your password is change successfully', 'data' => null], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Current Password is wrong!', 'data' => null], 200);
        }
    }

    public function forgetPassword(Request $request)
    {
        Log::info('=== Forgot Password API Called ===');
        Log::info('Email: ' . $request->email);

        try {
            $request->validate([
                'email' => 'bail|required|email',
            ]);
        } catch (\Exception $e) {
            Log::error('Validation Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 422);
        }

        $user = AppUser::where('email', $request->email)->first();
        Log::info('User found: ' . ($user ? 'Yes - ID: ' . $user->id : 'No'));

        if (!$user) {
            Log::info('User not found, returning 404');
            return response()->json(['success' => false, 'msg' => 'Invalid email ID', 'data' => null], 404);
        }

        $password = rand(100000, 999999);
        Log::info('Generated password: ' . $password);

        $notificationTemplate = NotificationTemplate::where('title', 'Reset Password')->first();
        if (!$notificationTemplate) {
            Log::error('Reset Password notification template not found');
            return response()->json(['success' => false, 'msg' => 'Email template not configured'], 500);
        }

        $content = $notificationTemplate->mail_content;
        $detail['user_name'] = $user->name;
        $detail['password'] = $password;
        $detail['app_name'] = Setting::find(1)->app_name;

        try {
            Log::info('Attempting to send email...');
            $setting = Setting::first();

            if (!$setting) {
                Log::error('Settings not found');
                return response()->json(['success' => false, 'msg' => 'Mail settings not configured'], 500);
            }

            Log::info('Mail config - Driver: ' . ($setting->mail_mailer ?? 'NULL') . ', Host: ' . ($setting->mail_host ?? 'NULL') . ', Port: ' . ($setting->mail_port ?? 'NULL'));

            $config = array(
                'driver'     => $setting->mail_mailer,
                'host'       => $setting->mail_host,
                'port'       => $setting->mail_port,
                'encryption' => $setting->mail_encryption,
                'username'   => $setting->mail_username,
                'password'   => $setting->mail_password,
                'from'       => [
                    'address' => $setting->sender_email ?? $setting->mail_username,
                    'name'    => $setting->app_name
                ]
            );
            Config::set('mail', $config);

            Log::info('About to send mail to: ' . $user->email);

            // Send mail
            Mail::to($user->email)->send(new ResetPassword($content, $detail));

            Log::info('Mail sent successfully');

            // Only update password if mail sent successfully
            AppUser::find($user->id)->update(['password' => Hash::make($password)]);

            Log::info('Password updated in database');

            return response()->json(['success' => true, 'msg' => 'New password sent to your email', 'data' => null], 200);

        } catch (\Throwable $th) {
            Log::error('=== Forgot Password Error ===');
            Log::error('Error Message: ' . $th->getMessage());
            Log::error('Error File: ' . $th->getFile() . ' Line: ' . $th->getLine());
            Log::error('Stack Trace: ' . $th->getTraceAsString());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to send reset email. Please try again later.',
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'data' => null
            ], 500);
        }
    }    public function clearNotification()
    {
        $noti = Notification::where('user_id', Auth::user()->id)->get();
        foreach ($noti as $value) {
            $value->delete();
        }
        return response()->json(['success' => true, 'msg' => 'Notification deleted successfully.'], 200);
    }
    public function user_delete($id)
    {
        $time = Carbon::now();
        $time->toArray();
        $app_user = AppUser::find($id);
        $app_user['name'] = 'User Deleted';
        $app_user['last_name'] = 'Deleted';
        $app_user['status'] = 0;
        $app_user['email'] =  $time->timestamp . '@deleteduser.com';
        $app_user->update();
        $app_user->delete();
        return response()->json(['success' => true, 'msg' => 'Account deleted successfully.'], 200);
    }

    /**
     * Delete user account (authenticated)
     * DELETE /api/user/delete-account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::guard('appuser')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Unauthorized. Please login first.'
                ], 401);
            }

            // Optional: Validate password confirmation
            $request->validate([
                'password' => 'nullable|string',
            ]);

            // Verify password if provided (optional security measure)
            if ($request->has('password') && $request->password) {
                if (!Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'msg' => 'Invalid password. Account deletion cancelled.'
                    ], 422);
                }
            }

            // Anonymize user data
            $timestamp = Carbon::now()->timestamp;
            $user->name = 'User Deleted';
            $user->last_name = 'Deleted';
            $user->email = $timestamp . '@deleteduser.com';
            $user->phone = null;
            $user->image = null;
            $user->address = null;
            $user->bio = null;
            $user->status = 0;
            $user->password = Hash::make('deleted_' . $timestamp);
            $user->save();

            // Delete the user (soft delete)
            $user->delete();

            // Revoke all tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'msg' => 'Your account has been successfully deleted.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'An error occurred while deleting your account. Please try again.'
            ], 500);
        }
    }

    public function otpVerify(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'otp' => 'required',
        ]);
        $user = AppUser::find($request->id);
        if ($user->is_verify == 1) {
            return response()->json(['msg' => 'User is already verified', 'success' => false], 400);
        }
        if ($user->otp == $request->otp) {
            $user->otp = null;
            $user->is_verify = 1;
            $user->device_token = $request->device_token ?? null;
            $user->update();
            Auth::guard('appuser')->login($user);
            $user = Auth::guard('appuser')->user();
            $user['token'] = $user->createToken('eventRight')->accessToken;
            return response()->json(['msg' => 'OTP verify successfully', 'data' => $user, 'success' => true], 200);
        } else {
            return response()->json(['msg' => 'Wrong OTP. Please try again.', 'success' => false]);
        }
    }
    // Wallet
    public function getBalance(Request $request)
    {
        $user = $request->user();
        $user = AppUser::find($user->id);
        $data['balance'] = $user->balance;
        $data['transactions'] = $user->transactions()->orderBy('created_at', 'desc')->get()->makeHidden(['payable_type', 'confirmed', 'uuid', 'created_at', 'updated_at']);
        return response()->json(['success' => true, 'msg' => null, 'data' => $data], 200);
    }
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'payment_type' => 'required',
            'currency' => 'required',
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => $validator->errors()->first(), 'data' => null], 400);
        }
        $user = $request->user();
        $user = AppUser::find($user->id);
        $user->deposit($request->amount, ['payment_mode' => $request->payment_type, 'currency' => $request->currency, 'token' => $request->token]);
        $user->wallet->refreshBalance();
        $balance = $user->balance;
        $data = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->first()
            ->makeHidden(['payable_type', 'confirmed', 'uuid', 'created_at', 'updated_at']);
        return response()->json(['success' => true, 'data' => $data,'balance'=>$balance]);
    }

    public function showbanner()
    {
        $banners = Banner::where('status', 1)
            ->where('id', '!=', 6)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        // Map each banner to include the full image URL
        $banners->transform(function ($banner) {
            $banner->image = url('images/upload/' . $banner->image);
            return $banner;
        });

        return response()->json(['success' => true, 'msg' => null, 'data' => $banners], 200);
    }

//         public function sendOtp(Request $request)
// {
//     $request->validate([
//         'email' => 'required|email',
//     ]);

//     $otp = rand(1000, 9999);

//     // App info from settings or fallback
//     $appName = Setting::value('app_name') ?? config('app.name', 'Laravel App');

//     // Force sender email as requested
//     $senderEmail = 'patil.atharva69@gmail.com';
//     $senderName = $appName;

//     $emailData = [
//         'email' => $request->email,
//         'title' => 'Guest OTP Verification',
//         'otp' => $otp,
//         'app_name' => $appName,
//     ];

//     try {
//         // Send email
//         Mail::send('guestemailverify', ['data' => $emailData], function ($message) use ($emailData, $senderEmail, $senderName) {
//             $message->from($senderEmail, $senderName)
//                     ->to($emailData['email'])
//                     ->subject($emailData['title']);
//         });

//         // Cache the OTP for 10 minutes
//         Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

//         return response()->json([
//             'success' => true,
//             'message' => 'OTP sent to email.',
//             'status_code' => 200
//         ]);
//     } catch (\Exception $e) {
//          \Log::error('OTP Email Send Failed: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,

//             'message' => 'Failed to send OTP.',
//             'status_code' => 500
//         ]);
//     }
// }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));
        $otp = rand(1000, 9999);
        $data = [
            'email' => $email,
            'title' => 'Guest OTP Verification',
            'otp' => $otp,
            'app_name' => Setting::value('app_name'),
        ];

        $sender = Setting::select('sender_email', 'app_name')->first();

        try {
            Cache::put('otp_' . $email, (string) $otp, now()->addMinutes(10));
            GuestOtp::where('email', $email)
                ->where('expires_at', '<=', now())
                ->delete();
            GuestOtp::create([
                'email' => $email,
                'otp' => (string) $otp,
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::send('guestemailverify', ['data' => $data], function ($message) use ($data, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                        ->to($data['email'])
                        ->subject($data['title']);
            });

            return response()->json(['success' => true, 'message' => 'OTP sent to email.']);
        } catch (\Exception $e) {
            Log::error('Guest OTP send failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send OTP.']);
        }
    }
//     public function sendOtp(Request $request)
// {
//     $request->validate([
//         'email' => 'required|email',
//     ]);

//     $otp = rand(1000, 9999); // Generate 4-digit OTP

//     // Save the OTP in cache for 10 minutes
//     Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));

//     // (Optional) Log OTP for testing/debugging - remove in production
//     \Log::info("Generated OTP for {$request->email}: $otp");

//     return response()->json([
//         'success' => true,
//         'message' => 'OTP generated successfully.',
//         'otp' => $otp // Only return this during testing, remove in production
//     ]);
// }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $email = strtolower(trim($request->email));
        $otp = trim((string) $request->otp);
        $cachedOtp = Cache::get('otp_' . $email);
        $record = GuestOtp::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
        $storedOtp = $record ? $record->otp : $cachedOtp;

        if ($storedOtp && trim((string) $storedOtp) === $otp) {
            Cache::forget('otp_' . $email);
            GuestOtp::where('email', $email)->delete();

            // Generate a verification token
            $verificationToken = bin2hex(random_bytes(32));

            // Store verification status in cache for 30 minutes
            Cache::put('guest_verified_' . $email, $verificationToken, now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'verification_token' => $verificationToken
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 422);
    }

    public function GuestCreateOrder(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'guest_first_name' => 'required|string',
                'guest_last_name' => 'required|string',
                'guest_email' => 'required|email',
                'guest_phone' => 'required|numeric',
                'ticket_id' => 'required',
                'event_id' => 'required|integer',
                'payment_type' => 'required|string',
                'payment' => 'required|numeric',
                'tax' => 'required|numeric',
                'verification_token' => 'required|string',
                'quantity' => 'nullable',
                'coupon_discount' => 'nullable|numeric|min:0',
                'payment_token' => 'nullable|string',
                'coupon_id' => 'nullable|integer',
                'tax_data' => 'nullable',
                'guest_hold_key' => 'nullable|string|max:120',
                'venue_seat_ids' => 'nullable',
                'selectedVenueSeatIds' => 'nullable',
                'seat_ids' => 'nullable',
                'seat_details' => 'nullable',
                'venue_seat_details' => 'nullable',
                // Add validation as needed
            ]);

            // Verify OTP using verification token
            // Compare using the same cache key as verifyOtp(). Normalize email to reduce mismatch.
            $normalizedGuestEmail = strtolower(trim($request->guest_email));
            $cachedToken = Cache::get('guest_verified_' . $normalizedGuestEmail);

            // If token is missing/expired => block booking
            if (!$cachedToken) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'OTP not verified'], 401);
            }

            // If client sends verification_token, validate it. Otherwise allow booking as long as token exists.
            if (!empty($request->verification_token) && $cachedToken !== $request->verification_token) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'OTP not verified'], 401);
            }

            $request->merge(['guest_email' => $normalizedGuestEmail]);

            // Handle tax_data from string or array
            if (is_string($request->tax_data)) {
                $request->merge(['tax_data' => json_decode($request->tax_data, true)]);
            }

            // Convert empty coupon_id to null
            if ($request->coupon_id === '' || $request->coupon_id === ' ') {
                $request->merge(['coupon_id' => null]);
            }

            // Convert "null" string to null for payment_token when payment_type is LOCAL
            if ($request->payment_type === 'LOCAL' && $request->payment_token === 'null') {
                $request->merge(['payment_token' => null]);
            }

            $ticketIds = $this->normalizeApiIds($request->input('ticket_id'), false);
            if (empty($ticketIds)) {
                throw ValidationException::withMessages([
                    'ticket_id' => 'At least one ticket is required.',
                ]);
            }
            $request->merge(['ticket_id' => $ticketIds]);

            $quantities = $this->normalizeApiIds($request->input('quantity'), false);
            if (empty($quantities)) {
                $quantities = array_fill(0, count($ticketIds), 1);
            }
            if (count($quantities) !== count($ticketIds)) {
                throw ValidationException::withMessages([
                    'quantity' => 'The number of tickets and quantities must match.',
                ]);
            }
            $request->merge(['quantity' => $quantities]);

            $venueSeatIds = $this->normalizeApiIds(
                $request->input('venue_seat_ids', $request->input('selectedVenueSeatIds', $request->input('seat_ids', [])))
            );
            $guestHoldOwner = $this->apiGuestHoldOwner($request->input('guest_hold_key'));

            if (! empty($venueSeatIds)) {
                $request->merge(['venue_seat_ids' => $venueSeatIds]);
            }
            $seatDetails = $request->input('seat_details', $request->input('venue_seat_details'));

            // Create guest user
            $guest = GuestUser::create([
                'name' => $request->guest_first_name,
                'last_name' => $request->guest_last_name,
                'email' => $request->guest_email,
                'phone' => $request->guest_phone,
            ]);

            $totalQuantity = array_sum($request->quantity);
            $event = Event::findOrFail($request->event_id);
            $seatRequiredQuantity = $this->venueSeatRequiredQuantity($event, $request->ticket_id, $request->quantity);

            if (! empty($venueSeatIds) && count($venueSeatIds) !== (int) $seatRequiredQuantity) {
                throw ValidationException::withMessages([
                    'venue_seat_ids' => 'Selected seat count must match selected ticket quantity.',
                ]);
            }

            // Prepare order data
            $orderData = [
                'order_id' => '#' . rand(9999, 100000),
                'organization_id' => $event->user_id,
                'guestuser_id' => $guest->id, // Use guest user instead of customer_id
                'event_id' => $request->event_id,
                'payment_status' => $request->payment_type == "LOCAL" ? 0 : 1,
                'order_status' => $request->payment_type == "LOCAL" ? 'Pending' : 'Complete',
                'quantity' => $totalQuantity,
                'ticket_id' => is_array($request->ticket_id) ? implode(',', $request->ticket_id) : $request->ticket_id,
                'coupon_discount' => $request->coupon_discount ?? 0,
                'payment' => $request->payment,
                'tax' => $request->tax ?? 0,
                'payment_type' => $request->payment_type,
                'payment_token' => $request->payment_token,
                'seat_details' => is_array($seatDetails) ? json_encode($seatDetails) : $seatDetails
            ];

            // Commission Calculation
            $com = Setting::first(['org_commission_type', 'org_commission']);
            $orderData['org_commission'] = $request->payment_type == "FREE" ? 0 : (
                $com->org_commission_type == "percentage"
                    ? ($request->payment - $request->tax) * $com->org_commission / 100
                    : $com->org_commission
            );

            // Create Order
            $order = Order::create($orderData);
            \Log::info("Order created successfully: #{$order->order_id}, payment_type: {$request->payment_type}, payment_token: " . ($order->payment_token ?? 'null'));

            // Order children
            foreach ($request->ticket_id as $index => $ticketId) {
                $ticket = Ticket::findOrFail($ticketId);
                $quantity = $request->quantity[$index];

                OrderChild::insert(
                    array_fill(0, $quantity, [
                        'ticket_number' => uniqid(),
                        'ticket_id' => $ticketId,
                        'order_id' => $order->id,
                        'customer_id' => null, // No customer_id for guest orders
                        'guestuser_id' => $guest->id, // Add guest user id
                        'checkin' => $ticket->maximum_checkins,
                        'paid' => $request->payment_type == 'LOCAL' ? 0 : 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ])
                );
            }

            $this->attachApiGuestVenueSeatsToOrder($order, $event, $venueSeatIds, $guestHoldOwner);

            // Order Taxes
            $taxData = $request->tax_data ?? [];
            if (!empty($taxData)) {
                $orderTaxes = array_map(function ($tax) use ($order) {
                    return [
                        'order_id' => $order->id,
                        'tax_id' => $tax['tax_id'],
                        'price' => $tax['price'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }, $taxData);

                OrderTax::insert($orderTaxes);
            }

            // Send notifications
            $this->sendGuestOrderNotifications($order, $guest, array_sum($request->quantity));

            DB::commit();

            // Clear the verification token after successful order creation
            Cache::forget('guest_verified_' . strtolower(trim($request->guest_email)));


            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $order->id,
                'data' => $order
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Guest order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Order creation failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function donationCreatePaymentIntent(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'amount' => 'required|numeric|min:1|max:99999',
            'guest_email' => 'nullable|email',
        ]);

        $paymentSetting = PaymentSetting::first();
        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json(['success' => false, 'message' => 'Payment not configured.'], 500);
        }

        try {
            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            $amountCents = (int) round((float) $request->amount * 100);

$paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
                // This makes Stripe record the donor email (receipt email)
                'receipt_email' => $request->guest_email ?? null,
                'metadata' => [
                    'event_id' => $request->event_id,
                    'app_user_id' => Auth::guard('userApi')->id() ?? '',
                    'guest_email' => $request->guest_email ?? '',
                    'type' => 'donation',
                ],
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Donation API create payment intent failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create payment intent.'], 500);
        }
    }

    public function donationProcess(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'amount' => 'required|numeric|min:1',
            'payment_intent_id' => 'required|string',
            'guest_email' => 'nullable|email',
            'verification_token' => 'nullable|string',
        ]);

        $paymentSetting = PaymentSetting::first();
        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json(['success' => false, 'message' => 'Payment not configured.'], 500);
        }

        $isGuest = !Auth::guard('userApi')->check();
        if ($isGuest) {
            if (!$request->guest_email || !$request->verification_token) {
                return response()->json(['success' => false, 'message' => 'Guest donation requires email verification.'], 401);
            }

            $cachedToken = Cache::get('guest_verified_' . $request->guest_email);
            if (!$cachedToken || $cachedToken !== $request->verification_token) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired verification token.'], 401);
            }
        }

        try {
            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            $paymentIntent = $stripe->paymentIntents->retrieve($request->payment_intent_id);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json(['success' => false, 'message' => 'Payment not completed.'], 422);
            }

            $metadataType = strtolower((string) ($paymentIntent->metadata->type ?? ''));
            if ($metadataType !== 'donation') {
                Log::warning('Rejected non-donation PaymentIntent in donation API process.', [
                    'payment_intent_id' => $paymentIntent->id,
                    'metadata_type' => $metadataType,
                ]);

                return response()->json(['success' => false, 'message' => 'Invalid donation payment.'], 422);
            }

            $existing = Donation::where('payment_intent_id', $request->payment_intent_id)->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'donation_id' => $existing->donation_id,
                    'message' => 'Donation already recorded.',
                ], 200);
            }

            $txnId = null;
            if (isset($paymentIntent->latest_charge) && $paymentIntent->latest_charge) {
                try {
                    $charge = $stripe->charges->retrieve($paymentIntent->latest_charge, []);
                    if (isset($charge->balance_transaction) && $charge->balance_transaction) {
                        $balanceTxn = $stripe->balanceTransactions->retrieve($charge->balance_transaction, []);
                        $txnId = $balanceTxn->id;
                    }
                } catch (\Exception $e) {
                    Log::warning('Donation API charge retrieval failed: ' . $e->getMessage());
                }
            }

            $guestUserId = null;
            if ($isGuest) {
                $guestUser = GuestUser::firstOrCreate(
                    ['email' => $request->guest_email],
                    ['name' => explode('@', $request->guest_email)[0], 'last_name' => '', 'phone' => '']
                );
                $guestUserId = $guestUser->id;
            }

            $donation = Donation::create([
                'app_user_id' => Auth::guard('userApi')->id(),
                'guest_user_id' => $guestUserId,
                'event_id' => $request->event_id,
                'amount' => $request->amount,
                'transaction_id' => $txnId,
                'payment_intent_id' => $paymentIntent->id,
                'status' => 'completed',
            ]);

            StripeTransaction::create([
                'payment_id' => $paymentIntent->id,
                'order_id' => null,
                'donation_id' => $donation->donation_id,
                'amount' => $paymentIntent->amount / 100,
                'client_secret' => $paymentIntent->client_secret,
                'currency' => $paymentIntent->currency,
                'latest_charge' => $paymentIntent->latest_charge ?? null,
                'txn_id' => $txnId,
                'payment_method_types' => $paymentIntent->payment_method_types ?? [],
                'status' => $paymentIntent->status,
                'full_response' => json_decode(json_encode($paymentIntent), true),
            ]);

            $sender = Setting::select('sender_email', 'app_name', 'currency_sybmol')->first();
            $donorEmail = $isGuest ? $request->guest_email : Auth::guard('userApi')->user()->email;
            $donorName = $isGuest
                ? trim(($guestUser->name ?? explode('@', $request->guest_email)[0]) . ' ' . ($guestUser->last_name ?? ''))
                : trim(Auth::guard('userApi')->user()->name . ' ' . Auth::guard('userApi')->user()->last_name);
            $currency = $sender->currency_sybmol ?? '$';

            try {
                $pdf = Pdf::loadView('donation.invoice', compact('donation', 'donorName', 'currency', 'sender'))
                    ->setPaper('a4', 'portrait');

                Mail::send('donation.mail', compact('donation', 'donorName', 'currency', 'sender'), function ($message) use ($sender, $donorEmail, $donorName, $pdf) {
                    $message->from($sender->sender_email, $sender->app_name ?? 'Teptix')
                        ->to($donorEmail)
                        ->subject('Donation Receipt - ' . ($donorName ?: 'Donor'))
                        ->attachData($pdf->output(), 'donation_invoice.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                });

                Mail::send('donation.thankyou', compact('donation', 'donorName', 'currency', 'sender'), function ($message) use ($sender, $donorEmail, $donorName) {
                    $message->from($sender->sender_email, $sender->app_name ?? 'Teptix')
                        ->to($donorEmail)
                        ->subject('Thank you for your donation - ' . ($donorName ?: 'Donor'));
                });
            } catch (\Exception $e) {
                Log::error('Donation API email sending failed: ' . $e->getMessage());
            }

            if ($isGuest) {
                Cache::forget('guest_verified_' . $request->guest_email);
            }

            return response()->json([
                'success' => true,
                'donation_id' => $donation->donation_id,
                'message' => 'Thank you for your donation!',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Donation API processing failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to process donation. Please contact support.'], 500);
        }
    }

    public function wristpeScanTicket(Request $request)
{
    $request->validate([
        'ticket_number' => 'required|string',
        'event_id' => 'required|integer|exists:events,id',
    ]);

    $code = $request->ticket_number;
    $event_id = $request->event_id;

    $child = OrderChild::where('ticket_number', $code)->first();
    if (!$child) {
        return response()->json(['msg' => 'Ticket not found.', 'success' => false], 404);
    }

    $ticket = Ticket::find($child->ticket_id);
    if (!$ticket) {
        return response()->json(['msg' => 'Ticket information is invalid.', 'success' => false], 404);
    }

    $event = Event::find($ticket->event_id);
    if (!$event) {
        return response()->json(['msg' => 'Event not found.', 'success' => false], 404);
    }

    $order = Order::find($child->order_id);
    if (!$order) {
        return response()->json(['msg' => 'Order not found.', 'success' => false], 404);
    }

    $currency = Setting::find(1)->currency_sybmol ?? 'USD';

    // Construct event logo URL using the 'event_logo' column
    $eventLogo = null;
    if (!empty($event->event_logo)) {
        $eventLogo = 'https://teptix.com/images/upload/' . $event->event_logo;
    }

    $data = [
        'payment_type' => $child->paid == 1 ? "STRIPE" : $order->payment_type,
        'amount' => $order->payment,
        'currency' => $currency,
        'event_id' => $order->event_id,
        'event_name' => $event->name ?? 'Unknown Event',
        'event_logo' => $eventLogo, // This now uses the properly constructed URL
        'ticket_number' => $child->ticket_number,
        'ticket_name' => $ticket->name ,
        'seat_details' => json_decode($order->seat_details),
        'qr_code' => base64_encode(\QrCode::format('png')->size(150)->generate($child->ticket_number)),
    ];

    // Rest of your validation logic remains the same...
    if ($order->order_status !== 'Complete') {
        return response()->json([
            'msg' => 'Ticket is not confirmed by your Organizer yet.',
            'success' => false
        ], 200);
    }

    if ($order->event_id != $event_id) {
        return response()->json([
            'msg' => 'Ticket does not belong to this event.',
            'success' => false
        ], 200);
    }

    if ($child->checkin === 0) {
        return response()->json([
            'msg' => 'Check-in limit exceeded!',
            'data' => $data,
            'success' => false
        ], 200);
    }

    $now = Carbon::now();
    if ($now->lt($event->start_time) || $now->gt($event->end_time)) {
        return response()->json([
            'msg' => 'Ticket is not valid at this time. Please check the event date and time.',
            'data' => $data,
            'success' => false
        ], 200);
    }

    if ($child->checkin !== null) {
        $child->checkin = max(0, $child->checkin - 1);
    }
    $child->status = 1;

    if ($child->paid == 0) {
        $child->paid = 1;
        $child->save();
        $data['remaining_check_ins'] = $child->checkin;

        return response()->json([
            'msg' => 'Please collect payment from the guest.',
            'success' => true,
            'data' => $data
        ], 200);
    }

    $child->save();
    $data['remaining_check_ins'] = $child->checkin;

    return response()->json([
        'msg' => 'Ticket scanned successfully.',
        'success' => true,
        'data' => $data
    ], 200);
}
    public function eventForWristpe()
{
    $events = Event::select('id', 'name', 'event_logo')->get();

    $events->transform(function ($event) {
        if (!empty($event->event_logo)) {
            $event->event_logo = 'https://teptix.com/images/upload/' . $event->event_logo;
        }
        return $event;
    });

    return response()->json([
        'success' => true,
        'msg' => null,
        'data' => $events
    ], 200);
}

public function scanTicketApi(Request $request)
{
    $request->validate([
        'ticket_number' => 'required|string',
        'event_id' => 'nullable|integer',
    ]);

    $code = $request->input('ticket_number');
    $event_id = $request->input('event_id');

    // Priority 1: Search by ticket_number (standard tickets / events without seat map)
    $child = OrderChild::where('ticket_number', $code)->first();

    // Priority 2: Search by Book_Seat_Id (events with seat map)
    if (!$child && !empty($code)) {
        $child = OrderChild::where('Book_Seat_Id', $code)->first();
    }

    // Priority 3: Search by primary key id (fallback)
    if (!$child && is_numeric($code)) {
        $child = OrderChild::find($code);
    }

    if (!$child) {
        return response()->json(['msg' => 'Ticket not found.', 'success' => false], 200);
    }

    $ticket = Ticket::find($child->ticket_id);
    if (!$ticket) {
        return response()->json(['msg' => 'Ticket information is invalid.', 'success' => false], 200);
    }

    $event = Event::find($ticket->event_id);
    if (!$event) {
        return response()->json(['msg' => 'Event not found.', 'success' => false], 200);
    }

    // If event_id is not provided, use the one from ticket
    if (!$event_id) {
        $event_id = $ticket->event_id;
    }

    $timezone = Setting::find(1)->timezone ?? null;
    $now = $timezone ? Carbon::now($timezone) : Carbon::now();
    if ($event && $event->start_time) {
        $startTime = $event->start_time instanceof Carbon ? $event->start_time : Carbon::parse($event->start_time);
        $allowScanTime = $startTime->copy()->subHours(3);
        if ($now->lt($allowScanTime)) {
            return response()->json([
                'msg' => 'Event is not started',
                'success' => false
            ], 200);
        }
    }

    $scannerIds = array_map('intval', explode(',', $event->scanner_id));
    $authId = (int) Auth::id();

    if (!in_array($authId, $scannerIds)) {
        return response()->json([
            'msg' => 'You are not authorized to scan this ticket.',
            'success' => false
        ], 200);
    }

    $order = Order::find($child->order_id);
    if (!$order) {
        return response()->json(['msg' => 'Order not found.', 'success' => false], 200);
    }

    $currency = Setting::find(1)->synbol ?? 'USD';

    $data = [
        'payment_type' => $child->paid == 1 ? "STRIPE" : $order->payment_type,
        'amount' => $order->payment,
        'currency' => $currency,
        'event_id' => $order->event_id,
        'event_name' => $event->name ?? 'Unknown Event',
        'ticket_number' => $child->ticket_number,
        'ticket_title' => $ticket->ticket_title ?? 'General',
        'seat_details' => json_decode(json_encode($order->seat_details)),
        'qr_code' => base64_encode(QrCode::format('png')->size(150)->generate($child->ticket_number)),
    ];

    // ✅ Add Book_Seat_Id only if it exists (not null)
    if (!is_null($child->Book_Seat_Id)) {
        $data['Book_Seat_Id'] = $child->Book_Seat_Id;
    }

    if ($order->order_status !== 'Complete') {
        return response()->json([
            'msg' => 'Ticket is not confirmed by your Organizer yet.',
            'success' => false
        ], 200);
    }

    if ($order->event_id != $event_id) {
        return response()->json([
            'msg' => 'Ticket does not belong to this event.',
            'success' => false
        ], 200);
    }

    if ($child->checkin === 0) {
        return response()->json([
            'msg' => 'Check-in limit exceeded!',
            'data' => $data,
            'success' => false
        ], 200);
    }

    if ($ticket->allday == 0 && $order->ticket_date !== Carbon::now()->format("Y-m-d")) {
        return response()->json([
            'msg' => 'Ticket date does not match today\'s date.',
            'data' => $data,
            'success' => false
        ], 200);
    }

    // Update check-in and status
    if ($child->checkin !== null) {
        $child->checkin = max(0, $child->checkin - 1);
    }
    $child->status = 1;

    if ($child->paid == 0) {
        $child->paid = 1;
        $child->save();
        $data['remaining_check_ins'] = $child->checkin;

        return response()->json([
            'msg' => 'Please collect payment from the guest.',
            'success' => true,
            'data' => $data,
            'auto_close' => true
        ], 200);
    }

    $child->save();
    $data['remaining_check_ins'] = $child->checkin;

    return response()->json([
        'msg' => 'Ticket scanned successfully.',
        'success' => true,
        'data' => $data,
        'auto_close' => true
    ], 200);
}

    /**
     * Update FCM token for authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_token' => 'nullable|string',
        ]);

        $user = Auth::user();
        $updateData = ['fcm_token' => $request->fcm_token];

        if ($request->has('device_token')) {
            $updateData['device_token'] = $request->device_token;
        }

        AppUser::where('id', $user->id)->update($updateData);

        return response()->json([
            'msg' => 'FCM token updated successfully',
            'success' => true
        ], 200);
    }

    /**
     * Get FCM notifications for authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFcmNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'msg' => 'User not authenticated',
                    'data' => [],
                    'debug' => [
                        'auth_guard' => Auth::getDefaultDriver(),
                        'headers' => $request->headers->all(),
                        'auth_header' => $request->header('Authorization')
                    ]
                ], 401);
            }

            $eventId = $request->input('event_id');
            if ($eventId === '' || $eventId === null) {
                $eventId = null;
            } else {
                $eventId = (int) $eventId;
            }

            $notificationsQuery = Notification::where('user_id', $user->id);

            if (!is_null($eventId)) {
                $notificationsQuery->whereIn('order_id', function ($q) use ($eventId) {
                    $q->select('id')
                        ->from('orders')
                        ->where('event_id', $eventId);
                });
            }

            $notifications = $notificationsQuery
                ->orderBy('created_at', 'DESC')
                ->get();

            $formattedNotifications = [];
            foreach ($notifications as $notification) {
                $eventImage = null;
                $eventName = null;
                $order = null;

                if ($notification->order_id) {
                    $order = Order::find($notification->order_id);
                    if ($order && $order->event_id) {
                        $event = Event::find($order->event_id);
                        if ($event) {
                            $eventImage = url('images/upload') . '/' . $event->image;
                            $eventName = $event->name;
                        }
                    }
                }

                $formattedNotifications[] = [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at,
                    'created_at_formatted' => $notification->created_at->format('M d, Y h:i A'),
                    'time_ago' => $notification->created_at->diffForHumans(),
                    'event_image' => $eventImage,
                    'event_name' => $eventName,
                    'event_id' => $order ? $order->event_id : null,
                    'order_id' => $notification->order_id
                ];
            }

            return response()->json([
                'success' => true,
                'msg' => 'Notifications retrieved successfully',
                'data' => $formattedNotifications,
                'total_count' => count($formattedNotifications)
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to get FCM notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to retrieve notifications: ' . $e->getMessage(),
                'data' => [],
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
            ], 500);
        }
    }


    /**
     * Test FCM service directly with detailed debugging
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testFcmService(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string',
                'title' => 'nullable|string',
                'message' => 'nullable|string'
            ]);

            $firebaseService = new FirebaseService();

            $data = [
                'title' => $request->title ?? 'Test FCM Notification',
                'description' => $request->message ?? 'This is a test FCM notification to debug the service.',
                'event_id' => null,
                'image' => null
            ];

            $tokens = [$request->fcm_token];

            \Log::info('FCM Test - Starting test with token: ' . substr($request->fcm_token, 0, 20) . '...');

            $result = $firebaseService->sendNotificationToTokens($tokens, $data);

            return response()->json([
                'success' => true,
                'msg' => 'FCM test completed',
                'data' => [
                    'test_token' => substr($request->fcm_token, 0, 20) . '...' . substr($request->fcm_token, -10),
                    'firebase_result' => $result,
                    'config_check' => [
                        'server_key_configured' => !empty(config('firebase.server_key')),
                        'server_key_preview' => config('firebase.server_key') ? 'AAAA...' . substr(config('firebase.server_key'), -10) : 'NOT FOUND'
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('FCM Test Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'FCM test failed: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Debug FCM tokens - check what tokens are stored in database
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugFcmTokens()
    {
        try {
            // Get all app users with FCM tokens
            $appUsers = AppUser::where(function($query) {
                $query->whereNotNull('fcm_token')->where('fcm_token', '!=', '')
                      ->orWhere(function($q) {
                          $q->whereNotNull('device_token')->where('device_token', '!=', '');
                      });
            })->get(['id', 'name', 'email', 'fcm_token', 'device_token']);

            // Get all guest users with FCM tokens
            $guestUsers = GuestUser::whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get(['id', 'name', 'email', 'fcm_token']);

            return response()->json([
                'success' => true,
                'msg' => 'FCM tokens debug info',
                'data' => [
                    'app_users_with_fcm' => $appUsers,
                    'guest_users_with_fcm' => $guestUsers,
                    'total_app_users' => $appUsers->count(),
                    'total_guest_users' => $guestUsers->count(),
                    'firebase_config' => [
                        'server_key_exists' => !empty(config('firebase.server_key')),
                        'server_key_preview' => config('firebase.server_key') ? 'AAAA...' . substr(config('firebase.server_key'), -10) : 'Not found'
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error getting FCM debug info: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Send FCM notification to specific users (for testing/debugging)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFcmNotification(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'fcm_tokens' => 'nullable|array',
                'user_ids' => 'nullable|array',
            ]);

            $firebaseService = new FirebaseService();
            $tokens = [];

            // If specific FCM tokens provided
            if ($request->has('fcm_tokens') && !empty($request->fcm_tokens)) {
                $tokens = array_merge($tokens, $request->fcm_tokens);
            }

            // If user IDs provided, get their FCM tokens
            if ($request->has('user_ids') && !empty($request->user_ids)) {
                $userTokens = AppUser::whereIn('id', $request->user_ids)
                    ->whereNotNull('fcm_token')
                    ->where('fcm_token', '!=', '')
                    ->pluck('fcm_token')
                    ->toArray();
                $tokens = array_merge($tokens, $userTokens);
            }

            // If no specific recipients, send to current user
            if (empty($tokens)) {
                $user = Auth::user();
                if ($user && ($user->fcm_token || $user->device_token)) {
                    $tokens[] = $user->fcm_token ?: $user->device_token;
                }
            }

            if (empty($tokens)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'No FCM tokens found for notification',
                    'data' => []
                ], 400);
            }

            $data = [
                'title' => $request->title,
                'description' => $request->message,
                'event_id' => $request->event_id ?? null,
                'image' => $request->image ?? null,
            ];

            $result = $firebaseService->sendNotificationToTokens($tokens, $data);

            return response()->json([
                'success' => true,
                'msg' => 'FCM notification sent successfully',
                'data' => [
                    'tokens_sent' => count($tokens),
                    'success_count' => $result['success'],
                    'failure_count' => $result['failure']
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Failed to send FCM notification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to send FCM notification: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    private function createOrderFees($order)
    {
        $event = \App\Models\Event::find($order->event_id);
        if (!$event) {
            return;
        }

        // Only apply fee logic to new events (created at or after 2026-08-17 00:00:00)
        if ($event->created_at < '2026-08-17 00:00:00') {
            return;
        }

        // Fetch default active fee types
        $defaultFees = \App\Models\FeeType::where('status', 1)->where('is_default', 1)->get();

        // Fetch event-specific fee types configured by admin
        $eventFeeIds = \App\Models\EventFee::where('event_id', $event->id)->pluck('fee_type_id')->toArray();
        $configuredFees = \App\Models\FeeType::where('status', 1)->whereIn('id', $eventFeeIds)->get();

        // Merge them uniquely
        $feesToApply = $defaultFees->merge($configuredFees)->unique('id');

        // Sum up the subtotal of the ticket purchases via OrderChild
        $orderChildren = \App\Models\OrderChild::where('order_id', $order->id)->get();
        $subtotal = 0;
        foreach ($orderChildren as $child) {
            $ticket = \App\Models\Ticket::find($child->ticket_id);
            if ($ticket) {
                $subtotal += $ticket->price;
            }
        }

        $baseSubtotal = max(0, $subtotal - ($order->coupon_discount ?? 0));

        $totalOrderFees = 0;
        foreach ($feesToApply as $fee) {
            if ($fee->amount_type == 'percentage') {
                $amount = ($fee->price * $baseSubtotal) / 100;
            } else {
                $amount = $fee->price;
            }

            \App\Models\OrderFee::create([
                'order_id' => $order->id,
                'fee_type_id' => $fee->id,
                'name' => $fee->name,
                'amount' => $amount,
                'price' => $fee->price,
                'amount_type' => $fee->amount_type,
            ]);

            $totalOrderFees += $amount;
        }

        if ($totalOrderFees > 0) {
            $order->org_revenue = (float)$order->org_revenue - $totalOrderFees;
            $order->save();
        }
    }
}
