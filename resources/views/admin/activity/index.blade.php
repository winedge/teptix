@extends('master')

@section('content')
<style>
    .activity-pagination-footer {
        gap: 12px;
    }
    .activity-pagination-nav nav {
        display: inline-block;
    }
    .activity-pagination-nav .pagination {
        margin-bottom: 0;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    .activity-pagination-nav .page-item .page-link {
        min-width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        padding: 0 10px;
        margin: 0 2px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background-color: #fff;
        color: #475569;
        transition: all 0.2s ease-in-out;
        text-decoration: none;
    }
    .activity-pagination-nav .page-item .page-link i,
    .activity-pagination-nav .page-item .page-link .fa,
    .activity-pagination-nav .page-item .page-link .fas {
        font-size: 11px !important;
        line-height: 1 !important;
    }
    .activity-pagination-nav .page-item.active .page-link {
        background-color: var(--primary_color, #6777ef) !important;
        border-color: var(--primary_color, #6777ef) !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(103, 119, 239, 0.35);
    }
    .activity-pagination-nav .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: var(--primary_color, #6777ef) !important;
        border-color: var(--primary_color, #6777ef) !important;
        color: #fff !important;
    }
    .activity-pagination-nav .page-item.disabled .page-link {
        color: #94a3b8 !important;
        background-color: #f8fafc !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed;
        opacity: 0.65;
    }
    .activity-pagination-nav svg,
    .activity-pagination-footer svg {
        width: 14px !important;
        height: 14px !important;
        max-width: 14px !important;
        max-height: 14px !important;
        display: inline-block;
    }
</style>

<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Admin Activity'),
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">{{ __('Activity') }}</h4>
                        <form method="GET" action="{{ route('admin.activity.index') }}" class="form-inline">
                            <select name="activity_type" class="form-control mr-2">
                                <option value="">{{ __('All Activity') }}</option>
                                @foreach ($activityTypes as $type => $label)
                                    <option value="{{ $type }}" @selected(request('activity_type') === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                            @if (request('activity_type'))
                                <a href="{{ route('admin.activity.index') }}" class="btn btn-outline-secondary ml-2">{{ __('Clear') }}</a>
                            @endif
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Activity') }}</th>
                                        <th>{{ __('Created By') }}</th>
                                        <th>{{ __('Subject') }}</th>
                                        <th class="text-center">{{ __('Details') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activities as $activity)
                                        @php
                                            $actor = $activity->actor;
                                            $actorName = $actor
                                                ? trim(($actor->organization_name ?: '') . ' ' . ($actor->first_name ?: '') . ' ' . ($actor->last_name ?: ''))
                                                : '';
                                        @endphp
                                        <tr>
                                            <td style="min-width: 140px;">
                                                {{ optional($activity->created_at)->format('Y-m-d H:i') }}
                                            </td>
                                            <td style="min-width: 180px;">
                                                <div class="font-weight-bold">{{ $activity->title }}</div>
                                                <small class="text-muted">{{ $activityTypes[$activity->activity_type] ?? $activity->activity_type }}</small>
                                                @if ($activity->description)
                                                    <div class="text-muted small mt-1">{{ $activity->description }}</div>
                                                @endif
                                            </td>
                                            <td style="min-width: 160px;">
                                                {{ $actorName ?: __('Unknown') }}
                                                @if ($activity->actor_user_id)
                                                    <div class="text-muted small">#{{ $activity->actor_user_id }}</div>
                                                @endif
                                            </td>
                                            <td style="min-width: 150px;">
                                                {{ $activity->subject_type ? class_basename($activity->subject_type) : __('N/A') }}
                                                @if ($activity->subject_id)
                                                    <div class="text-muted small">#{{ $activity->subject_id }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center" style="min-width: 100px;">
                                                @if (!empty($activity->details))
                                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#activityDetailsModal{{ $activity->id }}" title="{{ __('View Details') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">{{ __('No activity found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($activities, 'links') && $activities->hasPages())
                            <div class="activity-pagination-footer d-flex justify-content-between align-items-center flex-wrap pt-3 mt-3 border-top">
                                <div class="text-muted small mb-2 mb-md-0">
                                    {{ __('Showing') }} <span class="font-weight-bold text-dark">{{ $activities->firstItem() ?? 0 }}</span> {{ __('to') }} <span class="font-weight-bold text-dark">{{ $activities->lastItem() ?? 0 }}</span> {{ __('of') }} <span class="font-weight-bold text-dark">{{ $activities->total() }}</span> {{ __('entries') }}
                                </div>
                                <div class="activity-pagination-nav">
                                    {{ $activities->links('vendor.pagination.bootstrap-4') }}
                                </div>
                            </div>
                        @elseif (method_exists($activities, 'count') && $activities->count())
                            <div class="activity-pagination-footer d-flex justify-content-between align-items-center flex-wrap pt-3 mt-3 border-top">
                                <div class="text-muted small">
                                    {{ __('Showing') }} <span class="font-weight-bold text-dark">1</span> {{ __('to') }} <span class="font-weight-bold text-dark">{{ $activities->count() }}</span> {{ __('of') }} <span class="font-weight-bold text-dark">{{ $activities->count() }}</span> {{ __('entries') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Details Modals --}}
    @foreach ($activities as $activity)
        @if (!empty($activity->details))
            <div class="modal fade activity-details-modal" id="activityDetailsModal{{ $activity->id }}" tabindex="-1" role="dialog" aria-labelledby="activityDetailsModalLabel{{ $activity->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header bg-primary text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h5 class="modal-title text-white" id="activityDetailsModalLabel{{ $activity->id }}">
                                <i class="fas fa-info-circle mr-1"></i> {{ __('Activity Details') }}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4 text-left">
                            <div class="mb-3 p-3 bg-light rounded" style="border-left: 4px solid #6777ef;">
                                <h6 class="mb-1 text-primary">{{ $activity->title }}</h6>
                                @if ($activity->description)
                                    <div class="text-muted small">{{ $activity->description }}</div>
                                @endif
                                <div class="text-muted small mt-1">
                                    <i class="far fa-clock mr-1"></i> {{ optional($activity->created_at)->format('Y-m-d H:i:s') }}
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 35%;">{{ __('Field') }}</th>
                                            <th>{{ __('Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($activity->details as $key => $value)
                                            <tr>
                                                <td class="font-weight-bold text-dark">{{ \Illuminate\Support\Str::headline($key) }}</td>
                                                <td>
                                                    @if (is_array($value))
                                                        <pre class="mb-0 small bg-dark text-light p-2 rounded text-left" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;"><code>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                    @else
                                                        {{ is_bool($value) ? ($value ? __('Yes') : __('No')) : ($value ?? __('N/A')) }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        @if ($activity->ip_address)
                                            <tr>
                                                <td class="font-weight-bold text-dark">{{ __('IP Address') }}</td>
                                                <td><code>{{ $activity->ip_address }}</code></td>
                                            </tr>
                                        @endif
                                        @if ($activity->user_agent)
                                            <tr>
                                                <td class="font-weight-bold text-dark">{{ __('User Agent') }}</td>
                                                <td class="small text-muted" style="word-break: break-all;">{{ $activity->user_agent }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-2">
                            <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">{{ __('Close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined') {
            $('.activity-details-modal').on('show.bs.modal', function () {
                $(this).appendTo('body');
            });
        }
    });
</script>
@endsection
