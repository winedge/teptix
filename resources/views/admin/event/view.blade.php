@extends('master')

@section('content')
    @push("css")
        <style>
.row.mb-4.event-statistics-row {
    display: flex;
    flex-wrap: wrap;
}
.row.mb-4.event-statistics-row > [class*='col-'] {
    display: flex;
    margin-bottom: 20px;
}
.row.mb-4.event-statistics-row .ticket-card {
    margin: 0;
    width: 100%;
}
@media (min-width: 1200px) {
    .row.mb-4.event-statistics-row {
        flex-wrap: nowrap;
    }
    .row.mb-4.event-statistics-row > [class*='col-'] {
        margin-bottom: 0;
    }
}
            @keyframes cardFadeInUp {
                0% {
                    opacity: 0;
                    transform: translateY(25px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .ticket-card {
                position: relative;
                overflow: hidden;
                width: 100%;
                max-width: 100%;
                margin-top: 15px;
                background: linear-gradient(155deg, rgba(0, 0, 0, 0.12) 0%, rgba(0, 0, 0, 0.32) 100%), 
                            linear-gradient(155deg, var(--primary_color) 0%, var(--primary_color) 100%) !important;
                border-radius: 16px;
                padding: 20px;
                color: white;
                font-family: Arial, sans-serif;
                text-align: center;
                box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.12);
                opacity: 0;
                animation: cardFadeInUp 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                animation-delay: calc(var(--card-index, 0) * 0.12s);
                transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                            box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .ticket-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: -150%;
                width: 40%;
                height: 100%;
                background: linear-gradient(
                    to right,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.35) 50%,
                    rgba(255, 255, 255, 0) 100%
                );
                transform: skewX(-30deg);
                transition: left 0.8s cubic-bezier(0.16, 1, 0.3, 1);
                z-index: 2;
            }

            .ticket-card:hover {
                transform: translateY(-6px) scale(1.015);
                box-shadow: 0px 16px 36px rgba(0, 0, 0, 0.2);
            }

            .ticket-card:hover::before {
                left: 150%;
            }

            .ticket-card-badge {
                transition: transform 0.3s ease, background-color 0.3s ease;
            }

            .ticket-card-badge:hover {
                transform: translateY(-2px) scale(1.05);
                background-color: #ffffff !important;
            }

            .ticket-card .card-header {
                position: relative;
                padding: 15px;
                background: transparent !important;
                border-bottom: none !important;
            }

            .breakdown-tickets-scroll-container {
                padding-right: 8px;
                overflow-y: visible;
            }

            @media (min-width: 992px) {
                .breakdown-tickets-scroll-container {
                    flex: 1 1 100px;
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: var(--primary_color) rgba(0, 0, 0, 0.05);
                }

                .breakdown-tickets-scroll-container::-webkit-scrollbar {
                    width: 6px;
                }

                .breakdown-tickets-scroll-container::-webkit-scrollbar-track {
                    background: rgba(0, 0, 0, 0.05);
                    border-radius: 999px;
                }

                .breakdown-tickets-scroll-container::-webkit-scrollbar-thumb {
                    background-color: var(--primary_color);
                    border-radius: 999px;
                }
            }

            .sales-end {
                font-size: 14px;
                opacity: 0.8;
                font-weight: bold;
                margin: 0;
            }

            .sales-date {
                font-size: 14px;
                opacity: 0.7;
                margin-bottom: 10px;
            }

            .card-icon {
                margin: 15px 0;
            }

            .card-icon i {
                font-size: 30px;
            }

            .ticket-info {
                font-size: 18px;
                font-weight: bold;
            }

            .ticket-name {
                font-size: 20px;
                font-weight: bold;
            }

            .ticket-price {
                font-size: 20px;
                font-weight: bold;
                color: #ffdd57;
            }

            .ticket-count {
                font-size: 16px;
                font-weight: bold;
                margin-top: 10px;
                background: rgba(255, 255, 255, 0.2);
                padding: 10px;
                border-radius: 8px;
            }
            .event-img {
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                width: 100%;
                height: auto;
                aspect-ratio: 16 / 9;
            }
        </style>
    @endpush
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Event Detail '),
            'headerData' => __('Event') ,
            'url' => 'events' ,
        ])

      <div class="section-body">
          <div class="row event-single align-items-stretch">
              <div class="col-lg-9 d-flex">
                  <div class="card w-100 mb-0">
                     <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="event-img " style="background: url({{url('images/upload/'.$event->image)}})">
                                </div>
                            </div>
                            <div class="col-12 event-description">
                                <h2 class="mt-3">{{$event->name}} <button type="button" class="btn btn-primary "><a class="text-white" href="{{url($event->id.'/'.preg_replace('/\s+/', '-', $event->name).'/tickets')}}">{{__('Manage Tickets')}}</a></button></h2>
                                <small class="fw-bold">Meta Pixel ID:  {{ $event->meta_pixel_id ?? "-" }}</small>
                                <p> {!!$event->description!!}  </p>
                            </div>
                        </div>
                        <div class="row ml-0 mr-0 mt-4">
                            <div class="col-lg-3">
                                <div class="card single-card-light">
                                    <div class="row">
                                        <div class="col-3 text-center">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="col-9">
                                            <p class="mb-0">{{__('People allowed')}}</p>
                                            <span>{{$event->people}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card single-card-light">
                                    <div class="row">
                                        <div class="col-3 text-center">
                                            <i class="far fa-calendar-alt"></i>
                                        </div>
                                        <div class="col-9">
                                            <p class="mb-0">{{__('Date')}}</p>
                                            <span>{{Carbon\Carbon::parse($event->start_time)->format('l').','}}</span>
                                            <span>{{$event->start_time->format('d F Y')}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="card single-card-light">
                                    <div class="row">
                                        <div class="col-2 text-center">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="col-9">
                                            <p class="mb-0">{{__('Location')}}</p>
                                            @if($event->type=="offline")
                                            <span> {{$event->address}} </span>
                                            @else
                                            <span> {{__('Online Event')}} </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                     </div>
                </div>

             </div>
             <div class="col-lg-3 d-flex flex-column mb-3" style="align-self: stretch;">
                  <h2 class="section-title mb-2"> {{__('Breakdown Tickets')}}</h2>
                  
                  <!-- Payment Method Legend definitions at topside -->
                  <div class="d-flex justify-content-around align-items-center mb-3 px-1 text-muted" style="font-size: 0.76rem; font-weight: 600; border-bottom: 1px dashed rgba(0,0,0,0.12); padding-bottom: 8px;">
                      <span><i class="fab fa-stripe text-dark mr-1" style="font-size: 1.1rem; vertical-align: middle;"></i> {{ __('Stripe') }}</span>
                      <span><i class="fas fa-money-bill-wave text-dark mr-1"></i> {{ __('Local') }}</span>
                      <span><i class="fas fa-gift text-dark mr-1"></i> {{ __('Free') }}</span>
                  </div>

                 @if(count($event->sales->groupBy('ticket_name'))>0)
                     <div class="breakdown-tickets-scroll-container">
                         @foreach ($event->sales->groupBy('ticket_name') as $nameData => $item)
                             <div class="ticket-card" style="--card-index: {{ $loop->index }};">
                                 <div class="card-header">
                                     <p class="sales-end">{{ __('Sales end on') }}</p>
                                     @php
                                         $allData = $item->groupBy('payment_type')->map(fn($group) => [
                                             'total_tickets' => $group->sum('ticket_count')
                                         ]);
                                     @endphp
                                     <p class="sales-date">
                                         {{ date('Y-m-d', strtotime($item[0]->ticket_end_time)) }},
                                         {{ date('h:i a', strtotime($item[0]->ticket_end_time)) }}
                                     </p>
                                     <div class="ticket-info">
                                         <span class="ticket-name">{{ $nameData }}</span> |
                                         <span class="ticket-price">{{ $currency . $item[0]->price }}</span>
                                     </div>
                                     <div class="ticket-count mt-2">
                                         @php
                                             $totalBooked = collect($allData)->sum('total_tickets');
                                         @endphp

                                         <h5 class="mb-2" style="font-size: 1.4rem; font-weight: 800;">{{ $totalBooked }} / {{ $item[0]->ticket_quantity }}</h5>
                                         
                                         <div class="d-flex flex-wrap justify-content-center align-items-center mt-2">
                                             @foreach ($allData as $paymentName => $dataVal)
                                                 @php
                                                     $iconClass = 'fas fa-credit-card';
                                                     $paymentNameUpper = strtoupper($paymentName);
                                                     if (strpos($paymentNameUpper, 'STRIPE') !== false) {
                                                         $iconClass = 'fab fa-stripe';
                                                     } elseif (strpos($paymentNameUpper, 'LOCAL') !== false || strpos($paymentNameUpper, 'PAID') !== false || strpos($paymentNameUpper, 'OFFLINE') !== false) {
                                                         $iconClass = 'fas fa-money-bill-wave';
                                                     } elseif (strpos($paymentNameUpper, 'FREE') !== false) {
                                                         $iconClass = 'fas fa-gift';
                                                     }
                                                 @endphp
                                                 <span class="mx-1 px-2 py-1 text-dark ticket-card-badge" title="{{ $paymentName }}" style="font-size: 0.85rem; font-weight: 700; border-radius: 6px; background-color: rgba(255, 255, 255, 0.9) !important; display: inline-flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.08);">
                                                     <i class="{{ $iconClass }} mr-1"></i> {{ $dataVal['total_tickets'] }}
                                                 </span>
                                             @endforeach
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         @endforeach
                     </div>
                    <h2 class="section-title"> {{__('Total Checked-in Tickets')}}</h2>
                    <div class="ticket-card" style="--card-index: {{ count($event->sales->groupBy('ticket_name')) }};">
                        <div class="card-header">
                            <div class="ticket-count">
                                <h5>{{ $checkin_order_child }} CheckIn / {{$total_order_child}} Total Booking</h5>
                            </div>
                            <div class="ticket-info">
                                <span class="ticket-name">{{ __('Status: Checked-in') }}</span>
                            </div>
                        </div>
                    </div>
                @else
                <div class="card card-hero">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="card-description">{{__('Not Yet ticket Booking')}}</div>
                            <a href="{{url($event->id.'/ticket/create')}}"><button type="button" class="btn btn-ticket" ><i class="fas fa-plus"></i> </button>  </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        @php
            $startIndex = count($event->sales->groupBy('ticket_name')) + 1;
        @endphp
        <h2 class="section-title"> {{__('Event Statistics')}}</h2>
        <div class="row mb-4 event-statistics-row">
            <div class="col-xl-3 col-lg-6">
                <div class="ticket-card" style="--card-index: {{ $startIndex }};">
                    <div class="card-header">
                        <div class="ticket-info">
                            <span class="ticket-name">{{ __('Total Total Fees & Charges') }}</span>
                        </div>
                        <div class="ticket-count">
                            <h5>{{ $currency }}{{ number_format($totalTax, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="ticket-card" style="--card-index: {{ $startIndex + 1 }};">
                    <div class="card-header">
                        <div class="ticket-info">
                            <span class="ticket-name">{{ __('Total Stripe Fee') }}</span>
                        </div>
                        <div class="ticket-count">
                            <h5>{{ $currency }}{{ number_format($totalStripeFee, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="ticket-card" style="--card-index: {{ $startIndex + 2 }};">
                    <div class="card-header">
                        <div class="ticket-info">
                            <span class="ticket-name">{{ __('Total Revenue') }}</span>
                        </div>
                        <div class="ticket-count">
                            <h5>{{ $currency }}{{ number_format($totalRevenue, 2) }}</h5>
                            <small style="opacity: 0.8;">{{ __('Without Tax') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="ticket-card" style="--card-index: {{ $startIndex + 3 }};">
                    <div class="card-header">
                        <div class="ticket-info">
                            <span class="ticket-name">{{ __('Total Commission') }}</span>
                        </div>
                        <div class="ticket-count">
                            <h5>{{ $currency }}{{ number_format($totalCommission, 2) }}</h5>
                            <small style="opacity: 0.8;">{{ __('With Tax') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="section-title"> {{__('Recent Sales')}}</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="recent_sale_data">
                                <thead>
                                    <tr>
                                        <th>{{__('Date')}}</th>
                                        <th>{{__('Order Id')}}</th>
                                        <th>{{__('Customer Name')}}</th>
                                        <th>{{__('Ticket Name')}}</th>
                                        <th>{{__('Sold Ticket')}}</th>
                                        <th>{{__('Payment')}}</th>
                                        <th>{{__('Payment Gateway')}}</th>
                                        <th>{{__('Ticket Number')}}</th>
                                        <th>{{__('Check In')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($event->groupedSales as $orderId => $orderItems)

                                        @foreach ($orderItems as $index => $item)
                                        <tr>
                                            <th>{{date('Y-m-d', strtotime($item->created_at))}}</th>
                                            <td>{{$item->ordersdata}}</td>
                                            <td>{{ ($item->user_first_name) ? $item->user_first_name.' '.$item->user_last_name : $item->guest_first_name.' '.$item->guest_last_name }}
                                            </td>
                                            <th>{{$item->ticket_name??''}}</th>
                                            <th>{{$item->ticket_count}}</th>
                                            <th>{{ $index === 0 ? $currency . $item->payment : '' }}</th>
                                            <th>{{$item->payment_type}}</th>
                                            <th>{{$item->ticketNumbers}}</th>
                                            <th>{{$item->checkins}}</th>
                                        </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
    </div>
</section>
@endsection
