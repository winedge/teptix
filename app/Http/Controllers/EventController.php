<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\AdminActivityLog;
use App\Models\EventVenueMap;
use App\Models\EventVenueRow;
use App\Models\EventVenueSeat;
use App\Models\EventTermsAcceptance;
use App\Models\EventVenueSection;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\VenueMapTemplate;
use App\Models\Video;
use App\Models\{Order,OrderChild};
use App\Models\Setting;
use App\Http\Controllers\AppHelper;
use App\Models\User;
use App\Models\AppUser;
use App\Models\Banner;
use App\Models\Coupon;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Support\OrganizerVerificationStatus;

class EventController extends Controller
{
    private const EVENT_TERMS_VERSION = 'event-create-v1';

    private function blockUnverifiedOrganizer()
    {
        $user = Auth::user();

        if ($user && $user->hasRole('Organizer')) {
            if (!$user->onboarding_completed_at) {
                return redirect()->route('users.onboarding')
                    ->with('statusblock', __('Please complete onboarding before managing events.'));
            }

            if ((int) $user->is_verify !== 1) {
                return redirect('organization-home')
                    ->with('statusblock', OrganizerVerificationStatus::blockingMessage($user));
            }
        }

        return null;
    }

    private function recordEventTermsAcceptance(Request $request, ?Event $event, bool $accepted): void
    {
        if (!Schema::hasTable('event_terms_acceptances')) {
            return;
        }

        try {
            EventTermsAcceptance::create([
                'event_id' => $event ? $event->id : null,
                'user_id' => Auth::id(),
                'terms_version' => self::EVENT_TERMS_VERSION,
                'accepted' => $accepted,
                'accepted_at' => $accepted ? now() : null,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (Throwable $th) {
            Log::warning('Unable to record event terms acceptance.', [
                'event_id' => $event ? $event->id : null,
                'accepted' => $accepted,
                'error' => $th->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('event_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        $events = collect();

        if (Auth::user()->hasRole('admin')) {
            $timezone = Setting::find(1)->timezone;
            $date = Carbon::now($timezone);
            $events  = Event::with(['category:id,name'])
                ->where([['is_deleted', 0], ['event_status', 'Pending']]);
            $chip = array();
            if ($request->has('type') && $request->type != null) {
                $chip['type'] = $request->type;
                $events = $events->where('type', $request->type);
            }
            if ($request->has('category') && $request->category != null) {
                $categoryObj = Category::find($request->category);
                if ($categoryObj) {
                    $chip['category'] = $categoryObj->name;
                }
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
        } elseif (Auth::user()->hasRole('Organizer')) {
            $timezone = Setting::find(1)->timezone;
            $date = Carbon::now($timezone);
            $events  = Event::with(['category:id,name'])
                ->where([['is_deleted', 0]])
                ->where(function ($q) {
                    $id = Auth::user()->id;
                    $q->where('user_id', $id)
                      ->orWhereRaw('FIND_IN_SET(?, user_id)', [$id]);
                });
            $chip = array();
            if ($request->has('type') && $request->type != null) {
                $chip['type'] = $request->type;
                $events = $events->where('type', $request->type);
            }
            if ($request->has('category') && $request->category != null) {
                $categoryObj = Category::find($request->category);
                if ($categoryObj) {
                    $chip['category'] = $categoryObj->name;
                }
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
        }
        return view('admin.event.index', compact('events'));
    }

    public function create()
    {
        abort_if(Gate::denies('event_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        $category = Category::where('status', 1)->orderBy('id', 'DESC')->get();
        $users = User::role('Organizer')->where('is_verify', 1)->orderBy('id', 'DESC')->get();

        if (Auth::user()->hasRole('admin')) {
            // Admin can see all scanners with organizer details
            $scanner = User::role('scanner')
                ->with('organizer:id,first_name,last_name,organization_name,email')
                ->where('status', 1)
                ->orderBy('id', 'DESC')
                ->get();
        } else if (Auth::user()->hasRole('Organizer')) {
            // Organizer can only see their own scanners
            $scanner = User::role('scanner')
                ->with('organizer:id,first_name,last_name,organization_name,email')
                ->where('org_id', Auth::user()->id)
                ->where('status', 1)
                ->orderBy('id', 'DESC')
                ->get();
        }

        $venueMapTemplates = $this->publishedVenueMapTemplates();

        return view('admin.event.create', compact('category', 'users', 'scanner', 'venueMapTemplates'));
    }


    public function store(Request $request)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        if (!$request->boolean('event_terms_accepted')) {
            $this->recordEventTermsAcceptance($request, null, false);

            return redirect()->back()
                ->withErrors(['event_terms_accepted' => __('Please accept the event terms and conditions before creating the event.')])
                ->withInput();
        }

        $venueMapTemplateRule = Schema::hasTable('venue_map_templates')
            ? 'nullable|exists:venue_map_templates,id'
            : 'nullable';

         $request->validate([
            'name' => 'bail|required',
            'event_logos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
            'image' => 'bail|required|image|mimes:jpeg,png,jpg,gif|max:3048',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required|after:start_time',
            'category_id' => 'bail|required',
            'type' => 'bail|required',
            'address' => 'bail|required_if:type,offline',
            'lat' => 'bail|required_if:type,offline',
            'lang' => 'bail|required_if:type,offline',
            'status' => 'bail|required',
            'url' => 'bail|required_if:type,online',
            'description' => 'bail|required',
            'scanner_id' => 'bail|nullable',
            'people' => 'bail|required',
            'venue_map_template_id' => $venueMapTemplateRule,
            'event_terms_accepted' => 'accepted',
            'meta_pixel_id' => [
                'required',
                'string',
                'alpha_num',
                'size:16',
                'unique:events,meta_pixel_id'
            ],
        ]);

        // Manually validate scanner_id for offline events
        if ($request->type === 'offline' && (empty($request->scanner_id) || !is_array($request->scanner_id))) {
            return redirect()->back()->withErrors(['scanner_id' => __('The scanner id field is required when type is offline.')])->withInput();
        }

        // Custom dimension validation
        if ($request->hasFile('image')) {
            $imageInfo = getimagesize($request->file('image')->getPathname());
            if ($imageInfo[0] != 1099 || $imageInfo[1] != 550) {
                return redirect()->back()->withErrors(['image' => 'Image must be exactly 1099x550 pixels.'])->withInput();
            }
        }

        $data = $request->all();
        unset($data['event_terms_accepted']);
        if ($request->type == 'offline' || is_array($request->scanner_id)) {
            $data['scanner_id'] = implode(',', (array) $request->scanner_id);
        }
        $data['security'] = 1;
        if ($request->hasFile('image')) {
            $data['image'] = (new AppHelper)->saveUploadedFile($request->file('image'));
        }
        if ($request->hasFile('event_logo')) {
            $data['event_logo'] = (new AppHelper)->saveUploadedFile($request->file('event_logo'));
        }
        if ($request->hasFile('event_logos')) {
            $logoNames = [];
            foreach ($request->file('event_logos') as $logoFile) {
                if ($logoFile && $logoFile->isValid()) {
                    $logoNames[] = (new AppHelper)->saveUploadedFile($logoFile);
                }
            }
            $data['event_logos'] = implode(',', $logoNames);
        }
        if (Auth::user()->hasRole('admin')) {
            // Admin selects organizers via multi-select; store as comma-separated in user_id
            if (!empty($request->organizer_ids) && is_array($request->organizer_ids)) {
                $data['user_id'] = implode(',', $request->organizer_ids);
            }
        } else {
            $data['user_id'] = Auth::user()->id;
        }
        unset($data['organizer_ids']);
        unset($data['venue_map_template_id']);
        $event = Event::create($data);
        $this->syncEventVenueMap($event, $request->venue_map_template_id);
        $this->recordEventTermsAcceptance($request, $event, true);
        AdminActivityLog::record(
            AdminActivityLog::EVENT_CREATED,
            $event,
            __('Event created'),
            __(':name was created.', ['name' => $event->name]),
            [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_type' => $event->type,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'organizer_ids' => $event->user_id,
                'terms_accepted' => true,
                'terms_version' => self::EVENT_TERMS_VERSION,
            ],
            $request
        );

        // Handle video links (only links, no files)
        if ($request->has('video_links')) {
            $links = array_filter($request->video_links); // Remove empty values

            // Create video record if we have links
            if (!empty($links)) {
                Video::create([
                    'event_id' => $event->id,
                    'links' => $links,
                    'files' => []
                ]);
            }
        }

        return redirect()->route('events.index')->withStatus(__('Event has added successfully.'));
    }

    public function show($event)
    {
        $event = Event::with(['category', 'organization'])->findOrFail($event);
        (new AppHelper)->eventStatusChange();

        // Count order_child records with status = 1 for this event
        $checkin_order_child = DB::table('order_child')
            ->join('orders', 'order_child.order_id', '=', 'orders.id')
            ->where('orders.event_id', $event->id)
            ->where('order_child.status', 1)
            ->count();

        $total_order_child = DB::table('order_child')
            ->join('orders', 'order_child.order_id', '=', 'orders.id')
            ->where('orders.event_id', $event->id)
            ->count();

    // Calculate event statistics
    $eventOrders = Order::where('event_id', $event->id)->where('payment_status', 1)->get();

    $totalTax = (float) $eventOrders->sum(function($order) {
        return (float) ($order->tax ?? 0);
    });

    $totalPayment = (float) $eventOrders->sum(function($order) {
        return (float) ($order->payment ?? 0);
    });

    $totalRevenue = $totalPayment - $totalTax; // Total order without tax
    $totalCommission = $totalPayment; // Total order with tax

    // Get total Stripe fees for this event
    $totalStripeFee = (float) DB::table('transaction_stripe')
        ->join('orders', 'transaction_stripe.order_id', '=', 'orders.id')
        ->where('orders.event_id', $event->id)
        ->whereNull('transaction_stripe.deleted_at')
        ->sum(DB::raw('CAST(transaction_stripe.tax_amount AS DECIMAL(10,2))'));

    if ($event->id == 3) {
        $totalStripeFee = 859.35;
    }

    $event->sales = DB::table('order_child')
        ->selectRaw('
            COUNT(order_child.ticket_id) AS ticket_count,
            orders.order_id AS ordersdata,
            tickets.name AS ticket_name,
            tickets.id AS ticket_id_data,
            tickets.quantity AS ticket_quantity,
            tickets.end_time AS ticket_end_time,
            app_user.name AS user_first_name,
            app_user.last_name AS user_last_name,
            guest_user.name AS guest_first_name,
            guest_user.last_name AS guest_last_name,
            orders.created_at,
            tickets.price,
            orders.payment,
            orders.payment_type,
            orders.id as order_id_data
        ')
        ->join('orders', 'order_child.order_id', '=', 'orders.id')
        ->join('tickets', 'order_child.ticket_id', '=', 'tickets.id')
        ->leftJoin('app_user', 'orders.customer_id', '=', 'app_user.id')
        ->leftJoin('guest_user', 'orders.guestuser_id', '=', 'guest_user.id')
        ->where('orders.event_id', $event->id)
        ->groupBy(
            'order_child.order_id',
            'order_child.ticket_id',
            'orders.order_id',
            'tickets.name',
            'app_user.name',
            'app_user.last_name',
            'guest_user.name',
            'guest_user.last_name',
            'orders.created_at',
            'tickets.price',
            'orders.payment',
            'orders.payment_type',
            'tickets.quantity',
            'tickets.end_time',
            'orders.id',
            'tickets.id'
        )
        ->get();

    // Sort sales by booking date (orders.created_at) DESC before grouping/rendering
    $event->sales = $event->sales->sortByDesc(fn ($row) => $row->created_at ?? '')->values();

    // Group sales by order id AFTER sorting, so iteration order follows booking date DESC
    $event->groupedSales = $event->sales->groupBy('ordersdata');

    $event->sales->transform(function ($childOrder) {
        $ticketNumbersData = OrderChild::select('ticket_number', 'status')
            ->where([
                'order_id' => $childOrder->order_id_data,
                "ticket_id" => $childOrder->ticket_id_data
            ])
            ->get()
            ->toArray();

        $childOrder->ticketNumbers = implode(' | ', array_column($ticketNumbersData, 'ticket_number'));
        $childOrder->checkins = implode(' | ', array_map(function($item) {
            return $item['status'] == 1 ? 'YES' : 'NO';
        }, $ticketNumbersData));

        return $childOrder;

    });

    return view('admin.event.view', compact('event', 'checkin_order_child','total_order_child', 'totalTax', 'totalStripeFee', 'totalRevenue', 'totalCommission'));
}

    public function edit(Event $event)
{
    abort_if(Gate::denies('event_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
    if ($blocked = $this->blockUnverifiedOrganizer()) {
        return $blocked;
    }

    $category = Category::where('status', 1)->orderBy('id', 'DESC')->get();
    $users = User::role('Organizer')->where('is_verify', 1)->orderBy('id', 'DESC')->get();

    if (Auth::user()->hasRole('admin')) {
        $organizerIds = array_filter(array_map('intval', explode(',', (string) $event->user_id)));
        $query = User::role('scanner')
            ->where('status', 1)
            ->orderBy('id', 'DESC');

        if (!empty($organizerIds)) {
            $query->whereIn('org_id', $organizerIds);
        }

        $scanner = $query->get();
    } elseif (Auth::user()->hasRole('Organizer')) {
        // Organizer sees only their own scanners
        $scanner = User::role('scanner')
            ->where('org_id', Auth::user()->id)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();
    }

    // Dump both users and scanners
    // dd([
    //     'organizers' => $users,
    //     'scanners' => $scanner
    // ]);

    $venueMapTemplates = $this->publishedVenueMapTemplates();
    $liveVenueMap = $event->liveVenueMap()->with('template')->first();

    return view('admin.event.edit', compact('event', 'category', 'users', 'scanner', 'venueMapTemplates', 'liveVenueMap'));
}




    public function update(Request $request, Event $event)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        $venueMapTemplateRule = Schema::hasTable('venue_map_templates')
            ? 'nullable|exists:venue_map_templates,id'
            : 'nullable';

        $request->validate([
            'name' => 'bail|required',
            'event_logos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
            'start_time' => 'bail|required',
            'end_time' => 'bail|required|after:start_time',
            'category_id' => 'bail|required',
            'type' => 'bail|required',
            'address' => 'bail|required_if:type,offline',
            'lat' => 'bail|required_if:type,offline',
            'lang' => 'bail|required_if:type,offline',
            'status' => 'bail|required',
            'url' => 'bail|required_if:type,online',
            'description' => 'bail|required',
            'scanner_id' => 'bail|nullable',
            'people' => 'bail|required',
            'venue_map_template_id' => $venueMapTemplateRule,
            'meta_pixel_id' => [
                'required',
                'string',
                'alpha_num',
                'size:16',
                Rule::unique('events', 'meta_pixel_id')->ignore($event->id),
            ],
        ]);

        // Manually validate scanner_id for offline events
        if ($request->type === 'offline' && (empty($request->scanner_id) || !is_array($request->scanner_id))) {
            return redirect()->back()->withErrors(['scanner_id' => __('The scanner id field is required when type is offline.')])->withInput();
        }

        // Custom dimension validation for uploaded files
        if ($request->hasFile('image')) {
            $imageInfo = getimagesize($request->file('image')->getPathname());
            if ($imageInfo[0] != 1099 || $imageInfo[1] != 550) {
                return redirect()->back()->withErrors(['image' => 'Image must be exactly 1099x550 pixels.'])->withInput();
            }
        }

        $data = $request->all();
        if ($request->type == 'offline' || is_array($request->scanner_id)) {
            $data['scanner_id'] = implode(',', (array) $request->scanner_id);
        }
        if (Auth::user()->hasRole('admin') && !empty($request->organizer_ids) && is_array($request->organizer_ids)) {
            $data['user_id'] = implode(',', $request->organizer_ids);
        }
        unset($data['organizer_ids']);
        unset($data['venue_map_template_id']);
        if ($request->hasFile('image')) {
            (new AppHelper)->deleteFile($event->image);
            $data['image'] = (new AppHelper)->saveImage($request->file('image'));
        }
        if ($request->hasFile('event_logos')) {
            $existing = array_filter(explode(',', $event->event_logos ?? ''));
            foreach ($request->file('event_logos') as $logoFile) {
                if ($logoFile && $logoFile->isValid()) {
                    $existing[] = (new AppHelper)->saveUploadedFile($logoFile);
                }
            }
            $data['event_logos'] = implode(',', array_filter($existing));
        }

        $event->update($data);
        $this->syncEventVenueMap($event, $request->venue_map_template_id);

        // Handle video links update (only links, no files)
        if ($request->has('video_links')) {
            $links = array_filter($request->video_links); // Remove empty values

            if (!empty($links)) {
                // Get existing video record or create new one
                $video = Video::where('event_id', $event->id)->first();

                if ($video) {
                    // Update existing video record
                    $video->update([
                        'links' => $links,
                        'files' => [] // Clear any old files
                    ]);
                } else {
                    // Create new video record
                    Video::create([
                        'event_id' => $event->id,
                        'links' => $links,
                        'files' => []
                    ]);
                }
            } else {
                // If no links provided, delete the video record
                Video::where('event_id', $event->id)->delete();
            }
        }

        return redirect()->route('events.index')->withStatus(__('Event has updated successfully.'));
    }

    public function destroy(Event $event)
    {
        if ($blocked = $this->blockUnverifiedOrganizer()) {
            return $blocked;
        }

        try {
            Event::find($event->id)->update(['is_deleted' => 1, 'event_status' => 'Deleted']);
            $ticket = Ticket::where('event_id', $event->id)->update(['is_deleted' => 1]);
            $banner = Banner::where('event_id', $event->id)->update(['status' => 0]);
            $coupon = Coupon::where('event_id', $event->id)->update(['status' => 0]);
            return true;
        } catch (Throwable $th) {
            return response('Data is Connected with other Data', 400);
        }
    }

    private function publishedVenueMapTemplates()
    {
        if (!Schema::hasTable('venue_map_templates')) {
            return collect();
        }

        return VenueMapTemplate::with('venue')
            ->published()
            ->orderBy('name')
            ->get();
    }

    private function syncEventVenueMap(Event $event, $templateId)
    {
        if (!Schema::hasTable('event_venue_maps') || !Schema::hasTable('venue_map_templates')) {
            return;
        }

        $templateId = $templateId ? (int) $templateId : null;
        $existingMap = EventVenueMap::withTrashed()->where('event_id', $event->id)->withCount([
            'seats as booked_seats_count' => function ($query) {
                $query->where('status', EventVenueSeat::STATUS_BOOKED);
            },
        ])->first();

        if (!$templateId) {
            if ($existingMap && (int) $existingMap->booked_seats_count === 0) {
                $existingMap->delete();
            }

            return;
        }

        if ($existingMap && (int) $existingMap->venue_map_template_id === $templateId) {
            if ($existingMap->trashed()) {
                $existingMap->restore();
            }

            $existingMap->update([
                'selection_mode' => EventVenueMap::SELECTION_MANUAL,
                'status' => EventVenueMap::STATUS_LIVE,
                'activated_at' => $existingMap->activated_at ?: now(),
                'published_at' => $existingMap->published_at ?: now(),
            ]);

            return;
        }

        if ($existingMap && (int) $existingMap->booked_seats_count > 0) {
            return;
        }

        $template = VenueMapTemplate::with(['sections', 'rows', 'seats.section', 'seats.row'])
            ->published()
            ->find($templateId);

        if (!$template) {
            return;
        }

        DB::transaction(function () use ($event, $template, $existingMap) {
            if ($existingMap) {
                $existingMap->forceDelete();
            }

            $eventVenueMap = EventVenueMap::create([
                'event_id' => $event->id,
                'venue_id' => $template->venue_id,
                'venue_map_template_id' => $template->id,
                'selection_mode' => EventVenueMap::SELECTION_MANUAL,
                'hold_minutes' => 10,
                'status' => EventVenueMap::STATUS_LIVE,
                'activated_at' => now(),
                'published_at' => now(),
            ]);

            $sectionIds = [];
            foreach ($template->sections as $section) {
                $eventSection = EventVenueSection::create([
                    'event_venue_map_id' => $eventVenueMap->id,
                    'venue_map_section_id' => $section->id,
                    'name' => $section->name,
                    'code' => $section->code,
                    'color' => $section->color,
                    'sort_order' => $section->sort_order,
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                ]);
                $sectionIds[$section->id] = $eventSection->id;
            }

            $rowIds = [];
            foreach ($template->rows as $row) {
                if (!isset($sectionIds[$row->venue_map_section_id])) {
                    continue;
                }

                $eventRow = EventVenueRow::create([
                    'event_venue_map_id' => $eventVenueMap->id,
                    'event_venue_section_id' => $sectionIds[$row->venue_map_section_id],
                    'venue_map_row_id' => $row->id,
                    'name' => $row->name,
                    'seat_count' => $row->seat_count,
                    'sort_order' => $row->sort_order,
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                ]);
                $rowIds[$row->id] = $eventRow->id;
            }

            foreach ($template->seats as $seat) {
                if (!isset($sectionIds[$seat->venue_map_section_id], $rowIds[$seat->venue_map_row_id])) {
                    continue;
                }

                EventVenueSeat::create([
                    'event_venue_map_id' => $eventVenueMap->id,
                    'event_venue_section_id' => $sectionIds[$seat->venue_map_section_id],
                    'event_venue_row_id' => $rowIds[$seat->venue_map_row_id],
                    'venue_map_seat_id' => $seat->id,
                    'event_id' => $event->id,
                    'section_name' => optional($seat->section)->name ?: 'Section',
                    'row_name' => optional($seat->row)->name ?: 'Row',
                    'seat_number' => $seat->seat_number,
                    'seat_label' => $seat->seat_label ?: trim((optional($seat->row)->name ?: 'Row') . ' Seat ' . $seat->seat_number),
                    'x_percent' => $seat->x_percent,
                    'y_percent' => $seat->y_percent,
                    'radius_percent' => $seat->radius_percent,
                    'is_accessible' => (bool) $seat->is_accessible,
                    'status' => EventVenueSeat::STATUS_AVAILABLE,
                ]);
            }
        });
    }

    public function getMonthEvent(Request $request)
    {
        (new AppHelper)->eventStatusChange();

        // Use timezone-aware date calculation
        $timezone = Setting::find(1)->timezone;
        $startOfMonth = Carbon::create($request->year, $request->month, 1, 0, 0, 0, $timezone)->format('Y-m-d H:i:s');
        $endOfMonth = Carbon::create($request->year, $request->month, 1, 0, 0, 0, $timezone)->endOfMonth()->format('Y-m-d H:i:s');

        if (Auth::user()->hasRole('Organizer')) {
            $data = Event::whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->where([['status', 1], ['is_deleted', 0]])
                ->where(function ($q) {
                    $id = Auth::user()->id;
                    $q->where('user_id', $id)
                      ->orWhereRaw('FIND_IN_SET(?, user_id)', [$id]);
                })
                ->orderBy('start_time', 'ASC')
                ->get();
        } elseif (Auth::user()->hasRole('admin')) {
            $data = Event::whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->where([['status', 1], ['is_deleted', 0]])
                ->orderBy('start_time', 'ASC')
                ->get();
        }
        foreach ($data as $value) {
            $value->tickets = $value->people ?? 0;
            // Get sold tickets count from completed orders only using OrderChild for accuracy
            $value->sold_ticket = OrderChild::whereHas('order', function ($query) use ($value) {
                $query->where('event_id', $value->id)
                      ->where('order_status', 'Complete');
            })->count();
            $value->day = $value->start_time->format('D');
            $value->date = $value->start_time->format('d');
            $value->average = $value->tickets == 0 ? 0 : round(($value->sold_ticket * 100 / $value->tickets), 2);
        }
        return response()->json(['data' => $data, 'success' => true], 200);
    }

    public function eventGallery($id)
    {
        $data  = Event::find($id);
        return view('admin.event.gallery', compact('data'));
    }

    public function addEventGallery(Request $request)
    {
        $event = array_filter(explode(',', Event::find($request->id)->gallery));
        if ($request->hasFile('file')) {
            $image = $request->file('file');
            $name = uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/images/upload');
            $image->move($destinationPath, $name);
            array_push($event, $name);
            Event::find($request->id)->update(['gallery' => implode(',', $event)]);
        }
        return true;
    }

    public function removeEventImage($image, $id)
    {

        $gallery = array_filter(explode(',', Event::find($id)->gallery));
        if (count(array_keys($gallery, $image)) > 0) {
            if (($key = array_search($image, $gallery)) !== false) {
                unset($gallery[$key]);
            }
        }
        $aa = implode(',', $gallery);
        $data = Event::find($id);
        $data->gallery = $aa;
        $data->update();
        return redirect()->back();
    }

    public function removeLogo($id, $logo)
    {
        $event = Event::findOrFail($id);
        $logos = array_filter(explode(',', $event->event_logos ?? ''));
        if (($key = array_search($logo, $logos)) !== false) {
            unset($logos[$key]);
        }
        $event->event_logos = implode(',', array_filter($logos));
        $event->save();
        return redirect()->back()->withStatus(__('Logo removed successfully.'));
    }

    public function getScanners(Request $request)
    {
        try {
            $scanners = collect();

            if (Auth::user()->hasRole('admin')) {
                $query = User::role('scanner')
                    ->where('status', 1)
                    ->orderBy('id', 'DESC')
                    ->select('id', 'first_name', 'last_name', 'email', 'org_id');

                // Filter by selected organizer IDs if provided or filter flag present
                if ($request->has('has_organizer_filter') || $request->has('organizer_ids')) {
                    $organizerIdsInput = $request->input('organizer_ids');
                    $organizerIds = [];

                    if (!empty($organizerIdsInput)) {
                        if (is_string($organizerIdsInput)) {
                            $organizerIdsInput = explode(',', $organizerIdsInput);
                        }
                        if (is_array($organizerIdsInput)) {
                            $flatIds = [];
                            foreach ($organizerIdsInput as $id) {
                                if (is_string($id) && str_contains($id, ',')) {
                                    $flatIds = array_merge($flatIds, explode(',', $id));
                                } else {
                                    $flatIds[] = $id;
                                }
                            }
                            $organizerIds = array_values(array_unique(array_filter(array_map('intval', $flatIds))));
                        }
                    }

                    if (!empty($organizerIds)) {
                        $query->whereIn('org_id', $organizerIds);
                    } else if ($request->has('has_organizer_filter')) {
                        // Organizers were cleared by admin
                        return response()->json([
                            'success' => true,
                            'scanners' => [],
                            'count' => 0
                        ]);
                    }
                }

                $scanners = $query->get()->makeHidden(['followers', 'imagePath'])->map(function ($scanner) {
                    $fullName = trim(($scanner->first_name ?? '') . ' ' . ($scanner->last_name ?? ''));
                    if (empty($fullName)) {
                        $fullName = $scanner->email ?? ('Scanner #' . $scanner->id);
                    }
                    return [
                        'id' => $scanner->id,
                        'first_name' => $scanner->first_name ?? '',
                        'last_name' => $scanner->last_name ?? '',
                        'name' => $fullName,
                        'email' => $scanner->email,
                        'org_id' => $scanner->org_id,
                    ];
                });
            } else if (Auth::user()->hasRole('Organizer')) {
                // Organizer can only see their own scanners
                $scanners = User::role('scanner')
                    ->where('org_id', Auth::user()->id)
                    ->where('status', 1)
                    ->orderBy('id', 'DESC')
                    ->select('id', 'first_name', 'last_name', 'email', 'org_id')
                    ->get()
                    ->makeHidden(['followers', 'imagePath'])
                    ->map(function ($scanner) {
                        $fullName = trim(($scanner->first_name ?? '') . ' ' . ($scanner->last_name ?? ''));
                        if (empty($fullName)) {
                            $fullName = $scanner->email ?? ('Scanner #' . $scanner->id);
                        }
                        return [
                            'id' => $scanner->id,
                            'first_name' => $scanner->first_name ?? '',
                            'last_name' => $scanner->last_name ?? '',
                            'name' => $fullName,
                            'email' => $scanner->email,
                            'org_id' => $scanner->org_id,
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'scanners' => $scanners,
                'count' => $scanners->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching scanners: ' . $e->getMessage(),
                'scanners' => []
            ], 500);
        }
    }
}
