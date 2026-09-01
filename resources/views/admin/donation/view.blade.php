@extends('master')

@section('content')
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Donation Details'),
        'breadcrumbs' => [
            ['url' => url('donations'), 'name' => __('Donations')],
            ['url' => '#', 'name' => __('Details')],
        ]
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            <i class="fas fa-heart text-danger mr-2"></i>
                            {{ __('Donation') }} <span class="badge badge-success ml-1">don_{{ $donation->id }}</span>
                        </h4>
                        <div class="card-header-action">
                            <a href="{{ url('donations') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">{{ __('Donation Details') }}</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">{{ __('Donation ID') }}</th>
                                        <td><span class="badge badge-success" style="font-size:0.9rem;">don_{{ $donation->id }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Amount') }}</th>
                                        <td><strong class="text-success" style="font-size:1.1rem;">{{ $currency }}{{ number_format($donation->amount, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Status') }}</th>
                                        <td>
                                            @if($donation->status === 'succeeded')
                                                <span class="badge badge-success badge-lg">{{ __('Succeeded') }}</span>
                                            @elseif($donation->status === 'pending')
                                                <span class="badge badge-warning badge-lg">{{ __('Pending') }}</span>
                                            @else
                                                <span class="badge badge-danger badge-lg">{{ ucfirst($donation->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Payment Intent ID') }}</th>
                                        <td>
                                            <code>{{ $donation->payment_intent_id }}</code>
                                            <button class="btn btn-sm btn-light ml-2" onclick="copyToClipboard('{{ $donation->payment_intent_id }}')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Transaction ID') }}</th>
                                        <td>
                                            @if($donation->transaction_id)
                                                <code class="text-info">{{ $donation->transaction_id }}</code>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <td>{{ $donation->created_at ? $donation->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3">{{ __('Event & Donor') }}</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">{{ __('Event') }}</th>
                                        <td>{{ $donation->event->name ?? 'N/A' }}</td>
                                    </tr>
                                    @php
                                        $donor = $donation->appUser
                                            ?? ($donation->app_user_id ? \App\Models\AppUser::find($donation->app_user_id) : null);
                                        $isDonorGuest = false;
                                        if (!$donor) {
                                            $donor = $donation->guestUser
                                                ?? ($donation->guest_user_id ? \App\Models\GuestUser::find($donation->guest_user_id) : null);
                                            $isDonorGuest = (bool) $donor;
                                        }
                                    @endphp
                                    <tr>
                                        <th>{{ __('Donor Name') }}</th>
                                        <td>
                                            @if($donor)
                                                <i class="fas fa-{{ $isDonorGuest ? 'user-secret text-secondary' : 'user text-primary' }} mr-1"></i>
                                                {{ $donor->name }} {{ $donor->last_name }}
                                                @if($isDonorGuest)
                                                    <span class="badge badge-warning ml-1">Guest</span>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('Anonymous / Guest') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Donor Email') }}</th>
                                        <td>
                                            @if($donor && $donor->email)
                                                {{ $donor->email }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                @php
                                    $stripeTransaction = \App\Models\StripeTransaction::where('donation_id', 'don_' . $donation->id)->first();
                                @endphp
                                @if($stripeTransaction)
                                <h5 class="mb-3 mt-4">{{ __('Linked Stripe Transaction') }}</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">{{ __('Stripe Fee') }}</th>
                                        <td>{{ $currency }}{{ number_format($stripeTransaction->tax_amount ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Stripe Status') }}</th>
                                        <td><span class="badge badge-{{ $stripeTransaction->status === 'succeeded' ? 'success' : 'warning' }}">{{ ucfirst($stripeTransaction->status) }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('View') }}</th>
                                        <td>
                                            <a href="{{ route('viewStripeTransaction', $stripeTransaction->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fab fa-stripe-s mr-1"></i>{{ __('View Transaction') }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                @endif
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
        iziToast.success({ title: '', message: 'Copied to clipboard!', position: 'topRight' });
    });
}
</script>
@endsection
