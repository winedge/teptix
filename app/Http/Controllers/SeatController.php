<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sponsership;
use App\Models\SeatTable;

class SeatController extends Controller
{
    // Show form to create a new Seat
    public function createSeat()
    {
        $sponsers = Sponsership::all();
        return view('admin.Seat.createSeat', compact('sponsers'));
    }

    // Store new Seat
    public function storeSeat(Request $request)
    {
        $request->validate([
            'name_of_table' => 'required',
            'number_seat' => 'required|integer',
            'sponsership_id' => 'nullable|exists:sponsership,id',
            'prefixname' => 'required'
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        SeatTable::create($data);

        return redirect()->route('admin.Seat.view')->with('success', 'Seat Table Created!');
    }

    // Show form to create a new Sponsership
    public function createSponser()
    {
        return view('admin.Seat.createSponser');
    }

    // Store Sponsership
    public function storeSponser(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'details' => 'nullable'
        ]);

        Sponsership::create($request->all());

        return redirect()->route('admin.Seat.view')->with('success', 'Sponsership Created!');
    }

    // View all seats and sponsers
    public function view()
    {
        $seats = SeatTable::with('sponsership')->get();
        $sponsers = Sponsership::all();
        return view('admin.Seat.view', compact('seats', 'sponsers'));
    }

    // Delete Seat Table
    public function deleteSeat($id)
    {
        $seat = SeatTable::findOrFail($id);
        $seat->delete();

        return redirect()->route('admin.Seat.view')->with('success', 'Seat Table Deleted Successfully!');
    }

    // Delete Sponsership
    public function deleteSponser($id)
    {
        $sponser = Sponsership::findOrFail($id);
        
        // Check if sponsership has associated seat tables
        if ($sponser->seatTables()->count() > 0) {
            return redirect()->route('admin.Seat.view')->with('error', 'Cannot delete sponsership. It has associated seat tables!');
        }
        
        $sponser->delete();

        return redirect()->route('admin.Seat.view')->with('success', 'Sponsership Deleted Successfully!');
    }
}
