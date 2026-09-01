@extends('master')

@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Transaction Details'),
        'breadcrumbs' => [
            ['url' => route('stripeTransactions'), 'name' => __('Transactions')],
            ['url' => '#', 'name' => __('Details')],
        ]
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ __('Stripe Transaction Information') }}</h4>
                        <div class="card-header-action">
                            <a href="{{ route('stripeTransactions') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">{{ __('Transaction Details') }}</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">{{ __('Payment ID') }}</th>
                                        <td>
                                            <code>{{ $transaction->payment_id }}</code>
                                            <button class="btn btn-sm btn-light ml-2" onclick="copyToClipboard('{{ $transaction->payment_id }}')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Amount') }}</th>
                                        <td><strong>{{ $currency }}{{ number_format($transaction->amount, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Currency') }}</th>
                                        <td>{{ strtoupper($transaction->currency) }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Status') }}</th>
                                        <td>
                                            @if($transaction->status == 'succeeded')
                                                <span class="badge badge-success badge-lg">{{ ucfirst($transaction->status) }}</span>
                                            @elseif($transaction->status == 'requires_payment_method')
                                                <span class="badge badge-warning badge-lg">{{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</span>
                                            @elseif($transaction->status == 'canceled')
                                                <span class="badge badge-danger badge-lg">{{ ucfirst($transaction->status) }}</span>
                                            @else
                                                <span class="badge badge-info badge-lg">{{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Payment Method Types') }}</th>
                                        <td>
                                            @if($transaction->payment_method_types)
                                                @foreach($transaction->payment_method_types as $method)
                                                    <span class="badge badge-primary">{{ ucfirst($method) }}</span>
                                                @endforeach
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Latest Charge') }}</th>
                                        <td>
                                            @if($transaction->latest_charge)
                                                <code>{{ $transaction->latest_charge }}</code>
                                                <button class="btn btn-sm btn-light ml-2" onclick="copyToClipboard('{{ $transaction->latest_charge }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Transaction ID') }}</th>
                                        <td>
                                            @if($transaction->txn_id)
                                                <code class="text-info">{{ $transaction->txn_id }}</code>
                                                <button class="btn btn-sm btn-light ml-2" onclick="copyToClipboard('{{ $transaction->txn_id }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Donation ID') }}</th>
                                        <td>
                                            @if($transaction->donation_id)
                                                <a href="{{ url('donations') . '?search=' . $transaction->donation_id }}" class="badge badge-success" style="font-size:0.85rem; padding:6px 10px;">
                                                    <i class="fas fa-heart mr-1"></i>{{ $transaction->donation_id }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Stripe Fee') }}</th>
                                        <td>
                                            @if($transaction->tax_amount)
                                                <span class="badge badge-warning badge-lg">{{ $currency }}{{ number_format($transaction->tax_amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Client Secret') }}</th>
                                        <td>
                                            <code class="text-muted">{{ Str::limit($transaction->client_secret, 30) }}***</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Created At') }}</th>
                                        <td>{{ $transaction->created_at ? $transaction->created_at->format('d M Y, H:i:s') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">{{ __('Order Information') }}</h5>
                                @if($transaction->order)
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">{{ __('Order ID') }}</th>
                                        <td>
                                            <a href="{{ url('order/' . $transaction->order_id) }}" class="badge badge-primary">
                                                {{ $transaction->order->order_id }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Event') }}</th>
                                        <td>
                                            @if($transaction->order->event)
                                                {{ $transaction->order->event->name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Customer') }}</th>
                                        <td>
                                            @if($transaction->order->appUser)
                                                {{ $transaction->order->appUser->name }} {{ $transaction->order->appUser->last_name }}
                                                <br><small class="text-muted">{{ $transaction->order->appUser->email }}</small>
                                            @elseif($transaction->order->guestUser)
                                                {{ $transaction->order->guestUser->name }} {{ $transaction->order->guestUser->last_name }}
                                                <span class="badge badge-warning">Guest</span>
                                                <br><small class="text-muted">{{ $transaction->order->guestUser->email }}</small>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Organizer') }}</th>
                                        <td>
                                            @if($transaction->order->organization)
                                                {{ $transaction->order->organization->first_name }} {{ $transaction->order->organization->last_name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Order Total') }}</th>
                                        <td><strong>{{ $currency }}{{ number_format($transaction->order->payment, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Order Status') }}</th>
                                        <td>
                                            @if($transaction->order->order_status == 'Complete')
                                                <span class="badge badge-success">{{ $transaction->order->order_status }}</span>
                                            @elseif($transaction->order->order_status == 'Pending')
                                                <span class="badge badge-warning">{{ $transaction->order->order_status }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $transaction->order->order_status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Payment Type') }}</th>
                                        <td><span class="badge badge-info">{{ $transaction->order->payment_type }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Order Date') }}</th>
                                        <td>{{ $transaction->order->created_at->format('d M Y, H:i:s') }}</td>
                                    </tr>
                                </table>
                                @else
                                <div class="alert alert-warning">
                                    {{ __('Order information not available') }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">{{ __('Full Stripe Response') }}</h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <button class="btn btn-sm btn-primary mb-2" onclick="copyJSON()">
                                            <i class="fas fa-copy"></i> {{ __('Copy JSON') }}
                                        </button>
                                        <pre id="jsonResponse" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($transaction->full_response, JSON_PRETTY_PRINT) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard!');
        }, function(err) {
            alert('Failed to copy: ', err);
        });
    }

    function copyJSON() {
        const jsonText = document.getElementById('jsonResponse').innerText;
        navigator.clipboard.writeText(jsonText).then(function() {
            alert('JSON copied to clipboard!');
        }, function(err) {
            alert('Failed to copy JSON: ', err);
        });
    }
</script>
@endsection
