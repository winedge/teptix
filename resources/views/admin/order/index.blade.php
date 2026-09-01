@extends('master')

@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('View Orders'),
    ])
<style>

    .emailticketstyle{
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f8f0f7;
    }
    .ticket {
display: flex;
background: white;
border: 2px solid #f55a8c;
border-radius: 8px;
overflow: hidden;
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
max-width: 800px;
width: 100%;
}

.left-section {
background: linear-gradient(135deg, #4a154b, #ec407a);
color: white;
padding: 20px;
text-align: center;
flex: 1;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
}

.left-section img {
border-radius: 8px;
width: 100%;
max-width: 120px;
height: auto;
margin-bottom: 20px;
}

.left-section span {
font-size: 14px;
}

.center-section {
padding: 20px;
flex: 3;
display: flex;
flex-direction: column;
align-items: center;
justify-content: center;
text-align: center;
}

.center-section h2 {
margin: 0;
font-size: 32px;
color: #4a154b;
font-family: 'Courier New', Courier, monospace;
}

.center-section h3 {
margin: 5px 0;
font-size: 24px;
color: #ec407a;
font-family: 'Courier New', Courier, monospace;
}

.center-section p {
margin: 10px 0;
font-size: 16px;
color: #333;
}

.center-section .event-info {
margin: 10px 0;
font-size: 18px;
color: #555;
font-weight: bold;
}

.center-section .event-location {
display: flex;
justify-content: center;
align-items: center;
margin-top: 10px;
font-size: 14px;
}

.center-section .event-location span {
margin: 0 10px;
}

.right-section {
flex: 1;
background: #f7e8ee;
text-align: center;
padding: 20px;
border-left: 2px dashed #f55a8c;
}

.right-section h4 {
margin: 0;
font-size: 16px;
color: #333;
}

.right-section .qr-code {
margin: 15px 0;
}

.right-section img {
width: 100px;
height: 100px;
}

.right-section .ticket-number {
margin-top: 10px;
font-size: 12px;
color: #555;
}

.date-section {
display: flex;
justify-content: space-between;
font-size: 14px;
font-weight: bold;
margin-bottom: 10px;
}

.date-section span {
color: #ec407a;
}

.emailticketstyle button {
margin-top: 20px;
padding: 10px 20px;
background-color: #ec407a;
color: white;
border: none;
border-radius: 5px;
cursor: pointer;
font-size: 16px;
}

.emailticketstyle button:hover {
background-color: #d03468;
}

#order_data_table td {
    vertical-align: middle !important;
}

#order_data_table .btn-action-dropdown {
    margin-top: 0 !important;
    height: 31px !important;
    line-height: 1.5 !important;
    font-size: 12px !important;
    min-width: 100px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0.25rem 0.5rem !important;
}

#order_data_table .payment-status-select {
    margin-top: 0 !important;
    height: 31px !important;
    font-size: 12px !important;
    min-width: 100px !important;
    text-align-last: center !important;
    padding: 2px 5px !important;
    border-radius: 4px !important;
    vertical-align: middle !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
}

#order_data_table .status-complete {
    background-color: #27ff007a !important;
    color: #0c5700 !important;
    border: 1px solid #27ff00 !important;
}

#order_data_table .status-pending {
    background-color: #ffc107 !important;
    color: #212529 !important;
    border: 1px solid #e0a800 !important;
}

#order_data_table .status-refunded {
    background-color: #007bff !important;
    color: #ffffff !important;
    border: 1px solid #0056b3 !important;
}

@if(Auth::user()->hasRole('Organizer') || Auth::user()->hasRole('Manager'))
    .dt-buttons .btn-danger,
    .dt-button.btn-danger,
    .buttons-remove {
        display: none !important;
    }
