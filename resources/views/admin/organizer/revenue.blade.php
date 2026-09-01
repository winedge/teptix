@extends('master')

@push('css')
<style>
    /* ===================== Revenue Cards ===================== */
    .rev-card-wrapper {
        perspective: 1000px;
    }
    .rev-card {
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        background: linear-gradient(346deg, rgba(0, 0, 0, 0.35) 13%, rgba(255, 255, 255, 0.2) 32%, rgba(0, 0, 0, 0.38) 56%, rgba(0, 0, 0, 0.3) 78%, rgba(255, 255, 255, 0.2) 94%), var(--primary_color);
        cursor: pointer;
        transform-style: preserve-3d;
        transform: rotateX(6deg) rotateY(-6deg);
        transition: transform 0.45s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.45s ease;
        color: #ffffff !important;
        min-height: 155px;
        box-shadow: -4px 8px 16px rgba(0, 0, 0, 0.08), -1px 3px 6px rgba(0, 0, 0, 0.04);
    }
    .rev-card:hover { 
        transform: rotateX(0deg) rotateY(0deg) translateY(-6px) translateZ(12px); 
        box-shadow: 0 18px 36px rgba(0, 0, 0, 0.25);
    }
    .rev-card .card-body { 
        position: relative; 
        z-index: 1; 
        padding: 1.4rem 1.6rem; 
        transform-style: preserve-3d;
    }

    .rev-chip {
        display: inline-flex; align-items: center;
        padding: 0.25rem 0.65rem;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.15);
        font-size: 0.68rem; font-weight: 700;
        letter-spacing: 0.06em; text-transform: uppercase;
        color: #ffffff;
        transform: translateZ(20px);
    }
    .rev-icon {
        width: 38px; height: 38px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.2);
        font-size: 1.1rem;
        color: #ffffff !important;
        transform: translateZ(25px);
    }
    .rev-icon i {
        color: #ffffff !important;
    }
    .rev-amount {
        margin: 0.75rem 0 0.15rem;
        font-size: 1.7rem; font-weight: 800; line-height: 1.15; color: #ffffff !important;
        transform: translateZ(30px);
    }
    .rev-label {
        font-size: 0.78rem; color: rgba(255, 255, 255, 0.85) !important; font-weight: 500;
        transform: translateZ(15px);
    }
    .rev-click-hint {
        margin-top: 0.6rem;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.65);
        transform: translateZ(10px);
    }
    .rev-click-hint i { font-size: 0.65rem; }

    /* ===================== Modal ===================== */
    .rev-modal .modal-content { border-radius: 16px; border: 0; overflow: hidden; }
    .rev-modal .modal-header { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.07); }
    .rev-modal .modal-body { padding: 1.4rem 1.5rem; }
    .rev-breakdown-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px dashed #eee;
    }
    .rev-breakdown-item:last-child { border-bottom: 0; }
    .rev-breakdown-item .item-label { font-size: 0.88rem; color: #555; font-weight: 500; }
    .rev-breakdown-item .item-val { font-size: 0.95rem; font-weight: 700; color: #222; }
    .rev-breakdown-item.total-row .item-label { font-weight: 700; color: #222; font-size: 0.95rem; }
    .rev-breakdown-item.total-row .item-val { font-size: 1.1rem; color: var(--primary_color); }

    /* ===================== Table ===================== */
    #revenue_table thead tr.table-info th {
        font-weight: 800 !important; color: #000000 !important;
    }
</style>
@endpush

@section('content')
<section class="section">
    @include('admin.layout.breadcrumbs', ['title' => __('Organization Income')])

    <div class="section-body">

        {{-- ============================================================ --}}
        {{--  4 REVENUE CARDS                                             --}}
        {{-- ============================================================ --}}
        <div class="row mb-5">
            {{-- Gross Revenue --}}
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4 rev-card-wrapper">
                <div class="card rev-card gross h-100" data-toggle="modal" data-target="#grossModal" title="{{ __('Click to view breakdown') }}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="rev-chip">{{ __('Gross Revenue') }}</span>
                            <span class="rev-icon"><i class="fas fa-chart-line"></i></span>
                        </div>
                        <div>
                            <h3 class="rev-amount">{{ $currency . number_format($onlineTicketPrice + $localTicketPrice, 2) }}</h3>
                            <div class="rev-label">{{ __('Online + Offline Combined') }}</div>
                            <div class="rev-click-hint"><i class="fas fa-info-circle"></i> {{ __('Click to view breakdown') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Online Revenue --}}
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4 rev-card-wrapper">
                <div class="card rev-card online h-100" data-toggle="modal" data-target="#onlineModal" title="{{ __('Click to view breakdown') }}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="rev-chip">{{ __('Online Revenue') }}</span>
                            <span class="rev-icon"><i class="fas fa-credit-card"></i></span>
                        </div>
                        <div>
                            <h3 class="rev-amount">{{ $currency . number_format($onlineRevenue, 2) }}</h3>
                            <div class="rev-label">{{ __('Online Payments') }}</div>
                            <div class="rev-click-hint"><i class="fas fa-info-circle"></i> {{ __('Click to view breakdown') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Local Revenue --}}
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4 rev-card-wrapper">
                <div class="card rev-card local h-100" data-toggle="modal" data-target="#localModal" title="{{ __('Click to view breakdown') }}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="rev-chip">{{ __('Offline Revenue') }}</span>
                            <span class="rev-icon"><i class="fas fa-store"></i></span>
                        </div>
                        <div>
                            <h3 class="rev-amount">{{ $currency . number_format($localRevenue, 2) }}</h3>
                            <div class="rev-label">{{ __('Offline Sales') }}</div>
                            <div class="rev-click-hint"><i class="fas fa-info-circle"></i> {{ __('Click to view breakdown') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Net Payout Card --}}
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4 rev-card-wrapper">
                <div class="card rev-card payout h-100" data-toggle="modal" data-target="#netPayoutModal" title="{{ __('Click to view breakdown') }}">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="rev-chip">{{ __('Net Payout') }}</span>
                            <span class="rev-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        </div>
                        <div>
                            <h3 class="rev-amount">{{ $currency . number_format($netPayout, 2) }}</h3>
                            <div class="rev-label">{{ __('Net Settlement Payout') }}</div>
                            <div class="rev-click-hint"><i class="fas fa-info-circle"></i> {{ __('Click to view breakdown') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{--  REVENUE TABLE                                                --}}
        {{-- ============================================================ --}}
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
                    <div class="row">
                        <div class="col-lg-8 ml-3"><h2 class="section-title">{{__('Revenue Report')}}</h2></div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ url('organization-income') }}">
                            @csrf
                            <div class="row">
                                <div class="col-lg-5">
                                    <div class="form-group">
                                        <label class="col-form-label">{{__('Event')}}</label>
                                        <select name="event_id" class="form-control select2">
                                            <option value="">{{ __('All Events') }}</option>
                                            @foreach ($events as $event)
                                            <option value="{{$event->id}}" {{ isset($request->event_id) && $request->event_id == $event->id ? 'selected' : '' }}>{{$event->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="col-form-label">{{__('Duration')}}</label>
                                        <input type="text" value="{{ isset($request->duration) ? $request->duration : '' }}" placeholder="{{ __('Choose date') }}" name="duration" class="form-control date duration">
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group mt-2">
                                        <input type="submit" class="btn btn-primary mt-4" value="Apply">
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table" id="revenue_table" data-page="organizer">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>{{__('Order Id')}}</th>
                                        <th>{{__('Customer')}}</th>
                                        <th>{{__('Event Name')}}</th>
                                        <th>{{__('Quantity')}}</th>
                                        <th>{{__('Ticket Price')}}</th>
                                        <th>{{__('Payment Gateway')}}</th>
                                        <th>{{__('Refunded')}}</th>
                                        <th>{{__('Discount')}}</th>
                                        <th>{{__('Processing Fee')}}</th>
                                        <th>{{__('Total Payment')}}</th>
                                        <th>{{__('Platform Fee')}}</th>
                                        <th>{{__('Org Tax')}}</th>
                                        <th>{{__('Total Order Value')}}</th>
                                        <th>{{__('Total Order Without Tax')}}</th>
                                        <th>{{__('Organizer Commission')}}</th>
                                        <th>{{__('Organizer Revenue')}}</th>
                                        <th>{{__('Created at')}}</th>
                                    </tr>
                                    <tr class="table-info font-weight-bold" style="background-color: var(--light_primary_color); border-bottom: 2px solid #dee2e6;">
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th><span class="text-primary d-block">{{ __('Main Total:') }}</span></th>
                                        <th id="top-total-col-6"></th>
                                        <th id="top-total-col-7"></th>
                                        <th id="top-total-col-8"></th>
                                        <th id="top-total-col-9"></th>
                                        <th id="top-total-col-processing"></th>
                                        <th id="top-total-col-10"></th>
                                        <th id="top-total-col-11"></th>
                                        <th id="top-total-col-12"></th>
                                        <th id="top-total-col-13"></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        @php
                                            $tax          = (float)$item->tax;
                                            $payment      = (float)$item->payment;
                                            $admin_rev    = (float)($item->admin_revenue ?? 0);
                                            $org_rev_db   = (float)($item->org_revenue ?? 0);
                                            $couponDiscount = (float)($item->coupon_discount ?? 0);
                                            $ticketPrice  = $payment + $couponDiscount - $tax;
                                            $orgrev       = ($admin_rev == 0 && $org_rev_db == 0)
                                                            ? (($tax < 0) ? $payment : $payment - $tax)
                                                            : $org_rev_db;
                                            $platformFee  = \App\Models\OrderFee::where('order_id', $item->id)->sum('amount');
                                        @endphp
                                        <tr>
                                            <td></td>
                                            <td>{{ $item->order_id }}</td>
                                            <td>
                                                @if($item->customer)
                                                    <h6>{{ $item->customer->name . ' ' . $item->customer->last_name }}</h6>
                                                    <p>{{ $item->customer->email }}</p>
                                                @else
                                                    <h6>{{ __('No Customer Data') }}</h6>
                                                @endif
                                            </td>
                                            <td>{{ $item->event?->name ?? __('No Event') }}</td>
                                            <td>{{ $item->quantity . ' tickets' }}</td>
                                            <td>{{ $currency . number_format($ticketPrice, 2) }}</td>
                                            <td>{{ $item->payment_type === 'STRIPE' ? 'STRIPE' : 'Offline' }}</td>
                                            <td>
                                                @if ($item->payment_status == 2 || $item->order_status == 'Refunded')
                                                    {{ $currency . number_format($payment, 2) }}
                                                @else NA @endif
                                            </td>
                                            <td>{{ $tax < 0 ? $currency . number_format(abs($tax), 2) : 'NA' }}</td>
                                            {{-- Processing Fee = Tax --}}
                                            <td>{{ $tax >= 0 ? $currency . number_format($tax, 2) : 'NA' }}</td>
                                            <td>{{ $currency . number_format($payment, 2) }}</td>
                                            {{-- Platform Fee = Organizer Fee --}}
                                            <td>{{ $platformFee > 0 ? $currency . number_format($platformFee, 2) : 'NA' }}</td>
                                            <td>{{ $currency . number_format($org_rev_db, 2) }}</td>
                                            <td>{{ $currency . number_format($payment, 2) }}</td>
                                            <td>{{ $tax < 0 ? $currency . number_format($payment, 2) : $currency . number_format($payment - $tax, 2) }}</td>
                                            <td>{{ $tax < 0 ? $currency . number_format($payment, 2) : $currency . number_format($payment - $tax, 2) }}</td>
                                            <td>{{ $currency . number_format($orgrev, 2) }}</td>
                                            <td>{{ $item->created_at ? $item->created_at->format('Y-m-d') : __('No Date') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                                        <th>{{ __('Page Total:') }}</th>
                                        <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{--  MODALS                                                        --}}
{{-- ============================================================ --}}

{{-- Gross Revenue Modal --}}
<div class="modal fade rev-modal" id="grossModal" tabindex="-1" role="dialog" aria-labelledby="grossModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="grossModalLabel">
                    <i class="fas fa-chart-line text-primary mr-2"></i>{{ __('Gross Revenue Breakdown') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-credit-card mr-2 text-success"></i>{{ __('Online Sales') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlineTicketPrice, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-store mr-2 text-warning"></i>{{ __('Offline Sales') }}</span>
                    <span class="item-val">{{ $currency . number_format($localTicketPrice, 2) }}</span>
                </div>
                <div class="rev-breakdown-item total-row">
                    <span class="item-label">{{ __('Total Gross Revenue') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlineTicketPrice + $localTicketPrice, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Online Revenue Modal --}}
<div class="modal fade rev-modal" id="onlineModal" tabindex="-1" role="dialog" aria-labelledby="onlineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#00b09b,#006955); color:#fff;">
                <h5 class="modal-title font-weight-bold" id="onlineModalLabel">
                    <i class="fas fa-credit-card mr-2"></i>{{ __('Online Revenue Breakdown') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-ticket-alt mr-2 text-primary"></i>{{ __('Total Ticket Price') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlineTicketPrice, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-receipt mr-2 text-info"></i>{{ __('Total Processing Fee') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlineProcessingFee, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-cogs mr-2 text-secondary"></i>{{ __('Total Platform Fee') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlinePlatformFee, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-undo-alt mr-2 text-danger"></i>{{ __('Total Refund') }}</span>
                    <span class="item-val text-danger">{{ $currency . number_format($onlineRefunded, 2) }}</span>
                </div>
                <div class="rev-breakdown-item total-row">
                    <span class="item-label">{{ __('Total Online Revenue') }}</span>
                    <span class="item-val">{{ $currency . number_format($onlineRevenue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Local Revenue Modal --}}
<div class="modal fade rev-modal" id="localModal" tabindex="-1" role="dialog" aria-labelledby="localModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#f7971e,#c45e00); color:#fff;">
                <h5 class="modal-title font-weight-bold" id="localModalLabel">
                    <i class="fas fa-store mr-2"></i>{{ __('Offline Revenue Breakdown') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-ticket-alt mr-2 text-primary"></i>{{ __('Total Ticket Price') }}</span>
                    <span class="item-val">{{ $currency . number_format($localTicketPrice, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-receipt mr-2 text-info"></i>{{ __('Total Processing Fee') }}</span>
                    <span class="item-val">{{ $currency . number_format($localProcessingFee, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-cogs mr-2 text-secondary"></i>{{ __('Total Platform Fee') }}</span>
                    <span class="item-val">{{ $currency . number_format($localPlatformFee, 2) }}</span>
                </div>
                <div class="rev-breakdown-item">
                    <span class="item-label"><i class="fas fa-undo-alt mr-2 text-danger"></i>{{ __('Total Refund') }}</span>
                    <span class="item-val text-danger">{{ $currency . number_format($localRefunded, 2) }}</span>
                </div>
                <div class="rev-breakdown-item total-row">
                    <span class="item-label">{{ __('Total Offline Revenue') }}</span>
                    <span class="item-val">{{ $currency . number_format($localRevenue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Net Payout Invoice Modal --}}
<div class="modal fade" id="netPayoutModal" tabindex="-1" role="dialog" aria-labelledby="netPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 20px; border: 0; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0" style="padding: 1.5rem 2rem;">
                <h5 class="modal-title font-weight-bold text-dark" id="netPayoutModalLabel">
                    <i class="fas fa-file-invoice text-success mr-2"></i>Net Payout Invoice View
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 1.5rem 2rem 2rem;">
                {{-- Invoice Container --}}
                <div class="invoice-box p-4" style="background-color: #fafbfc; border: 1px solid #eaedf1; border-radius: 12px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
                    
                    {{-- Invoice Header --}}
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 2px solid #eaedf1;">
                        <div>
                            <h4 class="font-weight-bold text-primary mb-1">{{ Auth::user()->name }}</h4>
                            <p class="text-muted mb-0" style="font-size: 0.8rem;">Revenue Summary & Settlement Invoice</p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-success px-3 py-2 font-weight-bold" style="border-radius: 40px;">SETTLED</span>
                        </div>
                    </div>

                    {{-- Invoice Items Table --}}
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <thead>
                                <tr style="border-bottom: 2px solid #eaedf1; font-weight: 700; color: #555; font-size: 0.85rem; text-transform: uppercase;">
                                    <th>Description</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Gross Revenue (starting point) --}}
                                <tr style="border-bottom: 0; font-size: 0.9rem;">
                                    <td class="pt-3 pb-1 text-dark font-weight-medium">
                                        <i class="fas fa-plus-circle text-success mr-2"></i>Gross Revenue
                                        <small class="d-block text-muted">Total online + offline ticket sales (incl. tax)</small>
                                    </td>
                                    <td class="pt-3 pb-1 text-right text-success font-weight-bold">+{{ $currency . number_format($onlineTicketPrice + $localTicketPrice, 2) }}</td>
                                </tr>
                                {{-- Gross Revenue sub-breakdown --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.85rem;">
                                    <td class="pt-0 pb-3 pl-4" colspan="2">
                                        <strong class="text-dark mr-3">
                                            <i class="fas fa-credit-card text-success mr-1"></i>
                                            Online: {{ $currency . number_format($onlineTicketPrice, 2) }}
                                        </strong>
                                        <strong class="text-dark">
                                            <i class="fas fa-store text-warning mr-1"></i>
                                            Offline: {{ $currency . number_format($localTicketPrice, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                                {{-- Deduction: Offline Sales --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Offline Sales
                                        <small class="d-block text-muted">Cash collected directly by organizer (not settled via platform)</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($localTicketPrice, 2) }}</td>
                                </tr>
                                {{-- Deduction: Processing Fee (Online) --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Processing Fee (Online)
                                        <small class="d-block text-muted">Stripe/payment gateway tax on online orders</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($onlineProcessingFee, 2) }}</td>
                                </tr>
                                {{-- Deduction: Processing Fee (Offline) --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Processing Fee (Offline)
                                        <small class="d-block text-muted">Tax on offline/cash orders</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($localProcessingFee, 2) }}</td>
                                </tr>
                                {{-- Deduction: Platform Fee (Online) --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Platform Fee (Online)
                                        <small class="d-block text-muted">Platform commission on online sales</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($onlinePlatformFee, 2) }}</td>
                                </tr>
                                {{-- Deduction: Refunded Amount --}}
                                <tr style="border-bottom: 1px solid #f1f3f5; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Refunded Amount
                                        <small class="d-block text-muted">Total refunds issued to customers</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($onlineRefunded + $localRefunded, 2) }}</td>
                                </tr>
                                {{-- Deduction: Platform Fee (Offline) --}}
                                <tr style="border-bottom: 1px solid #eaedf1; font-size: 0.9rem;">
                                    <td class="py-3 text-dark font-weight-medium">
                                        <i class="fas fa-minus-circle text-danger mr-2"></i>Platform Fee (Offline)
                                        <small class="d-block text-muted">Platform commission on offline sales</small>
                                    </td>
                                    <td class="py-3 text-right text-danger font-weight-bold">-{{ $currency . number_format($localPlatformFee, 2) }}</td>
                                </tr>
                                {{-- Net Payout Total Row --}}
                                <tr>
                                    <td class="pt-4 pb-2 text-dark font-weight-bold" style="font-size: 1.1rem;">
                                        Net Settlement Payout
                                    </td>
                                    <td class="pt-4 pb-2 text-right text-primary font-weight-bold" style="font-size: 1.4rem; font-family: Courier, monospace;">
                                        {{ $currency . number_format($netPayout, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
