<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>{{ \App\Models\Setting::find(1)->app_name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- General CSS Files -->
    <link href="{{ url('images/upload/' . \App\Models\Setting::find(1)->favicon) }}" rel="icon" type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">


    <!-- CSS Libraries -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <!-- Template CSS -->

    <link rel="stylesheet" href="{{ url('admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('admin/css/components.css') }}">
    <link rel="stylesheet" href="{{ url('admin/css/custom.css') }}">
    @if (session('direction') == 'rtl')
     <link rel="stylesheet" href="{{ url('admin/css/rtl.css') }}">
    @endif
</head>

<body>
    <?php $primary_color = \App\Models\Setting::find(1)->primary_color; ?>

    <style>
        <?php $pc = ltrim($primary_color, '#'); ?>
        :root {
            /* primary color from settings (sanitized) */
            --primary_color: #<?php echo $pc; ?>;
            --light_primary_color: #<?php echo $pc . '1a'; ?>;
            --middle_light_primary_color: #<?php echo $pc . '85'; ?>;
        }

        @media print {
            /* Force browsers that support it to print background colors/images */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
            }


            .invoice-print {
                /*width: 210mm;*/
                /*max-width: 210mm;*/
                /*height: 357mm;*/
                /*max-height: 500mm;*/
                page-break-inside: avoid;
            }

            .invoice-title h3 {
                font-size: 18px;
            }



            .invoice-detail-item {
                font-size: 12px;
            }

            .media img {
                max-width: 150px;
                max-height: 50px;
            }

            .btn, .no-print {
                display: none !important;
            }

            /* Prevents breaking important sections */
            .row, .invoice-title, .table-responsive, .invoice-detail-item {
                page-break-inside: avoid;
            }

            .ticket {
                width: 48%;
                max-width: 48%;
                box-sizing: border-box;
                page-break-inside: avoid;
            }

            .ticket img {
                max-width: 100px;
                max-height: 100px;
            }

            .btn, .no-print {
                display: none !important;
            }
            .invoice-table-3d {
                border-collapse: separate;
                border-spacing: 0;
                width: 100%;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.18), 0 1.5px 4px rgba(0,0,0,0.12);
                overflow: hidden;
                margin-bottom: 0;
            }
            .invoice-table-3d th, .invoice-table-3d td {
                /*padding: 5px 0;*/
                text-align: center;
                background: linear-gradient(135deg, #ffecec 80%, #e0e0e0 100%);
                border-bottom: 1px solid #ececec;
                font-size: 14px;
                /*box-shadow: 0 2px 6px rgba(0,0,0,0.04);*/
                color: #000000;

            }
            .invoice-table-3d th {
                background: linear-gradient(135deg, var(--primary_color, #ff7b7b) 80%, #fff 100%);

                font-weight: 700;
                font-size: 15px;
                border-bottom: 2px solid var(--primary_color, #3e4643);
                box-shadow: 0 4px 12px rgba(245,90,140,0.08);
            }
            .invoice-table-3d tr:last-child td {
                border-bottom: none;
            }
            .invoice-table-3d tr {
                transition: transform 0.15s, box-shadow 0.15s;
            }
        }
        .invoice {
                box-shadow: 0 4px 8px rgb(0 0 0 / 65%);
                background-color: #f7f7f7;
                border-radius: 3px;
                border: none;
                position: relative;
                margin-bottom: 30px;
                /*padding: 40px;*/
            }
        .block{
            padding-right: 150px;
            padding-left: 150px
        }
        .invoice-table-3d {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18), 0 1.5px 4px rgba(0,0,0,0.12);
            overflow: hidden;
            margin-bottom: 0;
        }
        .invoice-table-3d th, .invoice-table-3d td {
            /*padding: 14px 18px;*/
            text-align: center;
            /*background: linear-gradient(135deg, #ffecec 80%, #e0e0e0 100%);*/
            border-bottom: 1px solid #ececec;
            font-size: 14px;
            /*box-shadow: 0 2px 6px rgba(0,0,0,0.04);*/
            color: #000000;

        }
        .invoice-table-3d th {
            background: linear-gradient(135deg, var(--primary_color, #ffe3e3) 80%, #fff 100%);
             border-radius: 1px;
            font-weight: 700;
            font-size: 15px;
            border-top: 2px solid var(--primary_color, #3e4643);
            border-bottom: 2px solid var(--primary_color, #3e4643);
            box-shadow: 0 4px 12px rgba(245,90,140,0.08);
        }
        .invoice-table-3d tr:last-child td {
            border-bottom: none;
        }
        .invoice-table-3d tr {
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .invoice-table-3d .add-ons-row {
            background: linear-gradient(90deg, #fce4ec 60%, #fff 100%);
            color: #f3f3f3;
            font-weight: bold;
            font-size: 15px;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(211,47,47,0.08);
        }

        .order-card-container,
        .order-summary-card {
            background: #ffffff;
            border-top: 4px solid var(--primary_color);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            padding: 35px;
            margin-bottom: 40px;
        }

        .order-card-title {
            font-size: 24px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 30px;
            text-align: center;
        }

        .order-card-title span {
            color: #ff0000;
        }

        .order-left-column {
            padding-right: 30px;
        }

        .order-right-column {
            border-left: 1px solid #e2e8f0;
            padding-left: 45px;
        }

        .info-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .event-poster-placeholder {
            width: 85px;
            height: 85px;
            background-color: #e2e8f0;
            border-radius: 8px;
            flex-shrink: 0;
            object-fit: cover;
        }

        .event-title-text {
            font-size: 20px;
            font-weight: 700;
            color: #000000;
            line-height: 1.3;
        }

        .custom-icon-wrapper {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #fff5f5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ff4d4d;
            font-size: 13px;
            flex-shrink: 0;
        }

        .detail-item-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .detail-item-content {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.4;
        }

        .detail-item-content strong {
            color: #000000;
        }

        .organizer-heading,
        .order-date-heading {
            color: #ff0000 !important;
            font-weight: 700;
        }

        .summary-header-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-header-wrapper h4 {
            font-size: 18px;
            font-weight: 700;
            color: #000000;
            margin: 0;
        }

        .summary-subtitle {
            color: #718096;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .invoice-table-3d,
        .invoice-table-3d th,
        .invoice-table-3d td {
            background: #ffffff;
            box-shadow: none;
        }

        .invoice-table-3d {
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .invoice-table-3d th {
            background: var(--primary_color);
            border: 0;
            border-bottom: 3px solid #2d3748;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 12px 10px;
            text-align: left;
        }

        .invoice-table-3d td {
            border: 0;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
            font-size: 13px;
            padding: 12px 10px;
            text-align: left;
            vertical-align: middle;
        }

        .invoice-table-3d th:first-child,
        .invoice-table-3d td:first-child,
        .invoice-table-3d th:nth-child(4),
        .invoice-table-3d td:nth-child(4),
        .invoice-table-3d th:nth-child(5),
        .invoice-table-3d td:nth-child(5) {
            text-align: center;
        }

        .invoice-table-3d th:last-child,
        .invoice-table-3d td:last-child {
            text-align: right;
        }

        .invoice-table-3d .add-ons-row td {
            background: #fff5f5;
            color: #c53030;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-align: center;
            text-transform: uppercase;
        }

        .payment-summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        .ticket-list {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: center;
        }

        .ticket {
            width: calc(50% - 9px);
            max-width: 420px;
            box-sizing: border-box;
        }

        .print-ticket-card {
            width: 100%;
            border: 1px solid #ffd0d0;
            border-radius: 8px;
            background-color: #ffffff;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
            margin: 0 auto;
            word-break: break-word;
        }

        .ticket-qr {
            width: 100%;
            max-width: 160px;
            height: auto;
        }

        @media (max-width: 767.98px) {
            .block {
                padding-right: 15px;
                padding-left: 15px;
            }

            .order-card-container,
            .order-summary-card {
                padding: 20px;
            }

            .order-left-column {
                padding-right: 15px;
            }

            .order-right-column {
                border-left: 0;
                border-top: 1px solid #e2e8f0;
                margin-top: 24px;
                padding-top: 24px;
                padding-left: 15px;
            }

            .ticket {
                width: 100%;
                max-width: 420px;
            }
        }

        @media print {
            .block {
                padding-right: 0;
                padding-left: 0;
            }

            .invoice {
                box-shadow: none;
                margin-bottom: 0;
            }

            .order-card-container,
            .order-summary-card {
                box-shadow: none;
                margin-bottom: 20px;
                padding: 20px;
                page-break-inside: avoid;
            }

            .ticket-list {
                gap: 0;
                justify-content: flex-start;
                padding: 12px 0 0;
            }

            .ticket {
                width: 50%;
                max-width: 50%;
                padding: 0 6px 12px;
                page-break-inside: avoid;
            }

            .print-ticket-card {
                box-shadow: none;
                max-width: 100%;
            }

            .ticket-qr {
                max-width: 140px !important;
                max-height: none !important;
            }

            .invoice-table-3d th,
            .invoice-table-3d td {
                font-size: 11px;
                padding: 8px 6px;
            }
        }

    </style>
    <script>
        window.print();
    </script>

    <div class="main-wrapper">
        @if(app('request')->has('print'))
            <div class="p-5" style="margin: 0; padding: 0; font-size: 12px;">
        @else
            <div class="p-5">
        @endif
            <div class="section-body block">
                <div class="invoice ">
                    <div class="invoice-print ">
                        <div class="order-card-container">
                            <div class="order-card-title">
                                {{ __('Order') }} <span>#{{ $order->order_id }}</span>
                            </div>

                            <div class="row">
                                <div class="col-md-7 order-left-column">
                                    <div class="info-label">{{ __('Event') }}</div>
                                    <div class="d-flex align-items-start mb-4">
                                        <img alt="Event Image" class="event-poster-placeholder mr-3" src="{{ url('images/upload/' . $order->event->image) }}">
                                        <div>
                                            <div class="event-title-text mb-2">{{ $order->event->name }}</div>
                                            <div class="d-flex align-items-center text-muted" style="font-size: 14px;">
                                                <div class="custom-icon-wrapper mr-2"><i class="fas fa-calendar-alt"></i></div>
                                                <span>{{ $order->event->start_time->format('l') . ', ' . $order->event->start_time->format('d M Y') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="info-label font-weight-bold text-dark mb-2" style="font-size: 14px; text-transform: none;">{{ __('Attendee:') }}</div>
                                        <div class="detail-item-content mb-1"><strong>{{ $order->customer->name . ' ' . $order->customer->last_name }}</strong></div>

                                        <div class="detail-item-row">
                                            <div class="custom-icon-wrapper text-danger bg-transparent p-0 w-auto h-auto"><i class="fas fa-envelope"></i></div>
                                            <div class="detail-item-content"><a href="mailto:{{ $order->customer->email }}" class="text-dark text-decoration-underline">{{ $order->customer->email }}</a></div>
                                        </div>

                                        @if($order->customer->phone)
                                            <div class="detail-item-row">
                                                <div class="custom-icon-wrapper text-danger bg-transparent p-0 w-auto h-auto"><i class="fas fa-phone-alt"></i></div>
                                                <div class="detail-item-content text-dark">{{ $order->customer->phone }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-5 order-right-column">
                                    <div class="detail-item-row align-items-center mb-1">
                                        <div class="custom-icon-wrapper"><i class="fas fa-user"></i></div>
                                        <div class="detail-item-content organizer-heading">{{ __('Organizer:') }}</div>
                                    </div>
                                    <div style="padding-left: 44px;" class="mb-4">
                                        <div class="detail-item-content mb-1"><strong>{{ $order->organization->first_name . ' ' . $order->organization->last_name }}</strong></div>
                                        <div class="detail-item-row mb-1">
                                            <div class="custom-icon-wrapper text-danger bg-transparent p-0 w-auto h-auto" style="font-size:11px;"><i class="fas fa-envelope"></i></div>
                                            <div class="detail-item-content"><a href="mailto:{{ $order->organization->email }}" class="text-muted text-decoration-underline" style="font-size: 13px;">{{ $order->organization->email }}</a></div>
                                        </div>
                                        <div class="detail-item-content text-muted" style="font-size: 13px;">{{ $order->organization->country }}</div>
                                    </div>

                                    <div class="detail-item-row align-items-center mb-1">
                                        <div class="custom-icon-wrapper"><i class="fas fa-calendar-check"></i></div>
                                        <div class="detail-item-content order-date-heading">{{ __('Order Date:') }}</div>
                                    </div>
                                    <div style="padding-left: 44px;">
                                        <div class="detail-item-content"><strong>{{ $order->created_at->format('d F, Y') }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="order-summary-card">
                                <div class="summary-header-wrapper">
                                    <div class="custom-icon-wrapper" style="width:36px; height:36px; background-color:#fff0f0; color:#ff4d4d;"><i class="fas fa-id-card"></i></div>
                                    <h4>{{ __('Order Summary') }}</h4>
                                </div>
                                <div class="summary-subtitle">{{ __('Tickets, assigned seats, quantity, and payment details for this order.') }}</div>
                                <div class="table-responsive">
                                    <table class="invoice-table-3d">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Ticket Name') }}</th>
                                            <th>{{ __('Ticket Number') }}</th>
                                            <th>{{ __('Seat Number') }}</th>
                                            <th>{{ __('Quantity') }}</th>
                                            <th>{{ __('Price') }}</th>
                                        </tr>
                                    @php
                                        $isPrintCustomAmount = ($order->tax_option === 'custom_amount');
                                        $printCustomPrice = $isPrintCustomAmount ? floatval($order->tax_custom_amount ?? 0) : 0;
                                        $totalOrderTickets = $order->ticket_data ? $order->ticket_data->count() : \App\Models\OrderChild::where('order_id', $order->id)->count();
                                    @endphp
                                        <?php $addonscount=0; ?>
                                        @foreach ($order->tickets() as $ticket)
                                            @php
                                                $itemchild = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                                    ->where('order_id', $order->id)
                                                    ->get();
                                                $count = $itemchild->count();
                                                $seatDetails = $itemchild->pluck('Book_Seat_Id')->filter()->values();
                                                $lineUnitPrice = 0.0;

                                                if ($isPrintCustomAmount) {
                                                    $lineUnitPrice = (float) $printCustomPrice;
                                                } else {
                                                    $lineUnitPrice = (float) ($ticket->price ?? 0);

                                                    foreach ($itemchild as $child) {
                                                        if (! empty($child->event_venue_seat_id)) {
                                                            $venueSeat = \App\Models\EventVenueSeat::find($child->event_venue_seat_id);
                                                            if ($venueSeat && is_numeric($venueSeat->price) && (float) $venueSeat->price > 0) {
                                                                $lineUnitPrice = (float) $venueSeat->price;
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if ($lineUnitPrice <= 0 && $order->payment > 0 && $totalOrderTickets > 0) {
                                                        $lineUnitPrice = (float) $order->payment / max(1, $totalOrderTickets);
                                                    }
                                                }

                                                $lineTotal = $lineUnitPrice * max(1, $count);
                                            @endphp

                                            @if($ticket->is_add_on < 1)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $ticket->name }}</td>
                                                    <td>{{ $ticket->ticket_number }}</td>
                                                    <td>
                                                        @if($seatDetails->isNotEmpty())
                                                            {{ $seatDetails->join(', ') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $count }}</td>
                                                    <td style="color: #ff0000; font-weight: bold;">{{ $currency . number_format($lineTotal, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($ticket->is_add_on > 0)
                                                <?php $addonscount++; ?>
                                                @if($addonscount == 1)
                                                    <tr class="add-ons-row">
                                                        <td colspan="6"><strong>Add Ons</strong></td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $ticket->name }}</td>
                                                    <td>{{ $ticket->ticket_number }}</td>
                                                    <td>
                                                        @if($seatDetails->isNotEmpty())
                                                            {{ $seatDetails->join(', ') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $count }}</td>
                                                    <td style="color: #ff0000; font-weight: bold;">{{ $currency . number_format($lineTotal, 2) }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </table>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-lg-8">
                                        <div class="payment-summary-box">
                                            <div class="section-title mb-2">{{ __('Payment Method') }}</div>
                                            <address class="mb-0">
                                                <strong>{{ $order->payment_type == 'LOCAL' ? 'Offline' : $order->payment_type }}</strong><br>
                                                @if ($order->payment_type == 'FREE')
                                                    <span class="badge mt-1 mb-1 badge-info">Free</span><br>
                                                @elseif ($order->payment_status == 1)
                                                    <span class="badge mt-1 mb-1 badge-success">Paid</span><br>
                                                    {{ __('Token:') }} {{ $order->payment_token == null ? '-' : $order->payment_token }}<br>
                                                @endif
                                            </address>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 text-right">
                                        <div class="payment-summary-box">
                                        @if($order->tax_option === 'custom_amount')
                                            <div class="invoice-detail-item">
                                                <div class="invoice-detail-name">{{ __('Subtotal') }}</div>
                                                <div class="invoice-detail-value">
                                                    {{ $currency . number_format((float)$order->payment + (float)$order->coupon_discount, 2) }}
                                                </div>
                                            </div>
                                            <div class="invoice-detail-item">
                                                <div class="invoice-detail-name">{{ __('Coupon Discount') }}</div>
                                                <div class="invoice-detail-value">
                                                    (-) {{ $currency . number_format((float)$order->coupon_discount, 2) }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="invoice-detail-item">
                                                <div class="invoice-detail-name">{{ __('Subtotal') }}</div>
                                                <div class="invoice-detail-value">
                                                    {{ $currency . number_format((float)$order->payment + (float)$order->coupon_discount - (float)$order->tax, 2) }}
                                                </div>
                                            </div>
                                            <div class="invoice-detail-item">
                                                <div class="invoice-detail-name">{{ __('Coupon Discount') }}</div>
                                                <div class="invoice-detail-value">
                                                    (-) {{ $currency . number_format((float)$order->coupon_discount, 2) }}
                                                </div>
                                            </div>

                                            @if($order->tax < 0)
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Discount') }}</div>
                                                    <div class="invoice-detail-value">
                                                        (-) {{ $currency . number_format(abs((float)$order->tax), 2) }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Fees and charges') }}</div>
                                                    <div class="invoice-detail-value">
                                                        {{ $currency . number_format((float)$order->tax, 2) }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endif

                                        <hr class="mt-2 mb-2">
                                        <div class="invoice-detail-item">
                                            <div class="invoice-detail-name">{{ __('Total') }}</div>
                                            <div class="invoice-detail-value invoice-detail-value-lg">
                                                {{ $currency . number_format((float)$order->payment, 2) }}</div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5">
                        <div class="card right-data ">
                            <div class="card-header">
                                <h2>{{__('Ticket')}}</h2>
                            </div>
                            <div class="card-body ticket-list">
                                @foreach ($order->ticket_data as $item)
                                @php
                                    $ticket = DB::table('order_child')
                                        ->select([
                                            'events.image',
                                            'events.name',
                                            'users.first_name',
                                            'users.last_name',
                                            'events.type',
                                            'events.address',
                                            'events.start_time',
                                            'tickets.name as ticket_name',
                                            'tickets.type as ticket_type',
                                            'order_child.ticket_number',
                                            'order_child.Book_Seat_Id',
                                            'app_user.email as email',
                                            'guest_user.email as email'
                                            ])
                                            ->join('orders', 'order_child.order_id', '=', 'orders.id')
                                            ->join('events', 'orders.event_id', '=', 'events.id')
                                            ->join('users', 'orders.organization_id', '=', 'users.id')
                                            ->leftJoin('app_user', function ($join) {
                                                $join->on('order_child.customer_id', '=', 'app_user.id')
                                                    ->whereNotNull('order_child.customer_id');
                                            })
                                            ->leftJoin('guest_user', function ($join) {
                                                $join->on('order_child.guestuser_id', '=', 'guest_user.id')
                                                    ->whereNotNull('order_child.guestuser_id');
                                            })
                                        ->join('tickets', 'order_child.ticket_id', '=', 'tickets.id')
                                        ->where('order_child.id', $item->id)
                                        ->first();
                                @endphp
                                <div class="ticket">
                                    <div class="print-ticket-card">
                                        <div style="background-color: var(--primary_color); color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold;">
                                            <span>The Event Palette</span>
                                        </div>
                                        <div style="padding: 15px; display: flex; flex-direction: column; gap: 10px;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                                {{-- <img src="{{$setting->imagePath . $ticket->image}}" alt="Event Poster" style="width: 100%; max-width: 150px; height: auto; border-radius: 5px; object-fit: cover;"> --}}
                                                <div style="flex-grow: 1; min-width: 150px;">
                                                    <div style="font-size: 16px; font-weight: bold;">{{ $ticket->name }}</div>
                                                    <div style="font-size: 12px; color: gray;">{{ $ticket->first_name . ' ' . $ticket->last_name }}</div>
                                                    <div style="font-size: 12px; color: gray;">{{ $ticket->type == 'online' ? 'Online Event' : $ticket->address }}</div>
                                                    <div style="font-size: 12px; color: gray;">{{\Carbon\Carbon::parse($ticket->start_time)->format('l') }}, {{ \Carbon\Carbon::parse($ticket->start_time)->format('d F') }} | {{ \Carbon\Carbon::parse($ticket->start_time)->format('h:i a') }}</div>
                                                </div>
                                            </div>
                                            <div style="border-top: 1px dashed #d32f2f; padding-top: 10px; text-align: center;">
                                                <div style="font-size: 20px; color: #d32f2f; font-weight: bold;">{{ $ticket->ticket_name }}</div>
                                                <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Ticket {{ $ticket->ticket_type }}</div>
                                                @if(!empty($ticket->Book_Seat_Id))
                                                    <div style="font-size: 14px; color: #666; margin: 5px 0;"><strong>Seat Number:</strong> {{ $ticket->Book_Seat_Id }}</div>
                                                @endif
                                            </div>
                                            <div style="text-align: center; margin-top: 10px;">
                                                @php
                                                    $qrCode=QrCode::format('png')->size(150)->generate($ticket->ticket_number);
                                                    $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                                                @endphp
                                                <img src="{{ $base64QrCode }}" alt="QR Code" class="ticket-qr mx-auto mt-2"/>
                                            </div>
                                            <div style="text-align: center; margin-top: 5px; font-size: 12px; color: gray;">#{{ $ticket->ticket_number }}</div>
                                        </div>
                                        <div style="background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: gray;">
                                            All Sales Are Final! No Refunds!
                                        </div>
                                    </div>
                                </div>
                                {{-- @php
                                    $setting=App\Models\Setting::find(1);
                                    $ticket=\App\Models\Order::with(['event:id,start_time,end_time,name,type,address,image', 'organization:id,image,first_name,last_name','appUser:id,name,last_name', 'guestUser:id,name,last_name'])->find($item->order_id);
                                @endphp
                                    <div class="ticket" id="ticket" style="display: flex; flex-direction: row; background: white; border: 2px solid #f55a8c; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); max-width: 800px; width: 100%;">
                                        <div class="left-section" style="background: linear-gradient(135deg, #4a154b, #ec407a); color: white; text-align: center; flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px;">
                                            <img src="{{$setting->imagePath . $ticket->event->image}}" alt="Event Image" crossOrigin="anonymous" style="border-radius: 8px; width: 100%; max-width: 120px; max-height: 120px; object-fit: contain; height: auto; margin-bottom: 20px;">
                                            <span>{{ __('Organizer:') }} {{ $ticket->organization->first_name . ' ' . $ticket->organization->last_name }}</span>
                                            <span>{{ __('Payment method:') }} {{ $ticket->payment_type == 'LOCAL' ? 'Offline' : $ticket->payment_type }}</span>
                                        </div>
                                        <div class="center-section" style="flex: 3; text-align: center; padding: 20px;">
                                            <div class="date-section" style="display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin-bottom: 10px;">
                                                <span>{{ $ticket->event->start_time->format('l') }}</span>
                                                <span>{{ $ticket->event->start_time->format('F jS') }}</span>
                                                <span>{{ $ticket->event->start_time->format('Y') }}</span>
                                            </div>
                                            <h2 style="margin: 5px 0;">{{ $ticket->event->name }}</h2>
                                            <p class="event-info" style="margin: 5px 0;">{{ $ticket->event->start_time->format('d F Y') . ', ' . $ticket->event->start_time->format('h:i a') }} to {{ $ticket->event->end_time->format('d F Y') . ', ' . $ticket->event->end_time->format('h:i a') }}</p>
                                            <p style="margin: 5px 0;">Doors @ 7:00 PM</p>
                                            <div class="event-location" style="display: flex; justify-content: center; align-items: center; margin-top: 10px; font-size: 14px;">
                                                <span style="margin: 0 10px;">
                                                    <img src="{{$setting->imagePath . $setting->logo}}" alt="Smiley" style="width: 30px; height: auto;">
                                                </span>
                                                <span>{{ $ticket->event->type == 'online' ? 'Online Event' : $ticket->event->address }}</span>
                                            </div>
                                        </div>
                                        <div class="right-section" style="flex: 1; background: #f7e8ee; text-align: center; border-left: 2px dashed #f55a8c; padding: 20px;">
                                            <h4>{{ $ticket->event->name }}</</h4>
                                            <div class="qr-code" style="margin-top: 10px;">
                                                @php
                                                    $qrCode = QrCode::format('png')->size(150)->generate($item->ticket_number);
                                                    $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                                                @endphp
                                                <img src="{{ $base64QrCode }}" alt="QR Code" crossOrigin="anonymous" style="width: 100px; height: 100px;">
                                            </div>
                                            <div class="ticket-number" style="margin-top: 10px;">#{{ $item->ticket_number }}</div>
                                        </div>
                                    </div>
                                    </br> --}}
                                    {{-- {!! QrCode::size(150)->generate($item->ticket_number) !!} --}}
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- General JS Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    <script type="text/javascript"
        src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.21/b-1.6.2/b-flash-1.6.2/b-html5-1.6.2/b-print-1.6.2/datatables.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>

</body>

</html>
