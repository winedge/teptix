@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
        'title' => __('Order Detail'),
        'headerData' => __('Orders') ,
        'url' => 'orders' ,
        ])

        <div class="section-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            @if (session('error_msg'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error_msg') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="invoice">
                <div class="invoice-print">
                    <!-- Top Action Row -->
                    <div class="invoice-action-row d-flex justify-content-between align-items-center mb-2">
                        <h3 class="invoice-action-title mb-0" style="font-weight: 700; color: #2d3748;">
                            <i class="fas fa-file-invoice-dollar mr-2 invoice-primary-text"></i>{{ __('Order') }} #{{ $order->order_id }}
                        </h3>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <a class="btn btn-primary btn-lg px-4" href="{{ url('send-mail/' . $order->id) }}" style="border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-paper-plane mr-2"></i>{{ __('Send to Mail') }}
                            </a>
                            <a class="invoice-print-btn btn btn-danger btn-lg px-4" target="_blank" href="{{ url('order-invoice-print/' . $order->id) }}" style="border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-print mr-2"></i>{{ __('Print') }}
                            </a>
                        </div>
                    </div>

                    <!-- main Upper Card: Details & Info -->
                    <div class="invoice-detail-card card border-0 shadow-sm mb-2" style="border-radius: 12px; border: 1px solid #edf2f7 !important;">
                        <div class="invoice-card-body card-body p-2">
                            <div class="row">
                                <!-- Left: Event & Attendee Info -->
                                <div class="invoice-info-main col-md-8" style="border-right: 1px solid #edf2f7; padding:0 25px;">
                                    <address class="text-muted mb-1 font-weight-bold" style="text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                                        {{ __('Event') }}
                                    </address>
                                    <div class="invoice-event-media media mb-2 align-items-center">
                                        <img alt="Event Banner" class="invoice-event-image mr-3" src="{{ url('images/upload/' . $order->event->image) }}" width="70" height="70" style="border-radius: 8px; object-fit: cover; background-color: #e2e8f0;">
                                        <div class="media-body">
                                            <h5 class="media-title mb-1 font-weight-bold" style="color: #1a202c; font-size: 1.15rem;">
                                                {{ $order->event->name }}
                                            </h5>
                                            <div class="media-description font-weight-600 invoice-primary-text" style="font-size: 0.95rem;">
                                                <i class="far fa-calendar-alt mr-1"></i> {{ $order->event->start_time->format('l') . ', ' . $order->event->start_time->format('d M Y') }}
                                            </div>
                                        </div>
                                    </div>

                                    <address>
                                        <strong class="text-muted font-weight-bold" style="text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">{{ __('Attendee') }}:</strong>
                                        <div class="font-weight-bold text-dark" style="font-size: 1.05rem;">
                                            {{ $order->customer->name . ' ' . $order->customer->last_name }}
                                        </div>
                                        <div class="text-muted ">
                                            <i class="far fa-envelope mr-1 invoice-primary-text"></i> <a href="mailto:{{ $order->customer->email }}" class="text-muted">{{ $order->customer->email }}</a>
                                        </div>
                                        @if($order->customer->phone)
                                            <div class="text-muted ">
                                                <i class="fas fa-phone-alt mr-1 invoice-primary-text"></i> {{ $order->customer->phone }}
                                            </div>
                                        @endif
                                    </address>
                                </div>

                                <!-- Right: Organizer & Booking Timestamp -->
                                <div class="invoice-info-side col-md-4 pl-md-4 mt-2 mt-md-0 d-flex flex-column justify-content-between">
                                    @if (Auth::user()->hasRole('admin'))
                                        <address class="mb-2">
                                            <strong class="text-muted font-weight-bold d-block mb-1" style="text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                                                <i class="far fa-user mr-1 invoice-primary-text"></i> {{ __('Organizer') }}:
                                            </strong>
                                            <span class="font-weight-bold text-dark d-block">{{ $order->organization->first_name . ' ' . $order->organization->last_name }}</span>
                                            <span class="text-muted d-block font-size-13"><a href="mailto:{{ $order->organization->email }}" class="text-muted">{{ $order->organization->email }}</a></span>
                                            <span class="text-muted d-block font-size-13">{{ $order->organization->country }}</span>
                                        </address>
                                    @endif

                                    <address class="mb-0">
                                        <strong class="text-muted font-weight-bold d-block mb-1" style="text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
                                            <i class="far fa-calendar-check mr-1 invoice-primary-text"></i> {{ __('Order Date') }}:
                                        </strong>
                                        <span class="font-weight-bold text-dark" style="font-size: 1.05rem;">{{ $order->created_at->format('d F, Y') }}</span>
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Section Card -->
                    <div class="invoice-summary-card card border-0 shadow-sm mb-2" style="border-radius: 12px; border: 1px solid #edf2f7 !important;">
                        <div class="card-header bg-white border-bottom-0 pt-2 px-4 pb-0">
                            <h5 class="font-weight-bold mb-0" style="color: #2d3748;">
                                <i class="fas fa-ticket-alt invoice-primary-text mr-2"></i>{{ __('Order Summary') }}
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-2 pt-2">
                            <div class="table-responsive">
                                <table class="invoice-summary-table table table-hover mb-0 align-middle">
                                    <thead>
                                        <tr style="background-color: #fff5f5; color: #2d3748;">
                                            <th class="border-0 font-weight-bold px-3 py-2" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;">#</th>
                                            <th class="border-0 font-weight-bold py-2">{{ __('Ticket Name') }}</th>
                                            <th class="border-0 font-weight-bold py-2 text-center">{{ __('Ticket Number') }}</th>
                                            <th class="border-0 font-weight-bold py-2 text-center">{{ __('Seat Detail') }}</th>
                                            <th class="border-0 font-weight-bold py-2 text-right">{{ __('Price') }}</th>
                                            <th class="border-0 font-weight-bold px-3 py-2 text-center" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;">{{ __('Code') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $addonscount = 0;
                                            $isCustomAmountOrder = ($order->tax_option === 'custom_amount');
                                            $invCustomPrice = $isCustomAmountOrder ? floatval($order->tax_custom_amount ?? 0) : 0;
                                        @endphp
                                        @foreach ($order->tickets() as $ticket)
                                            @php
                                                $invDisplayPrice = $isCustomAmountOrder ? $invCustomPrice : $ticket->price;
                                            @endphp
                                            @if($ticket->is_add_on < 1)
                                                <tr style="border-bottom: 1px solid #edf2f7;">
                                                    <td class="px-3 py-2 font-weight-bold text-muted">{{ $loop->iteration }}</td>
                                                    <td class="py-2 font-weight-bold text-dark">{{ $ticket->name }}</td>
                                                    <td class="py-2 text-center text-muted font-weight-600">{{ $ticket->ticket_number }}</td>
                                                    @php
                                                        $itemchild = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                                            ->where('order_id', $order->id)
                                                            ->get();
                                                        $seatDetails = $itemchild->pluck('Book_Seat_Id')->filter()->values();
                                                    @endphp
                                                    <td class="py-2 text-center">
                                                        @if($seatDetails->isNotEmpty())
                                                            <div class="d-flex flex-wrap justify-content-center" style="gap: 4px;">
                                                                @foreach($seatDetails as $seatDetail)
                                                                    <span class="badge badge-light border text-dark font-weight-bold" style="border-radius: 4px; padding: 5px 8px;">{{ $seatDetail }}</span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2 text-right font-weight-bold text-dark">{{ $currency . number_format($invDisplayPrice, 2) }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        @foreach($itemchild as $ichild)
                                                            <a href="{{ url('get-code/' . $ichild->id) }}" class="btn btn-sm btn-outline-light border invoice-primary-text" style="border-radius: 6px; padding: .25rem .5rem;"><i class="fas fa-print"></i></a>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($ticket->is_add_on > 0)
                                                @php $addonscount++; @endphp
                                                @if($addonscount == 1)
                                                    <tr class="bg-light">
                                                        <td colspan="6" class="text-center font-weight-bold py-1 text-muted" style="text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Add Ons</td>
                                                    </tr>
                                                @endif
                                                <tr style="border-bottom: 1px solid #edf2f7;">
                                                    <td class="px-3 py-2 font-weight-bold text-muted">{{ $loop->iteration }}</td>
                                                    <td class="py-2 font-weight-bold text-dark">{{ $ticket->name }}</td>
                                                    <td class="py-2 text-center text-muted font-weight-600">{{ $ticket->ticket_number }}</td>
                                                    @php
                                                        $itemchild = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                                            ->where('order_id', $order->id)
                                                            ->get();
                                                        $seatDetails = $itemchild->pluck('Book_Seat_Id')->filter()->values();
                                                    @endphp
                                                    <td class="py-2 text-center">
                                                        @if($seatDetails->isNotEmpty())
                                                            <div class="d-flex flex-wrap justify-content-center" style="gap: 4px;">
                                                                @foreach($seatDetails as $seatDetail)
                                                                    <span class="badge badge-light border text-dark font-weight-bold" style="border-radius: 4px; padding: 5px 8px;">{{ $seatDetail }}</span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-2 text-right font-weight-bold text-dark">{{ $currency . number_format($invDisplayPrice, 2) }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        @foreach($itemchild as $ichild)
                                                            <a href="{{ url('get-code/' . $ichild->id) }}" class="btn btn-sm btn-outline-light border invoice-primary-text" style="border-radius: 6px; padding: .25rem .5rem;"><i class="fas fa-print"></i></a>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Breakdown Layout Split Row -->
                    <div class="invoice-breakdown-row row mt-2">
                        <!-- Bottom Left: Payment Framework details -->
                        <div class="col-lg-7 mb-2 mb-lg-0">
                            <div class="card border-0 h-100 shadow-sm" style="border-radius: 12px; border: 1px solid #edf2f7 !important;">
                                <div class="card-body p-2 d-flex flex-column justify-content-start">
                                    <h5 class="font-weight-bold mb-2" style="color: #2d3748;">
                                        <i class="fas fa-credit-card invoice-primary-text mr-2"></i>{{ __('Payment Method') }}
                                    </h5>
                                    <div class="p-2 bg-light mb-0" style="border-radius: 8px;">
                                        <strong class="text-dark d-block mb-1" style="font-size: 1.1rem;">
                                            {{ $order->payment_type == 'LOCAL' ? 'Offline' : $order->payment_type }}
                                        </strong>
                                        @if ($order->payment_type != 'FREE')
                                            <div>
                                                <span class="badge px-3 py-1 font-weight-bold {{ $order->payment_status == 1 ? 'badge-success' : 'badge-warning' }}" style="border-radius: 30px;">
                                                    {{ $order->payment_status == 1 ? 'Paid' : 'Waiting' }}
                                                </span>
                                            </div>
                                            <div class="text-muted font-size-13">
                                                <strong>{{ __('Token:') }}</strong> {{ $order->payment_token ?? '-' }}
                                            </div>
                                        @else
                                            @php
                                                $freeTicketLabel = $order->tax_option === 'complimentary'
                                                    ? __('Complementary Ticket')
                                                    : __('FREE Ticket');
                                            @endphp
                                            <div class=" text-success font-weight-bold" style="font-size: 1.05rem;">
                                                <i class="fas fa-check-circle mr-1"></i> {{ $freeTicketLabel }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Right: Total Financial Calculation Sidebar -->
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1px solid #edf2f7 !important; overflow: hidden;">
                                <div class="card-body p-2 bg-white">
                                    @if($order->tax_option === 'custom_amount')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted font-weight-600"><i class="fas fa-calculator mr-2 text-primary"></i>{{ __('Subtotal') }}</span>
                                            <span class="font-weight-bold text-dark">{{ $currency . number_format((float)$order->payment + (float)$order->coupon_discount, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted font-weight-600"><i class="fas fa-tags mr-2 text-success"></i>{{ __('Coupon Discount') }}</span>
                                            <span class="font-weight-bold text-muted">(-) {{ $currency . number_format((float)$order->coupon_discount, 2) }}</span>
                                        </div>
                                    @else
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted font-weight-600"><i class="fas fa-calculator mr-2 text-primary"></i>{{ __('Subtotal') }}</span>
                                            <span class="font-weight-bold text-dark">
                                                {{ $currency . number_format((float)$order->payment + (float)$order->coupon_discount - (float)$order->tax, 2) }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted font-weight-600"><i class="fas fa-tags mr-2 text-success"></i>{{ __('Coupon Discount') }}</span>
                                            <span class="font-weight-bold text-muted">(-) {{ $currency . number_format((float)$order->coupon_discount, 2) }}</span>
                                        </div>

                                        @if($order->tax < 0)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted font-weight-600"><i class="fas fa-percent mr-2 text-info"></i>{{ __('Discount') }}</span>
                                                <span class="font-weight-bold text-muted">(-) {{ $currency . number_format(abs((float)$order->tax), 2) }}</span>
                                            </div>
                                        @else
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted font-weight-600"><i class="fas fa-hand-holding-usd mr-2 text-info"></i>{{ __('Fees and charges') }}</span>
                                                <span class="font-weight-bold text-muted">(+) {{ $currency . number_format((float)$order->tax, 2) }}</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                <!-- Final Absolute Total Summary Strip -->
                                <div class="invoice-total-strip p-2 d-flex justify-content-between align-items-center" style="background-color: #fff5f5; border-top: 1px dashed #feb2b2;">
                                    <span class="h5 font-weight-bold mb-0" style="color: #2d3748;">{{ __('Total') }}</span>
                                    <span class="h4 font-weight-bold mb-0 invoice-primary-text">
                                        {{ $currency . number_format((float)$order->payment, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css')
<style>
    .invoice-print {
        max-width: 100%;
    }

    .invoice {
        padding: 10px !important;
    }

    .invoice .card {
        margin-bottom: 0 !important;
    }

    .invoice-primary-text {
        color: var(--primary_color) !important;
    }

    .invoice-action-title,
    .invoice-print a,
    .invoice-info-main,
    .invoice-info-side,
    .invoice-summary-table {
        word-break: break-word;
    }

    .invoice-summary-card .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .invoice-summary-table {
        min-width: 860px;
    }

    .invoice-summary-table:not(.table-sm):not(.table-md):not(.dataTable) td,
    .invoice-summary-table:not(.table-sm):not(.table-md):not(.dataTable) th {
        padding-left: 0.55rem !important;
        padding-right: 0.55rem !important;
    }

    .invoice-card-body {
        padding-top: 0.9rem !important;
        padding-bottom: 0.9rem !important;
    }

    .invoice-summary-card .card-header {
        padding-top: 0.9rem !important;
    }

    .invoice-summary-card .card-body {
        padding-top: 0.55rem !important;
        padding-bottom: 0.9rem !important;
    }

    .invoice-event-media {
        margin-bottom: 0.8rem !important;
    }

    .invoice-breakdown-row {
        margin-top: 0.8rem !important;
    }

    .invoice-breakdown-row .card-body,
    .invoice-total-strip {
        padding-top: 0.9rem !important;
        padding-bottom: 0.9rem !important;
    }

    @media (max-width: 1399.98px) {
        .invoice-print {
            padding: 0 6px;
        }

        .invoice-action-title {
            font-size: 1.45rem;
            line-height: 1.3;
        }

        .invoice-print-btn {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            font-size: 0.98rem;
            white-space: nowrap;
        }

        .invoice-card-body {
            padding: 0.85rem 1rem !important;
        }

        .invoice-summary-card .card-body,
        .invoice-summary-card .card-header {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .invoice-summary-table th,
        .invoice-summary-table td {
            font-size: 0.9rem;
            vertical-align: middle;
        }
    }

    @media (max-width: 991.98px) {
        .invoice-action-row {
            align-items: flex-start !important;
            gap: 8px;
        }

        .invoice-info-main {
            border-right: 0 !important;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 0.75rem;
        }

        .invoice-info-side {
            padding-left: 15px !important;
        }

        .invoice-breakdown-row .card {
            height: auto !important;
        }
    }

    @media (max-width: 767.98px) {
        .invoice-print {
            padding: 0;
        }

        .invoice-action-row {
            flex-direction: column;
            align-items: stretch !important;
            margin-bottom: 1rem !important;
        }

        .invoice-action-title {
            font-size: 1.2rem;
        }

        .invoice-print-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
        }

        .invoice-card-body,
        .invoice-summary-card .card-body {
            padding: 0.75rem !important;
        }

        .invoice-summary-card .card-header {
            padding: 0.75rem 0.75rem 0 !important;
        }

        .invoice-event-media {
            align-items: flex-start !important;
        }

        .invoice-event-image {
            width: 56px !important;
            height: 56px !important;
            margin-right: 0.75rem !important;
        }

        .invoice-event-media .media-title {
            font-size: 1rem !important;
            line-height: 1.35;
        }

        .invoice-event-media .media-description {
            font-size: 0.86rem !important;
            line-height: 1.45;
        }

        .invoice-summary-table {
            min-width: 740px;
        }

        .invoice-summary-table th,
        .invoice-summary-table td {
            padding: 0.5rem 0.45rem !important;
            font-size: 0.82rem;
        }

        .invoice-total-strip {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 4px;
            padding: 0.75rem !important;
        }

        .invoice-total-strip .h4 {
            font-size: 1.35rem;
        }
    }

    @media (max-width: 575.98px) {
        .invoice-detail-card,
        .invoice-summary-card,
        .invoice-breakdown-row .card {
            border-radius: 10px !important;
        }

        .invoice-event-media {
            display: block;
        }

        .invoice-event-image {
            display: block;
            margin: 0 0 0.5rem 0 !important;
        }

        .invoice-summary-table {
            min-width: 680px;
        }

        .invoice-breakdown-row .card-body {
            padding: 0.75rem !important;
        }

        .invoice-breakdown-row .d-flex.justify-content-between {
            align-items: flex-start !important;
            gap: 6px;
        }
    }
</style>
@endpush
