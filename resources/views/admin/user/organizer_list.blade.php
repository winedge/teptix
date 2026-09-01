@extends('master')

@push('css')
    <style>
        .action-dropdown-btn {
            align-items: center;
            background: transparent;
            border: 1px solid #ef4444;
            border-radius: 6px;
            color: #ef4444;
            display: inline-flex;
            font-size: 0.72rem;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .action-dropdown-btn:hover {
            background-color: #fef2f2;
        }

        .dropdown-item-custom {
            align-items: center;
            color: #334155;
            display: flex;
            font-size: 0.875rem;
            gap: 10px;
            padding: 0.5rem 1rem;
        }

        .dropdown-item-custom:hover {
            background-color: #f1f5f9;
            color: #0f172a;
            text-decoration: none;
        }
    </style>
@endpush

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => $pageTitle,
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
                    @if (session('statusblock'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('statusblock') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-4 mt-2 align-items-center">
                                <div class="col-md-6">
                                    <h2 class="section-title mt-0">{{ $pageTitle }}</h2>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="{{ url('users') }}" class="btn btn-light border text-muted font-weight-bold mr-2 mb-2 mb-lg-0 px-3" style="background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;">
                                        {{ __('Active Organizers') }}
                                    </a>
                                    <a href="{{ route('users.recentOrganizers') }}" class="btn {{ request()->routeIs('users.recentOrganizers') ? 'text-white' : 'btn-light border text-muted' }} font-weight-bold mr-2 mb-2 mb-lg-0 px-3" style="{{ request()->routeIs('users.recentOrganizers') ? 'background-color: #ff0000; border-radius: 6px; font-size: 14px;' : 'background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;' }}">
                                        {{ __('Recent Organizer') }}
                                    </a>
                                    <a href="{{ route('users.organizerPending') }}" class="btn {{ request()->routeIs('users.organizerPending') ? 'text-white' : 'btn-light border text-muted' }} font-weight-bold mr-2 mb-2 mb-lg-0 px-3" style="{{ request()->routeIs('users.organizerPending') ? 'background-color: #ff0000; border-radius: 6px; font-size: 14px;' : 'background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;' }}">
                                        {{ __('Pending Organizer') }}
                                    </a>
                                    <a href="{{ route('users.organizerDenied') }}" class="btn {{ request()->routeIs('users.organizerDenied') ? 'text-white' : 'btn-light border text-muted' }} font-weight-bold px-3" style="{{ request()->routeIs('users.organizerDenied') ? 'background-color: #ff0000; border-radius: 6px; font-size: 14px;' : 'background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;' }}">
                                        {{ __('Denied Organizer') }}
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="report_table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{{ __('Organizer') }}</th>
                                            <th>{{ __('Organization') }}</th>
                                            <th>{{ __('Phone') }}</th>
                                            <th>{{ __('Onboarding') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            @if ($debugMode == true && Auth::user()->hasRole('admin'))
                                                <th>{{ __('Login') }}</th>
                                            @endif
                                            @if (Gate::check('user_edit'))
                                                <th>{{ __('Action') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $item)
                                            <tr>
                                                <td></td>
                                                <td>
                                                    <div class="media">
                                                        <img alt="image" class="mr-3 avatar"
                                                            src="{{ url('images/upload/' . $item->image) }}">
                                                        <div class="media-body">
                                                            <div class="media-title mb-0">
                                                                {{ $item->first_name . ' ' . $item->last_name }}
                                                            </div>
                                                            <div class="media-description text-muted">{{ $item->email }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $item->organization_name ?: __('No Data') }}</td>
                                                <td>{{ $item->phone }}</td>
                                                <td>
                                                    <span class="badge {{ $item->onboarding_completed_at ? 'badge-success' : 'badge-warning' }} m-1">
                                                        {{ $item->onboarding_completed_at ? __('Completed') : __('Pending') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($item->is_verify == '1')
                                                        <span class="badge badge-success m-1">{{ __('Verified') }}</span>
                                                    @elseif ($item->is_verify == '2')
                                                        <span class="badge badge-danger m-1">{{ __('Denied') }}</span>
                                                    @else
                                                        <span class="badge badge-warning m-1">{{ __('Unverified') }}</span>
                                                    @endif
                                                </td>
                                                @if ($debugMode == true && Auth::user()->hasRole('admin'))
                                                    <td>
                                                        <a href="{{ route('loginAsOrganizer', $item->id) }}" class="btn btn-primary">
                                                            {{ __('Login As') }}
                                                        </a>
                                                    </td>
                                                @endif
                                                @if (Gate::check('user_edit'))
                                                    <td>
                                                        <div class="dropdown d-inline-block">
                                                            <button class="action-dropdown-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right shadow-sm border border-light rounded-lg py-2">
                                                                <a class="dropdown-item dropdown-item-custom" href="{{ route('users.edit', $item->id) }}">
                                                                    <i class="far fa-edit text-muted"></i> {{ __('Edit') }}
                                                                </a>
                                                                @if (!empty($showVerifyAction) && $item->is_verify != 1)
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item dropdown-item-custom text-success" href="#" onclick="event.preventDefault(); if(confirm('{{ __('Verify this organizer?') }}')) document.getElementById('verify-form-{{ $item->id }}').submit();">
                                                                        <i class="fas fa-check text-success"></i> {{ __('Verify') }}
                                                                    </a>
                                                                    <form id="verify-form-{{ $item->id }}" action="{{ route('users.verifyOrganizer', $item->id) }}" method="post" class="d-none">
                                                                        @csrf
                                                                    </form>
                                                                @endif
                                                                @if ($item->is_verify != 2)
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item dropdown-item-custom text-danger" href="#" onclick="event.preventDefault(); let reason = prompt('{{ __('Please enter the reason for denying this organizer request:') }}'); if(reason !== null) { let form = document.getElementById('deny-form-{{ $item->id }}'); form.querySelector('input[name=\'denied_reason\']').value = reason; form.submit(); }">
                                                                        <i class="fas fa-times text-danger"></i> {{ __('Denied') }}
                                                                    </a>
                                                                    <form id="deny-form-{{ $item->id }}" action="{{ route('users.denyOrganizer', $item->id) }}" method="post" class="d-none">
                                                                        @csrf
                                                                        <input type="hidden" name="denied_reason" value="">
                                                                    </form>
                                                                @endif
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item dropdown-item-custom text-danger font-weight-bold" href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to permanently delete this organizer? This action cannot be undone.') }}')) document.getElementById('delete-form-{{ $item->id }}').submit();">
                                                                    <i class="fas fa-trash-alt text-danger"></i> {{ __('Delete') }}
                                                                </a>
                                                                <form id="delete-form-{{ $item->id }}" action="{{ route('users.deleteOrganizer', $item->id) }}" method="post" class="d-none">
                                                                    @csrf
                                                                </form>
                                                            </div>
                                                        </div>
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
            </div>
        </div>
    </section>
@endsection
