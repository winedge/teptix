@extends('master')

@section('content')
@php
    $categories = $events->pluck('category.name')->filter()->unique()->sort()->values();
    $organizations = $events
        ->flatMap(function ($event) {
            if ($event->organizations && $event->organizations->count()) {
                return $event->organizations->pluck('organization_name')->filter();
            }

            return $event->organization ? [$event->organization->organization_name] : [];
        })
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp
<style>
    .bg-main-panel { background-color: #f8fafc; }
    .admin-events-page .container-fluid { padding-left: 1rem; padding-right: 1rem; }
    .admin-events-page .card { overflow: visible !important; }
    .admin-events-card-body { padding: 1.5rem !important; }
    .events-titlebar { gap: 0.75rem; }
    .events-header-row { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; }
    .events-header-row h2 { flex: 0 0 auto; font-size: 1.1rem; white-space: nowrap; margin-right: 0.15rem; }
    
    /* Perfect Row Realignment Container */
    .events-controls-row { display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; margin-bottom: 1rem; overflow: visible; }
    .events-toolbar { display: flex; align-items: center; gap: 0.65rem; margin-left: 0; margin-right: 0; row-gap: 0.75rem; }
    .events-toolbar > [class*="col-"] { padding-left: 0; padding-right: 0; }
    
    /* Fixed control wrap dimensions */
    .events-search-wrap { flex: 0 0 190px; width: 190px !important; max-width: 190px !important; margin-bottom: 0 !important; }
    .events-filter-wrap { flex: 0 0 auto; width: auto !important; max-width: none !important; margin-bottom: 0 !important; }
    .events-export-group { display: flex !important; align-items: center; justify-content: flex-end; gap: 0.45rem; margin-left: auto; margin-bottom: 0 !important; flex-wrap: nowrap; visibility: visible !important; opacity: 1 !important; }
    
    .events-add-wrap { display: block !important; visibility: visible !important; opacity: 1 !important; }
    .events-add-btn { padding: 0.42rem 0.75rem !important; font-size: 0.78rem; white-space: nowrap; }
    .events-filter-group { gap: 0.35rem; display: flex; align-items: center; }
    .events-filter-group .mr-2 { margin-right: 0 !important; }
    .events-filter-group select { flex: 0 1 auto; min-width: 0; }
    
    #filterStatus { width: 110px; }
    #filterCategory { width: 135px; }
    #filterOrganization { width: 155px; }
    .events-search { min-width: 0; }

    /* UNIFIED ELEMENT HEIGHT CONTROLS (Desktop) */
    .form-control-custom,
    .events-search-wrap .input-group-text,
    .btn-export {
        height: 38px !important;
        box-sizing: border-box !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
    }
    .form-control-custom { border: 1px solid #cbd5e1; border-radius: 6px; }
    .events-search-wrap .input-group-text { border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; margin-right: -1px; }
    .events-search-wrap .events-search { border-radius: 0 6px 6px 0 !important; }
    
    .btn-export { border: 1px solid #ef4444; color: #ef4444; background: transparent; font-weight: 500; border-radius: 6px; transition: all 0.2s; white-space: nowrap; gap: 4px; }
    .btn-export:hover { background-color: #fef2f2; color: #dc2626; border-color: #dc2626; text-decoration: none; }

    /* Custom Table Styling */
    .custom-table { table-layout: fixed; width: 100% !important; margin-bottom: 0 !important; }
    .custom-table th { font-weight: 600; color: #475569; background-color: #f1f5f9 !important; border-bottom: none; white-space: normal; }
    .custom-table td { vertical-align: middle !important; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .custom-table th,
    .custom-table td { padding: 0.75rem 0.65rem; font-size: 0.84rem; line-height: 1.35; }
    .custom-table th:nth-child(1) { width: 22px; }
    .custom-table th:nth-child(2) { width: 21%; }
    .custom-table th:nth-child(3) { width: 13%; }
    .custom-table th:nth-child(5) { width: 12%; }
    .custom-table th:nth-child(6) { width: 14%; }
    .custom-table th:nth-child(9) { width: 13.5%; }
    .custom-table .col-status { width: 5% !important; max-width: 5% !important; text-align: center; vertical-align: middle !important; }
    .custom-table .col-people { width: 5% !important; max-width: 5% !important; text-align: center; vertical-align: middle !important; padding-left: 0.12rem !important; padding-right: 0.12rem !important; }
    .custom-table .col-action { width: 3.7% !important; max-width: 3.7% !important; text-align: center; vertical-align: middle !important; padding-left: 0 !important; padding-right: 0 !important; }
    .custom-table th:nth-child(2),
    .custom-table td:nth-child(2) { text-align: left !important; }
    .custom-table th.col-people,
    .custom-table th.col-action { font-size: 0.68rem; line-height: 1.1; word-break: break-word; }
    .event-name { max-width: 170px; line-height: 1.3; white-space: normal; word-break: break-word; }
    .event-name-link { text-decoration: none !important; color: inherit !important; }
    .event-name-link:hover .event-name { color: var(--primary_color) !important; transition: color 0.15s ease-in-out; }
    
    .badge-published { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: 50px; padding: 0.25rem 0.75rem; font-size: 0.8125rem; font-weight: 500; display: inline-flex; align-items: center; }
    .badge-published::before { content: ''; display: inline-block; width: 6px; height: 6px; background-color: #16a34a; border-radius: 50%; margin-right: 6px; }
    .badge-draft { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 50px; padding: 0.25rem 0.75rem; font-size: 0.8125rem; font-weight: 500; display: inline-flex; align-items: center; }
    .badge-draft::before { content: ''; display: inline-block; width: 6px; height: 6px; background-color: #d97706; border-radius: 50%; margin-right: 6px; }
    .btn-manage-tickets { background-color: #ef4444; color: white !important; font-weight: 500; border-radius: 50px; padding: 0.375rem 1.25rem; font-size: 0.8125rem; border: none; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2); transition: all 0.2s; }
    .btn-manage-tickets:hover { background-color: #dc2626; box-shadow: 0 4px 12px -1px rgba(239, 68, 68, 0.3); text-decoration: none; }
    .action-dropdown-btn { color: #ef4444; border: 1px solid #ef4444; background: transparent; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 0.72rem; }
    .action-dropdown-btn:hover { background-color: #fef2f2; }
    .dropdown-item-custom { display: flex; align-items: center; gap: 10px; padding: 0.5rem 1rem; color: #334155; font-size: 0.875rem; }
    .dropdown-item-custom:hover { background-color: #f1f5f9; color: #0f172a; text-decoration: none; }
    .custom-checkbox input { accent-color: #ef4444; width: 16px; height: 16px; cursor: pointer; }
    .custom-table th:first-child,
    .custom-table td:first-child { padding: 0 !important; text-align: center; vertical-align: middle !important; }
    .custom-table td.select-checkbox { position: relative; }
    .custom-table td.select-checkbox::before,
    .custom-table td.select-checkbox::after {
        left: 50% !important;
        top: 50% !important;
        margin-left: -6px !important;
        margin-top: -6px !important;
    }
.custom-table td.col-action .dropdown { display: inline-flex !important; align-items: center; justify-content: center; }

    /* Fix dropdown alignment/positioning issues (Bootstrap dropdown) */
    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
    }
    .dropdown-menu-right {
        right: 0;
        left: auto;
    }

    #report_table_wrapper > .row:first-child { display: none; }
    #report_table_wrapper .row:last-child { align-items: center; margin-top: 1rem; }
    #report_table_wrapper .dataTables_info,
    #report_table_wrapper .dataTables_length,
    #report_table_wrapper .dataTables_paginate { font-size: 0.82rem; }
    .table-responsive { overflow-x: visible; }

    /* UNIFIED ELEMENT HEIGHT CONTROLS FOR RESPONSIVE MEDIA BREAKPOINTS */
    @media (max-width: 1399.98px) {
        .admin-events-page { padding-top: 1rem !important; padding-bottom: 1rem !important; }
        .admin-events-page .container-fluid { padding-left: 0.65rem; padding-right: 0.65rem; }
        .admin-events-card-body { padding: 0.85rem !important; }
        .events-header-row { gap: 0.5rem; margin-bottom: 0.65rem; }
        .events-header-row h2 { font-size: 0.9rem; margin-right: 0; }
        .events-controls-row { gap: 0.35rem; flex-wrap: wrap; }
        .events-toolbar { gap: 0.38rem; flex-wrap: nowrap; }
        .events-search-wrap { flex: 0 0 145px; width: 145px !important; max-width: 145px !important; }
        .events-filter-wrap { flex: 0 0 auto; }
        #filterStatus { width: 85px; }
        #filterCategory { width: 105px; }
        #filterOrganization { width: 120px; }
        
        /* Height Sync at 32px */
        .form-control-custom,
        .events-search-wrap .input-group-text,
        .btn-export {
            height: 32px !important;
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
        }

        .custom-table th,
        .custom-table td { padding: 0.44rem 0.34rem; font-size: 0.7rem; line-height: 1.25; }
        .custom-table th:nth-child(1) { width: 20px; }
        .custom-table th:nth-child(2) { width: 19%; }
        .custom-table th:nth-child(3) { width: 12%; }
        .custom-table th:nth-child(5) { width: 11%; }
        .custom-table th:nth-child(6) { width: 14%; }
        .custom-table th:nth-child(9) { width: 18%; }
        .custom-table .col-status { width: 5% !important; max-width: 5% !important; }
        .custom-table .col-people { width: 5% !important; max-width: 5% !important; }
        .custom-table .col-action { width: 3.7% !important; max-width: 3.7% !important; }
        .custom-table th.col-people,
        .custom-table th.col-action { font-size: 0.56rem; }
        .event-name { max-width: 105px; font-size: 0.72rem; }
        .events-add-btn { padding: 0.3rem 0.46rem !important; font-size: 0.66rem; }
        .btn-manage-tickets { padding: 0.28rem 0.48rem; font-size: 0.66rem; line-height: 1.15; white-space: normal; }
        .badge-published,
        .badge-draft { padding: 0.16rem 0.42rem; font-size: 0.66rem; }
        .badge-published::before,
        .badge-draft::before { width: 5px; height: 5px; margin-right: 4px; }
        .action-dropdown-btn { width: 20px; height: 20px; font-size: 0.6rem; }
        .dropdown-item-custom { gap: 7px; padding: 0.38rem 0.65rem; font-size: 0.76rem; }
        #report_table_wrapper .dataTables_info,
        #report_table_wrapper .dataTables_length,
        #report_table_wrapper .dataTables_paginate { font-size: 0.7rem; }
    }

    @media (max-width: 1199.98px) {
        .events-titlebar { align-items: flex-start !important; }
        .events-titlebar h2 { font-size: 1.1rem; }
        .events-header-row { gap: 0.35rem; margin-bottom: 0.5rem; }
        .events-header-row h2 { font-size: 0.74rem; }
        .events-controls-row { gap: 0.24rem; flex-wrap: wrap; }
        .events-titlebar .btn-danger { padding: 0.34rem 0.65rem !important; font-size: 0.72rem; }
        .events-toolbar { gap: 0.28rem; flex-wrap: nowrap; }
        .events-filter-group { gap: 0.18rem; }
        .events-search-wrap { flex: 0 0 120px; width: 120px !important; max-width: 120px !important; }
        .events-filter-wrap { flex: 0 0 auto; }
        #filterStatus { width: 75px; }
        #filterCategory { width: 90px; }
        #filterOrganization { width: 100px; }
        
        /* Height Sync at 28px */
        .form-control-custom,
        .events-search-wrap .input-group-text,
        .btn-export {
            height: 28px !important;
            padding: 0.2rem 0.4rem !important;
            font-size: 0.68rem !important;
        }

        .events-export-group { justify-content: flex-end !important; margin-left: auto; }
        .custom-table th,
        .custom-table td { padding: 0.36rem 0.25rem; font-size: 0.62rem; }
        .event-name { max-width: 85px; font-size: 0.64rem; }
        .events-add-btn { padding: 0.22rem 0.3rem !important; font-size: 0.54rem; }
        .btn-manage-tickets { padding: 0.24rem 0.36rem; font-size: 0.58rem; }
        .badge-published,
        .badge-draft { padding: 0.14rem 0.32rem; font-size: 0.58rem; }
        .action-dropdown-btn { width: 18px; height: 18px; font-size: 0.56rem; }
    }

    @media (min-width: 768px) and (max-width: 1099.98px) {
        .events-header-row { display: flex; justify-content: space-between; gap: 0.25rem; }
        .events-header-row h2 { font-size: 0.52rem; }
        .events-controls-row { gap: 0.14rem; flex-wrap: wrap; }
        .events-search-wrap { flex: 0 0 95px; width: 95px !important; max-width: 95px !important; }
        .events-filter-wrap { flex: 0 0 auto; width: auto !important; max-width: none !important; }
        .events-export-group { display: flex !important; justify-content: flex-end !important; gap: 0.12rem; visibility: visible !important; opacity: 1 !important; flex: 0 0 auto; margin-left: auto; }
        #filterStatus { width: 65px; }
        #filterCategory { width: 80px; }
        #filterOrganization { width: 85px; }
        
        /* Height Sync at 24px */
        .form-control-custom,
        .events-search-wrap .input-group-text,
        .btn-export {
            height: 24px !important;
            padding: 0.1rem 0.25rem !important;
            font-size: 0.6rem !important;
        }
        
        .events-filter-group { gap: 0.1rem; }
        .events-export-group .btn-export { display: inline-flex !important; visibility: visible !important; opacity: 1 !important; }
        .btn-export .btn-label { display: inline; }
        .events-add-btn { padding: 0.18rem 0.22rem !important; font-size: 0.48rem; }
    }

    @media (max-width: 767.98px) {
        .min-vh-100 {
            min-height: unset !important;
        }
        .admin-events-card-body { padding: 0.9rem !important; }
        .events-titlebar { flex-wrap: wrap; }
        .events-header-row {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .events-header-row h2 {
            font-size: 1rem;
            white-space: nowrap;
            flex: 1 1 auto;
            min-width: 0;
        }
        .events-controls-row { flex-wrap: wrap; gap: 0.75rem; }
        .events-toolbar { display: flex; flex-wrap: wrap; }
        .events-search-wrap,
        .events-filter-wrap,
        .events-export-group { width: 100%; max-width: 100%; margin-left: 0; justify-content: flex-start; }
        .events-add-wrap {
            width: auto;
            flex: 0 0 auto;
            margin-left: auto;
        }
        .events-add-btn {
            justify-content: center;
            white-space: nowrap;
            padding: 0.375rem 0.625rem !important;
            font-size: 0.72rem !important;
        }
        .events-search-wrap .input-group {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            width: 100%;
        }
        .events-search-wrap .input-group-prepend {
            display: flex;
            flex: 0 0 auto;
        }
        .events-search-wrap .input-group-text {
            display: inline-flex !important;
            align-items: center !important;
            white-space: nowrap;
        }
        .events-search {
            width: 100% !important;
            min-width: 0;
            flex: 1 1 auto;
        }
        .events-filter-group {
            width: 100%;
            gap: 0.35rem;
        }
        .events-filter-group select {
            flex: 1 1 calc(33.333% - 0.35rem);
            width: calc(33.333% - 0.35rem) !important;
            min-width: 86px;
            max-width: 110px;
            font-size: 0.72rem !important;
        }
        .events-export-group .btn-export { flex: 1 1 calc(25% - 0.5rem); }
        #report_table_wrapper .row:last-child {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            overflow-x: auto;
            overflow-y: hidden;
        }
        #report_table_wrapper .row:last-child > div {
            flex: 0 0 auto;
            width: auto;
            max-width: none;
            float: none;
            display: inline-flex;
            align-items: center;
            padding-left: 0.15rem;
            padding-right: 0.15rem;
        }
        #report_table_wrapper .row:last-child .col-sm-12,
        #report_table_wrapper .row:last-child .col-md-5,
        #report_table_wrapper .row:last-child .col-md-7 {
            width: auto;
            max-width: none;
            flex: 0 0 auto;
        }
        #report_table_wrapper .dataTables_info,
        #report_table_wrapper .dataTables_length,
        #report_table_wrapper .dataTables_paginate {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            margin-bottom: 0;
            font-size: 0.72rem;
        }
        #report_table_wrapper .dataTables_info {
            display: none !important;
        }
        #report_table_wrapper .dataTables_length {
            display: inline-flex !important;
            justify-content: flex-start !important;
            margin-right: auto;
        }
        #report_table_wrapper .row:last-child > div:nth-child(2) {
            margin-right: auto;
            padding-right: 0.5rem;
        }
        #report_table_wrapper .row:last-child > div:last-child {
            margin-left: auto;
            padding-left: 0.5rem;
        }
        #report_table_wrapper .dataTables_length label {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0;
            white-space: nowrap;
        }
        #report_table_wrapper .dataTables_length select {
            width: auto;
            min-width: 52px;
        }
        #report_table_wrapper .dataTables_paginate {
            display: inline-flex;
            align-items: center;
        }
        #report_table_wrapper .dataTables_paginate .pagination {
            flex-wrap: nowrap;
            margin-bottom: 0;
        }
        #report_table_wrapper .dataTables_paginate .page-link {
            padding: 0.2rem 0.4rem;
        }
        .table-responsive { overflow-x: auto; }
        .custom-table { min-width: 760px; }
        
        .form-control-custom,
        .events-search-wrap .input-group-text,
        .btn-export {
            height: 38px !important;
            font-size: 0.875rem !important;
        }
        .input-group-text,
        select.form-control:not([size]):not([multiple]),
        .form-control:not(.form-control-sm):not(.form-control-lg) {
            padding: 0 !important;
        }
    }
</style>

<section class="section bg-main-panel min-vh-100 py-4 admin-events-page">
    <div class="container-fluid">
        
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                {{ session('status') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-lg">
            <div class="card-body admin-events-card-body">
                
                <div class="events-header-row mb-4">
                    <h2 class="h4 font-weight-bold text-dark mb-0">{{ __('All Events') }}</h2>
                    <div class="events-add-wrap">
                        <a href="{{ url('event/create') }}" class="btn btn-danger font-weight-bold rounded-lg d-flex align-items-center events-add-btn" style="background-color: #ef4444; border: none;">
                            <i class="fas fa-plus mr-1"></i> {{ __('Add Event') }}
                        </a>
                    </div>
                </div>

                <div class="events-controls-row">
                    <!-- Global Search Field -->
                    <div class="mb-0 events-search-wrap">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white text-muted d-flex align-items-center"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" id="customSearch" class="form-control form-control-custom border-left-0 events-search" placeholder="Search Events ...">
                        </div>
                    </div>

                    <!-- Dynamic Select Filters -->
                    <div class="mb-0 events-filter-group events-filter-wrap">
                        <select class="form-control form-control-custom" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="Published">Published</option>
                            <option value="Draft">Draft</option>
                        </select>
                        <select class="form-control form-control-custom" id="filterCategory">
                            <option value="">All Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                        @if (Auth::user()->hasRole('admin'))
                        <select class="form-control form-control-custom" id="filterOrganization">
                            <option value="">All Organization</option>
                            @foreach ($organizations as $organization)
                                <option value="{{ $organization }}">{{ $organization }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <!-- Export Buttons -->
                    <div class="events-export-group">
                        <button class="btn btn-export" title="Print"><i class="fas fa-print"></i><span class="btn-label">Print</span></button>
                        <button class="btn btn-export" title="Excel"><i class="far fa-file-excel"></i><span class="btn-label">Excel</span></button>
                        <button class="btn btn-export" title="CSV"><i class="fas fa-file-csv"></i><span class="btn-label">CSV</span></button>
                        <button class="btn btn-export" title="PDF"><i class="far fa-file-pdf"></i><span class="btn-label">PDF</span></button>
                    </div>
                </div>

                @if ($events->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-calendar-plus fa-3x text-muted"></i>
                        </div>
                        <h4 class="font-weight-bold text-dark">{{ __('No events found') }}</h4>
                        <p class="text-muted mb-4">{{ __('Start by adding your first event. It will appear here once created.') }}</p>
                        @can('event_create')
                            <a href="{{ url('event/create') }}" class="btn btn-danger btn-lg rounded-lg">
                                <i class="fas fa-plus mr-2"></i> {{ __('Add Event') }}
                            </a>
                        @endcan
                    </div>
                @else
                    <!-- Core Data View Table -->
                    <div class="table-responsive">
                        <table class="table custom-table" id="report_table">
                            <thead>
                                <tr>
                                    <th width="40px"></th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Start date') }}</th>
                                    <th class="col-people">{{ __('Capacity') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    @if (Auth::user()->hasRole('admin'))
                                        <th>{{ __('Organization') }}</th>
                                    @endif
                                    <th class="col-status">{{ __('Status') }}</th>
                                    @if (Gate::check('event_edit') || Gate::check('event_delete'))
                                        <th class="text-center col-action">{{ __('Action') }}</th>
                                    @endif
                                    @if (Gate::check('ticket_access'))
                                        <th class="text-center">{{ __('Tickets') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($events as $item)
                                    @php
                                        $organizationNames = $item->organizations && $item->organizations->count()
                                            ? $item->organizations->pluck('organization_name')->filter()->join(', ')
                                            : ($item->organization ? $item->organization->organization_name : '');
                                        $statusLabel = $item->status == '1' ? 'Published' : 'Draft';
                                    @endphp
                                    <tr data-status="{{ $statusLabel }}" data-category="{{ $item->category ? $item->category->name : '' }}" data-organization="{{ $organizationNames }}">
                                        <td></td>
                                        <td class="col-status">
                                            <div>
                                                <a href="{{ url('/events_details', $item->id) }}" class="event-name-link">
                                                    <h6 class="font-weight-bold text-dark mb-0 event-name">{{ $item->name }}</h6>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-dark font-weight-bold mb-0">
                                                {{ Carbon\Carbon::parse($item->start_time)->format('Y-m-d') }} {{ Carbon\Carbon::parse($item->start_time)->format('l') }}
                                            </div>
                                            <small class="text-muted">{{ Carbon\Carbon::parse($item->start_time)->format('h:i a') }}</small>
                                        </td>
                                        <td class="text-muted col-people">{{ $item->people ?? '0' }}</td>
                                        <td class="text-muted">{{ $item->category ? $item->category->name : 'N/A' }}</td>
                                        @if (Auth::user()->hasRole('admin'))
                                            <td class="text-muted">{{ $organizationNames ?: 'N/A' }}</td>
                                        @endif
                                        <td>
                                            <span class="{{ $item->status == '1' ? 'badge-published' : 'badge-draft' }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        @if (Gate::check('event_edit') || Gate::check('event_delete'))
                                            <td class="text-center col-action">
                                                <div class="dropdown d-inline-block">
                                                    <button class="action-dropdown-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right shadow-sm border border-light rounded-lg py-2">
                                                        <a href="{{ url('/events_details', $item->id) }}" class="dropdown-item dropdown-item-custom">
                                                            <i class="far fa-eye text-muted"></i> View Event
                                                        </a>
                                                        <a href="{{ url('event-gallery/' . $item->id) }}" class="dropdown-item dropdown-item-custom">
                                                            <i class="far fa-images text-muted"></i> Event Gallery
                                                        </a>
                                                        @can('event_edit')
                                                            <a href="{{ route('events.edit', $item->id) }}" class="dropdown-item dropdown-item-custom">
                                                                <i class="far fa-edit text-muted"></i> Edit Event
                                                            </a>
                                                        @endcan
                                                        @can('event_delete')
                                                            <div class="dropdown-divider"></div>
                                                            <a href="#" onclick="deleteData('events','{{ $item->id }}');" class="dropdown-item dropdown-item-custom text-danger">
                                                                <i class="far fa-trash-alt text-danger"></i> Delete Event
                                                            </a>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </td>
                                        @endif
                                        @if (Gate::check('ticket_access'))
                                            <td class="text-center">
                                                <a href="{{ url($item->id . '/' . Str::slug($item->name) . '/tickets') }}" class="btn btn-manage-tickets">
                                                    {{ __('Manage Tickets') }}
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

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

            if (table.select && table.select.style) {
                table.select.style('multi');
            }

            $('#customSearch').on('keyup change', function () {
                table.search(this.value).draw();
            });

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'report_table') {
                    return true;
                }

                var row = table.row(dataIndex).node();
                var status = $('#filterStatus').val();
                var category = $('#filterCategory').val();
                var organization = $('#filterOrganization').length ? $('#filterOrganization').val() : '';

                if (status && $(row).data('status') !== status) {
                    return false;
                }

                if (category && $(row).data('category') !== category) {
                    return false;
                }

                if (organization && String($(row).data('organization')).indexOf(organization) === -1) {
                    return false;
                }

                return true;
            });

            $('#filterStatus, #filterCategory, #filterOrganization').on('change', function () {
                table.draw();
            });

            $('.btn-export').on('click', function () {
                var index = $(this).index();
                table.button(index).trigger();
            });
        });
    </script>
@endpush
