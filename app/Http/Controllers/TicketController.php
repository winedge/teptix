<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\AdminActivityLog;
use App\Models\EventVenueRow;
use App\Models\EventVenueSeat;
use App\Models\Ticket;
use App\Models\TicketAllowUser;
use App\Models\Event;
use App\Models\Module;
use App\Models\Tax;
use Auth;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Seatmap\Entities\SeatMaps;
use App\Models\SeatTable;
use Carbon\Carbon;
use App\Support\OrganizerVerificationStatus;

class TicketController extends Controller
{
    private function blockUnverifiedOrganizer()
    {
        $user = Auth::user();

        if ($user && $user->hasRole('Organizer')) {
            if (!$user->onboarding_completed_at) {
                return redirect()->route('users.onboarding')
                    ->with('statusblock', __('Please complete onboarding before managing tickets.'));
            }

            if ((int) $user->is_verify !== 1) {
                return redirect('organization-home')
                    ->with('statusblock', OrganizerVerificationStatus::blockingMessage($user));
            }
        }

        return null;
    }

    public function index($id, $name)
    {
        abort_if(Gate::denies('ticket_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }
        $event = Event::find($id);
        $ticket = Ticket::where([['event_id', $id], ['is_deleted', 0]])->orderBy('id', 'DESC')->get();
        return view('admin.ticket.index', compact('ticket', 'event'));
    }

    public function create($id)
    {
        abort_if(Gate::denies('ticket_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }
        $event = Event::find($id);
        $venueMapRows = $this->venueMapRowsForEvent($event);
        $seatModule = Module::where('module', 'Seatmap')->first();
        $tax = Tax::where('status', 1)
            ->where(function($query) {
                $query->where('allow_all_bill', 1)
                      ->orWhere('user_id', auth()->id());
            })
            ->orderBy('id', 'DESC')
            ->get();

        // Get SeatTable_id from frontend input (request), default to empty array if not present
        $SeatTable_id = request()->input('SeatTable_id', []);
        // Ensure it's always an array
        if (!is_array($SeatTable_id)) {
            $SeatTable_id = $SeatTable_id ? [$SeatTable_id] : [];
        }

        // Get SeatTables - check if user is admin (user_id = 1) or has admin permissions
        if (auth()->id() == 1 || (auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->role === 'admin'))) {
            // Admin can see all seat tables
            $seatTables = SeatTable::all();
        } else {
            // Organizers see their own + admin created ones
            $seatTables = SeatTable::where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhere('user_id', 1);
            })->get();
        }

        if ($seatModule->is_enable == 1 && $seatModule->is_install == 1) {
            $seatMaps = SeatMaps::where('organizer_id', auth()->user()->id)->get();
            $tickets = Ticket::where('seatmap_id', '!=', Null)->get();
            foreach ($tickets as $value) {
                foreach ($seatMaps as $key => $map) {
                    if ($map->id == $value->seatmap_id) {
                        unset($seatMaps[$key]);
                    }
                }
            }
            return view('admin.ticket.create', compact('event', 'seatModule', 'seatMaps', 'tax', 'SeatTable_id', 'seatTables', 'venueMapRows'));
        }
        return view('admin.ticket.create', compact('event', 'seatModule', 'tax', 'SeatTable_id', 'seatTables', 'venueMapRows'));
    }


    public function store(Request $request)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        $request->validate([
            'name' => 'bail|required',
            'quantity' => 'bail|required',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'type' => 'bail|required',
            'ticket_per_order' => 'bail|required',
            'price' =>  'bail|required_if:type,paid',
            'SeatTable_id' => 'nullable|array',
            'SeatTable_id.*' => 'nullable|integer|exists:seat_table,id',
            'venue_map_row_ids' => 'nullable|array',
            'venue_map_row_ids.*' => 'nullable|integer|exists:event_venue_rows,id',
        ]);
        $data = $request->all();

        if ($request->type == "free") {
            $data['price'] = 0;
        }

        if (!isset($data['maximum_checkins']) || $data['maximum_checkins'] == '' || $data['maximum_checkins'] === null) {
            $data['maximum_checkins'] = 1;
        }

        // Handle connect_with_seat checkbox
        if ($request->connect_with_seat != "1") {
            $data['connect_with_seat'] = 0;
        }

        $data['ticket_number'] = chr(rand(65, 90)) . chr(rand(65, 90)) . '-' . rand(999, 10000);
        $event = Event::find($request->event_id);

        // Validate ticket sale dates against event dates
        $eventEndTime = Carbon::parse($event->end_time);
        $ticketSaleStartTime = Carbon::parse($request->start_time);
        $ticketSaleEndTime = Carbon::parse($request->end_time);

        if ($ticketSaleStartTime->gt($eventEndTime)) {
            return redirect()->back()->withErrors(['start_time' => 'Ticket sale start time cannot be after the event end time (' . $eventEndTime->format('d M Y, H:i') . ')'])->withInput();
        }

        if ($ticketSaleEndTime->gt($eventEndTime)) {
            return redirect()->back()->withErrors(['end_time' => 'Ticket sale end time cannot be after the event end time (' . $eventEndTime->format('d M Y, H:i') . ')'])->withInput();
        }

        // Validate ticket quantity against event capacity
        $currentTotalTickets = Ticket::where([['event_id', $request->event_id], ['is_deleted', 0]])->sum('quantity');
        $remainingCapacity = $event->people - $currentTotalTickets;

        if ($request->quantity > $remainingCapacity) {
            return redirect()->back()->withErrors(['quantity' => 'Ticket quantity exceeds available capacity. Maximum allowed: ' . $remainingCapacity])->withInput();
        }

        $data['user_id'] = $event->user_id;
        $data['tax_id']=isset($data['tax_ids'])?implode(',', $data['tax_ids']):'';

        // Handle SeatTable_id - support multiple seat tables as array
        if ($request->has('connect_with_seat') && $request->connect_with_seat == 1 && $request->filled('SeatTable_id')) {
            $seatTableIds = $request->SeatTable_id;
            // Ensure it's an array
            if (!is_array($seatTableIds)) {
                $seatTableIds = [$seatTableIds];
            }
            // Store as comma-separated string
            $data['SeatTable_id'] = implode(',', array_filter($seatTableIds));
        } else {
            $data['SeatTable_id'] = null;
        }

        $ticket = Ticket::create($data);
        $this->syncTicketVenueRows($ticket, $request->input('venue_map_row_ids', []));

        $allowToUserVal = $request->has('allow_to_user') ? (int) $request->allow_to_user : 0;
        TicketAllowUser::updateOrCreate(
            ['ticket_id' => $ticket->id],
            ['allow_to_user' => $allowToUserVal]
        );
        AdminActivityLog::record(
            AdminActivityLog::TICKET_CREATED,
            $ticket,
            __('Ticket created'),
            __(':name ticket was created for :event.', ['name' => $ticket->name, 'event' => $event->name]),
            [
                'ticket_id' => $ticket->id,
                'ticket_name' => $ticket->name,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'ticket_type' => $ticket->type,
                'quantity' => $ticket->quantity,
                'price' => $ticket->price,
                'sale_start_time' => $ticket->start_time,
                'sale_end_time' => $ticket->end_time,
                'organizer_ids' => $event->user_id,
            ],
            $request
        );

        return redirect($request->event_id . '/' . preg_replace('/\s+/', '-', $event->name) . '/tickets')->withStatus(__('Ticket has added successfully.'));
    }

