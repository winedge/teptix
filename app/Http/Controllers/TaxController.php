<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\OrganizerTax;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Mail\TaxApproved;
use App\Mail\TaxRejected;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use Throwable;

class TaxController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('tax_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');
        $organizerTaxes = collect();

        if ($isOrganizer) {
            $usertype = 'org';
            $taxes = OrganizerTax::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
            $pendingCount = 0;
        } else {
            $usertype = 'admin';
            // Admin sees all global taxes
            $taxes = Tax::orderBy('id', 'DESC')->get();
            // Admin also sees all organizer tax requests
            $organizerTaxes = OrganizerTax::orderBy('id', 'DESC')->with(['user', 'ticket.event'])->get();
            $pendingCount = OrganizerTax::where('approval_status', 'pending')->count();
        }

        return view('admin.tax.index', compact('taxes', 'usertype', 'pendingCount', 'organizerTaxes'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('tax_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');
        $tickets = collect();

        if ($isOrganizer) {
            $organizerId = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
            $eventIds = Event::whereRaw('FIND_IN_SET(?, user_id)', [$organizerId])->pluck('id');
            $tickets = Ticket::whereIn('event_id', $eventIds)->where('is_deleted', 0)->with('event')->get();
        }

        return view('admin.tax.create', compact('tickets', 'isOrganizer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'bail|required',
            'price' => 'bail|required',
            'amount_type' => 'required|in:price,percentage',
        ]);

        $data = $request->all();
        if (!isset($request->allow_all_bill)) {
            $data['allow_all_bill'] = 0;
        }

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');

        if ($isOrganizer) {
            // Organizer: Create record in organizer_taxes table (pending approval)
            OrganizerTax::create([
                'user_id' => Auth::user()->id,
                'ticket_id' => $request->ticket_id,
                'name' => $data['name'],
                'amount_type' => $data['amount_type'],
                'price' => $data['price'],
                'status' => 0, // inactive until approved
                'allow_all_bill' => $data['allow_all_bill'],
                'approval_status' => 'pending',
            ]);

            return redirect()->route('tax.index')->withStatus(__('Tax request has been submitted for admin approval. You will receive an email once it is reviewed.'));
        } else {
            // Admin: Direct insertion into the tax table (auto active)
            $data['created_by'] = 0;
            $data['status'] = 1;
            Tax::create($data);

            return redirect()->route('tax.index')->withStatus(__('Tax has been added successfully.'));
        }
    }

    public function edit($id)
    {
        abort_if(Gate::denies('tax_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');
        $tickets = collect();

        if ($isOrganizer) {
            $tax = OrganizerTax::findOrFail($id);
            $organizerId = Auth::user()->hasRole('Manager') ? Auth::user()->org_id : Auth::user()->id;
            $eventIds = Event::whereRaw('FIND_IN_SET(?, user_id)', [$organizerId])->pluck('id');
            $tickets = Ticket::whereIn('event_id', $eventIds)->where('is_deleted', 0)->with('event')->get();
        } else {
            $tax = Tax::findOrFail($id);
        }

        return view('admin.tax.edit', compact('tax', 'tickets', 'isOrganizer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'bail|required',
            'price' => 'bail|required',
            'amount_type' => 'required|in:price,percentage',
        ]);

        $data = $request->all();
        if (!isset($request->allow_all_bill)) {
            $data['allow_all_bill'] = 0;
        }

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');

        if ($isOrganizer) {
            $tax = OrganizerTax::findOrFail($id);
            $tax->update([
                'name' => $data['name'],
                'amount_type' => $data['amount_type'],
                'price' => $data['price'],
                'allow_all_bill' => $data['allow_all_bill'],
                'ticket_id' => $request->ticket_id,
                'approval_status' => 'pending', // Re-evaluate on edit
                'status' => 0,
            ]);
        } else {
            $tax = Tax::findOrFail($id);
            if (Auth::user()->hasRole('Organizer')) {
                $data['updated_by'] = 1;
            } else {
                $data['updated_by'] = 0;
            }
            $tax->update($data);
        }

        return redirect()->route('tax.index')->withStatus(__('Tax has been updated successfully.'));
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('tax_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isOrganizer = Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager');

        try {
            if ($isOrganizer) {
                $tax = OrganizerTax::findOrFail($id);
                if ($tax->tax_id) {
                    Tax::where('id', $tax->tax_id)->delete();
                }
                $tax->delete();
            } else {
                Tax::findOrFail($id)->delete();
            }
            return true;
        } catch (Throwable $th) {
            return response('Data is Connected with other Data', 400);
        }
    }

    public function setdefault($id)
    {
        $data['is_default'] = 0;
        Tax::query()->update($data);
        $data['is_default'] = 1;
        Tax::find($id)->update($data);
        return response()->json(['msg' => 'success']);
    }

    /**
     * Admin approves organizer's tax request.
     */
    public function approve(OrganizerTax $organizerTax)
    {
        abort_if(!Auth::user()->hasRole('admin'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // 1. Create entry in main tax table
        $tax = Tax::create([
            'user_id' => $organizerTax->user_id,
            'name' => $organizerTax->name,
            'amount_type' => $organizerTax->amount_type,
            'price' => $organizerTax->price,
            'status' => 1, // Approved taxes are active
            'allow_all_bill' => $organizerTax->allow_all_bill,
            'is_default' => 0,
            'created_by' => 1, // Organizer created
        ]);

        // 2. Link to selected ticket if any
        if ($organizerTax->ticket_id) {
            $ticket = Ticket::find($organizerTax->ticket_id);
            if ($ticket) {
                $existingTaxIds = array_filter(explode(',', (string)$ticket->tax_id));
                if (!in_array($tax->id, $existingTaxIds)) {
                    $existingTaxIds[] = $tax->id;
                }
                $ticket->tax_id = implode(',', $existingTaxIds);
                $ticket->save();
            }
        }

        // 3. Update organizerTax request state
        $organizerTax->update([
            'approval_status' => 'approved',
            'status' => 1,
            'tax_id' => $tax->id,
            'rejection_reason' => null,
        ]);

        // 4. Send approval email
        $user = User::find($organizerTax->user_id);
        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new TaxApproved($organizerTax));
            } catch (Throwable $th) {
                // Fail silently
            }
        }

        return redirect()->route('tax.index')->withStatus(__('Tax approved successfully and linked to ticket. Organizer notified.'));
    }

    /**
     * Admin rejects organizer's tax request.
     */
    public function reject(Request $request, OrganizerTax $organizerTax)
    {
        abort_if(!Auth::user()->hasRole('admin'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $organizerTax->update([
            'approval_status' => 'rejected',
            'status' => 0,
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Send rejection email
        $user = User::find($organizerTax->user_id);
        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new TaxRejected($organizerTax));
            } catch (Throwable $th) {
                // Fail silently
            }
        }

        return redirect()->route('tax.index')->withStatus(__('Tax request has been rejected. Organizer notified.'));
    }
}