@endif
</style>
    <div class="section-body">

        <div class="row">
            <div class="col-12">
                @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif
            </div>
            <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4 mt-2">
                        <div class="col-lg-8"><h2 class="section-title mt-0"> {{__('Order List')}}</h2></div>
                        <div class="col-lg-4 text-right">
                        </div>
                    </div>
                <div class="table-responsive">
                    <form method="GET" action="{{ url('/orders') }}" class="order-filter-form mb-3 d-flex align-items-center justify-content-start" style="flex-wrap: wrap; gap: 1rem; width: 100%;">
                        <div class="form-group mb-0" style="min-width: 280px; max-width: 420px; flex: 0 0 auto;">
                            <label for="event_id" class="sr-only">{{ __('Event') }}</label>
                            <select name="event_id" id="event_id" class="form-control">
                                <option value="">{{ __('Filter by Event') }}</option>
                                @if(isset($events))
                                    @foreach($events as $event)
                                        <option value="{{ $event->id }}" {{ isset($request->event_id) && $request->event_id == $event->id ? 'selected' : '' }}>{{ $event->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group mb-0" style="min-width: 220px; max-width: 280px; flex: 0 0 auto;">
                            <label for="promotion_status" class="sr-only">{{ __('Promotion Applied') }}</label>
                            <select name="promotion_status" id="promotion_status" class="form-control">
                                <option value="">{{ __('All Promotion Status') }}</option>
                                <option value="applied" {{ isset($request->promotion_status) && $request->promotion_status === 'applied' ? 'selected' : '' }}>
                                    {{ __('Promotion Applied') }}
                                </option>
                                <option value="not_applied" {{ isset($request->promotion_status) && $request->promotion_status === 'not_applied' ? 'selected' : '' }}>
                                    {{ __('No Promotion Applied') }}
                                </option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 0.75rem; flex: 0 0 auto;">
                            <button type="submit" class="btn btn-primary" style="margin-top: 0 !important;">{{ __('Apply') }}</button>
                            <a href="{{ url('/orders') }}" class="btn btn-secondary" style="margin-top: 0 !important;">{{ __('Reset') }}</a>
                        </div>
                    </form>

                    <table class="table" id="order_data_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{__('Order Id')}}</th>
                                <th>{{__('Customer Name')}}</th>
                                <th>{{__('Event Name')}}</th>
                                <th>{{__('Date')}}</th>
                                <th>{{__('Quantity')}}</th>
                                <th>{{__('Seat Detail')}}</th>
                                <th>{{__('Promotion Amount')}}</th>
                                <th>{{__('Payment')}}</th>
                                <th>{{__('Payment Gateway')}}</th>
                                <th class="d-none">{{ __('Order Status') }}</th>{{-- for print and pdf only --}}
                                <th class="d-none">{{ __('Payment Status') }}</th>{{-- for print and pdf only --}}
                                <th>{{__('Payment Status')}}</th>
                                <th>{{__('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $key=>$item)
                                <tr>
                                    <td></td>
                                    <td>{{$item->order_id}} </td>
                                    @if (isset($item->customer) && $item->customer)
                                        @if (isset($item->customer->is_guest_user) && $item->customer->is_guest_user)
                                            <td>{{$item->customer->name.' '.$item->customer->last_name}} <br> {{__('(Guest User)')}}</td>
                                        @else
                                            <td>{{$item->customer->name.' '.$item->customer->last_name}}</td>
                                        @endif
                                    @else
                                        <td>{{ __('No Customer Data') }}</td>
                                    @endif
                                    <td>
                                        <h6 class="mb-0" style="font-size: 13px;">{{$item->event?->name}}</h6>
                                    </td>
                                    <td>
                                        @if($item->created_at)
                                            <p class="mb-0">{{$item->created_at->format('Y-m-d')}}</p>
                                            <p class="mb-0">{{$item->created_at->format('h:i a')}}</p>
                                        @else
                                            <p class="mb-0">{{ __('No Date') }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $quantityValue = is_array($item->quantity)
                                                ? array_sum(array_map('intval', $item->quantity))
                                                : array_sum(array_map('intval', array_filter(explode(',', (string) $item->quantity))));
                                        @endphp
                                        {{ $quantityValue }}
                                    </td>
                                    <td>
                                        @php
                                            $seatDetails = $item->orderChild
                                                ->pluck('Book_Seat_Id')
                                                ->filter()
                                                ->values();
                                        @endphp

                                        @if($seatDetails->isNotEmpty())
                                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                                @foreach($seatDetails as $seatDetail)
                                                    <span class="badge badge-light border">{{ $seatDetail }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $currency }}{{ number_format((float) ($item->coupon_discount ?? 0), 2) }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{$currency}}{{$item->payment}}</span>
                                            @if(Auth::user()->hasRole('admin'))
                                                <a href="{{ url('edit-order-payment/'.$item->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item->payment_type == 'LOCAL' ? 'Offline' : $item->payment_type }}</td>
                                    <td class="d-none">{{ $item->order_status }}</td>{{-- for print and pdf only --}}
                                    @if ($item->payment_status == 0)
                                    <td class="d-none">{{ $item->payment_status == 0? 'Pending':'' }}</td>{{-- for print and pdf only --}}
                                    @else
                                    <td class="d-none">{{ $item->payment_status == 1? 'Complete':'' }}</td>{{-- for print and pdf only --}}
                                    @endif

                                    <td class="align-middle text-center" style="min-width: 115px;">
                                        @if ($item->payment_status == 2 || $item->order_status == 'Refunded')
                                            <span class="badge status-refunded payment-status-select font-weight-bold">
                                                <i class="fas fa-undo mr-1"></i> {{ __('Refunded') }}
                                            </span>
                                        @else
                                            <select name="payment_status" id="payment-{{ $item->id }}" class="form-control payment-status-select font-weight-bold {{ $item->payment_status == 1 ? 'status-complete' : 'status-pending' }}" onchange="changePaymentStatus({{$item->id}})" {{ $item->payment_status == 1? 'disabled':'' }}>
                                                <option value="0" {{ $item->payment_status == 0? 'selected':''}} class="status-pending"> {{ __('Pending') }} </option>
                                                <option value="1" {{ $item->payment_status == 1? 'selected':''}} class="status-complete"> {{ __('Complete') }} </option>
                                            </select>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center" style="min-width: 110px;">
                                        <div class="dropdown d-inline-flex align-items-center justify-content-center m-0 p-0">
                                            <button class="btn btn-primary btn-action-dropdown dropdown-toggle font-weight-bold" type="button" id="dropdownMenuButton{{ $item->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{ __('Action') }}
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow-sm" aria-labelledby="dropdownMenuButton{{ $item->id }}">
                                                <a class="dropdown-item" href="{{ url('order-invoice/'.$item->id) }}">
                                                    <i class="far fa-eye mr-2 text-primary"></i> {{ __('View Invoice') }}
                                                </a>
                                                @if ($item->payment_status == 2 || $item->order_status == 'Refunded')
                                                    <a class="dropdown-item disabled text-muted" href="javascript:void(0);">
                                                        <i class="fas fa-ban mr-2"></i> {{ __('Refunded') }}
                                                    </a>
                                                @else
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger btn-refund-order" href="javascript:void(0);" onclick="refundOrder({{ $item->id }}, '{{ $item->order_id }}')" data-id="{{ $item->id }}" data-order-id="{{ $item->order_id }}">
                                                        <i class="fas fa-undo mr-2"></i> {{ __('Refund Order') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>
    </section>

    <script>
    function refundOrder(id, orderId) {
        Swal.fire({
            title: '{{ __("Refund Order #") }}' + orderId + '?',
            text: '{{ __("Are you sure you want to refund this order? All booked seats associated with this order will be released for other bookings.") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("Yes, Refund & Release Seats") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '{{ __("Processing Refund...") }}',
                    text: '{{ __("Please wait while we process the refund and release seats.") }}',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ url('/order/refund') }}",
                    type: "POST",
                    data: {
                        id: id,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire({
                                title: '{{ __("Refunded!") }}',
                                text: res.msg,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            var msg = res.msg || res.message || '{{ __("Failed to refund order.") }}';
                            Swal.fire('{{ __("Error!") }}', msg, 'error');
                        }
                    },
                    error: function (xhr) {
                        var errorMsg = '{{ __("Error refunding order.") }}';
                        if (xhr.responseJSON) {
                            errorMsg = xhr.responseJSON.msg || xhr.responseJSON.message || xhr.responseJSON.error || errorMsg;
                        } else if (xhr.responseText) {
                            try {
                                var parsed = JSON.parse(xhr.responseText);
                                errorMsg = parsed.msg || parsed.message || errorMsg;
                            } catch (e) {
                                errorMsg = xhr.responseText.substring(0, 300);
                            }
                        }
                        Swal.fire('{{ __("Error!") }}', errorMsg, 'error');
                    }
                });
            }
        });
    }

    $(document).on('click', '.btn-refund-order', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var orderId = $(this).data('order-id');
        if (id) {
            refundOrder(id, orderId);
        }
    });
    </script>
@endsection
