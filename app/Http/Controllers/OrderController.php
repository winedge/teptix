<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Models\AppUser;
use App\Models\Review;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Setting;
use App\Models\OrderTax;
use App\Models\OrderChild;
use App\Models\EventVenueSeat;
use App\Models\User;
use App\Models\Settlement;
use App\Models\EventReport;
use App\Models\Module;
use App\Models\PaymentSetting;
use App\Models\OrganizerPaymentKeys;
use App\Models\Tax;
use App\Models\StripeTransaction;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Support\Facades\Storage;
use Stripe;
use Carbon\Carbon;
use Exception;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Modules\BankPayout\Entities\BankDetails;
use Throwable;
use Illuminate\Support\Facades\File;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['event','appUser', 'guestUser', 'orderChild'])->orderBy('id', 'DESC');

        if (Auth::user()->hasRole('admin')) {
            $events = Event::orderBy('start_time', 'DESC')->get(['id', 'name']);
        } elseif (Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager')) {
            $org_id = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
            $query->where('organization_id', $org_id);
            $events = Event::whereRaw('FIND_IN_SET(?, user_id)', [$org_id])->orderBy('start_time', 'DESC')->get(['id', 'name']);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->promotion_status === 'applied') {
            $query->where(function ($promotionQuery) {
                $promotionQuery->whereNotNull('coupon_id')
                    ->orWhere('coupon_discount', '>', 0);
            });
        } elseif ($request->promotion_status === 'not_applied') {
            $query->where(function ($promotionQuery) {
                $promotionQuery->whereNull('coupon_id')
                    ->where(function ($discountQuery) {
                        $discountQuery->whereNull('coupon_discount')
                            ->orWhere('coupon_discount', '<=', 0);
                    });
            });
        }

        $orders = $query->get();

        return view('admin.order.index', compact('orders', 'events', 'request'));
    }

    public function show($order_id, $id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser', 'orderChild'])->find($order_id);
        $noti = Notification::find($id);
        if (isset($noti) && $noti->status == 1) {
            DB::table('notification')->where('id', $id)->update(['status' => 0]);
        }
        return view('admin.order.view', compact('order'));
    }

    public function orderInvoice($id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($id);
        $order->tax_data = OrderTax::where('order_id', $id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
        return view('admin.order.invoice', compact('order'));
    }

    public function userReview()
    {
        $data = Review::orderBy('id', 'DESC')->get();
        return view('admin.review', compact('data'));
    }
    public function eventReports()
    {
        $data = EventReport::orderBy('id', 'DESC')->get();
        return view('admin.report', compact('data'));
    }
    public function changeReviewStatus($id)
    {
        Review::find($id)->update(['status' => 1]);
        return redirect()->back()->withStatus(__('Review is published successfully.'));
    }

    public function deleteReview($id)
    {
        $data = Review::find($id);
        $data->delete();
        return redirect()->back()->withStatus(__('Review is deleted successfully.'));
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasRole('Manager')) {
            return response()->json(['message' => __('Unauthorized action.')], 403);
        }

        try {
            DB::beginTransaction();

            $ids = $request->input('ids'); // Get order_id values from request payload

            if (!$ids) {
                return response()->json(['message' => 'Invalid request'], 400);
            }

            $ids = is_array($ids) ? $ids : [$ids];
            $orders = Order::whereIn('order_id', $ids)->get();

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Release seats and delete related records
            foreach ($orders as $order) {
                $childSeatIds = OrderChild::where('order_id', $order->id)->pluck('event_venue_seat_id')->filter()->toArray();
                $seatQuery = EventVenueSeat::where('booked_order_id', $order->id);
                if (!empty($childSeatIds)) {
                    $seatQuery->orWhereIn('id', $childSeatIds);
                }
                $seatQuery->update([
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                    'booked_order_id' => null,
                    'booked_order_child_id' => null,
                    'booked_at' => null,
                    'hold_token' => null,
                    'held_by_session_id' => null,
                    'held_by_app_user_id' => null,
                    'held_by_guest_user_id' => null,
                    'held_at' => null,
                    'hold_expires_at' => null,
                ]);

                OrderChild::where('order_id', $order->id)->delete();
                OrderTax::where('order_id', $order->id)->delete();
                $order->delete();
            }

            DB::commit();
            return response()->json(['message' => 'Orders deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting order', 'error' => $e->getMessage()], 500);
        }
    }


    public function customerReport(Request $request)
    {
        $data = AppUser::orderBy('id', 'DESC');
        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }
        $data = $data->get();

        foreach ($data as $value) {
            $value->buy_tickets = Order::where('customer_id', $value->id)->sum('quantity');
        }
        return view('admin.report.org_customer_report', compact('data', 'request'));
    }

    public function ordersReport(Request $request)
    {
        $data = Order::where([['organization_id', Auth::user()->id], ['payment_status', 1]]);
        if (isset($request->customer) && $request->customer >= 1) {
            $data->where('customer_id', $request->customer);
        }
        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }

        $data = $data->orderBy('id', 'DESC')->get();

        return view('admin.report.org_orders_report', compact('data', 'request'));
    }

    public function orgRevenueReport(Request $request)
    {
        $data = Settlement::where('user_id', Auth::user()->id)->orderBy('id', 'DESC');
        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }
        $data = $data->get();

        foreach ($data as $value) {
            $value->user = User::find($value->user_id);
        }
        return view('admin.report.org_revenue', compact('data', 'request'));
    }

    public function adminCustomerReport(Request $request)
    {
        $data = AppUser::orderBy('id', 'DESC');

        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }
        $data = $data->get();
        foreach ($data as $value) {
            $value->buy_tickets = Order::where('customer_id', $value->id)->sum('quantity');
        }
        return view('admin.report.admin_customer_report', compact('data', 'request'));
    }

    public function adminOrgReport(Request $request)
    {
        $data = User::role('Organizer')->orderBy('id', 'DESC');
        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }
        $data = $data->get();
        foreach ($data as $value) {
            $value->total_events = Event::where('user_id', $value->id)->count();
            $value->total_tickets = Ticket::where([['user_id', $value->id], ['is_deleted', 0]])->sum('quantity');
            $value->sold_tickets = Order::where('organization_id', $value->id)->sum('quantity');
        }
        return view('admin.report.admin_org_report', compact('data', 'request'));
    }

    public function adminRevenueReport(Request $request)
    {
        $data = Order::with(['event:id,name','appUser:id,name,last_name,email', 'guestUser:id,name,last_name,email', 'stripeTransaction:id,order_id,tax_amount']);
        
        if (isset($request->payment_status_filter) && $request->payment_status_filter !== '') {
            $data->where('payment_status', $request->payment_status_filter);
        } else {
            $data->whereIn('payment_status', [1, 2]);
        }

        if (isset($request->organizer) && $request->organizer >= 1) {
            $data->where('organization_id', $request->organizer);
        }
        if (isset($request->customer) && $request->customer >= 1) {
            $data->where('customer_id', $request->customer);
        }
        if (isset($request->payment_type) && !empty($request->payment_type)) {
            $data->where('payment_type', $request->payment_type);
        }
        if (isset($request->event_id) && $request->event_id >= 1) {
            $data->where('event_id', $request->event_id);
        }
        if (isset($request->duration) && $request->duration != null) {
            $start_date = explode(' to ', $request->duration)[0];
            $end_date = count(explode(' to ', $request->duration)) == 1 ? explode(' to ', $request->duration)[0] : explode(' to ', $request->duration)[1];
            $data->whereBetween('created_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        }

        $data = $data->orderBy('id', 'DESC')->get();
        $events = \App\Models\Event::orderBy('id', 'DESC')->get(['id', 'name']);

        // Revenue Card Breakdowns (respect organizer, customer, event_id and duration filters)
        $revenueOrganizerId = (isset($request->organizer) && $request->organizer >= 1) ? $request->organizer : null;
        $revenueCustomerId = (isset($request->customer) && $request->customer >= 1) ? $request->customer : null;
        $revenueEventId = (isset($request->event_id) && $request->event_id >= 1) ? $request->event_id : null;
        $revenueDateRange = (isset($request->duration) && $request->duration != null) ? $request->duration : null;

        $buildRevenueQuery = function (string $paymentType, int $paymentStatus) use ($revenueOrganizerId, $revenueCustomerId, $revenueEventId, $revenueDateRange) {
            $q = Order::where('payment_status', $paymentStatus);

            if ($paymentType === 'STRIPE') {
                $q->where('payment_type', 'STRIPE');
            } else {
                $q->where('payment_type', '!=', 'STRIPE');
            }

            if ($revenueOrganizerId) {
                $q->where('organization_id', $revenueOrganizerId);
            }

            if ($revenueCustomerId) {
                $q->where('customer_id', $revenueCustomerId);
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
        $onlineTicketPrice   = $onlineOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
        $onlineProcessingFee = $onlineOrders->sum(fn($o) => (float)$o->tax);
        $onlinePlatformFee   = \App\Models\OrderFee::whereIn('order_id', $onlineOrders->pluck('id'))->sum('amount');
        $onlineRevenue       = $onlineOrders->sum(fn($o) => (float)$o->org_revenue
            ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
            : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

        // Local totals
        $localTicketPrice    = $localOrders->sum(fn($o) => (float)$o->payment + (float)($o->coupon_discount ?? 0));
        $localProcessingFee  = $localOrders->sum(fn($o) => (float)$o->tax);
        $localPlatformFee    = \App\Models\OrderFee::whereIn('order_id', $localOrders->pluck('id'))->sum('amount');
        $localRevenue        = $localOrders->sum(fn($o) => (float)$o->org_revenue
            ? ((float)$o->org_revenue + (float)($o->coupon_discount ?? 0))
            : ((float)$o->payment + (float)($o->coupon_discount ?? 0) - (float)$o->tax));

        $grossRevenue = $onlineRevenue + $localRevenue;

        $grossTicket = $onlineTicketPrice + $localTicketPrice;
        $netPayout   = $grossTicket
            - $localTicketPrice
            - $onlineProcessingFee
            - $localProcessingFee
            - $onlinePlatformFee
            - ($onlineRefunded + $localRefunded)
            - $localPlatformFee;

        $currency = \App\Models\Setting::first()->currency_sybmol ?? '$';

        return view('admin.report.admin_revenue_report', compact(
            'data', 'request', 'events', 'currency',
            'onlineRevenue', 'localRevenue', 'grossRevenue', 'netPayout',
            'onlineTicketPrice', 'onlineProcessingFee', 'onlinePlatformFee', 'onlineRefunded',
            'localTicketPrice', 'localProcessingFee', 'localPlatformFee', 'localRefunded'
        ));
    }

    public function getStatistics($month)
    {
        $day = Carbon::parse(Carbon::now()->year . '-' . Carbon::now()->month . '-01')->daysInMonth;

        if (Auth::user()->hasRole('admin')) {
            $master['total_order'] = Order::whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['pending_order'] = Order::where('order_status', 'Pending')->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['complete_order'] = Order::where('order_status', 'Complete')->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['cancel_order'] = Order::where('order_status', 'Cancel')->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
        } elseif (Auth::user()->hasRole('Organizer')) {
            $master['total_order'] = Order::where('organization_id', Auth::user()->id)->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['pending_order'] = Order::where([['order_status', 'Pending'], ['organization_id', Auth::user()->id]])->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['complete_order'] = Order::where([['order_status', 'Complete'], ['organization_id', Auth::user()->id]])->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
            $master['cancel_order'] = Order::where([['order_status', 'Cancel'], ['organization_id', Auth::user()->id]])->whereBetween('created_at', [Carbon::now()->year . "-" . $month . "-01 00:00:00",  Carbon::now()->year . "-" . $month . "-" . $day . " 23:59:59"])->count();
        }

        return response()->json(['success' => true, 'data' => $master], 200);
    }

    public function settlementReport()
    {
        // $data = User::role('Organizer')->orderBy('id', 'DESC')->get();
        // foreach ($data as $value) {
            // $value->total_orders = Order::where('organization_id', $value->id)->count();
            // $value->total_commission = Order::where([['organization_id', $value->id], ['payment_status', 1]])
            //     ->sum(DB::raw('org_commission + tax'));
            // $sumOrgCommissionAndTax = Order::where([['organization_id', $value->id]])->sum('org_commission') + Order::where([['organization_id', $value->id]])->sum('tax');
            // $sumPayment = Order::where([['organization_id', $value->id]])->sum('payment');
            // $value->total_commission = $sumPayment - $sumOrgCommissionAndTax;
            // $value->pay_commission = Order::where([['organization_id', $value->id], ['payment_status', 1], ['org_pay_status', 1]])
                // ->sum(DB::raw('org_commission + tax'));
            // $value->organization_commission = Order::where([['organization_id', $value->id], ['payment_status', 1], ['org_pay_status', 0]])
            //     ->sum(DB::raw('org_commission + tax'));
        //     $value->organization_commission  = $sumPayment - $sumOrgCommissionAndTax;
        // }


        // return view('admin.report.admin_settlement_report', compact('data'));


        $data = User::role('Organizer')->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->total_orders = Order::where('organization_id', $value->id)->count();
            $value->total_commission = Order::where([['organization_id', $value->id], ['payment_status', 1]])
                ->sum(DB::raw('org_commission + tax'));
            $value->pay_commission = Order::where([['organization_id', $value->id], ['payment_status', 1], ['org_pay_status', 1]])
                ->sum(DB::raw('org_commission + tax'));
            $value->organization_commission = Order::where([['organization_id', $value->id], ['payment_status', 1], ['org_pay_status', 0]])
                ->sum(DB::raw('org_commission + tax'));
        }
        $bankModule = Module::where('module', 'BankPayout')->first();
        return view('admin.report.admin_settlement_report', compact('data', 'bankModule'));
    }

    public function viewSettlement($id)
    {
        $data = Settlement::where('user_id', $id)->orderBy('id', 'DESC')->get();
        foreach ($data as $value) {
            $value->user = User::find($value->user_id);
        }
        return view('admin.report.view_settlement', compact('data'));
    }

    public function payToUser(Request $request)
    {
        $data = $request->all();
        if ($request->payment_type == "STRIPE") {
            $currency = Setting::find(1)->currency;
            $stripe_secret = OrganizerPaymentKeys::find(1)->stripeSecretKey;
            Stripe\Stripe::setApiKey($stripe_secret);
            $stripeDetail =  Stripe\Charge::create([
                "amount" => intval($request->payment) * 100,
                "currency" => $currency,
                "source" => $request->stripeToken,
            ]);
            $data['payment_token'] = $stripeDetail->id;
            $data['payment_status'] = 1;
        }
        Settlement::create($data);
        Order::where([['organization_id', $request->user_id], ['payment_status', 1], ['org_pay_status', 0]])->update(['org_pay_status' => 1]);
        return redirect()->back()->withStatus(__('Your Payment done successfully.'));
    }

    public function payToOrganization(Request $request)
    {
        if ($request->payment_type == 'BANK') {
            $bankDetails = BankDetails::where('organizer_id',$request->user_id)->first();
            if (!$bankDetails) {
                return response()->json(['msg' => 'Please add bank details', 'success' => false], 200);
            }
        }
        Settlement::create($request->all());
        Order::where([['organization_id', $request->user_id], ['payment_status', 1], ['org_pay_status', 0]])->update(['org_pay_status' => 1]);
        return response()->json(['msg' => null, 'success' => true], 200);
    }

    public function getQrCode($id)
    {
        $ticket = DB::table('order_child')
        ->select([
            'events.image',
            'events.name',
            'users.first_name',
            'users.last_name',
            'users.organization_name',
            'events.type',
            'events.address',
            'events.start_time',
            'tickets.name as ticket_name',
            'tickets.type as ticket_type',
            'order_child.ticket_number',
            'order_child.Book_Seat_Id',
            'order_child.seat_id',
            'seat_table.name_of_table as seat_table_name',
            DB::raw('COALESCE(app_user.email, guest_user.email) as email')
            ])
            ->join('orders', 'order_child.order_id', '=', 'orders.id')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->join('users', 'orders.organization_id', '=', 'users.id')
            ->leftJoin('app_user', function ($join) {
                $join->on('orders.customer_id', '=', 'app_user.id')
                     ->whereNotNull('orders.customer_id');
            })
            ->leftJoin('guest_user', function ($join) {
                $join->on('orders.guestuser_id', '=', 'guest_user.id')
                     ->whereNotNull('orders.guestuser_id');
            })
        ->join('tickets', 'order_child.ticket_id', '=', 'tickets.id')
        ->leftJoin('seat_table', 'order_child.seat_id', '=', 'seat_table.id')
        ->where('order_child.id', $id)
        ->first();
        $ticket->qrCode = QrCode::format('png')->size(150)->generate($ticket->ticket_number);
        $setting=Setting::find(1);
        return view('admin.order.printTicket', compact('ticket','setting'));
    }

    public function changeStatus(Request $request)
    {
        Order::find($request->id)->update(['order_status' => $request->order_status]);
        return response()->json(['success' => true, 'msg' => 'Status Changed'], 200);
    }

    public function changePaymentStatus(Request $request)
    {
        Order::find($request->id)->update(['payment_status' => 1]);
        return response()->json(['success' => true, 'msg' => 'Status Changed'], 200);
    }

    public function editPayment($id)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $order = Order::with(['event', 'customer'])->findOrFail($id);
        return view('admin.order.edit_payment', compact('order'));
    }

    public function updatePayment($id, Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $order = Order::findOrFail($id);
            $order->payment = $request->payment;
            $order->save();

            return redirect()->back()->with('status', __('Payment amount updated successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update payment amount'));
        }
    }


public function orderInvoicePrint($order_id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($order_id);
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
        $maintax = array();
        foreach ($order->tax_data as $item) {
            $tax = Tax::find($item->tax_id);
            if ($tax) {
                $maintax[] = $tax;
            }
        }
        $order->maintax = $maintax;
        return view('admin.order.invoicePrint', compact('order'));
    }

public function sendMail($id)
{
    $order = Order::with(['event', 'organization', 'ticket', 'appUser', 'guestUser'])->find($id);

    if (!$order) {
        return redirect()->back()->with('error_msg', __('Order not found.'));
    }

    $customerEmail = optional($order->customer)->email;
    if (!$customerEmail) {
        return redirect()->back()->with('error_msg', __('Customer email address not found.'));
    }

    $order->tax_data = OrderTax::where('order_id', $order->id)->get();
    $order->ticket_data = OrderChild::where('order_id', $order->id)->get();

    $customPaper = array(0, 0, 720, 1440);
    $pdf = FacadePdf::loadView('ticketmail', compact('order'))->save(public_path("ticket.pdf"))->setPaper($customPaper, $orientation = 'portrait');

    // Create a temporary directory to store QR code images
    $tempDirectory = storage_path('temp_qrcodes');
    if (!file_exists($tempDirectory)) {
        mkdir($tempDirectory, 0777, true);
    }

    $qrCodeFiles = [];
    foreach ($order->ticket_data as $item) {
        $qrId = $item->id;
        $qrCodeFileName = "qr_code_{$item->ticket_number}.pdf";
        $qrCodeFilePath = "{$tempDirectory}/{$qrCodeFileName}";

        $qrpdf = FacadePdf::loadView('emails.ticket', compact('qrId'))
            ->setPaper('a5', 'portrait');

        file_put_contents($qrCodeFilePath, $qrpdf->output());
        $qrCodeFiles[] = $qrCodeFilePath;
    }

    $data["email"] = $customerEmail;
    $data["title"] = "Event Tickets & Invoice | " . (Setting::first()->app_name ?? 'Teptix');
    $data["body"] = "";
    $tempp = $pdf->output();
    $sender = Setting::select('sender_email', 'app_name')->first();

    try {
        (new AppHelper)->mailConfig();
        Mail::send('mail', $data, function ($message) use ($data, $tempp, $sender, $qrCodeFiles) {
            $fromAddress = $sender->sender_email ?? config('mail.from.address');
            $fromName = $sender->app_name ?? config('mail.from.name');

            $message->from($fromAddress, $fromName)
                ->to($data["email"])
                ->subject($data["title"])
                ->attachData($tempp, "invoice_ticket.pdf");

            foreach ($qrCodeFiles as $qrCodeFile) {
                if (file_exists($qrCodeFile)) {
                    $message->attach($qrCodeFile);
                }
            }
        });
    } catch (\Exception $e) {
        Log::error('Order invoice sendMail error: ' . $e->getMessage());
        return redirect()->back()->with('error_msg', __('Failed to send email: ') . $e->getMessage());
    }

    if (file_exists($tempDirectory)) {
        $files = File::files($tempDirectory);
        foreach ($files as $file) {
            File::delete($file);
        }
        @rmdir($tempDirectory);
    }

    return redirect()->back()->withStatus(__('Invoice & Ticket QR PDF sent successfully to ') . $customerEmail);
}




    public function showTicket($id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($id);
        $orderchild = OrderChild::where('order_id', $order->id)->get();
        return view('frontend.singleticket', compact('order', 'orderchild'));
    }
    public function ticketDownload($id)
    {
        $order = Order::with(['event', 'organization', 'ticket','appUser', 'guestUser'])->find($id);
        $order->tax_data = OrderTax::where('order_id', $order->id)->get();
        $order->ticket_data = OrderChild::where('order_id', $order->id)->get();
        $customPaper = array(0, 0, 720, 1440);
        $pdf = FacadePdf::loadView('ticketmail', compact('order'))->save(public_path("ticket.pdf"))->setPaper($customPaper, $orientation = 'portrait');
        $tempp = $pdf->output();
        $response = response($tempp, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ticket.pdf"',
        ]);
        return $response;
    }

    public function stripeTransactions(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        // If this is a POST request with filters, redirect to GET with query parameters
        if ($request->isMethod('post') && !$request->has('export')) {
            $queryParams = $request->only(['event_id', 'duration']);
            return redirect()->route('stripeTransactions', $queryParams);
        }

        $query = StripeTransaction::with('order.event', 'order.appUser', 'order.guestUser');

        // Apply event filter
        if ($request->filled('event_id')) {
            $query->whereHas('order', function($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // Apply date filter
        if ($request->filled('duration')) {
            $dates = explode(' - ', $request->duration);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        $transactions = $query->orderBy('created_at', 'DESC')->get();

        // Calculate total tax amount
        $totalTaxAmount = $transactions->sum('tax_amount');

        // Get all events for filter dropdown ordered by event start date descending
        $events = Event::orderBy('start_time', 'DESC')->get();

        // Handle export
        if ($request->input('export')) {
            return $this->exportStripeTransactions($transactions, $request->input('export'));
        }

        return view('admin.transaction.index', compact('transactions', 'events', 'totalTaxAmount', 'request'));
    }

    private function exportStripeTransactions($transactions, $format)
    {
        $currency = Setting::first()->currency;

        if ($format == 'csv') {
            $filename = 'stripe_transactions_' . date('Y-m-d') . '.csv';

            // Create CSV content
            $csvContent = '';

            // Add BOM for UTF-8
            $csvContent .= chr(0xEF).chr(0xBB).chr(0xBF);

            // Create file handle for writing
            $handle = fopen('php://temp', 'w+');

            // Write CSV header
            fputcsv($handle, ['#', 'Payment ID', 'Order ID', 'Event', 'Customer', 'Amount', 'Currency', 'Latest Charge', 'Transaction ID', 'Stripe Fee', 'Status', 'Payment Methods', 'Date']);

            // Write data rows
            foreach ($transactions as $key => $item) {
                try {
                    $customer = 'N/A';
                    if ($item->order) {
                        if ($item->order->appUser) {
                            $customer = $item->order->appUser->name . ' ' . $item->order->appUser->last_name;
                        } elseif ($item->order->guestUser) {
                            $customer = $item->order->guestUser->name . ' ' . $item->order->guestUser->last_name;
                        }
                    }

                    $event = ($item->order && $item->order->event) ? $item->order->event->name : 'N/A';
                    $orderId = $item->order ? $item->order->order_id : 'N/A';
                    $paymentMethods = $item->payment_method_types ? implode(', ', array_map('ucfirst', $item->payment_method_types)) : 'N/A';

                    fputcsv($handle, [
                        $key + 1,
                        $item->payment_id ?? 'N/A',
                        $orderId,
                        $event,
                        $customer,
                        $currency . number_format($item->amount ?? 0, 2),
                        strtoupper($item->currency ?? 'USD'),
                        $item->latest_charge ?? 'N/A',
                        $item->txn_id ?? 'N/A',
                        $item->tax_amount ? $currency . number_format($item->tax_amount, 2) : 'N/A',
                        $item->status ? ucfirst(str_replace('_', ' ', $item->status)) : 'N/A',
                        $paymentMethods,
                        $item->created_at ? $item->created_at->format('d M Y, H:i') : 'N/A'
                    ]);
                } catch (\Exception $e) {
                    // Log error and continue with next item
                    Log::error('Error exporting transaction ' . ($item->id ?? 'unknown') . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Get CSV content
            rewind($handle);
            $csvContent .= stream_get_contents($handle);
            fclose($handle);

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            return response($csvContent, 200, $headers);
        } elseif ($format == 'pdf') {
            $pdf = FacadePdf::loadView('admin.transaction.pdf', [
                'transactions' => $transactions,
                'currency' => $currency
            ])->setPaper('a4', 'landscape');
            return $pdf->download('stripe_transactions_' . date('Y-m-d') . '.pdf');
        }
    }

    public function viewStripeTransaction($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $transaction = StripeTransaction::with('order.event', 'order.appUser', 'order.guestUser', 'order.organization')
            ->findOrFail($id);

        return view('admin.transaction.view', compact('transaction'));
    }

    public function deleteStripeTransaction($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $transaction = StripeTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->route('stripeTransactions')->with('status', 'Transaction deleted successfully!');
    }

    public function refundOrder(Request $request)
    {
        $orderId = $request->input('id');
        Log::info("[Refund Process] Step 1: Initiating refund request", [
            'request_order_id' => $orderId,
            'user_id' => Auth::id(),
            'user_role' => Auth::user() ? Auth::user()->roles->pluck('name')->toArray() : []
        ]);

        try {
            if (!$orderId) {
                Log::error("[Refund Process] Step 1 Failed: Invalid or missing order ID in request payload", ['payload' => $request->all()]);
                return response()->json(['success' => false, 'msg' => __('Invalid order ID.')], 400);
            }

            // Step 2: Fetch order with relations
            $order = Order::with(['orderChild', 'event', 'ticket'])->find($orderId);
            if (!$order) {
                Log::error("[Refund Process] Step 2 Failed: Order not found in database", ['order_id' => $orderId]);
                return response()->json(['success' => false, 'msg' => __('Order not found for ID: ') . $orderId], 404);
            }

            $event = $order->event;
            Log::info("[Refund Process] Step 2 Success: Order retrieved", [
                'order_db_id'   => $order->id,
                'order_code'    => $order->order_id,
                'event_id'      => $order->event_id,
                'event_name'    => $event ? $event->name : 'N/A',
                'event_status'  => $event ? $event->status : 'N/A',
                'event_is_deleted' => $event ? $event->is_deleted : 'N/A',
                'start_time'    => $event ? $event->start_time : null,
                'end_time'      => $event ? $event->end_time : null,
                'current_order_status' => $order->order_status,
                'current_payment_status' => $order->payment_status,
            ]);

            // Step 3: Check permissions
            if (Auth::user()->hasRole('Organizer') && $order->organization_id != Auth::user()->id) {
                Log::error("[Refund Process] Step 3 Failed: Organizer unauthorized for this order", [
                    'auth_user_id' => Auth::id(),
                    'order_organization_id' => $order->organization_id
                ]);
                return response()->json(['success' => false, 'msg' => __('Unauthorized: You do not have permission to refund this order.')], 403);
            }
            Log::info("[Refund Process] Step 3 Success: Authorization passed");

            // Step 4: Check if order is already refunded
            if ($order->payment_status == 2 || $order->order_status === 'Refunded') {
                Log::warning("[Refund Process] Step 4 Warning: Order is already refunded", [
                    'order_code' => $order->order_id,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status
                ]);
                return response()->json(['success' => false, 'msg' => __('Order #') . $order->order_id . __(' is already refunded.')], 400);
            }
            Log::info("[Refund Process] Step 4 Success: Order is eligible for refund");

            DB::beginTransaction();

            // Step 5: Update Order status
            $order->order_status = 'Refunded';
            $order->payment_status = 2; // 2 = Refunded
            $order->save();
            Log::info("[Refund Process] Step 5 Success: Order updated to Refunded status", ['order_db_id' => $order->id]);

            // Step 6: Update OrderChild records
            $childCount = OrderChild::where('order_id', $order->id)->update(['status' => 0]);
            Log::info("[Refund Process] Step 6 Success: OrderChild records status reset", ['updated_children_count' => $childCount]);

            // Step 7: Release venue seats (EventVenueSeat)
            $childSeatIds = $order->orderChild ? $order->orderChild->pluck('event_venue_seat_id')->filter()->toArray() : [];

            $seatQuery = EventVenueSeat::where('booked_order_id', $order->id);
            if (!empty($childSeatIds)) {
                $seatQuery->orWhereIn('id', $childSeatIds);
            }

            $seatsToRelease = $seatQuery->get(['id', 'seat_number', 'section_name', 'row_name', 'status']);
            $releasedCount = $seatQuery->update([
                'status'                => EventVenueSeat::STATUS_AVAILABLE,
                'booked_order_id'       => null,
                'booked_order_child_id' => null,
                'booked_at'             => null,
                'hold_token'            => null,
                'held_by_session_id'    => null,
                'held_by_app_user_id'   => null,
                'held_by_guest_user_id' => null,
                'held_at'               => null,
                'hold_expires_at'       => null,
            ]);

            Log::info("[Refund Process] Step 7 Success: EventVenueSeats released", [
                'released_seat_count' => $releasedCount,
                'seats' => $seatsToRelease->toArray()
            ]);

            // Step 8: Stripe Refund Attempt (if transaction exists)
            $transaction = StripeTransaction::where('order_id', $order->id)->first();
            if ($transaction) {
                Log::info("[Refund Process] Step 8: Stripe transaction record found", [
                    'stripe_transaction_id' => $transaction->id,
                    'payment_id' => $transaction->payment_id,
                    'status' => $transaction->status
                ]);

                if ($transaction->status !== 'refunded') {
                    $paymentSetting = PaymentSetting::first();
                    if ($paymentSetting && !empty($paymentSetting->stripeSecretKey)) {
                        try {
                            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
                            $paymentIntent = $stripe->paymentIntents->retrieve($transaction->payment_id, [
                                'expand' => ['charges']
                            ]);
                            $charge = $paymentIntent->charges->data[0] ?? null;
                            $latestCharge = $paymentIntent->latest_charge ?: ($charge ? $charge->id : null);

                            if ($latestCharge) {
                                $stripeRefund = $stripe->refunds->create([
                                    'charge' => $latestCharge,
                                ]);
                                Log::info("[Refund Process] Step 8 Success: Stripe API refund created", ['stripe_refund_id' => $stripeRefund->id]);
                            }
                            $transaction->update(['status' => 'refunded']);
                        } catch (\Exception $se) {
                            Log::warning("[Refund Process] Step 8 Notice: Stripe refund notice for order #{$order->id}: " . $se->getMessage());
                            if (str_contains($se->getMessage(), 'already been refunded')) {
                                $transaction->update(['status' => 'refunded']);
                            }
                        }
                    } else {
                        Log::info("[Refund Process] Step 8 Notice: Stripe secret key not configured, skipping online gateway call.");
                    }
                }
            } else {
                Log::info("[Refund Process] Step 8 Notice: No Stripe transaction record attached to order.");
            }

            DB::commit();

            // Record Activity Log
            try {
                \App\Models\AdminActivityLog::record(
                    'order_refunded',
                    $order,
                    'Order Refunded',
                    'Order #' . $order->order_id . ' was refunded by ' . (Auth::user() ? Auth::user()->name : 'Organizer'),
                    [
                        'order_id' => $order->order_id,
                        'order_db_id' => $order->id,
                        'event_name' => $order->event?->name ?? 'N/A',
                        'refunded_amount' => $order->payment,
                    ]
                );
            } catch (\Exception $ae) {
                Log::warning("[Refund Process] Could not record AdminActivityLog: " . $ae->getMessage());
            }

            // Send Refund Email to Customer
            try {
                $customerEmail = null;
                $customerName = 'Customer';

                if ($order->appUser && !empty($order->appUser->email)) {
                    $customerEmail = $order->appUser->email;
                    $customerName = trim(($order->appUser->name ?? '') . ' ' . ($order->appUser->last_name ?? ''));
                } elseif ($order->guestUser && !empty($order->guestUser->email)) {
                    $customerEmail = $order->guestUser->email;
                    $customerName = trim(($order->guestUser->name ?? '') . ' ' . ($order->guestUser->last_name ?? ''));
                }

                if ($customerEmail) {
                    $currency = Setting::first()->currency_sybmol ?? '$';
                    Mail::to($customerEmail)->send(new \App\Mail\OrderRefund($order, [
                        'customer_name' => $customerName ?: 'Customer',
                        'currency' => $currency
                    ]));
                    Log::info("[Refund Process] Customer refund notification email sent to {$customerEmail}");
                }
            } catch (\Exception $me) {
                Log::error("[Refund Process] Error sending customer refund email: " . $me->getMessage());
            }

            Log::info("[Refund Process] Step 9 COMPLETED SUCCESSFULLY: Order refund finished and seats released", [
                'order_db_id' => $order->id,
                'order_code'  => $order->order_id
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('Order #') . $order->order_id . __(' refunded successfully and seat(s) released.')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Refund Process] EXCEPTION CAUGHT: Step Execution Failed!", [
                'order_id' => $orderId ?? null,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('Error refunding order: ') . $e->getMessage()
            ], 500);
        }
    }
}
