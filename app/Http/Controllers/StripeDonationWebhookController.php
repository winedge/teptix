<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\GuestUser;
use App\Models\PaymentSetting;
use App\Models\StripeTransaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Webhook;

class StripeDonationWebhookController extends Controller
{

    private const PAYMENT_LINK_ID = 'plink_1TdSNcKzSXP4o2z9jfCnQOK8';

    public function handle(Request $request)
    {
        $rawBody = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        if (empty($webhookSecret)) {
            $this->logDaily('error', [
                'error' => 'Missing STRIPE_WEBHOOK_SECRET',
            ]);
            return response()->json(['success' => false, 'message' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($rawBody, $sigHeader, $webhookSecret);
        } catch (\Throwable $e) {
            $this->logDaily('error', [
                'error' => 'Webhook signature verification failed',
                'stripe_message' => $e->getMessage(),
                'received_signature' => $sigHeader,
            ], $rawBody);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        // Always log the raw event (and some summary)
        $this->logDaily('event', [
            'type' => $event->type,
            'id' => $event->id,
            'created' => $event->created,
        ], $rawBody);

        try {
            $payloadObject = $event->data->object;

            // We primarily care about PaymentIntent succeeded events.
            // Some setups might also send payment_link / checkout session events.
            $paymentIntentId = $payloadObject->id ?? null;
            if ($event->type === 'payment_intent.succeeded') {
                $this->handlePaymentIntentSucceeded($paymentIntentId, $payloadObject);
            } elseif ($event->type === 'checkout.session.completed') {
                // If your checkout uses payment links, you might get session completion events.
                // Try to recover payment_intent from the session.
                $paymentIntentId = $payloadObject->payment_intent ?? null;

                // Dedicated payment log for checkout.session.completed
                $paymentLinkFromSession = $payloadObject->payment_link ?? null;

                // Some payloads may not directly include payment_link; log best-effort fields.
                $this->logDaily('info', [
                    'message' => 'checkout.session.completed received',
                    'type' => $event->type,
                    'event_id' => $event->id,
                    'payment_intent' => $paymentIntentId,
                    'payment_link' => $paymentLinkFromSession,
                    'payment_link_matches_expected' => ($paymentLinkFromSession === self::PAYMENT_LINK_ID),
                    'session_metadata' => $payloadObject->metadata ?? null,
                    'raw_object_keys' => is_object($payloadObject) ? array_keys(get_object_vars($payloadObject)) : [],
                ], $rawBody);


                $this->handlePaymentIntentSucceeded($paymentIntentId, $payloadObject);
            } else {
                // For any other events we only log and return 200.
                $this->logDaily('ignored', [
                    'event_type' => $event->type,
                    'payment_intent_id_guess' => $paymentIntentId,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->logDaily('error', [
                'error' => 'Webhook processing failed',
                'stripe_event_id' => $event->id,
                'stripe_event_type' => $event->type,
                'message' => $e->getMessage(),
            ], $rawBody);
            return response()->json(['success' => false, 'message' => 'Webhook processing failed'], 500);
        }
    }

    private function handlePaymentIntentSucceeded($paymentIntentId, $paymentIntentOrPayload)
    {
        if (empty($paymentIntentId)) {
            $this->logDaily('warn', ['message' => 'No payment_intent id available in webhook payload']);
            return;
        }

        // Idempotency: avoid recording donation twice.
        $already = Donation::where('payment_intent_id', $paymentIntentId)->first();
        if ($already) {
            $this->logDaily('info', ['message' => 'Donation already exists for payment_intent_id', 'payment_intent_id' => $paymentIntentId, 'donation_id' => $already->donation_id ?? $already->id]);
            return;
        }

        // Extract metadata (best effort)
        $metadata = $paymentIntentOrPayload->metadata ?? (property_exists($paymentIntentOrPayload, 'metadata') ? $paymentIntentOrPayload->metadata : []);
        $eventId = $this->metadataValue($metadata, 'event_id');
        $guestEmail = $this->metadataValue($metadata, 'guest_email');
        $guestName = $this->metadataValue($metadata, 'guest_name');
        $guestMobile = $this->metadataValue($metadata, 'guest_mobile');
        $paymentLinkId = $this->metadataValue($metadata, 'payment_link_id');
        $metadataType = strtolower((string) $this->metadataValue($metadata, 'type', ''));
        $venueSeatIds = $this->metadataValue($metadata, 'venue_seat_ids');

        // Some payloads provide payment link in the checkout session object (not metadata).
        // If metadata is empty, try to recover payment_link_id from known fields.
        if (empty($paymentLinkId) && isset($paymentIntentOrPayload->payment_link)) {
            $paymentLinkId = $paymentIntentOrPayload->payment_link;
        }

        $linkMatches = ($paymentLinkId === self::PAYMENT_LINK_ID);
        $isKnownTicketPayment = in_array($metadataType, ['event_ticket', 'ticket', 'seat_booking', 'order'], true)
            || !empty($venueSeatIds);

        if ($isKnownTicketPayment || ($metadataType !== 'donation' && !$linkMatches)) {
            $this->logDaily('ignored', [
                'message' => 'Ignoring non-donation Stripe payment in donation webhook',
                'payment_intent_id' => $paymentIntentId,
                'metadata_type' => $metadataType,
                'event_id' => $eventId,
                'has_venue_seats' => !empty($venueSeatIds),
                'payment_link_id' => $paymentLinkId,
            ]);
            return;
        }

        // If payment_intent came from checkout, also try to infer guest details from checkout payload style.
        if (empty($guestEmail) && isset($paymentIntentOrPayload->customer_details->email)) {
            $guestEmail = $paymentIntentOrPayload->customer_details->email;
        }
        if (empty($guestName) && isset($paymentIntentOrPayload->customer_details->name)) {
            $guestName = $paymentIntentOrPayload->customer_details->name;
        }
        if (empty($guestMobile) && isset($paymentIntentOrPayload->customer_details->phone)) {
            $guestMobile = $paymentIntentOrPayload->customer_details->phone;
        }


        // If webhook came without link metadata, we still may be able to infer PaymentLink by metadata you store.
        // Your requirement: only record donation in guest_user when payment_link_id matches.
        // For "without payment link" donations: record as well (requirement says record all webhook, and then conditional donation logic).
        // We'll treat it as:
        // - Always log
        // - Always create donation
        // - But guest_user population is driven by payment link id match (else minimal guest creation).

        // Resolve guest user (if possible)
        $guestUserId = null;
        $resolvedEmail = $guestEmail ?: ($paymentIntentOrPayload->receipt_email ?? null);

        if (!empty($resolvedEmail)) {
            $nameForUser = $guestName;
            if (empty($nameForUser)) {
                $nameForUser = explode('@', $resolvedEmail)[0];
            }

            $guest = GuestUser::firstOrCreate(
                ['email' => $resolvedEmail],
                [
                    'name' => $nameForUser,
                    'last_name' => '',
                    'phone' => $guestMobile ?: '',
                ]
            );

            // If link matches and mobile/name exist, update them
            if ($linkMatches) {
                $update = [];
                if (!empty($guestMobile)) {
                    $update['phone'] = $guestMobile;
                }
                if (!empty($guestName)) {
                    $update['name'] = $guestName;
                    // last_name not provided separately in metadata; keep existing
                }
                if (!empty($update)) {
                    $guest->update($update);
                }
            }

            $guestUserId = $guest->id;
        }

        // Amount, client_secret, latest_charge, txn_id, tax_amount.
        // Handle both PaymentIntent-like and Checkout Session-like payloads.
        $isCheckoutSession = isset($paymentIntentOrPayload->object) && ($paymentIntentOrPayload->object === 'checkout.session');

        $amount = 0.00;
        $taxAmount = 0.00;

        $clientSecret = $paymentIntentOrPayload->client_secret ?? null;
        $latestChargeId = $paymentIntentOrPayload->latest_charge ?? null;

        // Extract from payload
        if ($isCheckoutSession) {
            $amountCents = $paymentIntentOrPayload->amount_total ?? $paymentIntentOrPayload->amount_subtotal ?? null;
            if (!empty($amountCents)) {
                $amount = ((int) $amountCents) / 100;
            }

            $taxCents = $paymentIntentOrPayload->total_details->amount_tax ?? null;
            if (!empty($taxCents)) {
                $taxAmount = ((int) $taxCents) / 100;
            }
        } else {
            $amountCents = $paymentIntentOrPayload->amount_received ?? $paymentIntentOrPayload->amount ?? null;
            if (!empty($amountCents)) {
                $amount = ((int) $amountCents) / 100;
            }

            $piTaxCents = $paymentIntentOrPayload->amount_details->tax->total_tax_amount
                ?? $paymentIntentOrPayload->amount_details->tax->total_tax_amount
                ?? null;
            if (!empty($piTaxCents)) {
                $taxAmount = ((int) $piTaxCents) / 100;
            }
        }

        // If checkout.session payload, fetch the PaymentIntent to populate missing fields.
        if ($isCheckoutSession) {
            try {
                $paymentSetting = PaymentSetting::first();
                if ($paymentSetting && !empty($paymentSetting->stripeSecretKey)) {
                    $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
                    $pi = $stripe->paymentIntents->retrieve($paymentIntentId);

                    $clientSecret = $pi->client_secret ?? $clientSecret;
                    $latestChargeId = $pi->latest_charge ?? $latestChargeId;

                    $piAmountCents = $pi->amount_received ?? $pi->amount ?? null;
                    if (!empty($piAmountCents)) {
                        $amount = ((int) $piAmountCents) / 100;
                    }

                    $piTaxCents = $pi->amount_details->tax->total_tax_amount
                        ?? null;
                    if (!empty($piTaxCents)) {
                        $taxAmount = ((int) $piTaxCents) / 100;
                    }
                }
            } catch (\Throwable $e) {
                $this->logDaily('warn', [
                    'message' => 'Could not fetch PaymentIntent for checkout.session.completed',
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Resolve txn_id (balance transaction id) from latest_charge
        $transactionId = null;
        if (!empty($latestChargeId)) {
            try {
                $paymentSetting = PaymentSetting::first();
                if ($paymentSetting && !empty($paymentSetting->stripeSecretKey)) {
                    $stripe = new \Stripe\StripeClient($paymentSetting->stripeSecretKey);
                    $charge = $stripe->charges->retrieve($latestChargeId);
                    if ($charge && !empty($charge->balance_transaction)) {
                        $balanceTxn = $stripe->balanceTransactions->retrieve($charge->balance_transaction);
                        $transactionId = $balanceTxn->id ?? null;
                    }
                }
            } catch (\Throwable $e) {
                $this->logDaily('warn', [
                    'message' => 'Could not resolve Stripe transaction id for donation',
                    'payment_intent_id' => $paymentIntentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }




        // Event id is required in donations.
        // Since your Stripe metadata is always empty, we must decide event_id from other fields.
        // For now, when payment link matches the expected one, we hardcode the event_id=46 as you described.
        if (empty($eventId)) {
            if ($linkMatches) {
                $eventId = 46;
                $this->logDaily('info', [
                    'message' => 'event_id missing in Stripe metadata; using fallback event_id=46 because payment_link matched',
                    'payment_intent_id' => $paymentIntentId,
                    'payment_link_id' => $paymentLinkId,
                    'guest_email' => $resolvedEmail,
                ]);
            } else {
                $this->logDaily('warn', [
                    'message' => 'Missing event_id metadata for donation record and payment_link did not match expected link',
                    'payment_intent_id' => $paymentIntentId,
                    'payment_link_id' => $paymentLinkId,
                    'guest_email' => $resolvedEmail,
                ]);
                return;
            }
        }

        // Create Donation record
        $donation = Donation::create([
            'app_user_id' => null, // unknown for webhook
            'guest_user_id' => $guestUserId,
            'event_id' => (int) $eventId,
            'amount' => $amount,
            'payment_intent_id' => $paymentIntentId,
            'transaction_id' => $transactionId,
            'status' => 'completed',
        ]);

        // Create Stripe transaction record for donations page UI
        // Donation pages depend on transaction_stripe rows.
        if ($donation) {
            StripeTransaction::create([
                'payment_id' => $paymentIntentId,
                'order_id' => null,
                // Admin view loads transaction by don_{$donation->id}
                'donation_id' => $donation->donation_id ?? ('don_' . $donation->id),
                'amount' => $amount,
                'client_secret' => $clientSecret,
                'currency' => $paymentIntentOrPayload->currency ?? 'usd',
                'latest_charge' => $latestChargeId,
                // Use resolved balance transaction id
                'txn_id' => $transactionId,
                'tax_amount' => $taxAmount,
                'payment_method_types' => $paymentIntentOrPayload->payment_method_types ?? [],
                'status' => $paymentIntentOrPayload->status ?? 'succeeded',
                'full_response' => json_decode(json_encode($paymentIntentOrPayload), true),
            ]);

            // Debug log: verify what we stored
            $this->logDaily('info', [
                'message' => 'StripeTransaction inserted for donation',
                'donation_id' => $donation->donation_id ?? ('don_' . $donation->id),
                'payment_id' => $paymentIntentId,
                'amount' => $amount,
                'client_secret' => $paymentIntentOrPayload->client_secret ?? null,
                'latest_charge' => $paymentIntentOrPayload->latest_charge ?? null,
                'txn_id' => $transactionId,
                'status' => $paymentIntentOrPayload->status ?? 'succeeded'
            ]);

        }


        // Ensure status is marked success as per your expectation.

        // If you store statuses like 'success' in UI, you can switch value.
        if ($donation && $donation->status !== 'completed') {
            $donation->status = 'completed';
            $donation->save();
        }


        $this->logDaily('created', [
            'payment_intent_id' => $paymentIntentId,
            'donation_id' => $donation->donation_id ?? $donation->id,
            'payment_link_id' => $paymentLinkId,
            'link_matches' => $linkMatches,
            'guest_user_id' => $guestUserId,
        ]);
    }

    private function metadataValue($metadata, string $key, $default = null)
    {
        if (is_array($metadata)) {
            return $metadata[$key] ?? $default;
        }

        if ($metadata instanceof \ArrayAccess && isset($metadata[$key])) {
            return $metadata[$key];
        }

        if (is_object($metadata) && isset($metadata->{$key})) {
            return $metadata->{$key};
        }

        return $default;
    }

    private function logDaily(string $level, array $data, ?string $rawBody = null): void
    {
        $date = now()->format('Y-m-d');
        $dir = storage_path('payment');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . $date . '.log';

        $record = [
            'ts' => now()->toIso8601String(),
            'level' => $level,
            'data' => $data,
        ];
        if (!empty($rawBody)) {
            // don’t log huge bodies repeatedly; but raw body is often needed for debugging
            $record['raw'] = json_decode($rawBody, true) ?? $rawBody;
        }

        $line = json_encode($record, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND);

        // also send to Laravel log for convenience
        Log::info('[stripe-webhook] ' . $date . ' ' . $level, $data);
    }
}
