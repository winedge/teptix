<?php

namespace App\Http\Controllers;

use App\Models\OrderChild;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\Order;
use App\Models\Event;
use App\Models\AppUser;
use App\Models\GuestUser;
use App\Models\Currency;
use App\Models\Ticket;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use App\Mail\ResetPassword;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class ScannerApiController extends Controller
{
    public function __construct()
    {
        (new AppHelper)->eventStatusChange();
    }
    public function scannerLogin(Request $request)
    {

        $request->validate([
            'email' => 'bail|required|email',
            'password' => 'bail|required',
        ]);
        $userdata = array('email' => $request->email, 'password' => $request->password);
        if (Auth::attempt($userdata)) {
            if (Auth::user()->hasRole('scanner')) {
                $user = Auth::user();
                $user['token'] = $user->createToken('eventRight')->accessToken;
                return response()->json(['msg' => 'Login successfully', 'data' => $user, 'success' => true], 200);
            } else {
                return response()->json(['msg' => 'Only scanner can login.', 'success' => false], 200);
            }
        } else {
            return response()->json(['msg' => 'Invalid Username or password', 'data' => null, 'success' => false], 400);
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email',
        ]);
        $user = User::where('email', $request->email)->first();

        $password = rand(100000, 999999);
        if ($user) {
            $content = NotificationTemplate::where('title', 'Reset Password')->first()->mail_content;
            $detail['user_name'] = $user->name;
            $detail['password'] = $password;
            $detail['app_name'] = Setting::find(1)->app_name;
            try {
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
                Mail::to($user)->send(new ResetPassword($content, $detail));
            } catch (\Throwable $th) {
                Log::info($th->getMessage());
            }
            return response()->json(['success' => true, 'msg' => 'Please check your email new password will send on it.', 'data' => null], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Invalid email ID', 'data' => null], 200);
        }
    }

    public function scannerSetting()
    {
        $data = Setting::find(1, ['currency', 'default_lat', 'default_long', 'privacy_policy_organizer', 'terms_use_organizer', 'app_version', 'or_onesignal_app_id', 'or_onesignal_project_number', 'footer_copyright']);
        $data->currency_symbol = Currency::where('code', $data->currency)->first()->symbol;
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function events()
    {
        $organizer = User::find(Auth::user()->org_id)->id;
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $data = Event::where([['status', 1], ['is_deleted', 0], ['user_id', $organizer], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
            ->orderBy('id', 'DESC')->get()->makeHidden(['created_at', 'updated_at', 'tags', 'security', 'lang', 'lat', 'people', 'gallery', 'description']);
        foreach ($data as $item) {
            if (!str_contains($item->scanner_id, Auth::user()->id)) {
                if (preg_match("/\bAuth::user()->id\b/", $item->scanner_id)) {
                    unset($item);
                }
            }
            $order = Order::where('event_id', $item->id)->get();
            if (count($order) == 0) {
                $item->scanTicket = 0;
            } else {
                $orderData = Order::where('event_id', $item->id)->pluck('id');
                $item->scanTicket  = OrderChild::whereIn('order_id', $orderData)->where('status', 1)->count();
            }
        }
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function eventDetail($id)
    {
        $img = array();
        $data = Event::find($id);
        if (!$data) {
            return response()->json([
                'data' => null,
                'msg' => 'Event not found',
                'success' => false
            ], 404);
        }

        $data->makeHidden(['created_at', 'updated_at']);

        if (!empty($data->gallery)) {
            foreach (array_filter(explode(',', $data->gallery)) as $value) {
                array_push($img, url('images/upload/') . '/' . $value);
            }
        }
        $data->gallery = $img;

        // Valid orders (Completed, excluding Refunded and Cancelled orders)
        $validOrdersQuery = Order::where('event_id', $data->id)
            ->where('order_status', 'Complete')
            ->where('order_status', '!=', 'Refunded')
            ->where('order_status', '!=', 'Cancel')
            ->where('payment_status', '!=', 2);

        $validOrderIds = $validOrdersQuery->pluck('id');

        if ($validOrderIds->isEmpty()) {
            $allOrderIds = Order::where('event_id', $data->id)->pluck('id');
            if ($allOrderIds->isEmpty()) {
                $ticketsSold = 0;
                $checkedIn = 0;
            } else {
                $ticketsSold = OrderChild::whereIn('order_id', $allOrderIds)->count();
                if ($ticketsSold === 0) {
                    $ticketsSold = max(0, intval(Order::whereIn('id', $allOrderIds)->sum('quantity')));
                }
                $checkedIn = OrderChild::whereIn('order_id', $allOrderIds)->where('status', 1)->count();
            }
        } else {
            $ticketsSold = OrderChild::whereIn('order_id', $validOrderIds)->count();
            if ($ticketsSold === 0) {
                $sumQty = $validOrdersQuery->get()->sum(function ($order) {
                    return intval(preg_replace('/\D/', '', (string) $order->quantity));
                });
                $ticketsSold = max(0, intval($sumQty));
            }
            $checkedIn = OrderChild::whereIn('order_id', $validOrderIds)->where('status', 1)->count();
        }

        $ticketsSold = max(0, intval($ticketsSold));
        $checkedIn = max(0, intval($checkedIn));
        $toCheckIn = max(0, $ticketsSold - $checkedIn);
        $attendancePercentage = $ticketsSold > 0 ? round(($checkedIn / $ticketsSold) * 100, 2) : 0.0;

        
        // Maintain existing scanTicket field with actual checked-in count
        $data->scanTicket = intval($checkedIn);
        $data->people = 0;

        // Scanner-specific ticket & attendance overview:
        // Capacity, tickets sold, and remaining stay 0.
        // checkedIn and toCheckIn show their actual response values.

        $data->ticketOverview = [
            'totalCapacity'         => 0,
            'ticketsSold'           => 0,
            'ticketsRemaining'      => 0,
            'checkedIn'             => (int) $checkedIn,
            'toCheckIn'             => (int) $toCheckIn,
            'ticketSalesPercentage' => 0.0,
            'attendancePercentage'  => (float) $attendancePercentage,
            'total_capacity'        => 0,
            'tickets_sold'          => 0,
            'tickets_remaining'     => 0,
            'checked_in'            => (int) $checkedIn,
            'to_check_in'           => (int) $toCheckIn,
        ];

        return response()->json(['data' => $data, 'success' => true], 200);
    }


    public function eventUsers($id)
    {
        $order = Order::where('event_id', $id)
            ->where(function ($query) {
                $query->whereNotNull('customer_id')
                    ->orWhereNotNull('guestuser_id');
            })
            ->pluck('id');

        $orderChild = OrderChild::whereIn('order_id', $order)->orderBy('id', 'DESC')->get();
        foreach ($orderChild as $value) {
            $o = Order::find($value->order_id);
            if (!$o) {
                $value->name = '';
                $value->address = '';
                $value->email = '';
                $value->phone = '';
                $value->user_type = '';
                $value->start_time = '';
                $value->end_time = '';
                $value->ticket_type = '';
                continue;
            }

            if ($o->customer_id) {
                $customer = AppUser::find($o->customer_id);
                $value->user_type = 'app_user';
            } else {
                $customer = GuestUser::find($o->guestuser_id);
                $value->user_type = 'guest_user';
            }

            $value->name = trim(($customer->name ?? '') . ' ' . ($customer->last_name ?? ''));
            $value->address = $customer->address ?? '';
            $value->email = $customer->email ?? '';
            $value->phone = $customer->phone ?? '';

            $ev = Event::find($o->event_id);
            if ($ev && $ev->start_time && $ev->end_time) {
                $value->start_time = $ev->start_time->format('d M Y') . ', ' . $ev->start_time->format('h:i a');
                $value->end_time = $ev->end_time->format('d M Y') . ', ' . $ev->end_time->format('h:i a');
            } else {
                $value->start_time = '';
                $value->end_time = '';
            }

            $ticket = Ticket::find($value->ticket_id);
            $value->ticket_type = $ticket->name ?? '';
        }
        return response()->json(['data' => $orderChild, 'success' => true], 200);
    }

    public function profile()
    {
        $data = User::find(Auth::user()->id);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function editProfile(Request $request)
    {
        User::find(Auth::user()->id)->update($request->all());
        $data = User::find(Auth::user()->id);
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function scanTicket($code, $event_id)
    {
        $stepLog = [];
        $stepLog[] = "Step 1: Received scan request for code/seat: '{$code}', event_id: '{$event_id}'";
        Log::info("[ScannerApi] Step 1: Received scan request", ['code' => $code, 'event_id' => $event_id]);

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
            $stepLog[] = "Step 2: FAILED - Ticket/Seat with code/ID '{$code}' not found in order_child table.";
            Log::warning("[ScannerApi] Step 2: Ticket not found", ['code' => $code]);
            $this->writeScanningLog($event_id, $code, false, 'Ticket or seat not found.');
            return response()->json([
                'msg' => 'Ticket or seat not found.',
                'success' => false,
                'step_by_step_log' => $stepLog
            ], 200);
        }

        $stepLog[] = "Step 2: SUCCESS - Found ticket/seat record (ID: {$child->id}, Ticket Number: {$child->ticket_number}, Book_Seat_Id: {$child->Book_Seat_Id})";
        Log::info("[ScannerApi] Step 2: Found order_child", ['id' => $child->id, 'Book_Seat_Id' => $child->Book_Seat_Id]);

        $order = Order::find($child->order_id);
        $ticket = Ticket::find($child->ticket_id);
        $event = Event::find($event_id);

        if (!$order) {
            $stepLog[] = "Step 3: FAILED - Order record (ID: {$child->order_id}) not found.";
            Log::error("[ScannerApi] Step 3: Order not found", ['order_id' => $child->order_id]);
            $this->writeScanningLog($event_id, $code, false, 'Order not found.', ['child_id' => $child->id]);
            return response()->json(['msg' => 'Order not found.', 'success' => false, 'step_by_step_log' => $stepLog], 200);
        }

        $stepLog[] = "Step 3: Found order ID: {$order->id}, order_event_id: {$order->event_id}";

        if ($order->event_id != $event_id) {
            $stepLog[] = "Step 4: FAILED - Event ID mismatch. Order event ID ({$order->event_id}) does not match scanned event ID ({$event_id}).";
            Log::warning("[ScannerApi] Step 4: Event mismatch", ['order_event_id' => $order->event_id, 'scanned_event_id' => $event_id]);
            $this->writeScanningLog($event_id, $code, false, 'Event ID mismatch.', ['child_id' => $child->id, 'order_event_id' => $order->event_id]);
            return response()->json(['msg' => 'Ticket can not be found for this event.', 'success' => false, 'step_by_step_log' => $stepLog], 200);
        }

        $stepLog[] = "Step 4: Event ID verified successfully.";

        $timezone = Setting::find(1)->timezone ?? null;
        $now = $timezone ? Carbon::now($timezone) : Carbon::now();
        if ($event && $event->start_time) {
            $startTime = $event->start_time instanceof Carbon ? $event->start_time : Carbon::parse($event->start_time);
            $allowScanTime = $startTime->copy()->subHours(3);
            if ($now->lt($allowScanTime)) {
                $stepLog[] = "Step 4.1: FAILED - Event scanning opens 3 hours before start time. Start time: {$startTime->toDateTimeString()}, Allowed scanning from: {$allowScanTime->toDateTimeString()}, Current time: {$now->toDateTimeString()}.";
                Log::warning("[ScannerApi] Event not started", ['event_id' => $event_id, 'start_time' => $startTime, 'allow_scan_time' => $allowScanTime, 'now' => $now]);
                $this->writeScanningLog($event_id, $code, false, 'Event is not started', ['child_id' => $child->id]);
                return response()->json([
                    'msg' => 'Event is not started',
                    'success' => false,
                    'step_by_step_log' => $stepLog
                ], 200);
            }
        }

        $ticketName = $ticket ? ($ticket->name ?? ($ticket->ticket_title ?? '')) : '';

        $data = [
            'ticket_number' => $child->ticket_number,
            'ticket_name'   => $ticketName,
            'ticket_title'  => $ticketName,
            'Book_Seat_Id'  => $child->Book_Seat_Id,
            'order_id'      => $child->order_id,
            'event_id'      => $order->event_id,
            'checkin'       => $child->checkin,
        ];

        if ($child->checkin === 0) {
            $stepLog[] = "Step 5: FAILED - Check-in limit exceeded for this ticket.";
            Log::warning("[ScannerApi] Step 5: Check-in limit exceeded", ['child_id' => $child->id]);
            $this->writeScanningLog($event_id, $code, false, 'Check-in limit exceeded!', ['child_id' => $child->id]);
            return response()->json(['msg' => 'Check-in limit exceeded!', 'data' => $data, 'success' => false, 'step_by_step_log' => $stepLog], 200);
        }

        if ($child->checkin !== null) {
            $stepLog[] = "Step 5: Check-in limit valid (Remaining: {$child->checkin}).";
        } else {
            $stepLog[] = "Step 5: Check-in limit valid (No check-in limit).";
        }

        if ($ticket && $ticket->allday == 0) {
            $today = Carbon::now()->format("Y-m-d");
            if ($order->ticket_date != $today) {
                $stepLog[] = "Step 6: FAILED - Ticket date ({$order->ticket_date}) does not match today ({$today}).";
                Log::warning("[ScannerApi] Step 6: Ticket date mismatch", ['ticket_date' => $order->ticket_date, 'today' => $today]);
                $this->writeScanningLog($event_id, $code, false, 'Ticket date is not match.', ['child_id' => $child->id, 'ticket_date' => $order->ticket_date, 'today' => $today]);
                return response()->json(['msg' => 'Ticket date is not match.', 'data' => $data, 'success' => false, 'step_by_step_log' => $stepLog], 200);
            }
            $stepLog[] = "Step 6: Ticket date verified for today ({$today}).";
        } else {
            $stepLog[] = "Step 6: All-day ticket or no date restriction.";
        }

        $newCheckin = $child->checkin !== null ? max(0, $child->checkin - 1) : null;

        $updateData = ['status' => 1];
        if ($child->checkin !== null) {
            $updateData['checkin'] = $newCheckin;
        }

        if ($child->paid == 0) {
            $updateData['paid'] = 1;
            $child->update($updateData);
            $data['remaining_check_ins'] = $newCheckin;
            $checkinText = $newCheckin !== null ? "Remaining check-ins: {$newCheckin}" : "Unlimited check-ins";
            $stepLog[] = "Step 7: SUCCESS - Updated ticket status to 1 (Checked in). Payment collection required from guest. {$checkinText}.";
            Log::info("[ScannerApi] Step 7: Ticket scanned (Unpaid -> Paid)", ['child_id' => $child->id, 'remaining' => $newCheckin]);
            $this->writeScanningLog($event_id, $code, true, 'Please collect from the guest', ['child_id' => $child->id, 'remaining' => $newCheckin]);
            return response()->json([
                'msg' => 'Please collect from the guest',
                'success' => true,
                'data' => $data,
                'step_by_step_log' => $stepLog
            ], 200);
        }

        $child->update($updateData);
        $data['remaining_check_ins'] = $newCheckin;
        $checkinText = $newCheckin !== null ? "Remaining check-ins: {$newCheckin}" : "Unlimited check-ins";
        $stepLog[] = "Step 7: SUCCESS - Updated ticket status to 1 (Checked in). {$checkinText}.";

        $this->writeScanningLog($event_id, $code, true, 'Ticket scanned successfully.', ['child_id' => $child->id, 'remaining' => $newCheckin]);

        return response()->json([
            'msg' => 'Ticket scanned successfully.',
            'data' => $data,
            'success' => true,
            'step_by_step_log' => $stepLog
        ], 200);
    }

    private function writeScanningLog($eventId, $ticketNumber, $success, $msg, $extra = [])
    {
        try {
            $dir = storage_path('scanning');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $timestamp = Carbon::now()->toDateTimeString();
            $statusStr = $success ? 'SUCCESS' : 'FAILED';
            $logLine = sprintf(
                "[%s] Event: %s | Ticket: %s | Status: %s | Message: %s | Extra: %s\n",
                $timestamp,
                $eventId ?? 'N/A',
                $ticketNumber ?? 'N/A',
                $statusStr,
                $msg,
                json_encode($extra)
            );

            file_put_contents($dir . '/scanning.log', $logLine, FILE_APPEND);
            if ($eventId) {
                file_put_contents($dir . '/event_' . $eventId . '.log', $logLine, FILE_APPEND);
            }
        } catch (\Exception $e) {
            // Silence any file log exceptions
        }
    }
    public function changePassword(Request $request)
    {
        $request->validate([

            'old_password' => 'bail|required',
            'password' => 'bail|required|min:6',
            'password_confirmation' => 'bail|required|same:password|min:6',
        ]);
        if (Hash::check($request->old_password, Auth::user()->password)) {
            User::find(Auth::user()->id)->update(['password' => Hash::make($request->password)]);
            return response()->json(['success' => true, 'msg' => 'Your password is change successfully', 'data' => null], 200);
        } else {
            return response()->json(['success' => false, 'msg' => 'Current Password is wrong!', 'data' => null], 200);
        }
    }

    public function singleOrder($orderChildId)
    {
        $orderChild = OrderChild::find($orderChildId);
        if (!$orderChild) {
            return response()->json(['success' => false, 'msg' => 'Ticket not found', 'data' => null], 404);
        }

        $orderChild->makeHidden(['created_at', 'updated_at']);
        $o = Order::find($orderChild->order_id);

        $customerId = $orderChild->customer_id ?: ($o->customer_id ?? null);
        $guestUserId = $orderChild->guestuser_id ?: ($o->guestuser_id ?? null);

        $customer = null;
        if ($customerId) {
            $customer = AppUser::find($customerId);
            $orderChild->user_type = 'app_user';
        } elseif ($guestUserId) {
            $customer = GuestUser::find($guestUserId);
            $orderChild->user_type = 'guest_user';
        } else {
            $orderChild->user_type = 'guest_user';
        }

        $name = '';
        if ($customer) {
            $firstName = $customer->name ?? ($customer->first_name ?? '');
            $lastName = $customer->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            if (empty($name)) {
                $name = $customer->email ?? ($customer->phone ?? 'Guest User');
            }
            $orderChild->address = $customer->address ?? ($o->address ?? '');
            $orderChild->email = $customer->email ?? ($o->email ?? '');
            $orderChild->phone = $customer->phone ?? ($o->phone ?? '');
        } else {
            $name = $o->name ?? 'Guest User';
            $orderChild->address = $o->address ?? '';
            $orderChild->email = $o->email ?? '';
            $orderChild->phone = $o->phone ?? '';
        }

        $orderChild->name = $name;
        $orderChild->owner_name = $name;
        $orderChild->user_name = $name;

        if ($o) {
            $ev = Event::find($o->event_id);
            if ($ev && $ev->start_time && $ev->end_time) {
                $orderChild->start_time = $ev->start_time->format('d M Y') . ', ' . $ev->start_time->format('h:i a');
                $orderChild->end_time = $ev->end_time->format('d M Y') . ', ' . $ev->end_time->format('h:i a');
            } else {
                $orderChild->start_time = '';
                $orderChild->end_time = '';
            }

            $ticketId = $orderChild->ticket_id ?: $o->ticket_id;
            $ticket = Ticket::find($ticketId);
            $orderChild->ticket_type = $ticket->name ?? ($ticket->ticket_title ?? '');
        } else {
            $orderChild->start_time = '';
            $orderChild->end_time = '';
            $orderChild->ticket_type = '';
        }

        return response()->json(['success' => true, 'data' => $orderChild], 200);
    }
}
