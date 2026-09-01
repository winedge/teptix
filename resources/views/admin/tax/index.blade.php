@extends('master')

@section('content')
@php
    $currency = \App\Models\Setting::first()->currency;
@endphp
<style>
    .disabledbtn { pointer-events: none; cursor: default; opacity: 0.4; }
    .badge-pending  { background-color: #f0ad4e; color: #fff; }
    .badge-approved { background-color: #28a745; color: #fff; }
    .badge-rejected { background-color: #dc3545; color: #fff; }
    .approval-actions .btn { font-size: 0.75rem; padding: 2px 8px; }
</style>

<section class="section">
    @include('admin.layout.breadcrumbs', ['title' => __('Tax')])
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

            @if(isset($usertype) && $usertype === 'admin')
                {{-- ADMIN VIEW --}}
                
                {{-- Pending approval warning banner --}}
                @if($pendingCount > 0)
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-clock mr-1"></i>
                            <strong>{{ $pendingCount }}</strong> organizer {{ Str::plural('tax request', $pendingCount) }} pending approval.
                        </div>
                    </div>
                @endif

                {{-- Section 1: Global Taxes --}}
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-4 mt-2">
                                <div class="col-lg-8">
                                    <h2 class="section-title mt-0">{{ __('Global Taxes') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    @can('tax_create')
                                        <a href="{{ url('tax/create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> {{ __('Add New Global Tax') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="report_table_global">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Charges</th>
                                            <th>Allow in all bills</th>
                                            <th>Status</th>
                                            <th>Default</th>
                                            @can('tax_edit')
                                                <th>Action</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($taxes as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    {{ $item->amount_type === 'percentage' ? $item->price . '%' : $currency . $item->price }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->allow_all_bill == 1 ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $item->allow_all_bill == 1 ? 'Allow' : 'Deny' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->status == 1 ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $item->status == 1 ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php $def = $item->is_default == 1 ? 'checked' : ''; @endphp
                                                    <input type="radio" name="default" onclick="setDefault('{{ $item->id }}')" {{ $def }} id="default_{{ $item->id }}">
                                                </td>
                                                @can('tax_edit')
                                                    <td>
                                                        <a href="{{ route('tax.edit', $item->id) }}" class="btn-icon">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" onclick="deleteData('tax','{{ $item->id }}');" class="btn-icon text-danger">
                                                            <i class="fas fa-trash-alt text-danger"></i>
                                                        </a>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Organizer Tax Requests --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-4 mt-2">
                                <div class="col-lg-12">
                                    <h2 class="section-title mt-0">{{ __('Organizer Tax Requests') }}</h2>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="report_table_requests">
                                    <thead>
                                        <tr>
                                            <th>Organizer</th>
                                            <th>Tax Name</th>
                                            <th>Charges</th>
                                            <th>Allow in all bills</th>
                                            <th>Linked Ticket</th>
                                            <th>Approval Status</th>
                                            <th>Rejection Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($organizerTaxes as $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->user?->name }}</strong><br>
                                                    <small class="text-muted">{{ $item->user?->email }}</small>
                                                </td>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    {{ $item->amount_type === 'percentage' ? $item->price . '%' : $currency . $item->price }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->allow_all_bill == 1 ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $item->allow_all_bill == 1 ? 'Allow' : 'Deny' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($item->ticket)
                                                        {{ $item->ticket->event?->name }} - {{ $item->ticket->name }}
                                                    @else
                                                        <span class="text-muted">None</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->approval_status === 'pending')
                                                        <span class="badge badge-pending">⏳ Pending</span>
                                                    @elseif($item->approval_status === 'approved')
                                                        <span class="badge badge-approved">✅ Approved</span>
                                                    @else
                                                        <span class="badge badge-rejected">❌ Rejected</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->rejection_reason)
                                                        <span class="text-danger" title="{{ $item->rejection_reason }}">{{ Str::limit($item->rejection_reason, 40) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="approval-actions">
                                                    @if($item->approval_status === 'pending')
                                                        <form method="POST" action="{{ route('tax.approve', $item->id) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this tax and link it?')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger btn-sm ml-1"
                                                            data-toggle="modal" data-target="#rejectModal"
                                                            data-id="{{ $item->id }}" data-name="{{ $item->name }}">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    @else
                                                        <a href="#" onclick="deleteData('tax','{{ $item->id }}');" class="btn-icon text-danger">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- ORGANIZER VIEW --}}
                
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        When you submit a new tax request, it must be approved by the admin. Once approved, it will be automatically linked to your ticket.
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-4 mt-2">
                                <div class="col-lg-8">
                                    <h2 class="section-title mt-0">{{ __('My Custom Taxes') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    @can('tax_create')
                                        <a href="{{ url('tax/create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> {{ __('Request Custom Tax') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="report_table_organizer">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Charges</th>
                                            <th>Allow in all bills</th>
                                            <th>Linked Ticket</th>
                                            <th>Approval Status</th>
                                            <th>Rejection Reason</th>
                                            @if(Gate::check('tax_edit') || Gate::check('tax_delete'))
                                                <th>Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($taxes as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    {{ $item->amount_type === 'percentage' ? $item->price . '%' : $currency . $item->price }}
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->allow_all_bill == 1 ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $item->allow_all_bill == 1 ? 'Allow' : 'Deny' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($item->ticket)
                                                        {{ $item->ticket->event?->name }} - {{ $item->ticket->name }}
                                                    @else
                                                        <span class="text-muted">None</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->approval_status === 'pending')
                                                        <span class="badge badge-pending">⏳ Pending Approval</span>
                                                    @elseif($item->approval_status === 'approved')
                                                        <span class="badge badge-approved">✅ Approved</span>
                                                    @else
                                                        <span class="badge badge-rejected">❌ Rejected</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->rejection_reason)
                                                        <span class="text-danger" title="{{ $item->rejection_reason }}">{{ Str::limit($item->rejection_reason, 40) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                @if(Gate::check('tax_edit') || Gate::check('tax_delete'))
                                                    <td>
                                                        @if($item->approval_status === 'pending')
                                                            <a href="{{ route('tax.edit', $item->id) }}" class="btn-icon">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        @endif
                                                        <a href="#" onclick="deleteData('tax','{{ $item->id }}');" class="btn-icon text-danger">
                                                            <i class="fas fa-trash-alt text-danger"></i>
                                                        </a>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            @endif
        </div>
    </div>
</section>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#dc3545;color:#fff;">
                <h5 class="modal-title" id="rejectModalLabel"><i class="fas fa-times-circle mr-2"></i>Reject Tax Request</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="rejectForm" action="">
                @csrf
                <div class="modal-body">
                    <p>You are rejecting the tax: <strong id="rejectTaxName"></strong></p>
                    <div class="form-group">
                        <label for="rejection_reason">Rejection Reason <span class="text-muted">(optional)</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"
                            placeholder="Explain why this tax request is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject Tax</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    $('#rejectModal').on('show.bs.modal', function (e) {
        var btn     = $(e.relatedTarget);
        var taxId   = btn.data('id');
        var taxName = btn.data('name');
        $('#rejectTaxName').text(taxName);
        $('#rejectForm').attr('action', '/tax/' + taxId + '/reject');
        $('#rejection_reason').val('');
    });
</script>
@endpush
@endsection
