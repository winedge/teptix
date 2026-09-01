<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\User;
use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    private const ORGANIZER_COUPON_ABILITIES = [
        'coupon_access',
        'coupon_create',
        'coupon_edit',
    ];

    private function authorizeCouponAbility(string $ability): void
    {
        if (Auth::user()->hasRole('Organizer') && in_array($ability, self::ORGANIZER_COUPON_ABILITIES)) {
            return;
        }

        abort_if(Gate::denies($ability), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function organizerEventQuery()
    {
        return Event::where('is_deleted', 0)
            ->where('status', 1)
            ->whereRaw('FIND_IN_SET(?, user_id)', [Auth::id()]);
    }

    private function eventQuery()
    {
        if (Auth::user()->hasRole('admin')) {
            return Event::where('is_deleted', 0)->where('status', 1);
        }

        if (Auth::user()->hasRole('Organizer')) {
            return $this->organizerEventQuery();
        }

        abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function organizerCouponQuery()
    {
        return Coupon::with(['event'])
            ->whereRaw('FIND_IN_SET(?, user_id)', [Auth::id()]);
    }

    private function ensureOrganizerCanManageCoupon(Coupon $coupon): void
    {
        if (Auth::user()->hasRole('Organizer') && !in_array(Auth::id(), array_filter(array_map('intval', explode(',', (string) $coupon->user_id))))) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }
    }

    private function findManageableEvent(int $eventId): Event
    {
        return $this->eventQuery()->where('id', $eventId)->firstOrFail();
    }

    public function index(Request $request)
    {
        $this->authorizeCouponAbility('coupon_access');
        $organizers = collect();
        $selectedOrganizer = $request->input('organizer_id');

        if(Auth::user()->hasRole('admin')){
            $organizers = User::role('Organizer')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            $couponQuery = Coupon::with(['event']);

            if ($selectedOrganizer) {
                $couponQuery->whereRaw('FIND_IN_SET(?, user_id)', [(int) $selectedOrganizer]);
            }

            $coupon = $couponQuery->OrderBy('id','DESC')->get();
        }
        elseif(Auth::user()->hasRole('Organizer')){
            $coupon = $this->organizerCouponQuery()->OrderBy('id','DESC')->get();
        } else {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        return view('admin.coupon.index', compact('coupon', 'organizers', 'selectedOrganizer'));
    }

    public function create()
    {
        $this->authorizeCouponAbility('coupon_create');
        $event = $this->eventQuery()->orderBy('id','DESC')->get();

        return view('admin.coupon.create', compact('event'));
    }

    public function store(Request $request)
    {
        $this->authorizeCouponAbility('coupon_create');

        $request->validate([
            'name' => 'bail|required',
            'event_id' => 'bail|required',
            'discount' => 'bail|required',
            'discount_type'=>'bail|required',
            'start_date' => 'bail|required',
            'end_date' => 'bail|required',
            'max_use' => 'bail|required',
            'description' => 'bail|required',
            'minimum_amount'=> 'bail|required',
            'maximum_discount' => 'bail|required',
            'max_use_per_user' => 'bail|required'
        ]);
        $event = $this->findManageableEvent((int) $request->event_id);
        $data = $request->all();
        $data['coupon_code'] =  chr(rand(65,90)).chr(rand(65,90)).'-'.rand(9999,100000);
        $data['user_id']= $event->user_id;

        $coupon = Coupon::create($data);
        AdminActivityLog::record(
            AdminActivityLog::PROMOTION_CREATED,
            $coupon,
            __('Promotion created'),
            __(':name coupon promotion was created for :event.', ['name' => $coupon->name, 'event' => $event->name]),
            [
                'promotion_kind' => 'coupon',
                'coupon_id' => $coupon->id,
                'coupon_name' => $coupon->name,
                'coupon_code' => $coupon->coupon_code,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'discount' => $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'start_date' => $coupon->start_date,
                'end_date' => $coupon->end_date,
                'organizer_ids' => $event->user_id,
            ],
            $request
        );
        return redirect()->route('coupon.index')->withStatus(__('Coupon has added successfully.'));
    }

    public function edit(Coupon $coupon)
    {
        $this->authorizeCouponAbility('coupon_edit');
        $this->ensureOrganizerCanManageCoupon($coupon);
        $event = $this->eventQuery()->orderBy('id','DESC')->get();

        return view('admin.coupon.edit', compact( 'coupon','event'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->authorizeCouponAbility('coupon_edit');

        $request->validate([
            'name' => 'bail|required',
            'event_id' => 'bail|required',
            'discount' => 'bail|required',
            'discount_type'=>'bail|required',
            'start_date' => 'bail|required',
            'end_date' => 'bail|required',
            'max_use' => 'bail|required',
            'description' => 'bail|required',
            'minimum_amount'=> 'bail|required',
            'maximum_discount' => 'bail|required',
            'max_use_per_user' => 'bail|required'
        ]);
        $this->ensureOrganizerCanManageCoupon($coupon);
        $event = $this->findManageableEvent((int) $request->event_id);
        $data = $request->all();
        $data['user_id']= $event->user_id;

        Coupon::find($coupon->id)->update($data);
        return redirect()->route('coupon.index')->withStatus(__('Coupon has update successfully.'));
    }

    public function destroy(Coupon $coupon)
    {
        abort_if(Gate::denies('coupon_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->ensureOrganizerCanManageCoupon($coupon);

        try{
            $coupon->delete();
            return true;
        }catch(Throwable $th){

            return response('Data is Connected with other Data', 400);
        }
    }
}
