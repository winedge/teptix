<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use App\Models\Event;
use App\Models\EventFee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class FeeController extends Controller
{
    // --- Fee Type Actions ---

    public function index()
    {
        abort_if(Gate::denies('tax_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $fees = FeeType::orderBy('id', 'DESC')->get();
        return view('admin.fee.index', compact('fees'));
    }

    public function create()
    {
        abort_if(Gate::denies('tax_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.fee.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'price' => 'bail|required|numeric|min:0',
            'amount_type' => 'required|in:price,percentage',
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $data['created_by'] = Auth::user()->id;

        FeeType::create($data);

        return redirect()->route('fee-type.index')->withStatus(__('Fee type added successfully.'));
    }

    public function edit($id)
    {
        abort_if(Gate::denies('tax_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $fee = FeeType::findOrFail($id);
        return view('admin.fee.edit', compact('fee'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'bail|required',
            'price' => 'bail|required|numeric|min:0',
            'amount_type' => 'required|in:price,percentage',
        ]);

        $fee = FeeType::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = Auth::user()->id;

        $fee->update($data);

        return redirect()->route('fee-type.index')->withStatus(__('Fee type updated successfully.'));
    }

    public function setdefault($id)
    {
        FeeType::query()->update(['is_default' => 0]);
        FeeType::find($id)->update(['is_default' => 1]);
        return response()->json(['msg' => 'success']);
    }

    // --- Event Fee Mapping Actions ---

    public function eventFees()
    {
        abort_if(Gate::denies('tax_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $events = Event::where('created_at', '>=', '2026-08-17 00:00:00')->orderBy('id', 'DESC')->get();

        foreach ($events as $event) {
            $assignedFeeIds = EventFee::where('event_id', $event->id)->pluck('fee_type_id')->toArray();
            $event->assigned_fees = FeeType::whereIn('id', $assignedFeeIds)->pluck('name')->toArray();
        }

        return view('admin.fee.event_fees', compact('events'));
    }

    public function editEventFees($id)
    {
        abort_if(Gate::denies('tax_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $event = Event::findOrFail($id);
        $fees = FeeType::where('status', 1)->orderBy('id', 'DESC')->get();
        $assignedFeeIds = EventFee::where('event_id', $event->id)->pluck('fee_type_id')->toArray();

        return view('admin.fee.edit_event_fees', compact('event', 'fees', 'assignedFeeIds'));
    }

    public function updateEventFees(Request $request, $id)
    {
        abort_if(Gate::denies('tax_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $event = Event::findOrFail($id);

        EventFee::where('event_id', $event->id)->delete();

        if ($request->has('fee_ids')) {
            foreach ($request->fee_ids as $feeId) {
                EventFee::create([
                    'event_id' => $event->id,
                    'fee_type_id' => $feeId,
                ]);
            }
        }

        return redirect()->route('event-fee.index')->withStatus(__('Event fees updated successfully.'));
    }
}
