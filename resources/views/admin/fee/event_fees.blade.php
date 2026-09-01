@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Event Fee Mapping'),
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
                            <div class="row mb-4 mt-2">
                                <div class="col-lg-12">
                                    <h2 class="section-title mt-0"> {{ __('Configure Event Fees') }}</h2>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="report_table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{{ __('Event Name') }}</th>
                                            <th>{{ __('Organizer') }}</th>
                                            <th>{{ __('Assigned Fee Types') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($events as $event)
                                            <tr>
                                                <td></td>
                                                <td>{{ $event->name }}</td>
                                                <td>{{ $event->user?->name }}</td>
                                                <td>
                                                    @if (empty($event->assigned_fees))
                                                        <span class="badge badge-light">{{ __('Default Only') }}</span>
                                                    @else
                                                        @foreach ($event->assigned_fees as $feeName)
                                                            <span class="badge badge-info m-1">{{ $feeName }}</span>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('event-fee.edit', $event->id) }}" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-cog"></i> {{ __('Configure Fees') }}
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
@endsection
