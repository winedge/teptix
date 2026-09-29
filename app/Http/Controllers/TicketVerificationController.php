<?php
// app/Http/Controllers/TicketVerificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth,log;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderChild;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Setting;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class TicketVerificationController extends Controller
{
    /**
     * Show the ticket verification page.
     */
    public function showPage()
    {
        $authId = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::id();

    if (Auth::user()->hasRole('admin')) {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events = Event::with(['category:id,name'])
            ->where([['status', 1], ['is_deleted', 0], ['event_status', 'Pending'], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
            ->orderBy('start_time', 'desc')->get();
    } else {
        $timezone = Setting::find(1)->timezone;
        $date = Carbon::now($timezone);
        $events = Event::where(function ($query) use ($authId) {
            $query->where('user_id', $authId)
                  ->orWhereRaw('FIND_IN_SET(?, user_id)', [$authId])
                  ->orWhereRaw('FIND_IN_SET(?, scanner_id)', [$authId]);
        })
        ->where([['status', 1], ['is_deleted', 0], ['end_time', '>', $date->format('Y-m-d H:i:s')]])
        ->orderBy('start_time', 'desc')
        ->get();
    }

    return view('admin.verification.ticketverify', compact('events'));
}



    public function scanTicket(Request $request)
    {
        $code = $request->ticket_number;
        $event_id = $request->event_id;

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

        // If event_id is not provided, use the event from the ticket
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

        $authId = (int) Auth::id();
        $scannerIds = array_filter(array_map('intval', explode(',', $event->scanner_id)));
        $eventOwnerIds = array_filter(array_map('intval', explode(',', $event->user_id)));
        $isAuthorizedManager = Auth::user()->hasRole('Manager') && in_array(Auth::user()->org_id, $eventOwnerIds);

        if (!Auth::user()->hasRole('admin') && !$isAuthorizedManager && !in_array($authId, $eventOwnerIds) && !in_array($authId, $scannerIds)) {
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

        $childSeatDetails = null;
        if (!empty($child->event_venue_seat_id)) {
            $venueSeat = \App\Models\EventVenueSeat::find($child->event_venue_seat_id);
            if ($venueSeat) {
                $childSeatDetails = [[
                    'id' => (string) $venueSeat->id,
                    'label' => $venueSeat->seat_label ?: trim(($venueSeat->section_name ?? '') . ' ' . ($venueSeat->row_name ?? '') . '-' . ($venueSeat->seat_number ?? '')),
                    'section' => $venueSeat->section_name ?? '',
                    'row' => $venueSeat->row_name ?? '',
                    'seat_number' => (string) ($venueSeat->seat_number ?? ''),
                    'ticket_id' => (string) ($venueSeat->ticket_id ?? $child->ticket_id ?? ''),
                ]];
            }
        }

        if (!$childSeatDetails && !empty($child->Book_Seat_Id)) {
            $rawSeats = $order->seat_details;
            if (is_string($rawSeats)) {
                $rawSeats = json_decode(html_entity_decode($rawSeats, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            }
            if (is_array($rawSeats)) {
                foreach ($rawSeats as $s) {
                    if (is_array($s) && (($s['label'] ?? null) === $child->Book_Seat_Id || ($s['seat_label'] ?? null) === $child->Book_Seat_Id)) {
                        $childSeatDetails = [$s];
                        break;
                    }
                }
            }
            if (!$childSeatDetails) {
                $childSeatDetails = [[
                    'id' => (string) $child->Book_Seat_Id,
                    'label' => (string) $child->Book_Seat_Id,
                    'section' => 'Reserved',
                    'row' => '',
                    'seat_number' => (string) $child->Book_Seat_Id,
                    'ticket_id' => (string) $child->ticket_id,
                ]];
            }
        }

        $resolvedBookSeatId = !empty($child->Book_Seat_Id) ? $child->Book_Seat_Id : (!empty($childSeatDetails) ? $childSeatDetails[0]['label'] : null);

        $data = [
            'payment_type' => $child->paid == 1 ? "STRIPE" : $order->payment_type,
            'amount' => $order->payment,
            'currency' => $currency,
            'event_id' => $order->event_id,
            'event_name' => $event->name ?? 'Unknown Event',
            'ticket_number' => $child->ticket_number,
            'ticket_title' => $ticket->ticket_title ?? 'General',
            'seat_details' => $childSeatDetails,
            'Book_Seat_Id' => $resolvedBookSeatId,
            'qr_code' => base64_encode(QrCode::format('png')->size(150)->generate($child->ticket_number)),
        ];

        if ((int) $order->payment_status !== 1) {
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
                'auto_close' => true  // Flag to indicate this should auto-close
            ], 200);
        }

        $child->save();
        $data['remaining_check_ins'] = $child->checkin;

        return response()->json([
            'msg' => 'Ticket scanned successfully.',
            'success' => true,
            'data' => $data,
            'auto_close' => true  // Flag to indicate this should auto-close
        ], 200);
    }



}
