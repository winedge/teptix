@extends('master')

@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Stripe Transactions'),
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        {{ session('status') }}
                    </div>
                </div>
                @endif
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="{{ route('stripeTransactions') }}" class="mb-4">
                            @csrf
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label>{{ __('Event') }}</label>
                                        <select name="event_id" class="form-control select2">
                                            <option value="">{{ __('All Events') }}</option>
                                            @foreach ($events as $event)
                                                <option value="{{ $event->id }}" {{ isset($request->event_id) && $request->event_id == $event->id ? 'selected' : '' }}>
                                                    {{ $event->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label>{{ __('Date Range') }}</label>
                                        <input type="text" name="duration" class="form-control date duration" placeholder="{{ __('Choose date range') }}" value="{{ $request->duration ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">{{ __('Apply Filter') }}</button>
                                            <a href="{{ route('stripeTransactions') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>{{ __('Total Stripe Fee') }}: <strong>{{ $currency }}{{ number_format($totalTaxAmount, 2) }}</strong></h5>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="{{ route('stripeTransactions', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-csv"></i> {{ __('Export CSV') }}
                                </a>
                                <a href="{{ route('stripeTransactions', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-file-pdf"></i> {{ __('Export PDF') }}
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped" id="tableOrders">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Payment ID') }}</th>
                                        <th>{{ __('Order ID') }}</th>
                                        <th>{{ __('Event') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Currency') }}</th>
                                        <th>{{ __('Latest Charge') }}</th>
                                        <th>{{ __('Transaction ID') }}</th>
                                        <th>{{ __('Stripe Fee') }}</th>
                                        <th>{{ __('Donation ID') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Payment Methods') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td title="{{ $item->payment_id }}">{{ Str::limit($item->payment_id, 10, '....') }}</td>
                                        <td>
                                            @if($item->order)
                                                {{ $item->order->order_id }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->order && $item->order->event)
                                                {{ Str::limit($item->order->event->name, 30) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->order)
                                                @if($item->order->appUser)
                                                    {{ $item->order->appUser->name }} {{ $item->order->appUser->last_name }}
                                                @elseif($item->order->guestUser)
                                                    {{ $item->order->guestUser->name }} {{ $item->order->guestUser->last_name }}
                                                @else
                                                    N/A
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $currency }}{{ number_format($item->amount, 2) }}</td>
                                        <td>{{ strtoupper($item->currency) }}</td>
                                        <td title="{{ $item->latest_charge ?: '' }}">{{ $item->latest_charge ? Str::limit($item->latest_charge, 10, '....') : 'N/A' }}</td>
                                        <td title="{{ $item->txn_id ?: '' }}">{{ $item->txn_id ? Str::limit($item->txn_id, 10, '....') : 'N/A' }}</td>
                                        <td>{{ $item->tax_amount ? $currency . number_format($item->tax_amount, 2) : 'N/A' }}</td>
                                        <td>
                                            @if($item->donation_id)
                                                <a href="{{ url('donations') . '?search=' . $item->donation_id }}" class="badge badge-success" style="font-size:0.8rem;">{{ $item->donation_id }}</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->status == 'refunded' || $item->status == 'Refunded')
                                                <span class="badge badge-primary px-2 py-1" style="background-color: #007bff; color: #fff;">
                                                    <i class="fas fa-undo mr-1"></i> {{ __('Refunded') }}
                                                </span>
                                            @elseif($item->status == 'succeeded' || $item->status == 'complete')
                                                <span class="badge badge-success px-2 py-1">
                                                    <i class="fas fa-check mr-1"></i> {{ __('Succeeded') }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">
                                                    {{ $item->status ? ucfirst(str_replace('_', ' ', $item->status)) : 'N/A' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->payment_method_types)
                                                {{ implode(', ', array_map('ucfirst', $item->payment_method_types)) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $item->created_at ? $item->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('viewStripeTransaction', $item->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('deleteStripeTransaction', $item->id) }}" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this transaction?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
    $(document).ready(function() {
        $('#tableOrders').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "No entries available",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No matching records found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });
    });
</script>
@endsection
