<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Event;
use App\Models\GuestOtp;
use App\Models\GuestUser;
use App\Models\PaymentSetting;
use App\Models\Setting;
use App\Models\StripeTransaction;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;

class DonationController extends Controller
{
    /**
     * Send OTP to guest email for donation verification.
     */
    public function verifyGuestEmail(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $email = strtolower(trim($request->email));
            $otp = rand(1000, 9999);

            GuestOtp::where('email', $email)->delete();
            GuestOtp::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(10),
            ]);

            $sender = Setting::select('sender_email', 'app_name')->first();
            
            if (!$sender || !$sender->sender_email) {
                Log::warning('Donation OTP: No sender email configured, but OTP saved for: ' . $email);
                return response()->json(['success' => true, 'otp' => $otp]); // For testing when email not configured
            }

            $data = [
                'email'    => $email,
                'title'    => 'Donation Email Verification',
                'otp'      => $otp,
                'app_name' => $sender->app_name ?? 'Teptix',
            ];

            try {
                Mail::send('guestemailverify', ['data' => $data], function ($message) use ($data, $sender) {
                    $message->from($sender->sender_email, $sender->app_name ?? 'Teptix')
                        ->to($data['email'])
                        ->subject($data['title']);
                });
            } catch (\Exception $e) {
                Log::error('Donation OTP email failed: ' . $e->getMessage());
                // Still return success because OTP is cached
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Donation verifyGuestEmail error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send OTP. Please try again.'], 500);
        }
    }

    /**
     * Verify guest OTP for donation and store verified email in session.
     */
    public function verifyGuestOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp'   => 'required|numeric',
            ]);

            $email = strtolower(trim($request->email));
            $record = GuestOtp::where('email', $email)->latest()->first();

            if ($record && !$record->isExpired() && (string) $record->otp === (string) $request->otp) {
                $record->delete();
                session(['donation_guest_email' => $email]);
                return response()->json(['success' => true]);
            }

            return response()->json(['error' => 'Invalid or expired OTP.'], 422);
        } catch (\Exception $e) {
            Log::error('Donation verifyGuestOtp error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    /**
     * Create a Stripe PaymentIntent for the donation and return the client_secret.
     */
    public function createPaymentIntent(Request $request)
    {
        // App user: already logged in - allow directly
        // Guest: must have OTP-verified email matching the session
        if (!Auth::guard('appuser')->check()) {
            $guestEmail = $request->guest_email ? strtolower(trim($request->guest_email)) : null;
            if (!$guestEmail || session('donation_guest_email') !== $guestEmail) {
                return response()->json(['error' => 'Please verify your email to donate.'], 401);
            }
            $request->merge(['guest_email' => $guestEmail]);
        }

        $request->validate([
            'event_id' => 'required|integer|exists:events,id',
            'amount'   => 'required|numeric|min:1|max:99999',
        ]);

        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json(['error' => 'Payment not configured.'], 500);
        }

        try {
            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);

            $amountCents = (int) round((float) $request->amount * 100);
            $donorEmail = Auth::guard('appuser')->check()
                ? Auth::guard('appuser')->user()->email
                : $request->guest_email;
            $donorEmail = filter_var($donorEmail, FILTER_VALIDATE_EMAIL) ? $donorEmail : null;

            $paymentIntentData = [
                'amount'   => $amountCents,
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'event_id'      => $request->event_id,
                    'app_user_id'   => Auth::guard('appuser')->id() ?? '',
                    'guest_email'   => $request->guest_email ?? '',
                    'donor_email'   => $donorEmail ?? '',
                    'type'          => 'donation',
                ],
            ];

            if ($donorEmail) {
                // Makes Stripe store donor email on the PaymentIntent.
                $paymentIntentData['receipt_email'] = $donorEmail;
            }

            $paymentIntent = $stripe->paymentIntents->create($paymentIntentData);

            return response()->json([
                'client_secret'     => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Donation PaymentIntent creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to initiate payment. Please try again.'], 500);
        }
    }

    /**
     * Store the donation record after successful Stripe payment.
     */
    public function processDonation(Request $request)
    {
        // Same auth check as createPaymentIntent
        if (!Auth::guard('appuser')->check()) {
            $guestEmail = $request->guest_email ? strtolower(trim($request->guest_email)) : null;
            if (!$guestEmail || session('donation_guest_email') !== $guestEmail) {
                return response()->json(['error' => 'Please verify your email to donate.'], 401);
            }
            $request->merge(['guest_email' => $guestEmail]);
        }

        $request->validate([
            'event_id'          => 'required|integer|exists:events,id',
            'amount'            => 'required|numeric|min:1',
            'payment_intent_id' => 'required|string',
        ]);

        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json(['error' => 'Payment not configured.'], 500);
        }

        try {
            $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
            $paymentIntent = $stripe->paymentIntents->retrieve($request->payment_intent_id);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json(['error' => 'Payment not completed.'], 422);
            }

            $metadataType = strtolower((string) ($paymentIntent->metadata->type ?? ''));
            if ($metadataType !== 'donation') {
                Log::warning('Rejected non-donation PaymentIntent in donation process.', [
                    'payment_intent_id' => $paymentIntent->id,
                    'metadata_type' => $metadataType,
                ]);

                return response()->json(['error' => 'Invalid donation payment.'], 422);
            }

            // Prevent duplicate donations for the same PaymentIntent
            $existing = Donation::where('payment_intent_id', $request->payment_intent_id)->first();
            if ($existing) {
                return response()->json([
                    'success'     => true,
                    'donation_id' => $existing->donation_id,
                    'message'     => 'Donation already recorded.',
                ]);
            }

            $txnId = null;
            if ($paymentIntent->latest_charge) {
                try {
                    $charge = $stripe->charges->retrieve($paymentIntent->latest_charge);
                    if ($charge->balance_transaction) {
                        $balanceTxn = $stripe->balanceTransactions->retrieve($charge->balance_transaction);
                        $txnId = $balanceTxn->id;
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve balance transaction for donation: ' . $e->getMessage());
                }
            }

            // Resolve guest_user_id for unauthenticated donors
            $guestUserId = null;
            if (!Auth::guard('appuser')->check() && $request->guest_email) {
                $guestUser = GuestUser::firstOrCreate(
                    ['email' => $request->guest_email],
                    ['name' => explode('@', $request->guest_email)[0], 'last_name' => '', 'phone' => '']
                );
                $guestUserId = $guestUser->id;
            }

            // Create donation record
            $donation = Donation::create([
                'app_user_id'       => Auth::guard('appuser')->id(),
                'guest_user_id'     => $guestUserId,
                'event_id'          => $request->event_id,
                'amount'            => $request->amount,
                'transaction_id'    => $txnId,
                'payment_intent_id' => $paymentIntent->id,
                'status'            => 'completed',
            ]);

            // Store in transaction_stripe table
            StripeTransaction::create([
                'payment_id'           => $paymentIntent->id,
                'order_id'             => null,
                'donation_id'          => $donation->donation_id,
                'amount'               => $paymentIntent->amount / 100,
                'client_secret'        => $paymentIntent->client_secret,
                'currency'             => $paymentIntent->currency,
                'latest_charge'        => $paymentIntent->latest_charge ?? null,
                'txn_id'               => $txnId,
                'payment_method_types' => $paymentIntent->payment_method_types ?? [],
                'status'               => $paymentIntent->status,
                'full_response'        => json_decode(json_encode($paymentIntent), true),
            ]);

            // Send donation receipt email with attached PDF invoice to the donor.
            $sender = Setting::select('sender_email', 'app_name', 'currency_sybmol')->first();
            $donorEmail = Auth::guard('appuser')->check()
                ? Auth::guard('appuser')->user()->email
                : ($guestUser->email ?? $request->guest_email);
            $donorName = Auth::guard('appuser')->check()
                ? trim(Auth::guard('appuser')->user()->name . ' ' . Auth::guard('appuser')->user()->last_name)
                : trim(($guestUser->name ?? explode('@', $request->guest_email)[0]) . ' ' . ($guestUser->last_name ?? ''));
            $currency = $sender->currency_sybmol ?? '$';

            try {
                $pdf = FacadePdf::loadView('donation.invoice', compact('donation', 'donorName', 'currency', 'sender'))
                    ->setPaper('a4', 'portrait');

                Mail::send('donation.mail', compact('donation', 'donorName', 'currency', 'sender'), function ($message) use ($sender, $donorEmail, $donorName, $pdf) {
                    $message->from($sender->sender_email, $sender->app_name ?? 'Teptix')
                        ->to($donorEmail)
                        ->subject('Donation Receipt - ' . ($donorName ?: 'Donor'))
                        ->attachData($pdf->output(), 'donation_invoice.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                });

                Mail::send('donation.thankyou', compact('donation', 'donorName', 'currency', 'sender'), function ($message) use ($sender, $donorEmail, $donorName) {
                    $message->from($sender->sender_email, $sender->app_name ?? 'Teptix')
                        ->to($donorEmail)
                        ->subject('Thank you for your donation - ' . ($donorName ?: 'Donor'));
                });
            } catch (\Exception $e) {
                Log::error('Donation email sending failed: ' . $e->getMessage());
            }

            return response()->json([
                'success'     => true,
                'donation_id' => $donation->donation_id,
                'message'     => 'Thank you for your donation!',
            ]);
        } catch (\Exception $e) {
            Log::error('Donation processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process donation. Please contact support.'], 500);
        }
    }
}