    public function show(Ticket $ticket)
    {
    }

    public function edit($id)
    {
        abort_if(Gate::denies('ticket_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }
        $ticket = Ticket::find($id);
        $event = Event::find($ticket->event_id);
        $venueMapRows = $this->venueMapRowsForEvent($event);
        $selectedVenueMapRowIds = $venueMapRows
            ->where('ticket_id', $ticket->id)
            ->pluck('id')
            ->map(function ($rowId) {
                return (int) $rowId;
            })
            ->all();
        $tax = Tax::where('status', 1)
            ->where(function($query) {
                $query->where('allow_all_bill', 1)
                      ->orWhere('user_id', auth()->id());
            })
            ->orderBy('id', 'DESC')
            ->get();

        // Get SeatTables - check if user is admin (user_id = 1) or has admin permissions
        try {
            if (auth()->id() == 1 || (auth()->user() && (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin')) || (isset(auth()->user()->role) && auth()->user()->role === 'admin'))) {
                // Admin can see all seat tables
                $seatTables = SeatTable::all();
            } else {
                // Organizers see their own + admin created ones
                $seatTables = SeatTable::where(function($query) {
                    $query->where('user_id', auth()->id())
                          ->orWhere('user_id', 1);
                })->get();
            }

            // Fallback: if no seat tables found, show all (for debugging)
            if ($seatTables->isEmpty()) {
                $seatTables = SeatTable::all();
            }
        } catch (\Exception $e) {
            // If there's any error with role checking, just get all seat tables
            $seatTables = SeatTable::all();
        }

        // Get current seat table IDs as array
        $currentSeatTableIds = [];
        $SeatTable_id = []; // For backward compatibility with the view
        if (!empty($ticket->SeatTable_id)) {
            $currentSeatTableIds = explode(',', $ticket->SeatTable_id);
            $SeatTable_id = $currentSeatTableIds; // Set for the view
        }

        return view('admin.ticket.edit', compact('ticket', 'event', 'tax', 'seatTables', 'currentSeatTableIds', 'SeatTable_id', 'venueMapRows', 'selectedVenueMapRowIds'));
    }

    public function update(Request $request, $id)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        $request->validate([
            'name' => 'bail|required',
            'quantity' => 'bail|required',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required',
            'type' => 'bail|required',
            'ticket_per_order' => 'bail|required',
            'price' =>  'bail|required_if:type,paid',
            'SeatTable_id' => 'nullable|array',
            'SeatTable_id.*' => 'nullable|integer|exists:seat_table,id',
            'venue_map_row_ids' => 'nullable|array',
            'venue_map_row_ids.*' => 'nullable|integer|exists:event_venue_rows,id',
        ]);
        $data = $request->all();
        if ($request->type == "free") {
            $data['price'] = 0;
        }

        if (!isset($data['maximum_checkins']) || $data['maximum_checkins'] == '' || $data['maximum_checkins'] === null) {
            $data['maximum_checkins'] = 1;
        }
        if ($request->is_add_on != "1") {
            $data['is_add_on'] = 0;
        }

        // Handle connect_with_seat checkbox
        if ($request->connect_with_seat != "1") {
            $data['connect_with_seat'] = 0;
        }

        $event = Event::find($request->event_id);

        // Validate ticket sale dates against event dates
        $eventEndTime = Carbon::parse($event->end_time);
        $ticketSaleStartTime = Carbon::parse($request->start_time);
        $ticketSaleEndTime = Carbon::parse($request->end_time);

        if ($ticketSaleStartTime->gt($eventEndTime)) {
            return redirect()->back()->withErrors(['start_time' => 'Ticket sale start time cannot be after the event end time (' . $eventEndTime->format('d M Y, H:i') . ')'])->withInput();
        }

        if ($ticketSaleEndTime->gt($eventEndTime)) {
            return redirect()->back()->withErrors(['end_time' => 'Ticket sale end time cannot be after the event end time (' . $eventEndTime->format('d M Y, H:i') . ')'])->withInput();
        }

        // Validate ticket quantity against event capacity
        $currentTicket = Ticket::find($id);
        $currentTotalTickets = Ticket::where([['event_id', $request->event_id], ['is_deleted', 0]])->sum('quantity');
        $remainingCapacity = $event->people - ($currentTotalTickets - $currentTicket->quantity);

        if ($request->quantity > $remainingCapacity) {
            return redirect()->back()->withErrors(['quantity' => 'Ticket quantity exceeds available capacity. Maximum allowed: ' . $remainingCapacity])->withInput();
        }

        $data['tax_id']=isset($data['tax_ids'])?implode(',', $data['tax_ids']):'';

        // Handle SeatTable_id - support multiple seat tables as array
        if ($request->has('connect_with_seat') && $request->connect_with_seat == 1 && $request->filled('SeatTable_id')) {
            $seatTableIds = $request->SeatTable_id;
            // Ensure it's an array
            if (!is_array($seatTableIds)) {
                $seatTableIds = [$seatTableIds];
            }
            // Store as comma-separated string
            $data['SeatTable_id'] = implode(',', array_filter($seatTableIds));
        } else {
            $data['SeatTable_id'] = null;
        }

        $ticket = Ticket::find($id);
        $ticket->update($data);
        $this->syncTicketVenueRows($ticket, $request->input('venue_map_row_ids', []));

        $allowToUserVal = $request->has('allow_to_user') ? (int) $request->allow_to_user : 0;
        TicketAllowUser::updateOrCreate(
            ['ticket_id' => $ticket->id],
            ['allow_to_user' => $allowToUserVal]
        );
        return redirect($request->event_id . '/' . preg_replace('/\s+/', '-', $event->name) . '/tickets')->withStatus(__('Ticket has updated successfully.'));
    }

    public function destroy(Ticket $ticket)
    {
    }

    public function deleteTickets($id)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        try {
            $ticket = Ticket::find($id);
            if ($ticket) {
                $this->syncTicketVenueRows($ticket, []);
                $ticket->update(['is_deleted' => 1]);

                AdminActivityLog::record(
                    AdminActivityLog::TICKET_DELETED,
                    $ticket,
                    __('Ticket deleted'),
                    __(':name was deleted.', ['name' => $ticket->name]),
                    [
                        'ticket_id' => $ticket->id,
                        'ticket_name' => $ticket->name,
                        'event_id' => $ticket->event_id,
                        'user_id' => Auth::id(),
                    ],
                    request()
                );
            }
            return true;
        } catch (Throwable $th) {
            return response('Data is Connected with other Data', 400);
        }
    }

    private function venueMapRowsForEvent($event)
    {
        if (!$event) {
            return collect();
        }

        $liveVenueMap = $event->liveVenueMap()->first();
        if (!$liveVenueMap) {
            return collect();
        }

        return EventVenueRow::with('section')
            ->where('event_venue_map_id', $liveVenueMap->id)
            ->orderBy('event_venue_section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function syncTicketVenueRows(Ticket $ticket, array $rowIds)
    {
        $event = Event::find($ticket->event_id);
        if (!$event) {
            return;
        }

        $liveVenueMap = $event->liveVenueMap()->first();
        if (!$liveVenueMap) {
            return;
        }

        $rowIds = collect($rowIds)
            ->filter(function ($rowId) {
                return is_numeric($rowId) && (int) $rowId > 0;
            })
            ->map(function ($rowId) {
                return (int) $rowId;
            })
            ->unique()
            ->values()
            ->all();

        $validRowIds = EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)
            ->whereIn('id', $rowIds)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($ticket, $liveVenueMap, $validRowIds) {
            EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)
                ->where('ticket_id', $ticket->id)
                ->update([
                    'ticket_id' => null,
                    'pricing_tier' => null,
                    'price' => null,
                ]);

            EventVenueSeat::where('event_venue_map_id', $liveVenueMap->id)
                ->where('ticket_id', $ticket->id)
                ->update([
                    'ticket_id' => null,
                    'pricing_tier' => null,
                    'price' => null,
                ]);

            if (empty($validRowIds)) {
                return;
            }

            EventVenueRow::where('event_venue_map_id', $liveVenueMap->id)
                ->whereIn('id', $validRowIds)
                ->update([
                    'ticket_id' => $ticket->id,
                    'pricing_tier' => $ticket->name,
                    'price' => $ticket->price,
                ]);

            EventVenueSeat::where('event_venue_map_id', $liveVenueMap->id)
                ->whereIn('event_venue_row_id', $validRowIds)
                ->update([
                    'ticket_id' => $ticket->id,
                    'pricing_tier' => $ticket->name,
                    'price' => $ticket->price,
                ]);
        });
    }
}
