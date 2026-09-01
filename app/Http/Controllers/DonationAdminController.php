<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Event;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonationAdminController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $query = Donation::with('event', 'appUser', 'guestUser');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // Support searching by don_N format
            if (preg_match('/^don_(\d+)$/i', $search, $m)) {
                $query->where('id', $m[1]);
            } else {
                // Match donor name via app_user or guest_user
                $appUserIds = \App\Models\AppUser::where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->pluck('id');
                $guestUserIds = \App\Models\GuestUser::where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->pluck('id');

                $query->where(function ($q) use ($search, $appUserIds, $guestUserIds) {
                    $q->where('payment_intent_id', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhereIn('app_user_id', $appUserIds)
                      ->orWhereIn('guest_user_id', $guestUserIds);
                });
            }
        }

        if ($request->filled('duration')) {
            $dates = explode(' - ', $request->duration);
            if (count($dates) === 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay(),
                ]);
            }
        }

        $donations = $query->orderBy('id', 'DESC')->get();

        // Donations are stored with status = "completed" in DonationController.
        // Older data / other flows may use "succeeded".
        $totalAmount = (clone $query)
            ->whereIn('status', ['completed', 'succeeded'])
            ->sum('amount');

        $events = Event::orderBy('start_time', 'DESC')->get();
        $currency = Setting::first()->currency ?? '$';

        if ($request->filled('export')) {
            return $this->export($donations, $request->export, $currency);
        }

        return view('admin.donation.index', compact('donations', 'events', 'totalAmount', 'currency', 'request'));
    }

    public function show($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $donation = Donation::with('event', 'appUser', 'guestUser')->findOrFail($id);
        $currency = Setting::first()->currency;

        return view('admin.donation.view', compact('donation', 'currency'));
    }

    private function export($donations, $format, $currency)
    {
        if ($format === 'csv') {
            $filename = 'donations_' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];
            $callback = function () use ($donations, $currency) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['#', 'Donation ID', 'Event', 'Customer', 'Amount', 'Status', 'Payment Intent ID', 'Transaction ID', 'Date']);
                foreach ($donations as $i => $d) {
                    $customer = $d->appUser
                        ? $d->appUser->name . ' ' . $d->appUser->last_name
                        : ($d->guestUser ? $d->guestUser->name . ' ' . $d->guestUser->last_name : 'Guest');
                    fputcsv($handle, [
                        $i + 1,
                        'don_' . $d->id,
                        $d->event->name ?? 'N/A',
                        $customer,
                        $currency . number_format($d->amount, 2),
                        ucfirst($d->status),
                        $d->payment_intent_id,
                        $d->transaction_id ?? 'N/A',
                        $d->created_at->format('d M Y H:i'),
                    ]);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
        }

        // PDF export
        $data = ['donations' => $donations, 'currency' => $currency, 'date' => date('d M Y')];
        $pdf = FacadePdf::loadView('admin.donation.pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('donations_' . date('Y-m-d') . '.pdf');
    }
}
