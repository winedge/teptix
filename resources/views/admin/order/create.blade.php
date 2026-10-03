@extends('master')
@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp

<section class="section">
    <div class="section-body">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div id="order-create-success-alert" class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa fa-check-circle mr-2"></i> {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <div class="card shadow-sm style-card border-0">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h2 class="section-title mt-0 mb-1" style="font-weight: 700; color: #1e293b;">{{ __('Create Order Behalf of User') }}</h2>
                                <p class="text-muted small mb-0">{{ __('Fill out the details below to complete an internal booking sequence for a client.') }}</p>
                            </div>
                        </div>

                        <form method="post" action="{{ route('orderCreateForUser') }}" class="modern-form">
                            @csrf
                            <input type="hidden" name="venue_seat_ids" id="venueSeatIdsInput" value="">

                            <div class="row">
                                <div class="col-12 form-group mb-3">
                                    <label class="form-label-bold">{{ __('Customer Name') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                        </div>
                                        <input name="name" type="text" class="form-control @error('name') is-invalid @enderror" placeholder="Enter full name">
                                    </div>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label-bold">{{ __('Email Address') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                        </div>
                                        <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" required>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label-bold">{{ __('Mobile Number') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                        </div>
                                        <input name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" id="mobileNumber" placeholder="10-digit number" required maxlength="10" pattern="\d{10}" inputmode="numeric" title="{{ __('Enter 10 digit mobile number') }}">
                                    </div>
                                    <small class="form-text text-muted mt-1">{{ __('Numbers only, no spaces or dashes.') }}</small>
                                    @error('phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label-bold">{{ __('Target Event') }}</label>
                                    <div class="input-group event-input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                        </div>
                                        <select class="form-control select2 eventId" name="event_id" required>
                                            <option value="" disabled selected>{{ __('Choose an event...') }}</option>
                                            @foreach ($eventData as $_eventData)
                                                <option value="{{ $_eventData->id }}" data-has-venue-map="{{ $_eventData->liveVenueMap ? 1 : 0 }}">{{ $_eventData->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('ticket_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 form-group mb-3">
                                    <label class="form-label-bold">{{ __('Tax Configuration Strategy') }}</label>
                                    <div class="input-group tax-input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-percent"></i></span>
                                        </div>
                                        <select class="form-control style-select" name="tax_option" id="taxOption" required>
                                            <option value="with_tax" selected>{{ __('Standard Configuration (With Tax)') }}</option>
                                            <option value="without_tax">{{ __('Tax Exempt (Without Tax)') }}</option>
                                            <option value="complimentary">{{ __('Complimentary Allocation (Free)') }}</option>
                                            <option value="custom_amount">{{ __('Custom Override with Manual Discount') }}</option>
                                        </select>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ __('Governs global transactional properties & automated fee adjustments applied downstream.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-group d-none ticketdiv mb-3">
                                <label class="form-label-bold">{{ __('Select Actionable Date') }}</label>
                                <input type="text" name="ticket_date" id="start_time" value="{{ old('ticket_date') }}" placeholder="{{ __('Choose Date') }}" class="form-control date @error('ticket_date') is-invalid @enderror">
                                @error('ticket_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4 border-light-subtle">

                            <div class="mb-2">
                                <h4 class="form-section-heading mb-3"><i class="fa fa-tags mr-2 text-primary"></i>{{ __('Ticket Allocation & Breakdown') }}</h4>
                            </div>

                            <div id="item-list">
                                <div class="item-container row mx-0 mb-3 align-items-center p-3 rounded-lg border">
                                    <div class="col-md-4 px-2 mb-2 mb-md-0">
                                        <label class="form-label-bold small mb-1">{{ __('Select Ticket Tier') }}</label>
                                        <select class="form-control ticket-dropdown select2" id="ticketId1" name="ticket_id[]" required style="width:100% !important">
                                            <option value="" disabled selected>{{ __('Please select event context first') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 px-2 mb-2 mb-md-0">
                                        <label class="form-label-bold small mb-1">{{ __('Quantity') }}</label>
                                        <input type="number" name="quantity[]" class="form-control quantity-input" id="quantityInput1" min="1" value="1" required>
                                    </div>

                                    <div class="tax-custom-inline col-md-4 col-sm-12 px-2 mb-2 mb-md-0" style="display: none;">
                                        <label class="form-label-bold small mb-1">{{ __('Custom Adjusted Price') }}</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                                            <input type="number" step="0.01" min="0" name="tax_custom_amount[]" id="taxCustomAmount" class="form-control custom-price-input" value="{{ old('tax_custom_amount.0', old('tax_custom_amount', 0)) }}" />
                                        </div>
                                        <small class="text-muted xs-text d-block mt-1">{{ __('Overrides individual unit base value details.') }}</small>
                                    </div>
                                    <div class="col-md-2 col-sm-6 px-2"></div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <button id="add-more" type="button" class="btn btn-light border btn-sm shadow-xs"><i class="fa fa-plus mr-1"></i> {{ __('Add Another Ticket Component') }}</button>
                            </div>

                            <div id="venueSeatMapAction" class="venue-seat-map-action mb-4" style="display:none;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <span class="form-label-bold mb-1 d-block">{{ __('Venue Seat Map') }}</span>
                                        <span class="text-muted small" id="venueSeatMapSummary">{{ __('Select ticket quantities, then choose seats from the map.') }}</span>
                                    </div>
                                    <button type="button" id="openVenueSeatMapBtn" class="btn btn-outline-primary btn-sm" disabled>
                                        <i class="fa fa-map-marked-alt mr-1"></i> {{ __('Select Map') }}
                                    </button>
                                </div>
                                @error('venue_seat_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4 border-light-subtle">

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <button type="button" id="previewBookingBtn" class="btn btn-outline-primary px-4 py-2" disabled title="{{ __('Fill in all required fields to preview') }}" style="border-radius:6px; font-weight:600;">
                                        <i class="fa fa-eye mr-2"></i> {{ __('Preview Summary') }}
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" id="mainSubmitBtn" class="btn btn-primary px-5 py-2" style="border-radius:6px; font-weight:600; min-width: 180px;">
                                        <i class="fa fa-check-circle mr-2"></i> {{ __('Complete Booking') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="bookingPreviewModal" tabindex="-1" role="dialog" aria-labelledby="bookingPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-dark text-white p-4 border-0">
                <div class="d-flex align-items-center">
                    <div class="modal-icon-circle mr-3">
                        <i class="fa fa-file-alt text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0 text-white">{{ __('Order Preview Summary') }}</h5>
                        <p class="mb-0 text-white-50 small">{{ __('Review details before formal transaction initialization') }}</p>
                    </div>
                </div>
                <button type="button" class="close text-white opacity-75" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size:24px;">&times;</span>
                </button>
            </div>

            <div class="modal-body p-0 bg-light">
                <div class="bg-white border-bottom px-4 py-3">
                    <div class="row text-secondary">
                        <div class="col-sm-3 mb-2 mb-sm-0">
                            <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Customer') }}</span>
                            <span class="font-weight-bold text-dark text-truncate d-block" id="preview_name">-</span>
                        </div>
                        <div class="col-sm-4 mb-2 mb-sm-0">
                            <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Email Contact') }}</span>
                            <span class="font-weight-bold text-dark text-break d-block" id="preview_email">-</span>
                        </div>
                        <div class="col-sm-3 mb-2 mb-sm-0">
                            <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Phone') }}</span>
                            <span class="font-weight-bold text-dark d-block" id="preview_phone">-</span>
                        </div>
                        <div class="col-sm-2">
                            <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Date') }}</span>
                            <span class="font-weight-bold text-dark d-block" id="preview_date">-</span>
                        </div>
                    </div>
                </div>

                <div class="bg-primary-subtle border-bottom px-4 py-3 d-flex align-items-center">
                    <i class="fa fa-calendar text-primary mr-3" style="font-size:20px;"></i>
                    <div>
                        <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Assigned Target Event') }}</span>
                        <h6 class="font-weight-bold mb-0 text-primary" id="preview_event">-</h6>
                    </div>
                </div>

                <div class="p-4">
                    <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold mb-2"><i class="fa fa-list-ul mr-2"></i>{{ __('Line Items Matrix') }}</span>
                    <div class="table-responsive rounded border bg-white shadow-xs">
                        <table class="table mb-0 small">
                            <thead>
                                <tr class="bg-light">
                                    <th class="border-0 font-weight-bold text-secondary">#</th>
                                    <th class="border-0 font-weight-bold text-secondary">{{ __('Ticket Item Breakdown') }}</th>
                                    <th class="border-0 font-weight-bold text-secondary text-center">{{ __('Qty') }}</th>
                                    <th class="border-0 font-weight-bold text-secondary text-right">{{ __('Base Rate') }}</th>
                                    <th class="border-0 font-weight-bold text-secondary text-right">{{ __('Calculated Total') }}</th>
                                </tr>
                            </thead>
                            <tbody id="preview_ticket_rows"></tbody>
                        </table>
                    </div>
                </div>

                <div class="px-4 pb-4">
                    <div class="bg-white border rounded p-3 shadow-xs">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Taxation Strategy Applied') }}</span>
                                <span class="badge badge-secondary font-weight-bold mt-1 px-2.5 py-1.5" id="preview_tax_option">-</span>
                                <div id="preview_custom_price_wrap" class="mt-3" style="display:none;">
                                    <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Custom Overridden Rate/Unit') }}</span>
                                    <span class="font-weight-bold text-dark" id="preview_custom_price">-</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-right">
                                <div class="mb-1 text-muted">{{ __('Subtotal Reference Value:') }} <span class="font-weight-bold text-dark ml-1" id="preview_subtotal">{{ $currency }}0.00</span></div>
                                <div class="border-top dashed my-2"></div>
                                <span class="d-block text-uppercase text-muted tracking-wider small font-weight-bold">{{ __('Grand Estimated Invoice Total:') }}</span>
                                <div class="display-4 font-weight-bold text-success mt-1" id="preview_grand_total" style="font-size: 28px;">{{ $currency }}0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded p-2 small d-flex align-items-start">
                        <i class="fa fa-info-circle mr-2 mt-0.5"></i>
                        <span>{{ __('Note: Operational service fee variables and structural itemized local processing taxes will clear directly matching server calculation specifications upon final confirmation.') }}</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white px-4 py-3 border-top justify-content-between">
                <button type="button" class="btn btn-light border px-3" data-dismiss="modal">
                    <i class="fa fa-pencil mr-1"></i> {{ __('Modify Form') }}
                </button>
                <button type="button" id="confirmBookingBtn" class="btn btn-success px-4 font-weight-bold">
                    <i class="fa fa-check mr-1"></i> {{ __('Confirm & Finalize Order') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="venueSeatMapModal" tabindex="-1" role="dialog" aria-labelledby="venueSeatMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <div>
                    <h5 class="modal-title font-weight-bold mb-0 text-white" id="venueSeatMapModalLabel">{{ __('Select Seats From Map') }}</h5>
                    <p class="mb-0 text-white-50 small" id="venueSeatMapModalSubtitle">{{ __('Choose exact seats for this order.') }}</p>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light p-3">
                <div id="venueSeatMapAlert" class="alert alert-warning py-2 px-3 small mb-3" style="display:none;"></div>
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                    <div class="small text-muted">
                        <span class="font-weight-bold text-dark" id="venueSeatMapSelectedCount">0</span>
                        <span id="venueSeatMapRequiredCount">/ 0</span>
                        {{ __('seats selected') }}
                    </div>
                    <div class="admin-seat-map-legend">
                        <span><i class="available"></i>{{ __('Available') }}</span>
                        <span><i class="selected"></i>{{ __('Selected') }}</span>
                        <span><i class="held"></i>{{ __('Held') }}</span>
                        <span><i class="booked"></i>{{ __('Booked') }}</span>
                        <span><i class="blocked"></i>{{ __('Blocked') }}</span>
                    </div>
                </div>
                <div id="venueSeatMapLoading" class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div>{{ __('Loading venue seat map...') }}</div>
                </div>
                <div id="venueSeatMapStageShell" class="admin-seat-map-shell" style="display:none;">
                    <div id="venueSeatMapStage" class="admin-seat-map-stage"></div>
                </div>
            </div>
            <div class="modal-footer bg-white justify-content-between">
                <button type="button" class="btn btn-light border" data-dismiss="modal">
                    <i class="fa fa-times mr-1"></i> {{ __('Cancel') }}
                </button>
                <button type="button" id="applyVenueSeatsBtn" class="btn btn-success">
                    <i class="fa fa-check mr-1"></i> {{ __('Use Selected Seats') }}
                </button>
            </div>
        </div>
    </div>
</div>

@if(session('order_id') !== null)
<div class="modal fade" id="processingModal" tabindex="-1" role="dialog" aria-labelledby="processingModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center py-5 px-4">
                <div class="spinner-border text-primary mb-4" role="status" style="width: 3.5rem; height: 3.5rem;">
                    <span class="sr-only">Processing workflow routines...</span>
                </div>
                <h5 class="font-weight-bold mb-2">{{ __('Generating Tickets & Dispatching Assets') }}</h5>
                <p class="text-muted small mb-0">{{ __('Synchronizing records, compiling local system PDFs and broadcasting delivery notices to client context endpoints.') }}</p>
                <div class="progress mt-4" style="height: 6px; border-radius: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="orderEmail d-none">
    @php
    $ticket_data = DB::table('order_child')
        ->select([
            'events.image', 'events.name', 'users.first_name', 'users.last_name', 'users.organization_name',
            'events.type', 'events.address', 'events.start_time', 'tickets.name as ticket_name',
            'tickets.type as ticket_type', 'order_child.ticket_number', 'order_child.Book_Seat_Id',
            'order_child.seat_id', 'seat_table.name_of_table as seat_table_name', 'app_user.email as app_email', 'guest_user.email as guest_email'
        ])
        ->join('orders', 'order_child.order_id', '=', 'orders.id')
        ->join('events', 'orders.event_id', '=', 'events.id')
        ->join('users', 'orders.organization_id', '=', 'users.id')
        ->leftJoin('app_user', function ($join) { $join->on('orders.customer_id', '=', 'app_user.id')->whereNotNull('orders.customer_id'); })
        ->leftJoin('guest_user', function ($join) { $join->on('orders.guestuser_id', '=', 'guest_user.id')->whereNotNull('orders.guestuser_id'); })
        ->join('tickets', 'order_child.ticket_id', '=', 'tickets.id')
        ->leftJoin('seat_table', 'order_child.seat_id', '=', 'seat_table.id')
        ->where('orders.id', session('order_id'))
        ->get();
    @endphp
    @foreach ($ticket_data as $ticket)
        <div class="ticket qrimageData" id="ticket">
            <div style="width: 100%; max-width: 320px; border: 2px solid #e2e8f0; border-radius: 10px; background-color: white; overflow: hidden; margin: auto;">
                <div style="background-color: #0f172a; color: white; text-align: center; padding: 12px; font-size: 14px; font-weight: bold;">
                    <span>The Event Palette</span>
                </div>
                <div style="padding: 15px; display: flex; flex-direction: column; gap: 10px; background-color: white;">
                    <div>
                        <div style="font-size: 16px; font-weight: bold; color:#1e293b;">{{ $ticket->name }}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top:2px;">{{ $ticket->organization_name }}</div>
                        <div style="font-size: 12px; color: #64748b;">{{ $ticket->type == 'online' ? 'Online Event' : $ticket->address }}</div>
                        <div style="font-size: 12px; color: #64748b;">{{ \Carbon\Carbon::parse($ticket->start_time)->format('l') }}, {{ \Carbon\Carbon::parse($ticket->start_time)->format('d F') }} | {{ \Carbon\Carbon::parse($ticket->start_time)->format('h:i a') }}</div>
                    </div>
                    <div style="border-top: 1px dashed #cbd5e1; padding-top: 10px; text-align: center; background-color: white;">
                        <div style="font-size: 18px; color: #2563eb; font-weight: bold;">{{ $ticket->ticket_name }}</div>
                        @if(strtolower($ticket->ticket_type) === 'complementry' || strtolower($ticket->ticket_type) === 'complementary')
                            <div style="font-size: 14px; font-weight: bold; color:#16a34a; margin: 4px 0;">{{ __('Free Allocation') }}</div>
                        @else
                            <div style="font-size: 14px; font-weight: bold; color:#475569; margin: 4px 0;">Tier: {{ $ticket->ticket_type }}</div>
                        @endif
                        @if($ticket->Book_Seat_Id)
                            <div style="font-size: 12px; color: #334155; margin: 5px 0; background:#f8fafc; padding:6px; border-radius:4px;">
                                <strong>Seat:</strong> {{ $ticket->Book_Seat_Id }}
                            </div>
                        @endif
                    </div>
                    <div style="text-align: center; margin-top: 5px; background-color: white;">
                        @php
                            $qrCode = QrCode::format('png')->size(150)->generate($ticket->ticket_number);
                            $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                        @endphp
                        <img src="{{ $base64QrCode }}" alt="QR Code Verification Identifier" style="width: 100%; max-width: 160px; height: auto;"/>
                    </div>
                    <div style="text-align: center; margin-top: 4px; font-size: 11px; color: #94a3b8; font-family:monospace;">#{{ $ticket->ticket_number }}</div>
                </div>
                <div style="background-color: #f8fafc; text-align: center; padding: 10px; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
                    All Sales Final - Non-Transferable
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@push("js")
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
    .style-card { border-radius: 12px; background: #ffffff; }
    .form-label-bold { font-weight: 600; color: #334155; font-size: 13.5px; margin-bottom: 6px; display: inline-block; }
    .form-section-heading { font-size: 15px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
    .item-container { background-color: #f8fafc; border-color: #e2e8f0 !important; transition: all 0.2s ease; }
    .item-container:hover { border-color: #cbd5e1 !important; background-color: #f1f5f9; }
    .input-group-text { background-color: #f8fafc; border-color: #d1d5db; color: #64748b; }
    .modern-form .form-control { border-color: #d1d5db; height: calc(2.25rem + 4px); font-size: 14px; border-radius: 0 6px 6px 0; }
    .modern-form .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
    .modern-form select.form-control { border-radius: 0 6px 6px 0; }
    .tax-input-group { flex-wrap: nowrap; }
    .tax-input-group .style-select {
        flex: 1 1 auto;
        height: auto !important;
        min-height: calc(2.25rem + 4px);
        min-width: 0;
        width: 1%;
    }
    .event-input-group { flex-wrap: nowrap; }
    .event-input-group .select2-container {
        flex: 1 1 auto;
        min-width: 0;
        width: 1% !important;
    }
    .event-input-group .select2-container .select2-selection--single {
        border-color: #d1d5db;
        border-radius: 0 6px 6px 0;
        height: calc(2.25rem + 4px);
    }
    .event-input-group .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(2.25rem + 2px);
    }
    .event-input-group .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px);
    }
    .bg-primary-subtle { background-color: #eff6ff; }
    .bg-warning-subtle { background-color: #fefce8; }
    .border-warning-subtle { border-color: #fef08a !important; }
    .text-warning-emphasis { color: #854d0e; }
    .modal-icon-circle { width: 42px; height: 42px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .tracking-wider { letter-spacing: 0.05em; }
    .xs-text { font-size: 11px; }
    .dashed { border-style: dashed !important; }
    .px-2\.5 { padding-left: 10px; padding-right: 10px; }
    .py-1\.5 { padding-top: 6px; padding-bottom: 6px; }
    .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .venue-seat-map-action { border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 8px; padding: 12px 14px; }
    .admin-seat-map-shell { position: relative; overflow: auto; max-height: 62vh; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; }
    .admin-seat-map-stage { position: relative; min-width: 720px; line-height: 0; background: linear-gradient(90deg, rgba(15, 23, 42, 0.05) 1px, transparent 1px), linear-gradient(0deg, rgba(15, 23, 42, 0.05) 1px, transparent 1px), #f8fafc; background-size: 30px 30px; }
    .admin-seat-map-stage img { display: block; width: 100%; height: 100%; object-fit: contain; pointer-events: none; user-select: none; }

    .admin-seat-dot {
        position: absolute;
        width: 19px;
        height: 17px;
        padding: 0;
        box-sizing: border-box;
        border-radius: 16% 16% 50% 50% / 22% 22% 65% 65%;
        transform: translate(-50%, -50%);
        border: 1.25px solid rgba(255, 255, 255, 0.6);
        background: #10b981;
        color: #ffffff;
        font-size: 0;
        font-weight: 700;
        line-height: 17px;
        text-align: center;
        box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.14), 0 2px 5px rgba(15, 23, 42, 0.28), inset 0 -1px 0 rgba(255, 255, 255, 0.4);
        cursor: pointer;
        z-index: 2;
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }

    .admin-seat-leg {
        position: absolute;
        top: -4px;
        width: 4px;
        height: 5px;
        background: inherit;
        border: inherit;
        border-bottom: none;
        box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.14);
        border-radius: 3px 3px 0 0;
        pointer-events: none;
        z-index: -1;
    }

    .admin-seat-leg-l { left: 1px; }
    .admin-seat-leg-r { right: 1px; }

    .admin-seat-dot:hover:not(:disabled) {
        transform: translate(-50%, -50%) scale(1.25) rotate(-4deg);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.35);
        z-index: 10;
    }

    .admin-seat-dot[data-seat-price]:not([data-seat-price=""]):hover::after {
        content: attr(data-seat-price);
        position: absolute;
        left: 50%;
        top: auto;
        right: auto;
        bottom: calc(100% + 8px);
        width: max-content;
        height: auto;
        transform: translateX(-50%);
        padding: 4px 6px;
        border: none;
        border-radius: 4px;
        background: rgba(15, 23, 42, 0.92);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.3);
        pointer-events: none;
        z-index: 20;
    }

    .admin-seat-dot.available { background: #10b981; color: #ffffff; }
    .admin-seat-dot.selected {
        background: #2563eb;
        color: #ffffff;
        box-shadow: inset 0 3px 0 rgba(0, 0, 0, 0.18), inset 0 -1px 0 rgba(255, 255, 255, 0.4), 0 0 0 3px rgba(37, 99, 235, 0.28), 0 4px 10px rgba(15, 23, 42, 0.28);
    }
    .admin-seat-dot.held { background: #f59e0b; color: #111827; cursor: not-allowed; }
    .admin-seat-dot.booked { background: #ef4444; color: #ffffff; cursor: not-allowed; box-shadow: none; }
    .admin-seat-dot.blocked { background: #94a3b8; color: #ffffff; cursor: not-allowed; box-shadow: none; }
    .admin-seat-dot:disabled { cursor: not-allowed; opacity: 0.85; }

    .admin-seat-map-legend { display: flex; flex-wrap: wrap; gap: 10px; color: #475569; font-size: 12px; }
    .admin-seat-map-legend span { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; padding: 4px 10px; }
    .admin-seat-map-legend i { width: 13px; height: 11px; border-radius: 50% 50% 16% 16% / 65% 65% 22% 22%; display: inline-block; }
    .admin-seat-map-legend i.available { background: #10b981; }
    .admin-seat-map-legend i.selected { background: #2563eb; }
    .admin-seat-map-legend i.held { background: #f59e0b; }
    .admin-seat-map-legend i.booked { background: #ef4444; }
    .admin-seat-map-legend i.blocked { background: #94a3b8; }

    @media (max-width: 1399.98px) {
        .section {
            padding-top: 0.75rem !important;
        }

        .section .section-body {
            padding-top: 0 !important;
        }

        .style-card .card-body {
            padding: 1rem !important;
        }

        .style-card .row.mb-4 {
            margin-bottom: 0.75rem !important;
        }

        .modern-form .row.mt-2 {
            margin-top: 0 !important;
        }

        .modern-form .form-group,
        .modern-form .form-group.mb-3 {
            margin-bottom: 0.65rem !important;
        }

        .form-label-bold {
            font-size: 12.5px;
            margin-bottom: 4px;
        }

        .modern-form .form-control:not(.style-select),
        .event-input-group .select2-container .select2-selection--single {
            height: 34px;
            font-size: 13px;
        }

        .tax-input-group .style-select {
            height: auto !important;
            min-height: 34px;
        }

        .event-input-group .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 32px;
        }

        .event-input-group .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 32px;
        }

        .input-group-text {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .my-4 {
            margin-bottom: 0.85rem !important;
            margin-top: 0.85rem !important;
        }

        .item-container {
            margin-bottom: 0.75rem !important;
            padding: 0.75rem !important;
        }
    }

    @media (max-width: 575.98px) {
        .tax-input-group,
        .event-input-group {
            width: 100%;
        }

        .tax-input-group .input-group-prepend,
        .event-input-group .input-group-prepend {
            flex: 0 0 auto;
        }

        .tax-input-group .style-select {
            font-size: 12px;
            height: auto !important;
            line-height: 1.25;
            min-height: 38px;
            min-width: 0;
            padding-left: 0.45rem;
            padding-right: 1.5rem;
            text-overflow: ellipsis;
        }

        .modern-form .text-muted.d-block.mt-1 {
            font-size: 11px;
            line-height: 1.35;
        }
    }
</style>

<script>
    $(document).ready(function() {
        var successAlert = $('#order-create-success-alert');
        if (successAlert.length) {
            setTimeout(function() {
                successAlert.alert('close');
            }, 30000);
        }

        @if(session('order_id'))
            $('#processingModal').modal('show');
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += 20;
                $('.progress-bar').css('width', progress + '%');
                if (progress >= 100) { clearInterval(progressInterval); }
            }, 800);
        @endif
    });

    setTimeout(function() {
        @if(session('order_id'))
            $('#processingModal').modal('hide');
        @endif
    }, 5000);

    $(document).ready(function() {
        var itemIndex = 1;
        var selectedVenueSeats = [];
        var venueSeatMapPayload = null;
        var activeSeatMapEventId = null;
        var venueSeatMapUrlTemplate = @json(route('admin.order.venueSeatMap', ['event' => '__EVENT__']));
        var venueSeatHoldUrl = @json(route('admin.order.holdVenueSeats'));

        function selectedEventHasVenueMap() {
            return parseInt($('.eventId option:selected').data('has-venue-map') || 0, 10) === 1;
        }

        function selectedEventId() {
            return $('.eventId').val();
        }

        function venueSeatMapUrl(eventId) {
            return venueSeatMapUrlTemplate.replace('__EVENT__', eventId);
        }

        function collectExpandedTicketIds() {
            var ticketIds = [];
            $('.item-container').each(function() {
                var ticketId = $(this).find('.ticket-dropdown').val();
                var qty = parseInt($(this).find('.quantity-input').val() || 0, 10);
                if (!ticketId || qty < 1) {
                    return;
                }

                for (var i = 0; i < qty; i++) {
                    ticketIds.push(parseInt(ticketId, 10));
                }
            });

            return ticketIds;
        }

        function selectedVenueSeatIds() {
            return selectedVenueSeats.map(function(seat) {
                return parseInt(seat.id, 10);
            });
        }

        function refreshVenueSeatHiddenInput() {
            $('#venueSeatIdsInput').val(selectedVenueSeats.length ? JSON.stringify(selectedVenueSeatIds()) : '');
        }

        function updateVenueSeatMapAction() {
            var hasMap = selectedEventHasVenueMap();
            var totalTickets = collectExpandedTicketIds().length;
            var selectedCount = selectedVenueSeats.length;

            $('#venueSeatMapAction').toggle(hasMap);
            $('#openVenueSeatMapBtn').prop('disabled', !(hasMap && totalTickets > 0));

            if (!hasMap) {
                $('#venueSeatMapSummary').text('{{ __("The selected event is not connected with a venue seat map.") }}');
                return;
            }

            if (totalTickets < 1) {
                $('#venueSeatMapSummary').text('{{ __("Select ticket quantities, then choose seats from the map.") }}');
                return;
            }

            $('#venueSeatMapSummary').text(selectedCount + '/' + totalTickets + ' {{ __("seats selected") }} - {{ __("seat selection is required for this event") }}');
        }

        function resetVenueSeatSelection(releaseHold) {
            var eventId = activeSeatMapEventId || selectedEventId();
            var hadSeats = selectedVenueSeats.length > 0;

            selectedVenueSeats = [];
            venueSeatMapPayload = null;
            refreshVenueSeatHiddenInput();
            updateVenueSeatMapAction();

            if (releaseHold && eventId && hadSeats) {
                $.ajax({
                    url: venueSeatHoldUrl,
                    type: 'POST',
                    data: {
                        _token: @json(csrf_token()),
                        event_id: eventId,
                        tickets: collectExpandedTicketIds(),
                        venue_seat_ids: []
                    }
                });
            }
        }

        function seatDataFromPayload(seat) {
            return {
                id: parseInt(seat.id, 10),
                label: seat.seat_label || '',
                ticket_id: seat.ticket_id ? parseInt(seat.ticket_id, 10) : null
            };
        }

        function selectedSeatById(seatId) {
            return selectedVenueSeats.find(function(seat) {
                return parseInt(seat.id, 10) === parseInt(seatId, 10);
            });
        }

        function showVenueSeatMapAlert(message) {
            $('#venueSeatMapAlert').text(message || '').toggle(!!message);
        }

        function updateVenueSeatMapCounts() {
            var requiredCount = collectExpandedTicketIds().length;
            $('#venueSeatMapSelectedCount').text(selectedVenueSeats.length);
            $('#venueSeatMapRequiredCount').text('/ ' + requiredCount);
            $('#applyVenueSeatsBtn').prop('disabled', selectedVenueSeats.length !== requiredCount || requiredCount < 1);
            refreshVenueSeatHiddenInput();
            updateVenueSeatMapAction();
        }

        function updateSeatButtonClasses() {
            $('.admin-seat-dot').each(function() {
                var seatId = parseInt($(this).data('seat-id'), 10);
                $(this).toggleClass('selected', !!selectedSeatById(seatId));
            });
            updateVenueSeatMapCounts();
        }

        function syncAdminVenueSeatHold() {
            return $.ajax({
                url: venueSeatHoldUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: @json(csrf_token()),
                    event_id: selectedEventId(),
                    tickets: collectExpandedTicketIds(),
                    venue_seat_ids: selectedVenueSeatIds()
                }
            });
        }

        function renderVenueSeatMap(payload) {
            venueSeatMapPayload = payload;
            activeSeatMapEventId = String(payload.event_id);
            var map = payload.map || {};
            var width = parseInt(map.background_width || 750, 10);
            var height = parseInt(map.background_height || 550, 10);
            var $stage = $('#venueSeatMapStage').empty();

            $stage.css({
                minWidth: Math.max(720, width) + 'px',
                aspectRatio: width + ' / ' + height
            });

            if (map.background_image_url) {
                $stage.append($('<img>', {
                    src: map.background_image_url,
                    alt: map.venue_name || '{{ __("Venue map") }}'
                }));
            } else {
                $stage.append($('<div>', {
                    class: 'w-100 h-100',
                    css: { minHeight: '420px' }
                }));
            }

            (payload.seats || []).forEach(function(seat) {
                var isSelected = !!selectedSeatById(seat.id);
                var baseClassStatus = seat.class_status || seat.status || 'available';
                if (!isSelected && baseClassStatus === 'selected') {
                    baseClassStatus = seat.status || 'available';
                }
                var classStatus = isSelected ? 'selected' : baseClassStatus;
                var seatLabel = seat.title || seat.seat_label || '';
                var $button = $('<button>', {
                    type: 'button',
                    class: 'admin-seat-dot ' + classStatus,
                    text: seat.seat_number || '',
                    title: '',
                    'aria-label': seatLabel,
                    disabled: !seat.selectable && !isSelected
                }).css({
                    left: (parseFloat(seat.x_percent || 50)) + '%',
                    top: (parseFloat(seat.y_percent || 50)) + '%'
                }).attr({
                    'data-seat-id': seat.id,
                    'data-ticket-id': seat.ticket_id || '',
                    'data-selectable': seat.selectable ? '1' : '0',
                    'data-seat-price': seatLabel
                }).data('seat', seat);

                $button.append($('<i>', { class: 'admin-seat-leg admin-seat-leg-l', 'aria-hidden': 'true' }));
                $button.append($('<i>', { class: 'admin-seat-leg admin-seat-leg-r', 'aria-hidden': 'true' }));

                $stage.append($button);
            });

            $('#venueSeatMapModalSubtitle').text((map.venue_name || '{{ __("Venue") }}') + ' - ' + collectExpandedTicketIds().length + ' {{ __("tickets selected") }}');
            $('#venueSeatMapLoading').hide();
            $('#venueSeatMapStageShell').show();
            updateVenueSeatMapCounts();
        }

        function loadVenueSeatMap() {
            var eventId = selectedEventId();
            var ticketIds = collectExpandedTicketIds();

            if (!eventId || ticketIds.length < 1) {
                alert('{{ __("Please select an event and ticket quantity before opening the map.") }}');
                return;
            }

            showVenueSeatMapAlert('');
            $('#venueSeatMapLoading').show();
            $('#venueSeatMapStageShell').hide();
            $('#venueSeatMapModal').modal('show');

            $.ajax({
                url: venueSeatMapUrl(eventId),
                type: 'GET',
                dataType: 'json',
                data: {
                    tickets: ticketIds,
                    venue_seat_ids: selectedVenueSeatIds()
                },
                success: function(response) {
                    if (!response.success) {
                        showVenueSeatMapAlert(response.message || '{{ __("Unable to load venue seat map.") }}');
                        $('#venueSeatMapLoading').hide();
                        return;
                    }

                    renderVenueSeatMap(response.data);
                },
                error: function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("Unable to load venue seat map.") }}';
                    showVenueSeatMapAlert(message);
                    $('#venueSeatMapLoading').hide();
                }
            });
        }

        function ticketSeatCounts() {
            var counts = {};
            collectExpandedTicketIds().forEach(function(ticketId) {
                counts[ticketId] = (counts[ticketId] || 0) + 1;
            });
            return counts;
        }

        function selectedSeatCountsWith(nextSeat) {
            var counts = {};
            selectedVenueSeats.concat(nextSeat ? [nextSeat] : []).forEach(function(seat) {
                if (!seat.ticket_id) {
                    return;
                }
                counts[seat.ticket_id] = (counts[seat.ticket_id] || 0) + 1;
            });
            return counts;
        }

        function canAddSeatForTicket(nextSeat) {
            if (!nextSeat.ticket_id) {
                return true;
            }

            var allowed = ticketSeatCounts();
            var selectedCounts = selectedSeatCountsWith(nextSeat);
            return (selectedCounts[nextSeat.ticket_id] || 0) <= (allowed[nextSeat.ticket_id] || 0);
        }

        function selectedTicketRows() {
            return $('.item-container').filter(function() {
                return !!$(this).find('.ticket-dropdown').val();
            });
        }

        function ticketRowForSeat(seat) {
            var $rows = selectedTicketRows();

            if (seat.ticket_id) {
                return $rows.filter(function() {
                    return parseInt($(this).find('.ticket-dropdown').val(), 10) === parseInt(seat.ticket_id, 10);
                }).first();
            }

            return $rows.length === 1 ? $rows.first() : $();
        }

        function increaseQuantityForSeat(seat) {
            var $row = ticketRowForSeat(seat);

            if (!$row.length) {
                alert('{{ __("Please select the matching ticket tier before selecting this seat.") }}');
                return false;
            }

            var $quantity = $row.find('.quantity-input');
            var maxQuantity = parseInt($row.find('.ticket-dropdown option:selected').data('available-qty') || $quantity.attr('max') || 0, 10);
            var currentQuantity = parseInt($quantity.val() || 0, 10);

            if (maxQuantity > 0 && currentQuantity >= maxQuantity) {
                alert('{{ __("No more available quantity for this ticket tier.") }}');
                return false;
            }

            $quantity.val(currentQuantity + 1);
            updateVenueSeatMapAction();
            checkPreviewReady();
            return true;
        }

        function decreaseQuantityForSeat(seat) {
            var $row = ticketRowForSeat(seat);

            if (!$row.length) {
                return;
            }

            var $quantity = $row.find('.quantity-input');
            var currentQuantity = parseInt($quantity.val() || 1, 10);
            var remainingSelectedForRow = 0;

            if (seat.ticket_id) {
                selectedVenueSeats.forEach(function(selectedSeat) {
                    if (parseInt(selectedSeat.ticket_id || 0, 10) === parseInt(seat.ticket_id, 10)) {
                        remainingSelectedForRow++;
                    }
                });
            } else if (selectedTicketRows().length === 1) {
                remainingSelectedForRow = selectedVenueSeats.length;
            }

            if (currentQuantity > 1 && currentQuantity === remainingSelectedForRow + 1) {
                $quantity.val(currentQuantity - 1);
            }

            updateVenueSeatMapAction();
            checkPreviewReady();
        }

        function ensureQuantityForSeat(nextSeat) {
            if (selectedVenueSeats.length < collectExpandedTicketIds().length && canAddSeatForTicket(nextSeat)) {
                return true;
            }

            if (!increaseQuantityForSeat(nextSeat)) {
                return false;
            }

            return canAddSeatForTicket(nextSeat);
        }

        $('#taxOption').on('change', function() {
            var selectedOption = $(this).val();
            var $submitBtn = $('#mainSubmitBtn');

            if (selectedOption === 'complimentary') {
                $submitBtn.removeClass('btn-primary').addClass('btn-success');
                $submitBtn.html('<i class="fa fa-gift mr-2"></i> {{ __("Create Complimentary Allocation") }}');
                $('.tax-custom-inline').hide();
                $('.custom-price-input').prop('required', false);
            } else if (selectedOption === 'custom_amount') {
                $submitBtn.removeClass('btn-success').addClass('btn-primary');
                $submitBtn.html('<i class="fa fa-check-circle mr-2"></i> {{ __("Book Ticket with Manual Override") }}');
                $('.tax-custom-inline').show();
                $('.custom-price-input').prop('required', true);
                $('#taxCustomAmount').focus();
            } else {
                $submitBtn.removeClass('btn-success').addClass('btn-primary');
                $submitBtn.html('<i class="fa fa-check-circle mr-2"></i> {{ __("Complete Booking") }}');
                $('.tax-custom-inline').hide();
                $('.custom-price-input').prop('required', false);
            }
        });

        if ($('#taxOption').val() === 'custom_amount') {
            $('.tax-custom-inline').show();
            $('.custom-price-input').prop('required', true);
        }

        $(document).on("change", ".eventId", function() {
            var eventId = $(this).val();
            resetVenueSeatSelection(true);
            updateVenueSeatMapAction();
            $(".ticket-dropdown").empty().append('<option value="" disabled selected>Loading ticket specifications...</option>');

            if (eventId) {
                $.ajax({
                    url: "{{ route('eventTicket') }}",
                    type: "GET",
                    data: { event_id: eventId },
                    dataType: "json",
                    success: function(response) {
                        $(".ticket-dropdown").empty().append('<option value="" disabled selected>Select Specific Ticket Type</option>');
                        $.each(response.tickets, function(index, ticket) {
                            var ticketText = ticket.name + ' [' + ticket.available_quantity + ' left / base ' + ticket.price + ']';
                            var option = $('<option></option>')
                                .attr('value', ticket.id)
                                .attr('data-has-seats', ticket.has_seats)
                                .attr('data-available-qty', ticket.available_quantity)
                                .attr('data-price', ticket.price)
                                .text(ticketText);

                            if (ticket.available_quantity <= 0) {
                                option.prop('disabled', true).text(ticket.name + ' (Sold Out)');
                            }
                            $(".ticket-dropdown").append(option);
                        });
                        checkPreviewReady();
                        updateVenueSeatMapAction();
                    },
                    error: function() {
                        alert("Transaction state sync failure during dynamic pricing evaluation fetches.");
                    }
                });
            }
        });

        $(document).on("change", ".ticket-dropdown", function() {
            var selectedOption = $(this).find('option:selected');
            var availableQty = selectedOption.data('available-qty') || 0;
            var $container = $(this).closest('.item-container');
            $container.find('.quantity-input').attr('max', availableQty);

            resetVenueSeatSelection(true);
            updateDisabledOptions();
            updateGlobalDiscountMax();
            updateVenueSeatMapAction();
        });

        $(document).on('input change', '.quantity-input', function() {
            resetVenueSeatSelection(true);
            updateVenueSeatMapAction();
        });

        $('form').on('submit', function(e) {
            var hasError = false;
            if ($('#taxOption').val() === 'complimentary') {
                if (!confirm('{{ __("Confirm manual authorization override for a complimentary zero-balance distribution profile?") }}')) {
                    e.preventDefault();
                    return false;
                }
            }

            $('.quantity-input').each(function() {
                var $container = $(this).closest('.item-container');
                var selectedOption = $container.find('.ticket-dropdown option:selected');
                var availableQty = parseInt(selectedOption.data('available-qty')) || 0;
                var requestedQty = parseInt($(this).val()) || 0;
                var ticketName = selectedOption.text();

                if (requestedQty > availableQty) {
                    alert('{{ __("Error: Quantity exceeds available tickets!") }}\n' +
                        'Ticket: ' + ticketName + '\n' +
                        '{{ __("Available:") }} ' + availableQty + '\n' +
                        '{{ __("Requested:") }} ' + requestedQty);
                    hasError = true;
                    return false;
                }

                if (requestedQty < 1) {
                    alert('{{ __("Error: Quantity must be at least 1!") }}\n' + 'Ticket: ' + ticketName);
                    hasError = true;
                    return false;
                }
            });

            if ($('#taxOption').val() === 'custom_amount') {
                $('.item-container').each(function() {
                    var $customInput = $(this).find('.custom-price-input');
                    var val = $customInput.val();
                    if (val === '' || parseFloat(val) < 0 || isNaN(parseFloat(val))) {
                        alert('{{ __("Please enter a valid custom price (0 or greater) for all ticket components.") }}');
                        $customInput.focus();
                        hasError = true;
                        return false;
                    }
                });
            }

            if (hasError) {
                e.preventDefault();
                return false;
            }

            var phoneVal = ($('input[name="phone"]').val() || '').replace(/\D/g, '');
            if (phoneVal.length !== 10) {
                e.preventDefault();
                alert('{{ __("Validation Failure: Mobile tracking string metrics require an exact length constraint matching 10 digits.") }}');
                return false;
            }

            if (selectedEventHasVenueMap()) {
                var requiredSeatCount = collectExpandedTicketIds().length;

                if (requiredSeatCount < 1 || selectedVenueSeats.length !== requiredSeatCount) {
                    e.preventDefault();
                    alert('{{ __("Please select seats from the venue seat map for every ticket before creating this order.") }}');
                    return false;
                }
            }
        });

        function updateGlobalDiscountMax() {
            var minTicketPrice = Infinity;
            $('.item-container').each(function() {
                var price = parseFloat($(this).find('.ticket-dropdown option:selected').data('price') || 0);
                if (price > 0 && price < minTicketPrice) { minTicketPrice = price; }
            });
            if (minTicketPrice < Infinity && minTicketPrice > 0) {
                $('#taxCustomAmount').attr('max', minTicketPrice.toFixed(2));
            }
        }

        function updateDisabledOptions() {
            let selectedTickets = [];
            $(".ticket-dropdown").each(function() {
                let val = $(this).val();
                if (val) { selectedTickets.push(val); }
            });
            $(".ticket-dropdown").each(function() {
                $(this).find("option").each(function() {
                    if (selectedTickets.includes($(this).val()) && !$(this).is(":selected")) {
                        $(this).prop("disabled", true);
                    } else {
                        $(this).prop("disabled", false);
                    }
                });
            });
        }

        $("#add-more").click(function() {
            itemIndex++;
            var existingTickets = $(".ticket-dropdown:first").html();
            var isCustom = $('#taxOption').val() === 'custom_amount';
            var customDisplay = isCustom ? '' : 'style="display: none;"';
            var customRequired = isCustom ? 'required' : '';
            var newRow = `
                <div class="row item-container mx-0 mb-3 align-items-center p-3 rounded-lg border">
                    <div class="col-md-4 px-2 mb-2 mb-md-0">
                        <label class="form-label-bold small mb-1">{{ __('Select Ticket Tier') }}</label>
                        <select class="form-control ticket-dropdown" name="ticket_id[]" required style="width:100% !important">
                            ${existingTickets ? existingTickets : '<option value="" disabled selected>{{ __("Please select event context first") }}</option>'}
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 px-2 mb-2 mb-md-0">
                        <label class="form-label-bold small mb-1">{{ __('Quantity') }}</label>
                        <input type="number" name="quantity[]" class="form-control quantity-input" min="1" value="1" required>
                    </div>
                    <div class="tax-custom-inline col-md-4 col-sm-12 px-2 mb-2 mb-md-0" ${customDisplay}>
                        <label class="form-label-bold small mb-1">{{ __('Custom Adjusted Price') }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">{{ $currency }}</span></div>
                            <input type="number" step="0.01" min="0" name="tax_custom_amount[]" class="form-control custom-price-input" value="0" ${customRequired} />
                        </div>
                        <small class="text-muted xs-text d-block mt-1">{{ __('Overrides individual unit base value details.') }}</small>
                    </div>
                    <div class="col-md-2 col-sm-6 px-2 text-right mt-2 mt-md-0">
                        <label class="d-block mb-1">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="fa fa-trash"></i> {{ __('Drop') }}</button>
                    </div>
                </div>`;
            $("#item-list").append(newRow);
            updateDisabledOptions();
            checkPreviewReady();
            updateVenueSeatMapAction();
        });

        $(document).on("click", ".remove-item", function() {
            $(this).closest(".item-container").remove();
            resetVenueSeatSelection(true);
            updateDisabledOptions();
            checkPreviewReady();
            updateVenueSeatMapAction();
        });

        function checkPreviewReady() {
            var email = $.trim($('input[name="email"]').val());
            var phone = $.trim($('input[name="phone"]').val()).replace(/\D/g, '');
            var eventId = $('.eventId').val();
            var ticketSelected = false;
            $('.ticket-dropdown').each(function () { if ($(this).val()) { ticketSelected = true; } });

            var ready = email.length > 0 && phone.length === 10 && eventId && ticketSelected;
            $('#previewBookingBtn').prop('disabled', !ready);
        }

        $(document).on('input change', 'input[name="email"], input[name="phone"], .custom-price-input', checkPreviewReady);
        $(document).on('change', '.eventId, .ticket-dropdown', checkPreviewReady);
        $('#openVenueSeatMapBtn').on('click', loadVenueSeatMap);

        $(document).on('click', '.admin-seat-dot', function() {
            var $button = $(this);
            var seat = $button.data('seat');

            if (!seat) {
                return;
            }

            var existingIndex = selectedVenueSeats.findIndex(function(selectedSeat) {
                return parseInt(selectedSeat.id, 10) === parseInt(seat.id, 10);
            });
            var previousSeats = selectedVenueSeats.slice();

            if (existingIndex >= 0) {
                var removedSeat = selectedVenueSeats.splice(existingIndex, 1)[0];
                decreaseQuantityForSeat(removedSeat);
            } else {
                if ($button.attr('data-selectable') !== '1') {
                    return;
                }

                var nextSeat = seatDataFromPayload(seat);
                if (!ensureQuantityForSeat(nextSeat)) {
                    alert('{{ __("Selected seat does not match the selected ticket quantity.") }}');
                    return;
                }

                selectedVenueSeats.push(nextSeat);
            }

            updateSeatButtonClasses();
            $('.admin-seat-dot').prop('disabled', true);

            syncAdminVenueSeatHold()
                .fail(function(xhr) {
                    selectedVenueSeats = previousSeats;
                    updateSeatButtonClasses();
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("Unable to hold selected seats. Please try again.") }}';
                    alert(message);
                })
                .always(function() {
                    if (venueSeatMapPayload) {
                        renderVenueSeatMap(venueSeatMapPayload);
                    }
                });
        });

        $('#applyVenueSeatsBtn').on('click', function() {
            var requiredCount = collectExpandedTicketIds().length;

            if (selectedVenueSeats.length !== requiredCount) {
                alert('{{ __("Selected seat count must match selected ticket quantity.") }}');
                return;
            }

            refreshVenueSeatHiddenInput();
            updateVenueSeatMapAction();
            $('#venueSeatMapModal').modal('hide');
        });

        updateVenueSeatMapAction();

        var taxOptionLabels = {
            'with_tax': 'Standard Pricing System (With Tax)',
            'without_tax': 'Tax Exempt Operational Profile (No Tax)',
            'complimentary': 'System Authorized Complimentary Pass (Free)',
            'custom_amount': 'Manual Rate Negotiation Adjustment'
        };

        $('#previewBookingBtn').on('click', function () {
            $('#preview_name').text($('input[name="name"]').val() || '-');
            $('#preview_email').text($('input[name="email"]').val() || '-');
            $('#preview_phone').text($('input[name="phone"]').val() || '-');
            $('#preview_event').text($('.eventId option:selected').text() || '-');
            $('#preview_date').text($('input[name="ticket_date"]').val() || '-');

            var taxOpt = $('#taxOption').val();
            $('#preview_tax_option').text(taxOptionLabels[taxOpt] || taxOpt);

            var tbody = $('#preview_ticket_rows').empty();
            var subtotal = 0;
            var rowIndex = 0;
            var customPrices = [];

            $('.item-container').each(function () {
                var selectedOpt = $(this).find('.ticket-dropdown option:selected');
                if(!selectedOpt.val()) return;

                var ticketName = selectedOpt.text().split(' [')[0] || '-';
                var qty = parseInt($(this).find('.quantity-input').val() || 1);
                var unitPrice = 0;

                if (taxOpt === 'complimentary') {
                    unitPrice = 0;
                } else if (taxOpt === 'custom_amount') {
                    unitPrice = parseFloat($(this).find('.custom-price-input').val() || 0);
                    customPrices.push(unitPrice);
                } else {
                    unitPrice = parseFloat(selectedOpt.data('price') || 0);
                }

                var lineTotal = unitPrice * qty;
                subtotal += lineTotal;
                rowIndex++;

                tbody.append(
                    '<tr>' +
                    '<td>' + rowIndex + '</td>' +
                    '<td class="font-weight-bold text-dark">' + ticketName + '</td>' +
                    '<td class="text-center">' + qty + '</td>' +
                    '<td class="text-right">' + (unitPrice > 0 ? '{{ $currency }}' + unitPrice.toFixed(2) : (taxOpt === 'complimentary' ? 'Free' : '{{ $currency }}0.00')) + '</td>' +
                    '<td class="text-right font-weight-bold">' + (lineTotal > 0 ? '{{ $currency }}' + lineTotal.toFixed(2) : (taxOpt === 'complimentary' ? 'Free' : '{{ $currency }}0.00')) + '</td>' +
                    '</tr>'
                );
            });

            var grandTotal = subtotal;
            if (taxOpt === 'complimentary') {
                grandTotal = 0;
                subtotal = 0;
            }

            if (taxOpt === 'custom_amount') {
                if (customPrices.length > 0) {
                    var allSame = customPrices.every(function(p) { return p === customPrices[0]; });
                    if (allSame) {
                        $('#preview_custom_price').text('{{ $currency }}' + customPrices[0].toFixed(2) + ' per ticket');
                    } else {
                        $('#preview_custom_price').text('{{ __("Per-component custom rates applied") }}');
                    }
                } else {
                    $('#preview_custom_price').text('-');
                }
                $('#preview_custom_price_wrap').show();
            } else {
                $('#preview_custom_price_wrap').hide();
            }

            $('#preview_subtotal').text('{{ $currency }}' + Math.max(0, subtotal).toFixed(2));
            $('#preview_grand_total').text('{{ $currency }}' + Math.max(0, grandTotal).toFixed(2));
            $('#bookingPreviewModal').modal('show');
        });

        $('#confirmBookingBtn').on('click', function () {
            $('#bookingPreviewModal').modal('hide');
            $('form').off('submit').submit();
        });
    });
</script>
@endpush
@endsection
