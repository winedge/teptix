<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use App\Mail\TicketBook;
use App\Mail\TicketBookOrg;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\AdminActivityLog;
use App\Models\Event;
use Illuminate\Support\Facades\Log;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\OrderTax;
use App\Models\AppUser;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsageHistory;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\OrganizerPaymentKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use App\Models\Language;
use App\Models\Module;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\OrderChild;
use App\Models\EventVenueSeat;
use App\Models\Settlement;
use Illuminate\Support\Facades\Rave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\SEOTools;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\JsonLd;
use Exception;
use Facade\FlareClient\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Stripe\Stripe;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Support\OrganizerVerificationStatus;


use function GuzzleHttp\Promise\all;
use Illuminate\Support\Facades\Config;
use Throwable;

class UserController extends Controller
{
    public function __construct()
    {
        (new AppHelper)->eventStatusChange();
    }

    /**
     * Normalize a CSV-like field that may be:
     * - a string like "1,2,3"
     * - a JSON array like "[1,2,3]"
     * - an actual PHP array [1,2,3]
     * Returns an array of trimmed scalars (strings).
     */
    private function normalizeList($value)
    {
        if (is_array($value)) {
            return array_map(function ($v) {
                return trim((string)$v, " \t\n\r\0\x0B\"'");
            }, array_values($value));
        }
        if (is_null($value) || $value === '') {
            return [];
        }
        if (is_numeric($value)) {
            return [(string)$value];
        }
        // If it's a JSON array string, decode it
        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = @json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_map(function ($v) {
                    return trim((string)$v, " \t\n\r\0\x0B\"'");
                }, array_values($decoded));
            }
            // Fallback to comma separated list
            $parts = array_filter(array_map('trim', explode(',', $value)), function ($v) {
                return $v !== '';
            });
            return array_map(function ($v) {
                return trim($v, " \t\n\r\0\x0B\"'");
            }, array_values($parts));
        }
        return [];
    }

    private function normalizeVenueSeatIds($value)
    {
        return collect($this->normalizeList($value))
            ->filter(function ($seatId) {
                return is_numeric($seatId) && (int) $seatId > 0;
            })
            ->map(function ($seatId) {
                return (int) $seatId;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function expandedTicketIdsFromArrays($ticketIds, $quantities)
    {
        $expanded = [];

        foreach ((array) $ticketIds as $index => $ticketId) {
            if (!is_numeric($ticketId) || (int) $ticketId <= 0) {
                continue;
            }

            $quantity = max(1, (int) ($quantities[$index] ?? 1));
            for ($i = 0; $i < $quantity; $i++) {
                $expanded[] = (int) $ticketId;
            }
        }

        return $expanded;
    }

    private function releaseExpiredVenueSeatHoldsForAdmin($eventId = null)
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
    public function index()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $users = User::role('Organizer')
            ->with(['roles:id,name'])
            ->where('is_verify', 1)
            ->orderBy('id', 'DESC')
            ->get();
        $recentOrganizerCount = User::role('Organizer')
            ->whereNotNull('onboarding_completed_at')
            ->where('is_verify', 0)
            ->count();
        $pendingOrganizerCount = User::role('Organizer')
            ->whereNull('onboarding_completed_at')
            ->where('is_verify', '!=', 2)
            ->count();
        $deniedOrganizerCount = User::role('Organizer')
            ->where('is_verify', 2)
            ->count();
        $debugMode = env('APP_DEBUG');
        return view('admin.user.index', compact('users', 'debugMode', 'recentOrganizerCount', 'pendingOrganizerCount', 'deniedOrganizerCount'));
    }

    public function organizerPending()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::role('Organizer')
            ->with(['roles:id,name'])
            ->whereNull('onboarding_completed_at')
            ->where('is_verify', '!=', 2)
            ->orderBy('id', 'DESC')
            ->get();
        $debugMode = env('APP_DEBUG');
        $pageTitle = __('Organizer Pending');
        $showVerifyAction = true;

        return view('admin.user.organizer_list', compact('users', 'debugMode', 'pageTitle', 'showVerifyAction'));
    }

    public function recentOrganizers()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::role('Organizer')
            ->with(['roles:id,name'])
            ->whereNotNull('onboarding_completed_at')
            ->where('is_verify', 0)
            ->orderBy('id', 'DESC')
            ->get();
        $debugMode = env('APP_DEBUG');
        $pageTitle = __('Recent Organizers');
        $showVerifyAction = true;

        return view('admin.user.organizer_list', compact('users', 'debugMode', 'pageTitle', 'showVerifyAction'));
    }

    public function organizerDenied()
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $users = User::role('Organizer')
            ->with(['roles:id,name'])
            ->where('is_verify', 2)
            ->orderBy('id', 'DESC')
            ->get();
        $debugMode = env('APP_DEBUG');
        $pageTitle = __('Denied Organizers');
        $showVerifyAction = true;

        return view('admin.user.organizer_list', compact('users', 'debugMode', 'pageTitle', 'showVerifyAction'));
    }

    public function verifyOrganizer(User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!$user->hasRole('Organizer')) {
            return redirect()->back()->with('statusblock', __('Selected user is not an organizer.'));
        }

        $user->update(['is_verify' => 1]);

        try {
            (new AppHelper)->mailConfig();
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OrganizerVerified($user));
        } catch (\Exception $e) {
            Log::error('Failed to send organizer verification email: ' . $e->getMessage());
        }

        return redirect()->route('users.index')->withStatus(__('Organizer has been verified successfully.'));
    }

    public function denyOrganizer(Request $request, User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!$user->hasRole('Organizer')) {
            return redirect()->back()->with('statusblock', __('Selected user is not an organizer.'));
        }

        $user->update([
            'is_verify' => 2,
            'denied_reason' => $request->input('denied_reason'),
        ]);

        return redirect()->route('users.index')->withStatus(__('Organizer request has been denied.'));
    }

    public function deleteOrganizer(User $user)
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!$user->hasRole('Organizer')) {
            return redirect()->back()->with('statusblock', __('Selected user is not an organizer.'));
        }

        // Delete related organizer payment keys and the user completely
        \App\Models\OrganizerPaymentKeys::where('organizer_id', $user->id)->delete();
        $user->forceDelete();

        return redirect()->route('users.index')->withStatus(__('Organizer has been deleted successfully.'));
    }

    public function requestVerification()
    {
        $user = Auth::user();
        if ($user && $user->hasRole('Organizer') && (int) $user->is_verify === 2) {
            $user->update([
                'is_verify' => 0,
                'created_at' => now(), // Resets the waiting period starting from today
            ]);
            return redirect()->back()->with('status', __('Re-verification request sent successfully. Your account is now pending review.'));
        }
        return redirect()->back();
    }

    public function onboarding()
    {
        $user = Auth::user() ?: Auth::guard('appuser')->user();

        if (!$user) {
            return redirect('user/login');
        }

        return view('admin.user.onboarding', compact('user'));
    }

    public function saveOnboarding(Request $request)
    {
        $user = Auth::user() ?: Auth::guard('appuser')->user();

        if (!$user) {
            return redirect('user/login');
        }

        $request->validate([
            'organization_name' => 'nullable|string|max:255',
            'first_name' => 'bail|required|string|max:255',
            'last_name' => 'bail|required|string|max:255',
            'phone' => 'bail|required|string|max:30',
            'country' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        if ($user instanceof \App\Models\User) {
            $user->update([
                'organization_name' => $request->organization_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'country' => $request->country,
                'bio' => $request->bio,
                'onboarding_completed_at' => $user->onboarding_completed_at ?: now(),
            ]);

            try {
                (new \App\Http\Controllers\AppHelper)->mailConfig();
                Mail::to($user->email)->send(new \App\Mail\OrganizerOnboardingCompleted($user));
            } catch (\Exception $e) {
                Log::error('Failed to send organizer onboarding email: ' . $e->getMessage());
            }

            return redirect('organization-home')->withStatus(__('Onboarding details submitted successfully.'));
        } else {
            $user->update([
                'name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'bio' => $request->bio,
            ]);

            return redirect('/')->withStatus(__('Onboarding details submitted successfully.'));
        }
    }

    public function create()
    {

        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $roles = Role::all();
        $orgs = User::role('Organizer')->orderBy('id', 'DESC')->get();
        return view('admin.user.create', compact('roles', 'orgs'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:users',
            'phone' => 'bail|required',
            'password' => 'bail|required|min:6',
            "roles"    => "bail|required|array|min:1",
            'roles.*' => 'bail|required|string|distinct|min:1',
        ]);
        $data = $request->all();
        $data['password'] =  Hash::make($request->password);
        $data['org_id'] = $request->organization;
        $data['language'] = Setting::first()->language;
        $data['status'] = $data['status'] ?? 1;
        $user = User::create($data);
        $user->assignRole($request->input('roles', []));
        $selectedRoleNames = Role::whereIn('id', $request->input('roles', []))->pluck('name');
        if ($selectedRoleNames->contains('Organizer')) {
            $user->update([
                'is_verify' => 0,
                'onboarding_completed_at' => now(),
            ]);
            OrganizerPaymentKeys::create([
                'organizer_id' => $user->id,
            ]);
        }
        return redirect()->route('users.index')->withStatus(__('User has added successfully.'));
    }

    public function show(User $user)
    {
        return view('admin.user.show', compact('user'));
    }

    public function edit(User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $roles = Role::all();
        if ($user->hasRole('admin')) {
            return redirect()->route('users.index')->withStatus(__('You can not edit admin.'));
        }
        $orgs = User::role('Organizer')->orderBy('id', 'DESC')->get();
        return view('admin.user.edit', compact('roles', 'user', 'orgs'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'phone' => 'bail|required',
            'email' => 'bail|required|unique:users,email,' . $user->id . ',id',
        ]);
        $data['first_name'] = $request->first_name;
        $data['last_name'] = $request->last_name;
        $data['email'] = $request->email;
        $data['phone'] = $request->phone;
        $data['org_id'] = $request->organization;
        $user->update($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.index')->withStatus(__('User has updated successfully.'));
    }

    public function bookTicket(Request $request)
    {

        $setting = Setting::first(['app_name', 'logo']);

        SEOMeta::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setCanonical(url()->current())
            ->addKeyword([
                'all event page',
                $setting->app_name,
                $setting->app_name . ' All-Events',
                'events page',
                $setting->app_name . ' Events',
            ]);

        OpenGraph::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'))
            ->setDescription('This is all events page')
            ->setUrl(url()->current());

        JsonLdMulti::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        JsonLdMulti::setDescription('This is all events page');
        JsonLdMulti::addImage($setting->imagePath . $setting->logo);

        SEOTools::setTitle($setting->app_name . ' - All-Events' ?? env('APP_NAME'));
        SEOTools::setDescription('This is all events page');
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            'all event page',
            $setting->app_name,
            $setting->app_name . ' All-Events',
            'events page',
            $setting->app_name . ' Events',
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);

        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events  = Event::with(['category:id,name'])
            ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]]);
        $chip = array();
        if ($request->has('type') && $request->type != null) {
            $chip['type'] = $request->type;
            $events = $events->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $chip['category'] = Category::find($request->category)->name;
            $events = $events->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            $chip['date'] = $request->duration;
            if ($request->duration == 'Today') {
                $temp = Carbon::now($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'Tomorrow') {
                $temp = Carbon::tomorrow($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'ThisWeek') {
                $now = Carbon::now($timezone);
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i:s');
                $events = $events->whereBetween('start_time', [$weekStartDate, $weekEndDate]);
            } else if ($request->duration == 'date') {
                if (isset($request->date)) {
                    $temp = Carbon::parse($request->date)->format('Y-m-d H:i:s');
                    $events = $events->whereBetween('start_time', [$request->date . ' 00:00:00', $request->date . ' 23:59:59']);
                }
            }
        }
        $events = $events->orderBy('start_time', 'ASC')->get();

        foreach ($events as $value) {
            $value->total_ticket = Ticket::where([['event_id', $value->id], ['is_deleted', 0], ['status', 1]])->sum('quantity');
            $value->sold_ticket = Order::where('event_id', $value->id)->sum('quantity');
            $value->available_ticket = $value->total_ticket - $value->sold_ticket;
        }
        return view('admin.org_bookTicket', compact('events', 'chip'));
    }

    public function organizerEventDetails(Request $request, $id)
    {
        $tickets = Ticket::all()->where('event_id', $id);
        return view('admin.organizer.organizerBookTicket', compact('tickets'));
    }

    public function organizerCheckout(Request $request, $id)
    {
        $data = Ticket::find($id);
        $data->user = AppUser::all();
        $data->event = Event::find($data->event_id);
        $setting = Setting::first(['app_name', 'logo']);


        SEOMeta::setTitle($data->name)
            ->setDescription($data->description)
            ->addKeyword([
                $setting->app_name,
                $data->name,
                $data->event->name,
                $data->event->tags
            ]);

        OpenGraph::setTitle($data->name)
            ->setDescription($data->description)
            ->setUrl(url()->current());

        JsonLd::setTitle($data->name)
            ->setDescription($data->description);

        SEOTools::setTitle($data->name);
        SEOTools::setDescription($data->description);
        SEOTools::opengraph()->setUrl(url()->current());
        SEOTools::setCanonical(url()->current());
        SEOTools::opengraph()->addProperty('keywords', [
            $setting->app_name,
            $data->name,
            $data->event->name,
            $data->event->tags
        ]);
        SEOTools::jsonLd()->addImage($setting->imagePath . $setting->logo);
        $arr = [];

        // Calculate used tickets correctly (handle both single and comma-separated ticket_ids)
        $used = 0;
        $orders = Order::where('event_id', $data->event_id)->get();

        foreach ($orders as $order) {
            $ticketIds = $this->normalizeList($order->ticket_id);
            $quantities = $this->normalizeList($order->quantity);

            foreach ($ticketIds as $index => $tid) {
                if ((int) trim($tid) === $id) {
                    if (isset($quantities[$index])) {
                        $used += (int) $quantities[$index];
                    }
                }
            }
        }

        $data->available_qty = $data->quantity - $used;
        $data->tax = Tax::where([['allow_all_bill', 1], ['status', 1]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);
        foreach ($data->tax as $key => $item) {
            if ($item->amount_type == 'percentage') {

                $amount = ($item->price * $data->price) / 100;
                array_push($arr, $amount);
            }
            if ($item->amount_type == 'price') {
                $amount = $item->price;
                array_push($arr, $amount);
            }
        }
        $data->tax_total = array_sum($arr);
        // $data->tax = Tax::where([['user_id', $data->event->user_id], ['allow_all_bill', 1], ['status', 1]])->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at']);
        // $data->tax_total = intval(Tax::where([['user_id', $data->event->user_id], ['allow_all_bill', 1], ['status', 1]])->sum('price'));
        $data->currency_code = Setting::find(1)->currency;
        $seat = '';
        $orders = Order::where('event_id', $data->event['id'])->get();
        return view('admin.organizer.organizerCheckout', compact('data'));
    }
    public function organizerCreateOrder(Request $request)
    {
        $data = $request->all();
        $ticket = Ticket::find($request->ticket_id);

        // Validate ticket quantity availability
        $totalTicketQuantity = $ticket->quantity;

        // Sum of all quantities from orders for this ticket (handle comma-separated ticket_ids)
        $bookedQuantity = 0;
        $orders = Order::where('event_id', $ticket->event_id)->get();

        foreach ($orders as $order) {
            $ticketIds = $this->normalizeList($order->ticket_id);
            $quantities = $this->normalizeList($order->quantity);

            foreach ($ticketIds as $index => $tid) {
                if ((int) trim($tid) === $ticket->id) {
                    if (isset($quantities[$index])) {
                        $bookedQuantity += (int) $quantities[$index];
                    }
                }
            }
        }

        $requestedQuantity = (int) $request->quantity;
        $availableQuantity = $totalTicketQuantity - $bookedQuantity;

        // Check maximum tickets allowed per order
        if ($ticket->ticket_per_order > 0 && $requestedQuantity > $ticket->ticket_per_order) {
            return redirect()->back()->withErrors([
                'quantity' => "Maximum of {$ticket->ticket_per_order} tickets can be booked per order."
            ])->withInput();
        }

        // Check if requested quantity exceeds available quantity
        if ($requestedQuantity > $availableQuantity) {
            return redirect()->back()->withErrors([
                'quantity' => "Only {$availableQuantity} tickets available. You requested {$requestedQuantity} tickets."
            ])->withInput();
        }

        $event = Event::find($ticket->event_id);
        $org = User::find($event->user_id);
        $user = $request->user;
        $data['order_id'] = '#' . rand(9999, 100000);
        $data['event_id'] = $event->id;
        $data['customer_id'] = $user;
        $data['organization_id'] = $org->id;
        $data['order_status'] = 'Pending';
        $data['payment_status'] = 0;


        $order = Order::create($data);
        if (isset($request->tax_data)) {
            foreach (json_decode($data['tax_data']) as $value) {
                $tax['order_id'] = $order->id;
                $tax['tax_id'] = $value->id;
                $tax['price'] = $value->price;
                OrderTax::create($tax);
            }
        }
        $this->createOrderFees($order);
        return redirect('orders')->withStatus(__('Order created successfully.'));
    }
    public function adminDashboard(Request $request)
    {
         if (!Auth::user() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }
        $master['organizations'] = User::role('Organizer')->count();
        $master['users'] = AppUser::count();
        $master['total_order_amount'] = Order::sum('payment');
        $master['total_order_tax'] = Order::where('payment_status', 1)->sum('tax');
        $master['eventDate'] = array();

        // Get timezone and current month
        $timezone = Setting::find(1)->timezone;
        $currentDate = Carbon::now($timezone);
        $master['current_month'] = $currentDate->format('F');
        $master['selected_order_month'] = $request->input('stats_month');
        $master['selected_order_month_label'] = __('All');
        $master['order_month_options'] = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthDate = $currentDate->copy()->month($month)->startOfMonth();
            $master['order_month_options'][] = [
                'value' => $monthDate->format('Y-m'),
                'label' => $monthDate->format('M'),
            ];
        }

        $orderStats = Order::query();
        if (!empty($master['selected_order_month'])) {
            try {
                $selectedOrderMonth = Carbon::createFromFormat('Y-m', $master['selected_order_month'], $timezone)->startOfMonth();
                $orderStats->whereBetween('created_at', [
                    $selectedOrderMonth->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                    $selectedOrderMonth->copy()->endOfMonth()->format('Y-m-d H:i:s'),
                ]);
                $master['selected_order_month_label'] = $selectedOrderMonth->format('F Y');
            } catch (Exception $e) {
                $master['selected_order_month'] = null;
            }
        }

        $master['total_order'] = (clone $orderStats)->count();
        $master['pending_order'] = (clone $orderStats)->where('order_status', 'Pending')->count();
        $master['complete_order'] = (clone $orderStats)->where('order_status', 'Complete')->count();
        $master['cancel_order'] = (clone $orderStats)->where('order_status', 'Cancel')->count();

        if ($request->ajax()) {
            return response()->json([
                'selected_order_month' => $master['selected_order_month'],
                'selected_order_month_label' => $master['selected_order_month_label'],
                'total_order' => $master['total_order'],
                'pending_order' => $master['pending_order'],
                'complete_order' => $master['complete_order'],
                'cancel_order' => $master['cancel_order'],
            ]);
        }

        $events = Event::where([['status', 1], ['is_deleted', 0]])->orderBy('id', 'DESC')->get();
        $date = Carbon::now($timezone);
        $events  = Event::with(['category:id,name'])
        ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending']]);
        $chip = array();
        if ($request->has('type') && $request->type != null) {
            $chip['type'] = $request->type;
            $events = $events->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $chip['category'] = Category::find($request->category)->name;
            $events = $events->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            $chip['date'] = $request->duration;
            if ($request->duration == 'Today') {
                $temp = Carbon::now($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'Tomorrow') {
                $temp = Carbon::tomorrow($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'ThisWeek') {
                $now = Carbon::now($timezone);
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i:s');
                $events = $events->whereBetween('start_time', [$weekStartDate, $weekEndDate]);
            } else if ($request->duration == 'date') {
                if (isset($request->date)) {
                    $temp = Carbon::parse($request->date)->format('Y-m-d H:i:s');
                    $events = $events->whereBetween('start_time', [$request->date . ' 00:00:00', $request->date . ' 23:59:59']);
                }
            }
        }
        $events = $events->orderBy('start_time', 'DESC')->get();

        // Use timezone-aware date calculation for monthly events
        $startOfMonth = $currentDate->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $currentDate->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $monthEvent = Event::whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where([['status', 1], ['is_deleted', 0]])
            ->orderBy('start_time', 'ASC')->get();

        foreach ($monthEvent as $value) {
            $value->tickets = $value->people;
            $value->capacity = $value->people; // Explicit capacity field
            // Get sold tickets count from completed orders only using OrderChild for accuracy
            $value->sold_ticket = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->average = $value->tickets == 0 ? 0 : round(($value->sold_ticket * 100 / $value->tickets), 2);
        }
        foreach ($events as $value) {
            $tickets = $value->people;
            $total = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->avaliable = $tickets - $total;
            $value->capacity = $value->people; // Explicit capacity field
            array_push($master['eventDate'], $value->start_time->format('Y-m-d'));
        }
        // dd( $events);
        $latestActivityLogs = Schema::hasTable('admin_activity_logs')
            ? AdminActivityLog::with('actor')->orderBy('created_at', 'DESC')->limit(5)->get()
            : collect();

        return view('admin.dashboard', compact('events', 'monthEvent', 'master', 'latestActivityLogs'));
    }

    public function scannerDashboard(Request $request){
        return view('scanner.dashboard');
    }
    public function scannerPrivacy(Request $request){
        return view('scanner-privacy');
    }
    public function managerDashboard(Request $request)
    {
        $org_id = Auth::user()->org_id;

        $master['events'] = Event::whereRaw('FIND_IN_SET(?, user_id)', [$org_id])->where('is_deleted', 0)->count();
        $master['total_order'] = Order::where('organization_id', $org_id)->count();

        $totalScanners = User::role('scanner')->where('org_id', $org_id)->count();

        $organizerOrders = Order::where('organization_id', $org_id)->where('payment_status', 1);
        $totalRevenueVal = $organizerOrders->sum('org_revenue');

        $currency = Setting::first()->currency_sybmol ?? '$';
        $earnings = $currency . number_format($totalRevenueVal, 2);

        return view('admin.manager.dashboard', compact('master', 'totalScanners', 'earnings', 'currency'));
    }

    public function organizationDashboard(Request $request)
    {
        if (Auth::user()->hasRole('Manager')) {
            abort(403, 'Unauthorized access.');
        }

        if (Auth::user()->hasRole('Organizer') && !Auth::user()->onboarding_completed_at && (int) Auth::user()->is_verify !== 2) {
            $user = Auth::user();

            return view('admin.user.onboarding', compact('user'));
        }

        $verificationContactDate = null;
        $organizerVerificationStatus = null;
        if (Auth::user()->hasRole('Organizer') && (int) Auth::user()->is_verify !== 1) {
            $organizerVerificationStatus = OrganizerVerificationStatus::status(Auth::user());
            $verificationContactDate = $organizerVerificationStatus['contact_date'] ?? null;
        }

        $master['total_tickets'] = Ticket::where('user_id', Auth::user()->id)->sum('quantity');
        $quantities = Order::where('organization_id', Auth::user()->id)->pluck('quantity');
        $master['used_tickets'] = $quantities->sum(function ($q) {
            return (int) trim($q); // Cast to integer safely
        });

        $master['events'] = Event::whereRaw('FIND_IN_SET(?, user_id)', [Auth::user()->id])->where('is_deleted', 0)->count();
        $master['total_order'] = Order::where('organization_id', Auth::user()->id)->count();
        $master['pending_order'] = Order::where([['order_status', 'Pending'], ['organization_id', Auth::user()->id]])->count();
        $master['complete_order'] = Order::where([['order_status', 'Complete'], ['organization_id', Auth::user()->id]])->count();
        $master['cancel_order'] = Order::where([['order_status', 'Cancel'], ['organization_id', Auth::user()->id]])->count();
        $master['eventspeople'] = Event::whereRaw('FIND_IN_SET(?, user_id)', [Auth::user()->id])->where('is_deleted', 0)->sum('people');
        // Get timezone for proper date calculation
        $timezone = Setting::find(1)->timezone;
        $currentDate = Carbon::now($timezone);
        $startOfMonth = $currentDate->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth = $currentDate->copy()->endOfMonth()->format('Y-m-d H:i:s');

        $monthEvent = Event::whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where([['status', 1], ['is_deleted', 0]])
            ->whereRaw('FIND_IN_SET(?, user_id)', [Auth::user()->id])
            ->orderBy('start_time', 'ASC')->get();

        foreach ($monthEvent as $value) {
            $value->tickets = Ticket::where('event_id', $value->id)->sum('quantity');
            // Get sold tickets count from completed orders only using OrderChild for accuracy
            $value->sold_ticket = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->average = $value->tickets == 0 ? 0 : round(($value->sold_ticket * 100 / $value->tickets), 2);
        }

        $date = Carbon::now($timezone);
        $events  = Event::with(['category:id,name'])
            ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
            ->whereRaw('FIND_IN_SET(?, user_id)', [Auth::user()->id]);
        $chip = array();
        if ($request->has('type') && $request->type != null) {
            $chip['type'] = $request->type;
            $events = $events->where('type', $request->type);
        }
        if ($request->has('category') && $request->category != null) {
            $chip['category'] = Category::find($request->category)->name;
            $events = $events->where('category_id', $request->category);
        }
        if ($request->has('duration') && $request->duration != null) {
            $chip['date'] = $request->duration;
            if ($request->duration == 'Today') {
                $temp = Carbon::now($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'Tomorrow') {
                $temp = Carbon::tomorrow($timezone)->format('Y-m-d');
                $events = $events->whereBetween('start_time', [$temp . ' 00:00:00', $temp . ' 23:59:59']);
            } else if ($request->duration == 'ThisWeek') {
                $now = Carbon::now($timezone);
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i:s');
                $events = $events->whereBetween('start_time', [$weekStartDate, $weekEndDate]);
            } else if ($request->duration == 'date') {
                if (isset($request->date)) {
                    $temp = Carbon::parse($request->date)->format('Y-m-d H:i:s');
                    $events = $events->whereBetween('start_time', [$request->date . ' 00:00:00', $request->date . ' 23:59:59']);
                }
            }
        }
        $events = $events->orderBy('start_time', 'ASC')->get();

        $master['eventDate'] = array();
        foreach ($events as $value) {
            $tickets = Event::find($value->id)->people;
            $total = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->avaliable = $tickets - $total;
            array_push($master['eventDate'], $value->start_time->format('Y-m-d'));
        }
        $user_id = Auth()->id();

        // Calculate earnings as SUM(payment) - SUM(tax) for organizer's events
        $sumPayment = DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->whereRaw('FIND_IN_SET(?, events.user_id)', [$user_id])
            ->where('orders.payment_status', 1)
            ->sum('orders.payment');

        $sumTax = DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->whereRaw('FIND_IN_SET(?, events.user_id)', [$user_id])
            ->where('orders.payment_status', 1)
            ->sum('orders.tax');

        $earnings = $sumPayment - $sumTax;
        $latestActivityLogs = Schema::hasTable('admin_activity_logs')
            ? AdminActivityLog::with('actor')
                ->where('actor_user_id', Auth::id())
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
            : collect();
        return view('admin.org_dashboard', compact('events', 'monthEvent', 'master', 'earnings', 'verificationContactDate', 'organizerVerificationStatus', 'latestActivityLogs'));
    }

    public function viewProfile()
    {
        $languages = Language::where('status', 1)->get();
        return view('admin.profile', compact('languages'));
    }

    public function editProfile(Request $request)
    {
        try {
            Log::info('Profile update started', ['user_id' => Auth::user()->id, 'has_file' => $request->hasFile('image')]);

            $data = $request->all();

            if ($request->hasFile('image')) {
                Log::info('Image file detected, starting validation');

                $request->validate([
                    'image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:3048',
                ]);

                Log::info('Image validation passed');

                $user = User::find(Auth::user()->id);

                // Delete old image if it's not the default
                if ($user->image && $user->image != "defaultuser.png") {
                    $oldImagePath = public_path('images/upload/' . $user->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                        Log::info('Old image deleted: ' . $user->image);
                    }
                }

                // Save new image
                $data['image'] = (new AppHelper)->saveImage($request->file('image'));
                Log::info('New image saved: ' . $data['image']);
            }

            User::find(Auth::user()->id)->update($data);
            Log::info('Profile updated successfully');

            if (session()->get('locale') != $request->language) {
                App::setLocale($request->language);
                session()->put('locale', $request->language);
                $direction = Language::where('name', $request->language)->first()->direction;
                session()->put('direction', $direction);
            }

            return redirect('profile')->withStatus(__('Profile has updated successfully.'));
        } catch (Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return redirect('profile')->withErrors(['error' => 'Failed to update profile. Please try again.']);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'bail|required',
            'password' => 'bail|required|min:6',
            'confirm_password' => 'bail|required|same:password|min:6'
        ]);

        if (Hash::check($request->current_password, Auth::user()->password)) {
            User::find(Auth::user()->id)->update(['password' => Hash::make($request->password)]);
            return redirect('profile')->with('status', __('Password has updated successfully.'));
        } else {
            return Redirect::back()->with('error_msg', 'Current Password is wrong!');
        }
    }

    public function deleteSelfAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'confirmation' => 'required|in:DELETE'
        ], [
            'confirmation.in' => 'Please type DELETE to confirm account deletion.'
        ]);

        $user = Auth::user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['password' => 'The password is incorrect.']);
        }

        // Check if user is an organizer with active events
        if ($user->hasRole('Organizer')) {
            $timezone = Setting::find(1)->timezone;
            $now = Carbon::now($timezone);

            $activeEvents = Event::where('user_id', $user->id)
                ->where('is_deleted', 0)
                ->where('end_time', '>', $now->format('Y-m-d H:i:s'))
                ->count();

            if ($activeEvents > 0) {
                return redirect()->back()->withErrors([
                    'error' => 'You cannot delete your account while you have active events. Please cancel or complete all your events first.'
                ]);
            }
        }

        try {
            // Mark the account as soft deleted with timestamp
            $user->deleted_softaccount = Carbon::now();
            $user->status = 0; // Disable account
            $user->save();

            // Also soft delete the user using Laravel's SoftDeletes
            $user->delete();

            // Logout the user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('status', 'Your account has been successfully deleted.');
        } catch (Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to delete account. Please try again or contact support.']);
        }
    }



    public function makePayment($id)
    {
        $order = Order::with(['customer'])->find($id);
        return view('createPayment', compact('order'));
    }

    public function transction_verify(Request $request, $order_id)
    {
        $order = Order::find($order_id);
        $id = $request->input('transaction_id');
        if ($request->input('status') == 'successful') {
            $order->payment_token = $id;
            $order->payment_status = 1;
            $order->save();
            return view('transction_verify');
        } else {
            return view('cancel');
        }
    }


    public function changeLanguage($lang)
    {
        App::setLocale($lang);
        session()->put('locale', $lang);
        $dir = Language::where('name', $lang)->first()->direction;
        session()->put('direction', $dir);
        return redirect()->back();
    }

    public function scanner()
    {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        if (Auth::user()->hasRole('admin')) {
            $scanners = User::role('scanner')->orderBy('id', 'DESC')->get();
            $events = Event::where([['status', 1], ['is_deleted', 0], ['end_time', '>', $date->format('Y-m-d H:i:s')]])->get();
        } else {
            $org_id = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
            $scanners = User::role('scanner')->where('org_id', $org_id)->orderBy('id', 'DESC')->get();
            $events = Event::where([['status', 1], ['is_deleted', 0], ['end_time', '>', $date->format('Y-m-d H:i:s')], ['user_id', $org_id]])->get();
        }
        foreach ($scanners as $value) {
            $value->total_event = 0;
            foreach ($events as $key => $event) {
                if (!str_contains($event->scanner_id,$value->id)) {
                    if (preg_match("/\b$value->id\b/", $event->scanner_id)){
                        $value->total_event += 1;
                    }
                }

            }
        }
        return view('admin.scanner.index', compact('scanners'));
    }

    public function scannerCreate()
    {
        return view('admin.scanner.create');
    }

    public function addScanner(Request $request)
    {
        $request->validate([
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|required|email|unique:users',
            'phone' => 'bail|required',
            'password' => 'bail|required|min:6',
        ]);
        $data = $request->all();
        $data['org_id'] = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
        $data['password'] =  Hash::make($request->password);
        $data['language'] = Setting::first()->language;
        $user = User::create($data);
        $user->assignRole('scanner');

        // Log scanner creation activity
        AdminActivityLog::record(
            AdminActivityLog::SCANNER_CREATED,
            $user,
            __('Scanner Created'),
            __('Scanner :name (:email) was created by :actor.', [
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'actor' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
            ]),
            [],
            $request
        );

        return redirect('scanner')->withStatus(__('Scanner is added successfully.'));
    }

    public function blockScanner($id)
    {
        $user = User::find($id);
        $user->status = $user->status == "1" ? "0" : "1";
        $user->save();
        return redirect('scanner')->withStatus(__('User status changed successfully.'));
    }

    public function getScanner($id)
    {
        if (empty($id)) {
            return response()->json(['data' => [], 'success' => true], 200);
        }
        $ids = is_array($id) ? $id : explode(',', (string) $id);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        $query = User::role('scanner')->where('status', 1)->orderBy('id', 'DESC');
        if (!empty($ids)) {
            $query->whereIn('org_id', $ids);
        }
        $data = $query->get();
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function main_user_block($id)
    {
        $event = Event::where('user_id', $id)->get();
        foreach ($event as $item) {

            if ($item->end_time >= Carbon::now()) {
                return redirect('users')->withstatusblock(__("Please turn off all the ongoing events of the user before blocking"));
            }
        }
        $user = User::find($id);
        $user->status = $user->status == "1" ? "0" : "1";
        $user->save();
        return redirect('users')->withStatus(__('User status changed successfully.'));
    }

    public function check_email(Request $request)
    {
        $data = '';
        $setting = Setting::find(1);
        try {
            $config = array(
                'driver'     => $setting->mail_mailer,
                'host'       => $setting->mail_host,
                'port'       => $setting->mail_port,
                'encryption' => $setting->mail_encryption,
                'username'   => $setting->mail_username,
                'password'   => $setting->mail_password
            );
            Config::set('mail', $config);
            Mail::send(
                'emails.check_email',
                ['data' => $data],
                function ($message) use ($request, $setting) {
                    $message->from($setting->sender_email);
                    $message->to($request->input('mail_to'));
                    $message->subject('this mail is just to check configure');
                }
            );

            return response()->json(['message' => 'Email sent successfully', 'data' => $request->mail_to, 'success' => true]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['message' => 'Failed to sent Email', 'data' => $error, 'success' => false]);
        }
    }
    public function editAppUser($id)
    {
        $user = AppUser::find($id);
        return View('admin.appUser.edit', compact('user'));
    }
    public function updateAppUser(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'last_name' => 'bail|required',
            'phone' => 'bail|required',
        ]);
        $user = AppUser::find($request->id);
        $emailcheck = AppUser::where('email', $request->email)->where('id', '!=', $user->id)->first();
        if ($emailcheck) {
            return redirect()->back()->with('email', 'The email address has already been taken.');
        }
        $data =  $request->all();
        $user->update($data);
        return redirect()->back()->with('status', 'AppUser Details Update Successfully.');
    }
    public function orgincome(Request $request)
    {
        // Get the current organizer's ID
        $organizerId = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;

        // Get orders for this specific organizer only, with proper relationships
        $query = Order::with([
            'event:id,name,user_id',
            'appUser:id,name,last_name,email',
            'guestUser:id,name,last_name,email'
        ])
        ->where('organization_id', $organizerId);

        if (isset($request->payment_status_filter) && $request->payment_status_filter !== '') {
            $query->where('payment_status', $request->payment_status_filter);
        } else {
            $query->whereIn('payment_status', [1, 2]);
        }

        if (isset($request->event_id) && $request->event_id >= 1) {
            $query->where('event_id', $request->event_id);
        }

        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $query->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }

        $data = $query->orderBy('id', 'DESC')->get();

        // Get events belonging to this organizer for the filter dropdown
        $events = \App\Models\Event::whereRaw('FIND_IN_SET(?, user_id)', [$organizerId])->orderBy('id', 'DESC')->get(['id', 'name']);

        // Calculate totals for this organizer only (active completed orders)
        $organizerOrders = Order::where('organization_id', $organizerId)->where('payment_status', 1);
        $tottax = $organizerOrders->sum('tax');
        $org_revenue = $organizerOrders->sum('org_revenue');
        $admin_revenue = $tottax - $org_revenue;

        // Add currency symbol
        $currency = \App\Models\Setting::first()->currency_sybmol ?? '$';

        // --- Revenue Card Breakdowns (respect event_id and duration filters) ---
        $revenueEventId = (isset($request->event_id) && $request->event_id >= 1) ? $request->event_id : null;
        $revenueDateRange = (isset($request->duration) && $request->duration != null) ? $request->duration : null;

        // Build a base query closure that applies shared filters
        $buildRevenueQuery = function (string $paymentType, int $paymentStatus) use ($organizerId, $revenueEventId, $revenueDateRange) {
            $q = Order::where('organization_id', $organizerId)
                ->where('payment_status', $paymentStatus);

            if ($paymentType === 'STRIPE') {
                $q->where('payment_type', 'STRIPE');
            } else {
                $q->where('payment_type', '!=', 'STRIPE');
            }

            if ($revenueEventId) {
                $q->where('event_id', $revenueEventId);
            }

            if ($revenueDateRange) {
                $parts      = explode(' to ', $revenueDateRange);
                $start_date = $parts[0];
                $end_date   = count($parts) === 1 ? $parts[0] : $parts[1];
                $q->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }

            return $q;
        };

        // Online orders = STRIPE payment type, payment_status = 1
        $onlineOrders = $buildRevenueQuery('STRIPE', 1)->get();

        // Local orders = non-STRIPE (offline/cash/local), payment_status = 1
        $localOrders = $buildRevenueQuery('OFFLINE', 1)->get();

        // Refunded orders
        $onlineRefunded = $buildRevenueQuery('STRIPE', 2)->sum('payment');
        $localRefunded  = $buildRevenueQuery('OFFLINE', 2)->sum('payment');

        // Online totals
        // Total Ticket Price includes tax (full amount customer paid)
        $onlineTicketPrice   = $onlineOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
        $onlineProcessingFee = $onlineOrders->sum(fn($o) => (float)$o->tax);
        $onlinePlatformFee   = \App\Models\OrderFee::whereIn('order_id', $onlineOrders->pluck('id'))->sum('amount');
        // Include coupon_discount so organizer sees full ticket value they are owed
        $onlineRevenue       = $onlineOrders->sum(fn($o) => (float)$o->org_revenue
            ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
            : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

        // Local totals
        // Total Ticket Price includes tax (full amount customer paid)
        $localTicketPrice    = $localOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
        $localProcessingFee  = $localOrders->sum(fn($o) => (float)$o->tax);
        $localPlatformFee    = \App\Models\OrderFee::whereIn('order_id', $localOrders->pluck('id'))->sum('amount');
        // Include coupon_discount so organizer sees full ticket value they are owed
        $localRevenue        = $localOrders->sum(fn($o) => (float)$o->org_revenue
            ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
            : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

        $grossRevenue = $onlineRevenue + $localRevenue;

        // Net Payout = Gross Revenue − Offline Sales − Processing Fee (Online)
        //            − Processing Fee (Offline) − Platform Fee (Online)
        //            − Refunded Amount − Platform Fee (Offline)
        $grossTicket = $onlineTicketPrice + $localTicketPrice;
        $netPayout   = $grossTicket
            - $localTicketPrice                         // Offline Sales (organizer collects directly)
            - $onlineProcessingFee                      // Processing Fee (Online)
            - $localProcessingFee                       // Processing Fee (Offline)
            - $onlinePlatformFee                        // Platform Fee (Online)
            - ($onlineRefunded + $localRefunded)        // Refunded Amount
            - $localPlatformFee;                        // Platform Fee (Offline)

        return view('admin.organizer.revenue', compact(
            'data', 'admin_revenue', 'currency', 'events', 'request',
            'onlineRevenue', 'localRevenue', 'grossRevenue', 'netPayout',
            'onlineTicketPrice', 'onlineProcessingFee', 'onlinePlatformFee', 'onlineRefunded',
            'localTicketPrice', 'localProcessingFee', 'localPlatformFee', 'localRefunded'
        ));
    }
    public function checkoutSession(Request $request)
    {
        $request->session()->put('request', $request->all());
        $key = OrganizerPaymentKeys::where('organizer_id', $request->id)->first()->stripeSecretKey;
        Stripe::setApiKey($key);
        $supportedCurrency = [
            "EUR",   # Euro
            "GBP",   # British Pound Sterling
            "CAD",   # Canadian Dollar
            "AUD",   # Australian Dollar
            "JPY",   # Japanese Yen
            "CHF",   # Swiss Franc
            "NZD",   # New Zealand Dollar
            "HKD",   # Hong Kong Dollar
            "SGD",   # Singapore Dollar
            "SEK",   # Swedish Krona
            "DKK",   # Danish Krone
            "PLN",   # Polish Złoty
            "NOK",   # Norwegian Krone
            "CZK",   # Czech Koruna
            "HUF",   # Hungarian Forint
            "ILS",   # Israeli New Shekel
            "MXN",   # Mexican Peso
            "BRL",   # Brazilian Real
            "MYR",   # Malaysian Ringgit
            "PHP",   # Philippine Peso
            "TWD",   # New Taiwan Dollar
            "THB",   # Thai Baht
            "TRY",   # Turkish Lira
            "RUB",   # Russian Ruble
            "INR",   # Indian Rupee
            "ZAR",   # South African Rand
            "AED",   # United Arab Emirates Dirham
            "SAR",   # Saudi Riyal
            "KRW",   # South Korean Won
            "CNY"    # Chinese Yuan
        ];
        $currencyCode = Setting::first()->currency;
        $amount = $request->total;
        if (!in_array($currencyCode, $supportedCurrency)) {
            $amount = $amount * 100;
        }
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currencyCode,
                        'product_data' => [
                            'name' => "Payment"
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => route('orgStripe.success'),
            'cancel_url' => route('settlementReport'),
        ]);
        return response()->json(['id' => $session->id, 'status' => 200]);
    }
    public function stripeSuccess()
    {
        $request = Session::get('request');
        $data['user_id'] = $request['id'];
        $data['payment'] = $request['total'];
        $data['payment_status'] = 1;
        $data['payment_token'] = $request['token'] ?? null;
        $data['payment_type'] = 'Stripe';
        Settlement::create($data);
        Order::where([['organization_id', $data['user_id']], ['payment_status', 1], ['org_pay_status', 0]])->update(['org_pay_status' => 1]);
        return redirect()->route('settlementReport')->withStatus(__('Payment has done successfully.'));
    }
    public function orgKey(Request $request)
    {
        $key = OrganizerPaymentKeys::where('organizer_id', $request->id)->first()->stripePublicKey;
        return response()->json(['key' => $key, 'status' => 200]);
    }
    public function eventTicket(Request $request)
    {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $tickets = Ticket::where([['event_id', $request->event_id], ['is_deleted', 0], ['status', 1], ['is_add_on', 0]])->orderBy('id', 'ASC')->get();

        // Calculate total booked for the event
        $event = Event::find($request->event_id);
        $totalBooked = Order::where('event_id', $request->event_id)->sum('quantity');
        $remainingCapacity = $event->people - $totalBooked;

        // Include seat information for tickets that have seats
        $ticketsWithSeats = $tickets->map(function ($ticket) use ($remainingCapacity) {
            $ticketData = $ticket->toArray();

            $ticketData['total_quantity'] = $ticket->quantity;

            // Calculate booked quantity for this specific ticket by parsing orders for the event
            $bookedForTicket = 0;
            $ordersForEvent = \App\Models\Order::where('event_id', $ticket->event_id)->get();
            foreach ($ordersForEvent as $ord) {
                $ticketIds = $this->normalizeList($ord->ticket_id);
                $quantities = $this->normalizeList($ord->quantity);
                foreach ($ticketIds as $index => $tid) {
                    if ((int) trim($tid) === (int) $ticket->id) {
                        if (isset($quantities[$index])) {
                            $bookedForTicket += (int) $quantities[$index];
                        }
                    }
                }
            }
            // Ensure booked does not exceed ticket total
            $bookedForTicket = min($bookedForTicket, $ticketData['total_quantity']);

            $ticketData['booked_quantity'] = $bookedForTicket;
            // Available is the remaining from this ticket, but cannot exceed overall event remaining capacity
            $ticketData['available_quantity'] = max(0, min($ticketData['total_quantity'] - $bookedForTicket, $remainingCapacity));

            // Get seat tables if ticket has seat table IDs
            if (!empty($ticket->SeatTable_id)) {
                $seatTableIds = $this->normalizeList($ticket->SeatTable_id);
                $seatTableIds = array_filter($seatTableIds);
                $seatTables = \App\Models\SeatTable::whereIn('id', $seatTableIds)->get();

                $seatTablesData = $seatTables->map(function ($seatTable) {
                    $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $seatTable->sponsership_id)
                        ->where('seat_id', $seatTable->id)
                        ->count();

                    $availableSeats = $seatTable->number_seat - $existingOrders;

                    // Check for sponsership_id == 8 (special case)
                    if ($seatTable->sponsership_id == 8) {
                        $availableSeats *= 2;
                        $totalSeats = $seatTable->number_seat * 2;
                    } else {
                        $totalSeats = $seatTable->number_seat;
                    }

                    return [
                        'id' => $seatTable->id,
                        'name_of_table' => $seatTable->name_of_table,
                        'prefixname' => $seatTable->prefixname,
                        'sponsership_id' => $seatTable->sponsership_id,
                        'available_seats' => $availableSeats,
                        'total_seats' => $totalSeats,
                        'is_available' => $availableSeats > 0
                    ];
                });

                $ticketData['seat_tables'] = $seatTablesData;
                $ticketData['has_seats'] = true;
            } else {
                $ticketData['seat_tables'] = [];
                $ticketData['has_seats'] = false;
            }

            return $ticketData;
        });

        return response()->json(['tickets' => $ticketsWithSeats, 'success' => true], 200);
    }

    public function adminVenueSeatMap(Request $request, Event $event)
    {
        $liveVenueMap = $event->liveVenueMap()
            ->with(['venue', 'template', 'seats.ticket:id,name,type,price'])
            ->first();

        if (!$liveVenueMap || !$liveVenueMap->template) {
            return response()->json([
                'success' => false,
                'message' => __('Venue seat map is not available for this event.'),
            ], 404);
        }

        $this->releaseExpiredVenueSeatHoldsForAdmin($event->id);

        $selectedTicketIds = $this->expandedTicketIdsFromArrays(
            $request->input('ticket_id', $request->input('tickets', [])),
            $request->input('quantity', [])
        );

        if ($request->has('tickets')) {
            $selectedTicketIds = collect($this->normalizeList($request->input('tickets')))
                ->filter(fn ($ticketId) => is_numeric($ticketId) && (int) $ticketId > 0)
                ->map(fn ($ticketId) => (int) $ticketId)
                ->values()
                ->all();
        }

        $selectedTicketIdsUnique = array_values(array_unique($selectedTicketIds));
        $selectedVenueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', []));
        $selectedVenueSeatLookup = array_flip($selectedVenueSeatIds);
        $sessionId = $request->session()->getId();

        $seats = $liveVenueMap->seats
            ->sortBy([
                ['event_venue_section_id', 'asc'],
                ['event_venue_row_id', 'asc'],
                ['seat_number', 'asc'],
            ])
            ->values();

        $usesTicketRows = $seats->contains(fn ($seat) => !empty($seat->ticket_id));

        $formattedSeats = $seats->map(function ($seat) use ($selectedTicketIdsUnique, $selectedVenueSeatLookup, $sessionId, $usesTicketRows) {
            $status = $seat->status ?: EventVenueSeat::STATUS_AVAILABLE;
            $seatTicketId = (int) ($seat->ticket_id ?? 0);
            $isHeldByCurrentSession = $status === EventVenueSeat::STATUS_HELD
                && $seat->held_by_session_id === $sessionId
                && $seat->hold_expires_at
                && $seat->hold_expires_at->isFuture();
            $isAvailable = $status === EventVenueSeat::STATUS_AVAILABLE;
            $matchesSelectedTicket = !$usesTicketRows
                || ($seatTicketId > 0 && in_array($seatTicketId, $selectedTicketIdsUnique, true));
            $isSelectable = ($isAvailable || $isHeldByCurrentSession) && $matchesSelectedTicket;
            $isSelected = isset($selectedVenueSeatLookup[$seat->id]) || $isHeldByCurrentSession;
            $classStatus = $isSelected
                ? 'selected'
                : ($isAvailable && !$isSelectable ? EventVenueSeat::STATUS_BLOCKED : $status);
            $seatLabel = $seat->seat_label ?: trim($seat->section_name . ' ' . $seat->row_name . ' Seat ' . $seat->seat_number);

            return [
                'id' => (int) $seat->id,
                'seat_label' => $seatLabel,
                'section_name' => $seat->section_name,
                'row_name' => $seat->row_name,
                'seat_number' => (int) $seat->seat_number,
                'x_percent' => (float) $seat->x_percent,
                'y_percent' => (float) $seat->y_percent,
                'ticket_id' => $seatTicketId ?: null,
                'ticket_name' => optional($seat->ticket)->name,
                'status' => $status,
                'class_status' => $classStatus,
                'selectable' => $isSelectable,
                'selected' => $isSelected,
                'title' => trim($seatLabel . ' - ' . ucfirst($classStatus)),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'event_id' => (int) $event->id,
                'map' => [
                    'id' => (int) $liveVenueMap->id,
                    'venue_name' => optional($liveVenueMap->venue)->name,
                    'background_image_url' => $liveVenueMap->template->background_image
                        ? url('images/upload/' . $liveVenueMap->template->background_image)
                        : null,
                    'background_width' => (int) ($liveVenueMap->template->background_width ?: 750),
                    'background_height' => (int) ($liveVenueMap->template->background_height ?: 550),
                    'hold_minutes' => (int) ($liveVenueMap->hold_minutes ?: 10),
                ],
                'uses_ticket_rows' => $usesTicketRows,
                'selected_ticket_quantity' => count($selectedTicketIds),
                'selected_seat_quantity' => count($selectedVenueSeatIds),
                'seats' => $formattedSeats,
            ],
        ]);
    }

    public function adminHoldVenueSeats(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'venue_seat_ids' => 'nullable',
            'tickets' => 'nullable',
        ]);

        $event = Event::findOrFail($request->input('event_id'));
        $liveVenueMap = $event->liveVenueMap()->first();

        if (!$liveVenueMap) {
            return response()->json([
                'success' => false,
                'message' => __('Venue seat map is not available for this event.'),
            ], 404);
        }

        $venueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', []));
        $selectedTicketIds = collect($this->normalizeList($request->input('tickets', [])))
            ->filter(fn ($ticketId) => is_numeric($ticketId) && (int) $ticketId > 0)
            ->map(fn ($ticketId) => (int) $ticketId)
            ->values()
            ->all();
        $selectedTicketIdsUnique = array_values(array_unique($selectedTicketIds));
        $sessionId = $request->session()->getId();

        $holdResult = DB::transaction(function () use ($event, $liveVenueMap, $venueSeatIds, $selectedTicketIdsUnique, $sessionId) {
            $this->releaseExpiredVenueSeatHoldsForAdmin($event->id);

            EventVenueSeat::where('event_id', $event->id)
                ->where('status', EventVenueSeat::STATUS_HELD)
                ->where('held_by_session_id', $sessionId)
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
                return ['success' => false, 'message' => __('One or more selected seats were not found.')];
            }

            $mappedSeatTicketIds = $lockedSeats
                ->pluck('ticket_id')
                ->filter()
                ->map(fn ($ticketId) => (int) $ticketId)
                ->unique()
                ->values()
                ->all();

            if (!empty($mappedSeatTicketIds) && !empty(array_diff($mappedSeatTicketIds, $selectedTicketIdsUnique))) {
                return ['success' => false, 'message' => __('Selected seat does not match the selected ticket.')];
            }

            $unavailableSeat = $lockedSeats->first(function ($seat) use ($sessionId) {
                if ($seat->status === EventVenueSeat::STATUS_AVAILABLE) {
                    return false;
                }

                return !(
                    $seat->status === EventVenueSeat::STATUS_HELD
                    && $seat->held_by_session_id === $sessionId
                    && !$seat->isHoldExpired()
                );
            });

            if ($unavailableSeat) {
                return ['success' => false, 'message' => __('One or more selected seats are no longer available.')];
            }

            $expiresAt = now()->addMinutes((int) ($liveVenueMap->hold_minutes ?: 10));

            EventVenueSeat::whereIn('id', $venueSeatIds)->update([
                'status' => EventVenueSeat::STATUS_HELD,
                'hold_token' => Str::random(40),
                'held_by_session_id' => $sessionId,
                'held_by_app_user_id' => null,
                'held_by_guest_user_id' => null,
                'held_at' => now(),
                'hold_expires_at' => $expiresAt,
            ]);

            return ['success' => true, 'hold_expires_at' => $expiresAt->toIso8601String()];
        });

        if (!$holdResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $holdResult['message'] ?? __('Unable to hold selected seats.'),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => __('Seats held successfully.'),
            'hold_expires_at' => $holdResult['hold_expires_at'],
        ]);
    }

     public function orderCreateForUser(Request $request)
{
    if ($request->isMethod('get')) {
        if (Auth::user()->hasRole('admin')) {
            $eventData = Event::with('liveVenueMap')
                ->where([['end_time', '>', now()], ['status', 1], ['is_deleted', 0]])
                ->get();
            $ticket = Ticket::with('event')->where('is_deleted', 0)->get();
        } else {
            $org_id = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
            $eventData = Event::with('liveVenueMap')
                ->where([['end_time', '>', now()], ['status', 1], ['is_deleted', 0]])
                ->where(function ($query) use ($org_id) {
                    $query->where('user_id', $org_id)
                          ->orWhereRaw('FIND_IN_SET(?, user_id)', [$org_id]);
                })
                ->get();
            $ticket = Ticket::where('is_deleted', 0)->where('user_id', $org_id)->get();
        }
        return view('admin.order.create', compact('ticket', 'eventData'));
    }

    if ($request->isMethod('post')) {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'custom_amount.*' => 'nullable|numeric|min:0',
            'tax_custom_amount' => 'nullable',
            'tax_custom_amount.*' => 'nullable|numeric|min:0',
        ]);

        // Get the event
        $event = Event::find($request->event_id);
        if (!$event) {
            return redirect()->back()->withErrors([
                'event_id' => 'Event not found.'
            ])->withInput();
        }

        // Validate ticket quantities against event capacity
        $ticketIds = $request->ticket_id;
        $quantities = $request->quantity;
        $venueSeatIds = $this->normalizeVenueSeatIds($request->input('venue_seat_ids', []));

        // Check if quantities are positive
        foreach ($quantities as $qty) {
            if ($qty < 1) {
                return redirect()->back()->withErrors([
                    'quantity' => 'Quantity must be at least 1.'
                ])->withInput();
            }
        }

        // Calculate total booked for the event
        $totalBooked = Order::where('event_id', $request->event_id)->sum('quantity');
        $totalRequested = array_sum($quantities);

        if ($totalRequested + $totalBooked > $event->people) {
            return redirect()->back()->withErrors([
                'quantity' => "Total requested tickets ({$totalRequested}) plus already booked ({$totalBooked}) exceeds event capacity ({$event->people})."
            ])->withInput();
        }

        $liveVenueMap = $event->liveVenueMap()->first();

        if ($liveVenueMap && count($venueSeatIds) !== (int) $totalRequested) {
            return redirect()->back()->withErrors([
                'venue_seat_ids' => 'Please select seats from the venue seat map for every ticket.'
            ])->withInput();
        }

        if (!$liveVenueMap && !empty($venueSeatIds)) {
            return redirect()->back()->withErrors([
                'venue_seat_ids' => 'Venue seat map is not available for this event.'
            ])->withInput();
        }

        if (!empty($venueSeatIds)) {
            if (count($venueSeatIds) !== (int) $totalRequested) {
                return redirect()->back()->withErrors([
                    'venue_seat_ids' => 'Selected seat count must match selected ticket quantity.'
                ])->withInput();
            }

            $this->releaseExpiredVenueSeatHoldsForAdmin($event->id);

            $heldSeats = EventVenueSeat::where('event_id', $event->id)
                ->where('event_venue_map_id', $liveVenueMap->id)
                ->whereIn('id', $venueSeatIds)
                ->get();

            if ($heldSeats->count() !== count($venueSeatIds)) {
                return redirect()->back()->withErrors([
                    'venue_seat_ids' => 'One or more selected seats are invalid.'
                ])->withInput();
            }

            $invalidHold = $heldSeats->first(function ($seat) {
                return !(
                    $seat->status === EventVenueSeat::STATUS_HELD
                    && $seat->held_by_session_id === session()->getId()
                    && !$seat->isHoldExpired()
                );
            });

            if ($invalidHold) {
                return redirect()->back()->withErrors([
                    'venue_seat_ids' => 'Your selected seat hold has expired. Please select seats again.'
                ])->withInput();
            }

            $mappedSeatTicketIds = $heldSeats
                ->pluck('ticket_id')
                ->filter()
                ->map(fn ($ticketId) => (int) $ticketId)
                ->unique()
                ->values()
                ->all();
            $selectedTicketIds = collect($ticketIds)
                ->filter(fn ($ticketId) => is_numeric($ticketId) && (int) $ticketId > 0)
                ->map(fn ($ticketId) => (int) $ticketId)
                ->unique()
                ->values()
                ->all();

            if (!empty($mappedSeatTicketIds) && !empty(array_diff($mappedSeatTicketIds, $selectedTicketIds))) {
                return redirect()->back()->withErrors([
                    'venue_seat_ids' => 'Selected seat does not match the selected ticket.'
                ])->withInput();
            }
        }

        // Check if tickets exist and belong to the event and validate per-ticket availability
        foreach ($ticketIds as $index => $ticketId) {
            $ticket = Ticket::find($ticketId);
            if (!$ticket || $ticket->event_id != $request->event_id) {
                return redirect()->back()->withErrors([
                    'ticket_id' => "Invalid ticket selected."
                ])->withInput();
            }

            // Calculate booked quantity for this specific ticket
            $bookedQty = 0;
            $ordersForEvent = Order::where('event_id', $request->event_id)->get();
            foreach ($ordersForEvent as $ord) {
                $tIds = $this->normalizeList($ord->ticket_id);
                $qtys = $this->normalizeList($ord->quantity);
                foreach ($tIds as $i => $tid) {
                    if ((int) trim($tid) === (int) $ticket->id) {
                        if (isset($qtys[$i])) {
                            $bookedQty += (int) $qtys[$i];
                        }
                    }
                }
            }

            $availableForTicket = max(0, min($ticket->quantity - $bookedQty, $event->people - $totalBooked));
            $requested = isset($quantities[$index]) ? (int) $quantities[$index] : 0;

            // Check maximum tickets allowed per order for this ticket
            if ($ticket->ticket_per_order > 0 && $requested > $ticket->ticket_per_order) {
                return redirect()->back()->withErrors([
                    'quantity' => "Maximum of {$ticket->ticket_per_order} tickets can be booked per order for {$ticket->name}."
                ])->withInput();
            }

            if ($requested > $availableForTicket) {
                return redirect()->back()->withErrors([
                    'quantity' => "Only {$availableForTicket} tickets available for {$ticket->name}. You requested {$requested}."
                ])->withInput();
            }
        }

        // Validate per-line custom amounts (per user's choice: custom amount is a flat amount applied per line)
        $customAmounts = $request->custom_amount ?? [];
        foreach ($request->ticket_id as $i => $tid) {
            $ticket = Ticket::find($tid);
            $qty = isset($request->quantity[$i]) ? intval($request->quantity[$i]) : 1;
            $custom = isset($customAmounts[$i]) ? floatval($customAmounts[$i]) : 0;
            // Ensure custom amount does not exceed line total
            if ($custom < 0 || $custom > ($ticket->price * $qty)) {
                return redirect()->back()->withErrors([
                    'custom_amount.' . $i => 'Custom amount must be between 0 and ' . ($ticket->price * $qty)
                ])->withInput();
            }
        }

        // Build associative mapping ticket_id => custom_amount for createOrder
        $customData = [];
        foreach ($request->ticket_id as $i => $tid) {
            $customData[$tid] = isset($customAmounts[$i]) ? floatval($customAmounts[$i]) : 0;
        }

        // Validate tax_custom_amount (custom price) must be >= 0
        if ($request->tax_option === 'custom_amount') {
            if (is_array($request->tax_custom_amount)) {
                foreach ($request->tax_custom_amount as $idx => $cAmount) {
                    if ($cAmount === null || $cAmount === '' || floatval($cAmount) < 0) {
                        return redirect()->back()->withErrors([
                            'tax_custom_amount' => 'Custom price must be 0 or greater for all ticket components.'
                        ])->withInput();
                    }
                }
            } else {
                $taxCustomAmount = floatval($request->tax_custom_amount ?? 0);
                if ($taxCustomAmount < 0) {
                    return redirect()->back()->withErrors([
                        'tax_custom_amount' => 'Custom price must be 0 or greater.'
                    ])->withInput();
                }
            }
        }

        $email = $request->email;
        $user = AppUser::where('email', $email)->first();

        if (!$user) {
            $user = AppUser::create([
                'email' => $email,
                'password' => bcrypt('123456'),
                'name' => $request->name ?? time(),
                'phone' => $request->phone,
                'provider' => 'LOCAL',
                'is_verify' => 1,
            ]);
        } else {
            // Update phone if user exists
            $user->update([
                'phone' => $request->phone,
                'name' => $request->name ?? $user->name,
            ]);
        }

        // Map ticket_id to custom price when tax_option is custom_amount
        $customPriceData = [];
        if ($request->tax_option === 'custom_amount') {
            if (is_array($request->tax_custom_amount)) {
                foreach ($request->ticket_id as $i => $tid) {
                    $customPriceData[$tid] = isset($request->tax_custom_amount[$i]) ? floatval($request->tax_custom_amount[$i]) : 0;
                }
            } else {
                foreach ($request->ticket_id as $i => $tid) {
                    $customPriceData[$tid] = floatval($request->tax_custom_amount ?? 0);
                }
            }
        }

        $firstCustomAmount = 0;
        if (is_array($request->tax_custom_amount)) {
            $firstCustomAmount = !empty($request->tax_custom_amount) ? floatval($request->tax_custom_amount[0]) : 0;
        } else {
            $firstCustomAmount = floatval($request->tax_custom_amount ?? 0);
        }

        $data = [
            'ticket_id'   => implode(',', $request->ticket_id),
            'quantity'    => implode(',', $request->quantity),
            'user_id'     => $user->id,
            'ticket_date' => $request->ticket_date,
            'ticketData'  => array_combine($request->ticket_id, $request->quantity),
            'event_id'    => $request->event_id,
            'seat_data'   => $request->seat_id ?? [], // kept for backward-compatibility (UI now uses custom amount)
            'customData'  => $customData, // per-line custom amounts (flat amount per line)
            'customPriceData' => $customPriceData, // per-ticket custom prices when tax_option is custom_amount
            'tax_option'  => $request->tax_option ?? 'with_tax', // Add tax option
            'tax_custom_amount' => $firstCustomAmount,
            'venue_seat_ids' => $venueSeatIds,
        ];

        $orderData = $this->createOrder($data);

        return redirect()->back()->with([
            'status' => 'Order has been created successfully.',
            'order_id' => $orderData
        ]);
    }
}
    public function createOrder($data)
    {
        $data['payment_type'] = 'LOCAL';
        $ticketIds = $this->normalizeList($data['ticket_id']);
        $ticket = Ticket::whereIn('id', $ticketIds)->get();
        $event = Event::find($data['event_id']);
        $org = User::find($event->user_id);
        $user = AppUser::find($data['user_id']);
        $data['order_id'] = '#' . rand(9999, 100000);
        $data['event_id'] = $event->id;
        $data['customer_id'] = $user->id;
        $data['organization_id'] = $org->id;
        $data['payment_status'] = 0;
        $data['order_status'] = 'Complete';
        $data['ticket_date'] = Carbon::parse($data['ticket_date'])->format('Y-m-d 00:00:00');
        $com = Setting::find(1, ['org_commission_type', 'org_commission']);
        // Base payment (sum of ticket price * qty)
        $payment = 0;
        $customTotal = 0;
        foreach ($ticket as $_ticket) {
            $qty = isset($data['ticketData'][$_ticket->id]) ? intval($data['ticketData'][$_ticket->id]) : 0;
            $payment += $_ticket->price * $qty;
            // subtract any per-line custom amount (flat amount per line)
            $customForTicket = isset($data['customData'][$_ticket->id]) ? floatval($data['customData'][$_ticket->id]) : 0;
            $customTotal += $customForTicket;
        }

        // Apply custom discounts/adjustments (ensure payment doesn't go negative)
        $payment = max(0, $payment - $customTotal);
        // keep custom total available in data for later use if needed
        $data['custom_total'] = $customTotal;

        // Handle tax based on tax_option
        $taxOption = $data['tax_option'] ?? 'with_tax';
        $totalTax = [];

        if ($taxOption === 'with_tax') {
            // Apply taxes normally
            $allTax = Tax::where(['status'=>1,"allow_all_bill"=>1])->get();
            foreach ($allTax as $key => $value) {
                if ($value->amount_type == 'percentage') {
                    $totalTax[$key]['id'] = $value->id;
                    $totalTax[$key]['price'] = $payment * $value->price / 100;
                }
                if ($value->amount_type == 'price') {
                    $totalTax[$key]['id'] = $value->id;
                    $totalTax[$key]['price'] =  $value->price;
                }
            }
        } elseif ($taxOption === 'without_tax') {
            // No tax applied
            $totalTax = [];
        } elseif ($taxOption === 'custom_amount') {
            // Custom amount: calculate payment based on each ticket component's custom price * quantity
            $customPriceMap = $data['customPriceData'] ?? [];
            $defaultCustomPrice = isset($data['tax_custom_amount']) ? floatval($data['tax_custom_amount']) : 0;
            $payment = 0;
            foreach ($ticket as $_ticket) {
                $qty = isset($data['ticketData'][$_ticket->id]) ? intval($data['ticketData'][$_ticket->id]) : 0;
                $customPrice = isset($customPriceMap[$_ticket->id]) ? floatval($customPriceMap[$_ticket->id]) : $defaultCustomPrice;
                $payment += $customPrice * $qty;
            }
            // No tax entries for custom_amount - price is already set
            $totalTax = [];
        } elseif ($taxOption === 'complimentary') {
            // Set payment to 0 and no tax
            $payment = 0;
            $totalTax = [];
            $data['payment_type'] = 'FREE';
        }

        // Store tax option in order for reference
        $data['tax_option'] = $taxOption;

        // calculate whole tax
        $taxDataJson = json_encode($totalTax);
        $data['tax'] = array_sum(array_column($totalTax, 'price'));

        // Calculate commission on base payment amount (before tax)
        if ($data['payment_type'] == "FREE" || $taxOption === 'complimentary') {
            $data['org_commission']  = 0;
        } else {
            if ($com->org_commission_type == "percentage") {
                $data['org_commission'] = $payment * $com->org_commission / 100;
            } else if ($com->org_commission_type == "amount") {
                $data['org_commission']  = $com->org_commission;
            }
        }

        // Set final payment amount
        if ($taxOption === 'complimentary') {
            $data['payment'] = 0;
        } else {
            $data['payment'] = $payment + $data['tax'];
        }
        $data['quantity'] = array_sum(array_map('intval', $this->normalizeList($data['quantity'])));

        // Remove non-DB keys before creating order
        $seatData = $data['seat_data'] ?? [];
        $ticketData = $data['ticketData'] ?? [];
        $venueSeatIds = $data['venue_seat_ids'] ?? [];
        unset($data['seat_data'], $data['ticketData'], $data['customData'], $data['custom_total'], $data['venue_seat_ids'], $data['customPriceData']);

        $order = Order::create($data);

        // Get seat data if provided
        $ticketIndex = 0;

        foreach($ticket as $tc){
            $selectedSeatId = isset($seatData[$ticketIndex]) && !empty($seatData[$ticketIndex]) ? $seatData[$ticketIndex] : null;
            $seatTable = null;

            if ($selectedSeatId) {
                $seatTable = \App\Models\SeatTable::find($selectedSeatId);
            }

            for ($i = 1; $i <= $ticketData[$tc->id]; $i++) {
                $child['ticket_number'] = uniqid();
                $child['ticket_id'] = $tc->id;
                $child['order_id'] = $order->id;
                $child['checkin'] = $tc->maximum_checkins ?? null;
                $child['paid'] =  1 ;

                // Add seat information if available
                if ($seatTable) {
                    $child['seat_id'] = $seatTable->id;
                    $child['SeatDetails_id'] = $seatTable->sponsership_id;

                    // Generate Book_Seat_Id with prefix and find next available seat number for this specific seat table
                    $prefix = $seatTable->prefixname ?? 'SEAT';

                    // Get all existing Book_Seat_Id for this specific seat table (seat_id)
                    // Yes, this groups by seat_id: it fetches all Book_Seat_Id values for the current seat table (seat_id)
                    $existingBookSeatIds = \App\Models\OrderChild::where('seat_id', $seatTable->id)
                        ->whereNotNull('Book_Seat_Id')
                        ->pluck('Book_Seat_Id')
                        ->toArray();

                    // Extract numbers from existing Book_Seat_Id (remove prefix and underscore)
                    $usedNumbers = [];
                    foreach ($existingBookSeatIds as $bookSeatId) {
                        $number = str_replace($prefix . '_', '', $bookSeatId);
                        if (is_numeric($number)) {
                            $usedNumbers[] = (int) $number;
                        }
                    }

                    // Find the next available number starting from 1
                    $nextNumber = 1;
                    while (in_array($nextNumber, $usedNumbers)) {
                        $nextNumber++;
                    }

                    $child['Book_Seat_Id'] = $prefix . '_' . $nextNumber;
                }

                OrderChild::create($child);
            }
            $ticketIndex++;
        }

        $this->attachAdminVenueSeatsToOrder($order, $event, $venueSeatIds);

        if (!empty($taxDataJson)) {
            foreach (json_decode($taxDataJson) as $value) {
                $tax['order_id'] = $order->id;
                $tax['tax_id'] = $value->id;
                $tax['price'] = $value->price;
                OrderTax::create($tax);
            }
        }
        $this->createOrderFees($order);

        $user = AppUser::find($order->customer_id);
        $setting = Setting::find(1);

        // for user notification
        $message = NotificationTemplate::where('title', 'Book Ticket')->first()->message_content;
        $detail['user_name'] = $user->name . ' ' . $user->last_name;
        $detail['quantity'] = (int) $data['quantity'];
        $detail['event_name'] = Event::find($order->event_id)->name;
        $detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $detail['app_name'] = $setting->app_name;
        $noti_data = ["{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
        $message1 = str_replace($noti_data, $detail, $message);
        $notification = array();
        $notification['organizer_id'] = null;
        $notification['user_id'] = $user->id;
        $notification['order_id'] = $order->id;
        $notification['title'] = 'Ticket Booked';
        $notification['message'] = $message1;
        Notification::create($notification);
        if ($setting->push_notification == 1) {
            if ($user->device_token != null) {
                (new AppHelper)->sendOneSignal('user', $user->device_token, $message1);
            }
        }
        // for user mail
        $ticket_book = NotificationTemplate::where('title', 'Book Ticket')->first();
        $details['user_name'] = $user->name . ' ' . $user->last_name;
        $details['quantity'] = (int) $data['quantity'];
        $details['event_name'] = Event::find($order->event_id)->name;
        $details['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $details['app_name'] = $setting->app_name;
        if ($setting->mail_notification == 1) {
            // Configure mail settings like FrontendController does
            (new AppHelper)->mailConfig();

            // Send ONE mail with invoice PDF and individual ticket PDFs
            try {
                $mailResult = $this->sendTicketQrMail($order->id);
                Log::info("Invoice and Ticket PDFs mail result: " . ($mailResult ? "success" : "failed"));
            } catch (\Throwable $th) {
                Log::error("Exception in sendTicketQrMail: " . $th->getMessage());
            }
        }

        // for Organizer notification
        $org =  User::find($order->organization_id);
        $or_message = NotificationTemplate::where('title', 'Organizer Book Ticket')->first()->message_content;
        $or_detail['organizer_name'] = $org->organization_name;
        $or_detail['user_name'] = $user->name . ' ' . $user->last_name;
        $or_detail['quantity'] = $data['quantity'];
        $or_detail['event_name'] = Event::find($order->event_id)->name;
        $or_detail['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $or_detail['app_name'] = $setting->app_name;
        $or_noti_data = ["{{organizer_name}}", "{{user_name}}", "{{quantity}}", "{{event_name}}", "{{date}}", "{{app_name}}"];
        $or_message1 = str_replace($or_noti_data, $or_detail, $or_message);
        $or_notification = array();
        $or_notification['organizer_id'] =  $org->id;
        $or_notification['user_id'] = null;
        $or_notification['order_id'] = $order->id;
        $or_notification['title'] = 'New Ticket Booked';
        $or_notification['message'] = $or_message1;
        Notification::create($or_notification);
        if ($setting->push_notification == 1) {
            if ($org->device_token != null) {
                (new AppHelper)->sendOneSignal('organizer', $org->device_token, $or_message1);
            }
        }
        // for Organizer mail
        $new_ticket = NotificationTemplate::where('title', 'Organizer Book Ticket')->first();
        $details1['organizer_name'] = $org->organization_name;
        $details1['user_name'] = $user->name . ' ' . $user->last_name;
        $details1['quantity'] = $data['quantity'];
        $details1['event_name'] = Event::find($order->event_id)->name;
        $details1['date'] = Event::find($order->event_id)->start_time->format('d F Y h:i a');
        $details1['app_name'] = $setting->app_name;
        if ($setting->mail_notification == 1) {
            try {
                Mail::to($org->email)->send(new TicketBookOrg($new_ticket->mail_content, $details1, $new_ticket->subject));
            } catch (\Throwable $th) {
                Log::info($th->getMessage());
            }
        }
        return $order->id;
    }

    private function attachAdminVenueSeatsToOrder($order, $event, array $venueSeatIds)
    {
        if (empty($venueSeatIds)) {
            return;
        }

        $this->releaseExpiredVenueSeatHoldsForAdmin($event->id);

        $orderChildren = OrderChild::where('order_id', $order->id)
            ->orderBy('id')
            ->get();
        $usedOrderChildIds = [];

        foreach ($venueSeatIds as $venueSeatId) {
            $seat = EventVenueSeat::where('event_id', $event->id)
                ->where('id', $venueSeatId)
                ->first();

            if (!$seat || $seat->status !== EventVenueSeat::STATUS_HELD) {
                continue;
            }

            if ($seat->held_by_session_id !== session()->getId() || $seat->isHoldExpired()) {
                continue;
            }

            $orderChild = null;

            if ($seat->ticket_id) {
                $orderChild = $orderChildren->first(function ($child) use ($seat, $usedOrderChildIds) {
                    return (int) $child->ticket_id === (int) $seat->ticket_id
                        && !in_array((int) $child->id, $usedOrderChildIds, true);
                });
            }

            if (!$orderChild) {
                $orderChild = $orderChildren->first(function ($child) use ($usedOrderChildIds) {
                    return !in_array((int) $child->id, $usedOrderChildIds, true);
                });
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
                $usedOrderChildIds[] = (int) $orderChild->id;

                $orderChildData = ['Book_Seat_Id' => $seatLabel];
                if (Schema::hasColumn('order_child', 'event_venue_seat_id')) {
                    $orderChildData['event_venue_seat_id'] = $seat->id;
                }

                $orderChild->update($orderChildData);
            }
        }
    }

    function getTicketsDetails(Request $request)
    {
        $ticket = Ticket::find($request->id, ['allday']);
        return response()->json(['allday' => $ticket->allday]);
    }
    public function sendMail($id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($id);
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
        $customPaper = array(0, 0, 720, 1440);
        $pdf = FacadePdf::loadView('ticketmail', compact('order'))->save(public_path("ticket.pdf"))->setPaper($customPaper, $orientation = 'portrait');

        $data["email"] = $order->customer->email;
        $data["title"] = "Invoice PDF";
        $data["body"] = "";
        $tempp = $pdf->output();
        $sender = Setting::select('sender_email', 'app_name')->first();
        try {
            Mail::send('mail', $data, function ($message) use ($data, $tempp, $sender) {
                $message->from($sender->sender_email, $sender->app_name)
                    ->to($data["email"])
                    ->subject($data["title"])
                    ->attachData($tempp, "invoice.pdf");
            });
        } catch (Throwable $th) {
            Log::info($th->getMessage());
        }
        return true;
    }

    public function sendTicketQrMail($id)
    {
        // Log the start of the function
        Log::info("Starting sendTicketQrMail for order ID: " . $id);

        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($id);

        if (!$order) {
            Log::error("Order not found for ID: " . $id);
            return false;
        }

        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::with('seatTable')->where('order_id', $order->id)->get();

        // Customer email (app_user or guest_user)
        $customerEmail = $order->appUser ? $order->appUser->email : ($order->guestUser ? $order->guestUser->email : null);

        Log::info("Customer email found: " . ($customerEmail ?? 'none'));

        if (!$customerEmail) {
            Log::error("No customer email found for order ID: " . $id);
            return false;
        }

        try {
            // Get full setting for PDF generation
            $setting = Setting::first();

            // Generate Invoice PDF using ticketmail layout
            $customPaper = array(0, 0, 720, 1440);
            $invoicePdf = FacadePdf::loadView('ticketmail', compact('order'))
                ->setPaper($customPaper, 'portrait');
            $invoicePdfOutput = $invoicePdf->output();

            Log::info("Invoice PDF generated for order: " . $id);

            // Generate individual Ticket PNGs for each ticket using GD
            $ticketPngOutputs = [];
            $ticketCount = 0;

            foreach ($order->ticket_data as $index => $ticketData) {
                $ticketCount++;
                $ticketInfo = Ticket::find($ticketData->ticket_id);

                // Generate QR code as PNG bytes
                $qrBytes = QrCode::format('png')->size(200)->generate($ticketData->ticket_number);

                // Build ticket PNG image using GD
                $imgWidth  = 400;
                $imgHeight = 620;
                $img = imagecreatetruecolor($imgWidth, $imgHeight);

                // Colors
                $red      = imagecolorallocate($img, 249, 11,  11);
                $white    = imagecolorallocate($img, 255, 255, 255);
                $darkGray = imagecolorallocate($img, 80,  80,  80);
                $gray     = imagecolorallocate($img, 130, 130, 130);
                $black    = imagecolorallocate($img, 33,  33,  33);
                $borderRed = imagecolorallocate($img, 211, 47, 47);

                // Fill white background
                imagefill($img, 0, 0, $white);

                // Red border
                imagerectangle($img, 0, 0, $imgWidth - 1, $imgHeight - 1, $borderRed);
                imagerectangle($img, 1, 1, $imgWidth - 2, $imgHeight - 2, $borderRed);
                imagerectangle($img, 2, 2, $imgWidth - 3, $imgHeight - 3, $borderRed);

                // Red header
                imagefilledrectangle($img, 3, 3, $imgWidth - 3, 55, $red);

                // Header text
                $font = 5; // built-in GD font
                $headerText = $setting->app_name ?? 'The Event Palette';
                $textWidth = strlen($headerText) * imagefontwidth($font);
                imagestring($img, $font, (int)(($imgWidth - $textWidth) / 2), 17, $headerText, $white);

                // Event name + optional event image (keep same design as PDF)
                $yPos = 70;
                $eventNameX = 20; // default x for text

                // Try to load event image from configured path; use it at left if available
                $eventImagePath = ($setting->imagePath ?? '') . ($order->event->image ?? '');
                $eventImageSize = 60; // square thumbnail
                $imageContents = @file_get_contents($eventImagePath);
                if ($imageContents) {
                    $evImg = @imagecreatefromstring($imageContents);
                    if ($evImg) {
                        $evResized = imagescale($evImg, $eventImageSize, $eventImageSize);
                        // place image at top-left area
                        $imgX = 20;
                        $imgY = $yPos - 6; // align with text line
                        imagecopy($img, $evResized, $imgX, $imgY, 0, 0, $eventImageSize, $eventImageSize);
                        imagedestroy($evImg);
                        imagedestroy($evResized);
                        // push text to the right of the image
                        $eventNameX = $imgX + $eventImageSize + 12;
                    }
                }

                $eventName = $order->event->name ?? '';
                imagestring($img, 4, $eventNameX, $yPos, substr($eventName, 0, 36), $black);

                // Organizer
                $yPos += 22;
                imagestring($img, 3, $eventNameX, $yPos, $order->organization->organization_name ?? '', $gray);

                // Date
                $yPos += 18;
                $dateStr = $order->event->start_time ? $order->event->start_time->format('D, d F Y | h:i a') : '';
                imagestring($img, 3, $eventNameX, $yPos, $dateStr, $gray);

                // Address / online
                $yPos += 18;
                $addr = ($order->event->type === 'online') ? 'Online Event' : ($order->event->address ?? '');
                imagestring($img, 3, $eventNameX, $yPos, substr($addr, 0, 40), $gray);

                // Dashed divider
                $yPos += 25;
                for ($x = 10; $x < $imgWidth - 10; $x += 10) {
                    imageline($img, $x, $yPos, $x + 6, $yPos, $borderRed);
                }

                // Ticket name
                $yPos += 12;
                $ticketName = $ticketInfo->name ?? 'Ticket';
                $tnWidth = strlen($ticketName) * imagefontwidth(5);
                imagestring($img, 5, (int)(($imgWidth - $tnWidth) / 2), $yPos, $ticketName, $red);

                // Ticket type
                $yPos += 26;
                $ticketType = ($ticketInfo && (strtolower($ticketInfo->type ?? '') === 'complementary' || strtolower($ticketInfo->type ?? '') === 'complementry')) ? 'Free' : ($ticketInfo->type ?? 'Paid');
                $ttWidth = strlen('Ticket: ' . $ticketType) * imagefontwidth(4);
                imagestring($img, 4, (int)(($imgWidth - $ttWidth) / 2), $yPos, 'Ticket: ' . $ticketType, $darkGray);

                // Seat info if available
                if ($ticketData->Book_Seat_Id) {
                    $yPos += 22;
                    $seatText = 'Seat: ' . $ticketData->Book_Seat_Id;
                    $stWidth = strlen($seatText) * imagefontwidth(3);
                    imagestring($img, 3, (int)(($imgWidth - $stWidth) / 2), $yPos, $seatText, $darkGray);
                }

                // QR code
                $yPos += 22;
                $qrImg = imagecreatefromstring($qrBytes);
                if ($qrImg) {
                    $qrSize = 200;
                    $qrResized = imagescale($qrImg, $qrSize, $qrSize);
                    $qrX = (int)(($imgWidth - $qrSize) / 2);
                    imagecopy($img, $qrResized, $qrX, $yPos, 0, 0, $qrSize, $qrSize);
                    imagedestroy($qrImg);
                    imagedestroy($qrResized);
                    $yPos += $qrSize + 8;
                }

                // Ticket number
                $tnumWidth = strlen('#' . $ticketData->ticket_number) * imagefontwidth(2);
                imagestring($img, 2, (int)(($imgWidth - $tnumWidth) / 2), $yPos, '#' . $ticketData->ticket_number, $gray);

                // Footer
                $footerText = 'All Sales Are Final! No Refunds!';
                $ftWidth = strlen($footerText) * imagefontwidth(2);
                imagestring($img, 2, (int)(($imgWidth - $ftWidth) / 2), $imgHeight - 20, $footerText, $gray);

                // Capture PNG bytes via temp file (avoids ob_start conflicts with Laravel)
                $tmpFile = tempnam(sys_get_temp_dir(), 'ticket_') . '.png';
                imagepng($img, $tmpFile);
                imagedestroy($img);
                $pngBytes = file_get_contents($tmpFile);
                @unlink($tmpFile);

                $ticketPngOutputs[] = [
                    'output'   => $pngBytes,
                    'filename' => 'ticket_' . $ticketCount . '.png',
                ];

                Log::info("Ticket PNG {$ticketCount} generated for order: " . $id);
            }

            // Send ONE email with invoice.pdf and all individual ticket PNGs attached
            $emailData = [
                'email' => $customerEmail,
                'title' => 'Your Tickets and Invoice - ' . $order->event->name,
                'body' => 'Please find your invoice and ticket images attached.',
                'order' => $order
            ];

            Mail::send('qrTicketmail', $emailData, function ($message) use ($emailData, $setting, $invoicePdfOutput, $ticketPngOutputs) {
                $message->from($setting->sender_email, $setting->app_name)
                    ->to($emailData['email'])
                    ->subject($emailData['title']);

                // Attach invoice PDF
                $message->attachData($invoicePdfOutput, "invoice.pdf", [
                    'mime' => 'application/pdf',
                ]);

                // Attach individual ticket PNGs
                foreach ($ticketPngOutputs as $ticketPng) {
                    $message->attachData($ticketPng['output'], $ticketPng['filename'], [
                        'mime' => 'image/png',
                    ]);
                }
            });

            Log::info("Invoice PDF and {$ticketCount} Ticket PNGs sent successfully to: " . $customerEmail);
            return true;

        } catch (\Throwable $th) {
            Log::error("Failed to send ticket QR mail: " . $th->getMessage());

            // Clean up any temporary files in case of error
            if (isset($attachments)) {
                foreach ($attachments as $attachment) {
                    if (File::exists($attachment)) {
                        File::delete($attachment);
                    }
                }
            }

            return false;
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
