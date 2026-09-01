@extends('master')

@section('content')
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Donations'),
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('status') }}
                    </div>
                </div>
                @endif
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        {{-- Filters --}}
                        <form method="get" action="{{ url('donations') }}" class="mb-4">
                            <div class="row">
                                <div class="col-lg-3">
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
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label>{{ __('Status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="">{{ __('All') }}</option>
                                            <option value="succeeded" {{ ($request->status ?? '') === 'succeeded' ? 'selected' : '' }}>{{ __('Succeeded') }}</option>
                                            <option value="pending" {{ ($request->status ?? '') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                            <option value="failed" {{ ($request->status ?? '') === 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>{{ __('Date Range') }}</label>
                                        <input type="text" name="duration" class="form-control date duration" placeholder="{{ __('Choose date range') }}" value="{{ $request->duration ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label>{{ __('Search') }}</label>
                                        <input type="text" name="search" class="form-control" placeholder="{{ __('don_1 or pi_...') }}" value="{{ $request->search ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                            <a href="{{ url('donations') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        {{-- Summary + Export --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3" style="gap:16px;">
                                    <h5 class="mb-0">
                                        {{ __('Total Collected') }}:
                                        <strong class="text-success">{{ $currency }}{{ number_format($totalAmount, 2) }}</strong>
                                    </h5>
                                    <span class="badge badge-info" style="font-size:0.85rem;">{{ $donations->count() }} {{ __('records') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="{{ url('donations') . '?' . http_build_query(array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-csv"></i> {{ __('Export CSV') }}
                                </a>
                                <a href="{{ url('donations') . '?' . http_build_query(array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-file-pdf"></i> {{ __('Export PDF') }}
                                </a>
                            </div>
                        </div>

                        {{-- Table --}}
                        <div class="table-responsive">
                            <table class="table table-striped" id="tableDonations">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Donation ID') }}</th>
                                        <th>{{ __('Event') }}</th>
                                        <th>{{ __('Donor') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Payment Intent') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($donations as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge badge-success" style="font-size:0.8rem;">don_{{ $item->id }}</span>
                                        </td>
                                        <td>
                                            {{ $item->event ? \Illuminate\Support\Str::limit($item->event->name, 35) : 'N/A' }}
                                        </td>
                                        <td>
                                            @php
                                                $donor = $item->appUser
                                                    ?? ($item->app_user_id ? \App\Models\AppUser::find($item->app_user_id) : null);
                                                $isGuest = false;
                                                if (!$donor) {
                                                    $donor = $item->guestUser
                                                        ?? ($item->guest_user_id ? \App\Models\GuestUser::find($item->guest_user_id) : null);
                                                    $isGuest = (bool) $donor;
                                                }
                                            @endphp
                                            @if($donor)
                                                <i class="fas fa-{{ $isGuest ? 'user-secret text-secondary' : 'user text-primary' }} mr-1"></i>
                                                {{ $donor->name }} {{ $donor->last_name }}
                                                @if($donor->email)
                                                    <br><small class="text-muted">{{ $donor->email }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted"><i class="fas fa-user-secret mr-1"></i>{{ __('Guest') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-success">{{ $currency }}{{ number_format($item->amount, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($item->status === 'succeeded')
                                                <span class="badge badge-success">{{ __('Succeeded') }}</span>
                                            @elseif($item->status === 'pending')
                                                <span class="badge badge-warning">{{ __('Pending') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ ucfirst($item->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <code style="font-size:0.75rem;">{{ \Illuminate\Support\Str::limit($item->payment_intent_id, 22) }}</code>
                                        </td>
                                        <td>{{ $item->created_at ? $item->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                                        <td>
                                            <a href="{{ url('donation/' . $item->id) }}" class="btn btn-primary btn-sm" title="{{ __('View') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">{{ __('No donations found.') }}</td>
                                    </tr>
                                    @endforelse
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
    $('#tableDonations').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No entries available",
            "zeroRecords": "No matching records found"
        }
    });
});
</script>
@endsection
