@extends('master')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>{{ __('Scanner Dashboard') }}</h1>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ __('Welcome to Scanner Portal') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-6 text-center">
                                <div class="mb-4">
                                    <i class="fas fa-qrcode fa-5x text-primary"></i>
                                </div>
                                <h5 class="mb-4">{{ __('Ticket Verification') }}</h5>
                                <a href="{{ route('admin.verification.ticketverify') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-ticket-alt mr-2"></i>{{ __('Verify Tickets') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
