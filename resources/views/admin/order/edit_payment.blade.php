@extends('master')

@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Edit Payment Amount'),
        'breadcrumbs' => [
            [
                'title' => __('Orders'),
                'url' => url('orders')
            ],
            [
                'title' => __('Edit Payment'),
            ]
        ]
    ])

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
                        <form action="{{ url('admin/update-order-payment/'.$order->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="order_id">{{ __('Order ID') }}</label>
                                        <input type="text" class="form-control" value="{{ $order->order_id }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="event">{{ __('Event') }}</label>
                                        <input type="text" class="form-control" value="{{ $order->event?->name }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer">{{ __('Customer') }}</label>
                                        <input type="text" class="form-control" value="{{ isset($order->customer) ? $order->customer->name.' '.$order->customer->last_name : __('No Customer Data') }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_amount">{{ __('Payment Amount') }} ({{ $currency }})</label>
                                        <input type="number" class="form-control" name="payment" value="{{ $order->payment }}" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-12 text-right">
                                    <a href="{{ url('orders') }}" class="btn btn-secondary mr-2">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('Update Payment') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
