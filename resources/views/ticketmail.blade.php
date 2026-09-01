<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }
        .invoice-border {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 24px 32px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .company-logo {
            float: right;
            width: 120px;
            margin-top: 10px;
        }
        .invoice-title {
            width: 120%;
            text-align:center;
            margin-bottom: 24px;
        }
        .invoice-title h3 {
            color: #6c757d;
            margin: 0;
        }
        .event-details {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .event-details .media img {
            width: 120px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
        }
        .media-body {
            margin-left: 16px;
            margin-top:30px;
        }
        .media-title {
            font-weight: bold;
            font-size: 1.1em;
        }
        .media-description {
            color: #888;
            font-size: 0.95em;
        }
        .organizer-attendee {
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
        }
        .organizer-details,
        .attendee-details {
            width: 48%;
            font-size: 0.97em;
        }
        .organizer-details strong,
        .attendee-details strong {
            color: #6c757d;
            margin-bottom: 6px;
            display: block;
        }
        .section-title {
            font-weight: bold;
            margin: 24px 0 10px 0;
            color: #495057;
        }
        .simple-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .simple-table th, .simple-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: left;
            font-size: 0.97em;
        }
        .simple-table th {
            background: #f1f3f4;
            color: #333;
        }
        .simple-table tr:nth-child(even) {
            background: #fafbfc;
        }
        .add-ons-row {
            background: #f9f9f9;
            text-align: center;
            font-weight: bold;
            color: #6c757d;
        }
        .payment-method, .total-table {
            margin-top: 18px;
            font-size: 0.97em;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.93em;
            margin-top: 4px;
        }
        .badge-success {
            background: #28a745;
            color: #fff;
        }
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        .total-table {
            width: 60%;
            float: right;
            margin-top: -60px;
        }
        .total-table td {
            border: none;
            padding: 6px 8px;
        }
        .total-table tr:last-child td {
            font-weight: bold;
            color: #333;
        }
        @media (max-width: 700px) {
            .invoice-border {
                padding: 12px 4px;
            }
            .event-details, .organizer-attendee {
                flex-direction: column;
            }
            .organizer-details, .attendee-details, .total-table {
                width: 100%;
                float: none;
                margin-top: 0;
            }
            .company-logo {
                float: none;
                display: block;
                margin: 0 auto 16px auto;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-border">
        <div>
        @php
            $setting = \App\Models\Setting::find(1);
            $logoPath = $setting && $setting->logo ? public_path('images/upload/' . $setting->logo) : public_path('images/logo.png');
            if (!file_exists($logoPath)) {
                $logoPath = $setting && $setting->logo ? url('images/upload/' . $setting->logo) : url('images/logo.png');
            }
            
            $eventImagePath = public_path('images/upload/' . $order->event->image);
            if (!file_exists($eventImagePath)) {
                $eventImagePath = url('images/upload/' . $order->event->image);
            }
        @endphp
        <img src="{{ $logoPath }}" alt="Company Logo" class="company-logo">

        <div class="invoice-title">
            <h3>{{ __('Order') }} {{ $order->order_id }}</h3>
        </div>
        <div class="event-details">
            <div class="media">
                <img alt="image" src="{{ $eventImagePath }}">
            </div>
            <div class="media-body ">
                <div class="media-title ">{{ $order->event->name }}</div>
                <div class="media-description">{{ $order->event->start_time->format('l, d M Y') }}</div>
            </div>
        </div>
        <div class="organizer-attendee">
            <div class="organizer-details">
                <strong>{{ __('Organizer') }}:</strong>
                {{ $order->organization->organization_name }}<br>
                {{ $order->organization->email }}<br>
                {{ $order->organization->country }}
            </div>
            <div class="attendee-details">
                <strong>{{ __('Attendee') }}:</strong>
                @if($order->customer)
                    {{ $order->customer->name . ' ' . $order->customer->last_name }}<br>
                    {{ $order->customer->email }}<br>
                @elseif($order->guestUser)
                    {{ $order->guestUser->name . ' ' . $order->guestUser->last_name }}<br>
                    {{ $order->guestUser->email }}<br>
                @else
                    {{ __('Guest User') }}<br>
                @endif
            </div>
        </div>
        <div class="section-title">{{ __('Order Summary') }}</div>
        <table class="simple-table">
            <thead>
                <tr>
                    <th>{{ __('Id') }}</th>
                    <th>{{ __('Ticket Name') }}</th>
                    <th>{{ __('Ticket Number') }}</th>
                    <th>{{ __('Quantity') }}</th>
                    <th>{{ __('Price') }}</th>
                </tr>
            </thead>
            <tbody>
                <?php $addonscount=0; ?>
                <?php $quantities = is_array($order->quantity) ? $order->quantity : explode(',', $order->quantity); ?>
                <?php $ticketIds = is_array($order->ticket_id) ? $order->ticket_id : explode(',', $order->ticket_id); ?>
                <?php
                    // Check if all tickets are free type
                    $allTicketsFree = true;
                    foreach ($order->tickets() as $t) {
                        if ($t->type != 'free') {
                            $allTicketsFree = false;
                            break;
                        }
                    }

                    // Determine effective price per ticket
                    $isCustomAmountOrder = ($order->tax_option === 'custom_amount');
                    $customFinalPrice = $isCustomAmountOrder ? floatval($order->tax_custom_amount ?? 0) : 0;
                ?>
                @foreach ($order->tickets() as $ticket)
                    <?php $ticketIndex = array_search($ticket->id, array_map('intval', $ticketIds)); ?>
                    <?php $ticketQty = isset($quantities[$ticketIndex]) ? intval(trim($quantities[$ticketIndex])) : 1; ?>
                    <?php
                        if ($isCustomAmountOrder) {
                            // Custom amount: the entered value is the final ticket price
                            $displayPrice = $customFinalPrice;
                        } else {
                            $displayPrice = $ticket->price * $ticketQty;
                        }
                    ?>
                    @if($ticket->is_add_on < 1)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $ticket->name }}</td>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticketQty }}</td>
                            <td>
                                @if ($allTicketsFree || $order->tax_option == 'complimentary')
                                    $0.00
                                @else
                                    ${{ number_format($displayPrice, 2) }}
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if($ticket->is_add_on > 0)
                        <?php $addonscount++; ?>
                        @if($addonscount == 1)
                            <tr class="add-ons-row">
                                <td colspan="5">{{ __('Add Ons') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $ticket->name }}</td>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticketQty }}</td>
                            <td>
                                @if ($allTicketsFree || $order->tax_option == 'complimentary')
                                    $0.00
                                @else
                                    ${{ number_format($displayPrice, 2) }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="payment-method">
            <strong>{{ __('Payment Method') }}:</strong>
            @if ($allTicketsFree || $order->payment_type == 'FREE' || $order->tax_option == 'complimentary')
                FREE<br>
                <span class="badge badge-success">FREE</span>
            @elseif ($order->payment_type == 'LOCAL' || $order->payment_type == 'Paid')
                Offline<br>
                <span class="badge badge-success">Paid</span>
            @else
                {{ $order->payment_type }}<br>
                <span class="badge badge-success">Paid</span>
            @endif
        </div>
        <table class="total-table">
            @if ($allTicketsFree || $order->tax_option == 'complimentary')
                <tr>
                    <td>{{ __('Subtotal') }}</td>
                    <td>$0.00</td>
                </tr>
                <tr>
                    <td>{{ __('Coupon Discount') }}</td>
                    <td>(-) $0.00</td>
                </tr>
                @if ($order->tax < 0)
                <tr>
                    <td>{{ __('Discount-') }}</td>
                    <td>${{ abs($order->tax) }}</td>
                </tr>
                @else
                <tr>
                    <td>{{ __('Fees and charges') }}</td>
                    <td>$0.00</td>
                </tr>
                @endif
                <tr>
                    <td>{{ __('Total') }}</td>
                    <td>$0.00</td>
                </tr>
            @elseif($order->tax_option === 'custom_amount')
                {{-- custom_amount: price was replaced, no tax/discount rows needed --}}
                <tr>
                    <td>{{ __('Subtotal') }}</td>
                    <td>${{ number_format($order->payment + $order->coupon_discount, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Coupon Discount') }}</td>
                    <td>(-) ${{ number_format($order->coupon_discount, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Total') }}</td>
                    <td>${{ number_format($order->payment, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td>{{ __('Subtotal') }}</td>
                    <td>${{ number_format($order->payment + $order->coupon_discount - $order->tax, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ __('Coupon Discount') }}</td>
                    <td>(-) ${{ number_format($order->coupon_discount, 2) }}</td>
                </tr>
                @if ($order->tax < 0)
                <tr>
                    <td>{{ __('Discount-') }}</td>
                    <td>(-) ${{ number_format(abs($order->tax), 2) }}</td>
                </tr>
                @else
                <tr>
                    <td>{{ __('Fees and charges') }}</td>
                    <td>${{ number_format($order->tax, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>{{ __('Total') }}</td>
                    <td>${{ number_format($order->payment, 2) }}</td>
                </tr>
            @endif
        </table>
        <div style="clear: both;"></div>

        </div>
    </div>
</body>
</html>
