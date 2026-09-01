@extends('master')

@push('css')
    <style>
        .organizer-index-card {
            border: 0;
            border-radius: 8px;
        }

        .organizer-index-card .card-body {
            padding: 1.5rem;
        }

        .users-export-group {
            align-items: center;
            display: flex;
            flex-wrap: nowrap;
            gap: 0.45rem;
            justify-content: flex-end;
        }

        .users-toolbar-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.55rem;
        }

        .users-filter-group {
            align-items: center;
            display: flex;
            flex: 1 1 auto;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .users-search-wrap {
            flex: 0 0 260px;
            max-width: 260px;
        }

        .users-filter-btn {
            white-space: nowrap;
        }

        .organizer-page-title {
            color: #000;
            font-family: sans-serif;
            font-size: 22px;
            line-height: 1.25;
        }

        .btn-export {
            align-items: center !important;
            background: transparent;
            border: 1px solid #ef4444;
            border-radius: 6px;
            box-sizing: border-box !important;
            color: #ef4444;
            display: inline-flex !important;
            font-size: 0.875rem !important;
            font-weight: 500;
            gap: 4px;
            height: 38px !important;
            justify-content: center !important;
            line-height: 1.5 !important;
            padding: 0.375rem 0.75rem !important;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-export:hover,
        .btn-export:focus {
            background-color: #fef2f2;
            border-color: #dc2626;
            color: #dc2626;
            text-decoration: none;
        }

        .organizer-status-pill {
            align-items: center;
            border-radius: 20px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            padding: .25rem .75rem;
            white-space: nowrap;
        }

        .organizer-status-dot {
            border-radius: 50%;
            display: inline-block;
            height: 6px;
            margin-right: 6px;
            width: 6px;
        }

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

        #report_table td.col-action .dropdown {
            align-items: center;
            display: inline-flex !important;
            justify-content: center;
        }

        #report_table_wrapper > .row:first-child {
            display: none;
        }

        #report_table th,
        #report_table td {
            vertical-align: middle;
        }

        @media (max-width: 1399.98px) {
            .organizer-page-title {
                font-size: 18px;
            }

            .users-toolbar-row {
                align-items: center;
                flex-wrap: nowrap;
                gap: 0.35rem;
            }

            .users-filter-group {
                flex: 1 1 auto;
                flex-wrap: nowrap !important;
                gap: 0.35rem;
                max-width: none;
                padding-left: 0;
                padding-right: 0;
                width: auto;
            }

            .users-search-wrap {
                flex: 0 0 190px;
                margin-bottom: 0 !important;
                margin-right: 0 !important;
                max-width: 190px !important;
                width: 190px !important;
            }

            .users-filter-btn {
                flex: 0 0 auto;
                font-size: 0.75rem !important;
                margin-bottom: 0 !important;
                margin-right: 0 !important;
                padding: 0.375rem 0.5rem !important;
            }

            .users-export-group {
                flex: 0 0 auto;
                flex-wrap: nowrap;
                gap: 0.35rem;
                margin-left: auto;
                max-width: none;
                padding-left: 0;
                padding-right: 0;
                width: auto;
            }

            .users-toolbar-row > .users-filter-group {
                max-width: none !important;
                width: auto !important;
            }

            .users-toolbar-row > .users-export-group {
                max-width: none !important;
                width: auto !important;
            }

            .btn-export {
                font-size: 0.75rem !important;
                height: 32px !important;
                padding: 0.25rem 0.5rem !important;
            }
        }

        @media (max-width: 991.98px) {
            .organizer-page-title {
                font-size: 16px;
            }
        }

        @media (max-width: 575.98px) {
            .organizer-page-title {
                font-size: 15px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Organizers'),
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
                    <div class="card shadow-sm organizer-index-card">
                        <div class="card-body">
                            <div class="row mb-4 align-items-center">
                                <div class="col-md-6">
                                    <h2 class="section-title organizer-page-title my-0 font-weight-bold">
                                        {{ __('View Organizers') }}
                                    </h2>
                                </div>
                                <div class="col-md-6 text-right">
                                    @can('user_create')
                                        <a href="{{ url('users/create') }}" class="btn btn-danger font-weight-bold px-3 py-2" style="background-color: #ff0000; border-color: #ff0000; border-radius: 6px; font-size: 14px;">
                                            <i class="fas fa-plus mr-1"></i> {{ __('Add Organizer') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="row mb-4 align-items-center users-toolbar-row">
                                <div class="col-xl-7 col-lg-8 users-filter-group">
                                    <div class="input-group mr-3 mb-2 mb-lg-0 shadow-sm users-search-wrap" style="border-radius: 6px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0" style="border-color: #ced4da;">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control border-left-0" style="border-color: #ced4da; font-size: 14px;" placeholder="{{ __('Search Organizers ...') }}" id="tableSearch">
                                    </div>

                                    <a href="{{ route('users.recentOrganizers') }}" class="btn text-white font-weight-bold mr-2 mb-2 mb-lg-0 px-3 users-filter-btn" style="background-color: #ff0000; border-radius: 6px; font-size: 14px;">
                                        {{ __('Recent Organizer') }} <span class="ml-1">({{ $recentOrganizerCount ?? 0 }})</span>
                                    </a>
                                    <a href="{{ route('users.organizerPending') }}" class="btn btn-light border text-muted font-weight-bold mr-2 px-3 users-filter-btn" style="background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;">
                                        {{ __('Pending Organizer') }} <span class="ml-1">({{ $pendingOrganizerCount ?? 0 }})</span>
                                    </a>
                                    <a href="{{ route('users.organizerDenied') }}" class="btn btn-light border text-muted font-weight-bold px-3 users-filter-btn" style="background-color: #fff; border-color: #ced4da; border-radius: 6px; font-size: 14px;">
                                        {{ __('Denied Organizer') }} <span class="ml-1">({{ $deniedOrganizerCount ?? 0 }})</span>
                                    </a>
                                </div>

                                <div class="col-xl-5 col-lg-4 users-export-group">
                                    <button type="button" class="btn btn-export js-export-btn" data-export-index="0" title="{{ __('Print') }}">
                                        <i class="fas fa-print"></i><span class="btn-label">{{ __('Print') }}</span>
                                    </button>
                                    <button type="button" class="btn btn-export js-export-btn" data-export-index="1" title="{{ __('Excel') }}">
                                        <i class="far fa-file-excel"></i><span class="btn-label">{{ __('Excel') }}</span>
                                    </button>
                                    <button type="button" class="btn btn-export js-export-btn" data-export-index="2" title="{{ __('CSV') }}">
                                        <i class="fas fa-file-csv"></i><span class="btn-label">{{ __('CSV') }}</span>
                                    </button>
                                    <button type="button" class="btn btn-export js-export-btn" data-export-index="3" title="{{ __('PDF') }}">
                                        <i class="far fa-file-pdf"></i><span class="btn-label">{{ __('PDF') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped align-middle" id="report_table" style="vertical-align: middle;">
                                    <thead>
                                        <tr class="text-secondary" style="border-bottom: 2px solid #edf2f7; font-size: 14px;">
                                            <th class="no-export" style="width: 40px;"></th>
                                            <th class="font-weight-bold">{{ __('Organizer') }}</th>
                                            <th class="font-weight-bold">{{ __('First name') }}</th>
                                            <th class="font-weight-bold">{{ __('Last name') }}</th>
                                            <th class="font-weight-bold">{{ __('Phone') }}</th>
                                            <th class="font-weight-bold">{{ __('Role') }}</th>
                                            <th class="font-weight-bold text-center">{{ __('Status') }}</th>
                                            <th class="font-weight-bold text-center">{{ __('Verified') }}</th>
                                            @if ($debugMode == true && Auth::user()->hasRole('admin'))
                                                <th class="font-weight-bold text-center">{{ __('Login') }}</th>
                                            @endif
                                            @if (Gate::check('user_edit') || Gate::check('user_delete'))
                                                <th class="font-weight-bold text-center no-export col-action" style="width: 80px;">{{ __('Action') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 14px; color: #2d3748;">
                                        @foreach ($users as $item)
                                            @php
                                                $initial = strtoupper(substr($item->first_name ?: ($item->organization_name ?: 'U'), 0, 1));
                                            @endphp
                                            <tr style="border-bottom: 1px solid #edf2f7;">
                                                <td class="no-export"></td>
                                                <td style="vertical-align: middle;">
                                                    <div class="media align-items-center">
                                                        @if ($item->image && $item->image != 'defaultuser.png')
                                                            <img alt="image" class="mr-3 rounded-circle shadow-sm" style="width: 42px; height: 42px; object-fit: cover;" src="{{ url('images/upload/' . $item->image) }}">
                                                        @else
                                                            <div class="mr-3 rounded-circle bg-light d-flex align-items-center justify-content-center text-muted font-weight-bold shadow-sm" style="width: 42px; height: 42px; font-size: 16px; background-color: #e2e8f0 !important;">
                                                                {{ $initial }}
                                                            </div>
                                                        @endif
                                                        <div class="media-body">
                                                            <div class="font-weight-bold mb-0" style="color: #1a202c; font-size: 14px;">
                                                                {{ $item->first_name . ' ' . $item->last_name }}
                                                            </div>
                                                            <div class="text-muted" style="font-size: 12px; margin-top: -2px;">
                                                                {{ $item->email }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="vertical-align: middle;">{{ $item->first_name }}</td>
                                                <td style="vertical-align: middle;">{{ $item->last_name }}</td>
                                                <td style="vertical-align: middle; color: #4a5568;">{{ $item->phone }}</td>
                                                <td style="vertical-align: middle; color: #4a5568;">
                                                    @forelse ($item->roles as $roles)
                                                        <span>{{ $roles->name }}</span>
                                                    @empty
                                                        <span class="text-muted font-italic">{{ __('No Data') }}</span>
                                                    @endforelse
                                                </td>

                                                <td class="text-center" style="vertical-align: middle;">
                                                    @if ($item->status == '1')
                                                        <span class="organizer-status-pill" style="background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7;">
                                                            <span class="organizer-status-dot" style="background-color: #2e7d32;"></span>
                                                            {{ __('Active') }}
                                                        </span>
                                                    @else
                                                        <span class="organizer-status-pill" style="background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a;">
                                                            <span class="organizer-status-dot" style="background-color: #c62828;"></span>
                                                            {{ __('Block') }}
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="text-center" style="vertical-align: middle;">
                                                    @if ($item->is_verify == '1')
                                                        <span class="organizer-status-pill" style="background-color: #e3f2fd; color: #1565c0; border: 1px solid #90caf9;">
                                                            <span class="organizer-status-dot" style="background-color: #1565c0;"></span>
                                                            {{ __('Verified') }}
                                                        </span>
                                                    @elseif ($item->is_verify == '2')
                                                        <span class="organizer-status-pill" style="background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a;">
                                                            <span class="organizer-status-dot" style="background-color: #c62828;"></span>
                                                            {{ __('Denied') }}
                                                        </span>
                                                    @else
                                                        <span class="organizer-status-pill" style="background-color: #fff8e1; color: #f57f17; border: 1px solid #ffe082;">
                                                            <span class="organizer-status-dot" style="background-color: #f57f17;"></span>
                                                            {{ __('Unverified') }}
                                                        </span>
                                                    @endif
                                                </td>

                                                @if ($debugMode == true && Auth::user()->hasRole('admin'))
                                                    <td class="text-center" style="vertical-align: middle;">
                                                        @if ($item->hasRole('Organizer'))
                                                            <a href="{{ route('loginAsOrganizer', $item->id) }}" class="btn btn-sm text-white font-weight-bold" style="background-color: #ff0000; border-radius: 4px;">
                                                                {{ __('Login As') }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                @endif

                                                @if (Gate::check('user_edit') || Gate::check('user_delete'))
                                                    <td class="text-center no-export col-action" style="vertical-align: middle;">
                                                        <div class="dropdown d-inline-block">
                                                            <button class="action-dropdown-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right shadow-sm border border-light rounded-lg py-2">
                                                                @if (!$item->hasRole('admin'))
                                                                    @can('user_edit')
                                                                        <a class="dropdown-item dropdown-item-custom" href="{{ route('users.edit', $item->id) }}">
                                                                            <i class="far fa-edit text-muted"></i> {{ __('Edit') }}
                                                                        </a>
                                                                    @endcan

                                                                    @if ($item->status == 1)
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item dropdown-item-custom text-danger" onclick="return confirm('Blocked person will not be able to Login, They will stay hidden from the public, including their events & bookings.\nAre you sure to block?')" href="{{ url('main_user_block/' . $item->id) }}">
                                                                            <i class="fas fa-ban text-danger"></i> {{ __('Block') }}
                                                                        </a>
                                                                    @else
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item dropdown-item-custom text-success" onclick="return confirm('Are you sure to Unblock!!')" href="{{ url('main_user_block/' . $item->id) }}">
                                                                            <i class="fa fa-unlock-alt text-success"></i> {{ __('Unblock') }}
                                                                        </a>
                                                                    @endif

                                                                    @if ($item->hasRole('Organizer'))
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item dropdown-item-custom text-danger font-weight-bold" href="#" onclick="event.preventDefault(); if(confirm('{{ __('Are you sure you want to permanently delete this organizer? This action cannot be undone.') }}')) document.getElementById('delete-form-{{ $item->id }}').submit();">
                                                                            <i class="fas fa-trash-alt text-danger"></i> {{ __('Delete') }}
                                                                        </a>
                                                                        <form id="delete-form-{{ $item->id }}" action="{{ route('users.deleteOrganizer', $item->id) }}" method="post" class="d-none">
                                                                            @csrf
                                                                        </form>
                                                                    @endif
                                                                @endif
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

@push('js')
    <script>
        $(function () {
            var table = $.fn.dataTable.isDataTable('#report_table')
                ? $('#report_table').DataTable()
                : $('#report_table').DataTable();

            $('#tableSearch').on('keyup change', function () {
                table.search(this.value).draw();
            });

            $('.js-export-btn').on('click', function () {
                var exportIndex = Number($(this).data('export-index'));
                table.button(exportIndex).trigger();
            });

        });
    </script>
@endpush
