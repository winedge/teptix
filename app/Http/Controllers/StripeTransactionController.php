<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\StripeTransaction;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Exception;

class StripeTransactionController extends Controller
{
    /**
     * Retrieve Stripe transaction info and store in database
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionInfo(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id'
            ]);

            $order = Order::findOrFail($request->order_id);

            if (!$order->payment_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment token not found for this order'
                ], 404);
            }

            // Get Stripe live key from payment_setting table
            $paymentSetting = PaymentSetting::first();

            if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe secret key not configured'
                ], 500);
            }

            // Initialize Stripe client with live key
            $stripe = new StripeClient($paymentSetting->stripeSecretKey);

            // Determine token type: PaymentIntent (pi_...) or Checkout Session (cs_...)
            $paymentIntent = null;
            try {
                if (strpos($order->payment_token, 'cs_') === 0) {
                    // It's a Checkout Session id - retrieve session then payment intent
                    $session = $stripe->checkout->sessions->retrieve($order->payment_token, []);
                    if (!$session || empty($session->payment_intent)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'PaymentIntent not found in checkout session'
                        ], 404);
                    }
                    $paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent, []);
                } else {
                    // Assume token is a PaymentIntent id
                    $paymentIntent = $stripe->paymentIntents->retrieve($order->payment_token, []);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving payment info from Stripe',
                    'error' => $e->getMessage()
                ], 500);
            }

            // Try to fetch charge and balance transaction to get txn_id and fee
            $txnId = null;
            $taxAmount = null;

            try {
                if (isset($paymentIntent->latest_charge) && $paymentIntent->latest_charge) {
                    $charge = $stripe->charges->retrieve($paymentIntent->latest_charge, []);
                    if ($charge && $charge->balance_transaction) {
                        $balanceTransaction = $stripe->balanceTransactions->retrieve($charge->balance_transaction, []);
                        if ($balanceTransaction) {
                            $txnId = $balanceTransaction->id;
                            $taxAmount = $balanceTransaction->fee / 100; // cents -> dollars
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Could not retrieve charge/balance transaction for order #{$order->id}: " . $e->getMessage());
            }

            // Store or update transaction in database
            $transaction = StripeTransaction::updateOrCreate(
                ['payment_id' => $paymentIntent->id],
                [
                    'order_id' => $order->id,
                    'donation_id' => null,
                    'amount' => $paymentIntent->amount / 100, // Convert from cents to dollars
                    'client_secret' => $paymentIntent->client_secret,
                    'currency' => $paymentIntent->currency,
                    'latest_charge' => $paymentIntent->latest_charge,
                    'txn_id' => $txnId,
                    'tax_amount' => $taxAmount,
                    'payment_method_types' => $paymentIntent->payment_method_types,
                    'status' => $paymentIntent->status,
                    'full_response' => $paymentIntent->toArray()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction info retrieved successfully',
                'data' => [
                    'transaction' => $transaction,
                    'stripe_response' => $paymentIntent->toArray()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refund all Stripe transactions for event_id = 42 (HoliDhoom)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundWithHoliDhoom()
    {
        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key not configured'
            ], 500);
        }

        $stripe = new StripeClient($paymentSetting->stripeSecretKey);

        // Fetch ALL stripe transactions for event_id = 42
        $transactions = StripeTransaction::whereHas('order', function ($q) {
            $q->where('event_id', 42);
        })->get();

        $refundedCount        = 0;
        $alreadyRefundedCount = 0;
        $errorCount           = 0;
        $results              = [];

        foreach ($transactions as $transaction) {
            $entry = [
                'transaction_id' => $transaction->id,
                'payment_id'     => $transaction->payment_id,
            ];

            try {
                $paymentIntent = $stripe->paymentIntents->retrieve($transaction->payment_id, [
                    'expand' => ['charges']
                ]);

                $charge         = $paymentIntent->charges->data[0] ?? null;
                $amount         = $charge ? $charge->amount : 0;
                $amountRefunded = $charge ? $charge->amount_refunded : 0;
                $latestCharge   = $paymentIntent->latest_charge;

                // Already fully refunded (Stripe reports it)
                if ($amount > 0 && $amount === $amountRefunded) {
                    $transaction->update(['status' => 'refunded']);
                    $entry['action'] = 'already_refunded';
                    $entry['status'] = 'refunded';
                    $alreadyRefundedCount++;
                } else {
                    $refund = $stripe->refunds->create([
                        'charge' => $latestCharge,
                    ]);

                    $transaction->update(['status' => 'refunded']);
                    $entry['action']    = 'refunded';
                    $entry['status']    = 'refunded';
                    $entry['refund_id'] = $refund->id;
                    $refundedCount++;
                }
            } catch (Exception $e) {
                // Stripe says charge was already refunded
                if (str_contains($e->getMessage(), 'already been refunded')) {
                    $transaction->update(['status' => 'refunded']);
                    $entry['action'] = 'already_refunded';
                    $entry['status'] = 'refunded';
                    $alreadyRefundedCount++;
                } else {
                    $entry['action'] = 'error';
                    $entry['error']  = $e->getMessage();
                    $errorCount++;
                }
            }

            $results[] = $entry;
        }

        $message = "{$refundedCount} transaction(s) refunded successfully, {$alreadyRefundedCount} transaction(s) already refunded and status updated.";
        if ($errorCount > 0) {
            $message .= " {$errorCount} transaction(s) encountered errors.";
        }

        return response()->json([
            'success'                => true,
            'message'                => $message,
            'total'                  => count($results),
            'refunded_count'         => $refundedCount,
            'already_refunded_count' => $alreadyRefundedCount,
            'error_count'            => $errorCount,
            'results'                => $results,
        ], 200);
    }

    /**
     * Refund a single Stripe transaction by order_id for event_id = 42 (HoliDhoom)
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundWithHoliDhoomId($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 200);
        }

        // Verify order belongs to event_id = 42
        if ((int)$order->event_id !== 42) {
            return response()->json([
                'success' => false,
                'message' => 'This order does not belong to HoliDhoom event (event_id=42)'
            ], 200);
        }

        $transaction = StripeTransaction::where('order_id', $orderId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe transaction not found for this order'
            ], 200);
        }

        $paymentSetting = PaymentSetting::first();

        if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key not configured'
            ], 500);
        }

        $stripe = new StripeClient($paymentSetting->stripeSecretKey);

        try {
            // Retrieve PaymentIntent with charges
            $paymentIntent = $stripe->paymentIntents->retrieve($transaction->payment_id, [
                'expand' => ['charges']
            ]);

            $charge          = $paymentIntent->charges->data[0] ?? null;
            $amount          = $charge ? $charge->amount : 0;
            $amountRefunded  = $charge ? $charge->amount_refunded : 0;
            $latestCharge    = $paymentIntent->latest_charge;

            // Already fully refunded
            if ($amount > 0 && $amount === $amountRefunded) {
                $transaction->update(['status' => 'refunded']);

                return response()->json([
                    'success' => true,
                    'message' => 'This transaction is already refunded. Status updated to refunded.',
                    'data'    => [
                        'transaction_id' => $transaction->id,
                        'order_id'       => $orderId,
                        'payment_id'     => $transaction->payment_id,
                        'action'         => 'already_refunded',
                        'status'         => 'refunded',
                    ]
                ], 200);
            }

            // Create refund
            $refund = $stripe->refunds->create([
                'charge' => $latestCharge,
            ]);

            $transaction->update(['status' => 'refunded']);

            return response()->json([
                'success' => true,
                'message' => 'Refund created successfully. Status updated to refunded.',
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'order_id'       => $orderId,
                    'payment_id'     => $transaction->payment_id,
                    'action'         => 'refunded',
                    'status'         => 'refunded',
                    'refund_id'      => $refund->id,
                ]
            ], 200);

        } catch (Exception $e) {
            // If Stripe says charge already refunded, update DB and return success
            if (str_contains($e->getMessage(), 'already been refunded')) {
                $transaction->update(['status' => 'refunded']);

                return response()->json([
                    'success' => true,
                    'message' => 'This transaction is already refunded. Status updated to refunded.',
                    'data'    => [
                        'transaction_id' => $transaction->id,
                        'order_id'       => (int)$orderId,
                        'payment_id'     => $transaction->payment_id,
                        'action'         => 'already_refunded',
                        'status'         => 'refunded',
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error processing refund',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh Stripe balance transaction ids and fees for event 54 Stripe orders
     * where transaction_stripe.txn_id is still missing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEventBalanceTransactions(Request $request)
    {
        try {
            $paymentSetting = PaymentSetting::first();

            if (!$paymentSetting || !$paymentSetting->stripeSecretKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe secret key not configured'
                ], 500);
            }

            $stripe = new StripeClient($paymentSetting->stripeSecretKey);

            $eventId = 54;
            $transactions = StripeTransaction::whereNull('txn_id')
                ->whereNotNull('order_id')
                ->whereHas('order', function ($query) use ($eventId) {
                    $query->where('event_id', $eventId)
                        ->where('payment_type', 'STRIPE');
                })
                ->orderBy('id')
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No event 54 Stripe transactions found with missing txn_id.',
                    'event_id' => $eventId,
                    'matched_count' => 0,
                    'updated_count' => 0,
                    'error_count' => 0,
                    'results' => [],
                ], 200);
            }

            $updatedCount = 0;
            $errorCount = 0;
            $results = [];

            foreach ($transactions as $transaction) {
                $entry = [
                    'transaction_stripe_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                    'payment_id' => $transaction->payment_id,
                    'latest_charge' => $transaction->latest_charge,
                ];

                try {
                    $latestCharge = $transaction->latest_charge;
                    $latestChargeObject = null;
                    $paymentIntent = null;

                    if (!$latestCharge && $transaction->payment_id) {
                        $paymentIntent = $stripe->paymentIntents->retrieve($transaction->payment_id, [
                            'expand' => ['latest_charge.balance_transaction'],
                        ]);

                        $latestChargeObject = $paymentIntent->latest_charge ?? null;
                        $latestCharge = is_object($latestChargeObject)
                            ? ($latestChargeObject->id ?? null)
                            : $latestChargeObject;
                    }

                    if (!$latestCharge) {
                        $entry['status'] = 'skipped';
                        $entry['message'] = 'latest_charge is missing';
                        $errorCount++;
                        $results[] = $entry;
                        continue;
                    }

                    $charge = isset($latestChargeObject) && is_object($latestChargeObject)
                        ? $latestChargeObject
                        : $stripe->charges->retrieve($latestCharge, [
                            'expand' => ['balance_transaction'],
                        ]);

                    $balanceTransaction = $charge->balance_transaction ?? null;
                    $balanceTransactionId = is_object($balanceTransaction)
                        ? ($balanceTransaction->id ?? null)
                        : $balanceTransaction;

                    if (!$balanceTransactionId) {
                        $entry['status'] = 'skipped';
                        $entry['latest_charge'] = $latestCharge;
                        $entry['message'] = 'Balance transaction not found on Stripe charge';
                        $errorCount++;
                        $results[] = $entry;
                        continue;
                    }

                    $resolvedBalanceTransaction = is_object($balanceTransaction) && isset($balanceTransaction->fee)
                        ? $balanceTransaction
                        : $stripe->balanceTransactions->retrieve($balanceTransactionId, []);

                    $fee = isset($resolvedBalanceTransaction->fee)
                        ? $resolvedBalanceTransaction->fee / 100
                        : null;

                    $update = [
                        'latest_charge' => $latestCharge,
                        'txn_id' => $resolvedBalanceTransaction->id ?? $balanceTransactionId,
                        'tax_amount' => $fee,
                    ];

                    if ($paymentIntent) {
                        $update['amount'] = $paymentIntent->amount / 100;
                        $update['currency'] = $paymentIntent->currency;
                        $update['status'] = $paymentIntent->status;
                        $update['payment_method_types'] = $paymentIntent->payment_method_types ?? [];
                        $update['client_secret'] = $paymentIntent->client_secret ?? $transaction->client_secret;
                        $update['full_response'] = method_exists($paymentIntent, 'toArray')
                            ? $paymentIntent->toArray()
                            : json_decode(json_encode($paymentIntent), true);
                    }

                    $transaction->update($update);
                    $updatedCount++;

                    $entry['status'] = 'updated';
                    $entry['latest_charge'] = $latestCharge;
                    $entry['txn_id'] = $transaction->fresh()->txn_id;
                    $entry['fee'] = $fee;
                    $results[] = $entry;
                } catch (Exception $e) {
                    $errorCount++;
                    $entry['status'] = 'error';
                    $entry['error'] = $e->getMessage();
                    $results[] = $entry;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Event {$eventId} Stripe balance transactions refreshed.",
                'event_id' => $eventId,
                'matched_count' => $transactions->count(),
                'updated_count' => $updatedCount,
                'error_count' => $errorCount,
                'results' => $results,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing Stripe balance transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction info by order ID
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionByOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            if (!$order->payment_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment token not found for this order'
                ], 404);
            }

            $transaction = StripeTransaction::where('payment_id', $order->payment_token)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found in database'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
