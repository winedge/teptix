@extends('master')

@section('content')
    <style>
        .text-red-primary { color: var(--primary_color) !important; }
        .bg-red-primary { background-color: var(--primary_color) !important; color: #fff !important; }
        .btn-red { background-color: var(--primary_color) !important; color: #fff !important; border: none; }
        .btn-red:hover { background-color: #cc0000 !important; color: #fff !important; }
        .card-hero-red { background: var(--primary_color) !important; color: white !important; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .card-hero-red .card-icon { color: rgba(255, 255, 255,1) !important; line-height: 1; margin-bottom: 0.75rem; }
        .card-hero-red .card-icon i { font-size: 4.85rem !important; line-height: 1; }
        .card-hero-red h4 { font-size: 22px; font-weight: 700; color: #fff !important; margin: 5px 0; }
        .card-hero-red .card-description { color: rgba(255, 255, 255, 0.9) !important; font-size: 13px; }
        .dashboard-metric-card {
            position: relative;
            min-height: 190px;
            overflow: hidden;
            border: 0;
            border-radius: 24px;
            background:
                linear-gradient(155deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.37) 100%),
                linear-gradient(155deg, var(--primary_color) 0%, var(--primary_color) 100%) !important;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            color: #fff !important;
            isolation: isolate;
            animation: dashboardCardReveal 0.75s ease both;
            animation-delay: var(--card-delay, 0s);
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }
        .dashboard-metric-card::before {
            content: "";
            position: absolute;
            inset: -35% auto auto -10%;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            filter: blur(4px);
            z-index: -1;
        }
        .dashboard-metric-card::after {
            content: "";
            position: absolute;
            right: -40px;
            bottom: -55px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            z-index: -1;
        }
        .dashboard-metric-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 42px rgba(15, 23, 42, 0.22);
        }
        .dashboard-metric-card .card-body {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
        }
        .dashboard-metric-card .metric-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .dashboard-metric-card .metric-card-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(6px);
        }
        .dashboard-metric-card .metric-card-icon {
            position: relative;
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        .dashboard-metric-card .metric-card-icon::after {
            content: "";
            position: absolute;
            inset: -6px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 22px;
            animation: dashboardIconPulse 2.4s ease-in-out infinite;
        }
        .dashboard-metric-card .metric-card-icon i {
            font-size: 1.35rem;
            color: #fff;
        }
        .dashboard-metric-card .metric-card-value {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #fff !important;
            letter-spacing: -0.03em;
        }
        .dashboard-metric-card .metric-card-content {
            margin-top: auto;
        }
        .dashboard-metric-card .metric-card-label {
            margin: 0.6rem 0 0.35rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff !important;
        }
        .dashboard-metric-card .metric-card-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.78rem;
            line-height: 1.4;
        }
        .dashboard-metric-card .metric-card-note i {
            font-size: 0.75rem;
        }
        @keyframes dashboardCardReveal {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes dashboardIconPulse {
            0%, 100% {
                opacity: 0.35;
                transform: scale(1);
            }
            50% {
                opacity: 0.75;
                transform: scale(1.08);
            }
        }
        .tbl-icon-bg { background-color: #ffe6e6; color: var(--primary_color); padding: 6px; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px; }
        .progress-bar-red { background-color: var(--primary_color) !important; }
        .dashboard-stats-row { align-items: stretch; }
        .order-stat-filter {
            display: inline-block;
            margin: 0;
        }
        .order-stat-select {
            width: auto;
            min-width: 48px;
            height: auto;
            padding: 0 16px 0 0;
            border: 0;
            background-color: transparent;
            color: var(--primary_color);
            font-size: inherit;
            font-weight: 700;
            line-height: inherit;
            cursor: pointer;
            outline: none;
        }
        .home-upcoming-event .date-left {
            min-width: 68px;
        }
        .home-upcoming-event .date-left h4,
        .home-upcoming-event .date-left p {
            white-space: nowrap;
            text-align: center;
        }
        .home-upcoming-event .date-left h4 {
            line-height: 1;
            font-size: 16px !important;
        }
        @media (min-width: 1400px) {
            .dashboard-stat-primary { flex: 0 0 32%; max-width: 32%; }
            .dashboard-stat-primary hr { display: block; }
            .dashboard-stat-card { flex: 0 0 17%; max-width: 17%; }
            .dashboard-metric-card .metric-card-value { font-size: 2.05rem; }
            .home-upcoming-event {
                max-height: 460px;
                overflow-y: auto;
                padding-right: 0.35rem;
            }
            .home-upcoming-event .date-left { min-width: 68px; }
            .home-upcoming-event .date-left h4 { font-size: 18px !important; }
            .home-upcoming-event .event-right p { font-size: 0.8rem; }
        }

        @media (min-width: 1200px) and (max-width: 1399.98px) {
            .dashboard-stat-primary { flex: 0 0 34%; max-width: 34%; }
            .dashboard-stat-primary { height: 180px; }
            .dashboard-stat-primary hr { display: none; }
            .dashboard-stat-primary .card-stats-title {
                align-items: flex-start !important;
                gap: 0.45rem;
            }
            .dashboard-stat-card {
                flex: 0 0 16.5%;
                max-width: 16.5%;
                height: 180px;
                padding-right: 7px;
                padding-left: 7px;
            }
            .dashboard-metric-card {
                height: 180px;
                min-height: 180px;
                border-radius: 20px;
            }
            .dashboard-metric-card::before {
                width: 112px;
                height: 112px;
            }
            .dashboard-metric-card::after {
                width: 140px;
                height: 140px;
                right: -32px;
                bottom: -42px;
            }
            .dashboard-metric-card .card-body {
                height: 100%;
                padding: 0.9rem;
            }
            .dashboard-metric-card .metric-card-top { margin-bottom: 0; }
            .dashboard-metric-card .metric-card-content { margin-top: auto; }
            .dashboard-metric-card .metric-card-chip {
                font-size: 0.58rem;
                padding: 0.3rem 0.55rem;
                letter-spacing: 0.06em;
            }
            .dashboard-metric-card .metric-card-value { font-size: 1.45rem; }
            .dashboard-metric-card .metric-card-label {
                margin: 0.45rem 0 0.2rem;
                font-size: 0.78rem;
            }
            .dashboard-metric-card .metric-card-note {
                gap: 0.3rem;
                font-size: 0.64rem;
                line-height: 1.25;
            }
            .dashboard-metric-card .metric-card-note i { font-size: 0.62rem; }
            .dashboard-metric-card .metric-card-icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
            }
            .dashboard-metric-card .metric-card-icon::after {
                inset: -4px;
                border-radius: 17px;
            }
            .dashboard-metric-card .metric-card-icon i { font-size: 0.95rem; }
            .admin-dashboard-events-table.table td,
            .admin-dashboard-events-table.table th {
                padding: 0.55rem 0.75rem !important;
                height: auto !important;
                font-size: 0.85em !important;
                vertical-align: middle !important;
            }
            .admin-dashboard-events-table h6 {
                font-size: 13px !important;
            }
            .admin-dashboard-events-table .tbl-info {
                font-size: 0.72rem !important;
            }
            .home-upcoming-event {
                max-height: 420px;
                overflow-y: auto;
                padding-right: 0.35rem;
            }
            .home-upcoming-event .date-left { min-width: 48px; }
            .home-upcoming-event .row.align-items-center { margin-bottom: 0.75rem !important; }
            .home-upcoming-event .date-left h4 { font-size: 16px !important; }
            .home-upcoming-event .event-right p {
                font-size: 0.74rem;
            }
        }

        @media (min-width: 992px) and (max-width: 1199.98px) {
            .dashboard-stat-primary { flex: 0 0 100%; max-width: 100%; }
            .dashboard-stat-card { flex: 0 0 25%; max-width: 25%; }
            .dashboard-metric-card { min-height: 170px; }
            .dashboard-metric-card .metric-card-value { font-size: 1.65rem; }
            .dashboard-metric-card .metric-card-label { font-size: 0.88rem; }
            .dashboard-metric-card .metric-card-note { font-size: 0.72rem; }
            .home-upcoming-event {
                max-height: 360px;
                overflow-y: auto;
                padding-right: 0.25rem;
            }
            .home-upcoming-event .date-left { min-width: 32px; }
            .home-upcoming-event .row.align-items-center { margin-bottom: 0.65rem !important; }
            .home-upcoming-event .date-left h4 { font-size: 15px !important; }
            .home-upcoming-event .event-right p { font-size: 0.72rem; }
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .dashboard-metric-card { min-height: 165px; }
            .dashboard-metric-card .metric-card-value { font-size: 1.7rem; }
            .home-upcoming-event {
                max-height: 340px;
                overflow-y: auto;
            }
            .home-upcoming-event .date-left { min-width: 40px; }
            .home-upcoming-event .date-left h4 { font-size: 15px !important; }
            .home-upcoming-event .event-right p { font-size: 0.78rem; }
        }

        @media (max-width: 767.98px) {
            .dashboard-metric-card {
                min-height: 158px;
                border-radius: 20px;
            }
            .dashboard-metric-card .card-body { padding: 1rem; }
            .dashboard-metric-card .metric-card-top { margin-bottom: 1rem; }
            .dashboard-metric-card .metric-card-chip {
                font-size: 0.64rem;
                padding: 0.32rem 0.6rem;
            }
            .dashboard-metric-card .metric-card-icon {
                width: 46px;
                height: 46px;
                border-radius: 15px;
            }
            .dashboard-metric-card .metric-card-icon i { font-size: 1rem; }
            .dashboard-metric-card .metric-card-value { font-size: 1.45rem; }
            .dashboard-metric-card .metric-card-label { font-size: 0.84rem; }
            .dashboard-metric-card .metric-card-note { font-size: 0.68rem; }
            .home-upcoming-event {
                max-height: none;
                overflow: visible;
            }
            .home-upcoming-event .date-left { min-width: 56px; }
            .home-upcoming-event .row.align-items-center {
                margin-bottom: 0.75rem !important;
            }
            .home-upcoming-event .date-left h4 { font-size: 14px !important; }
            .home-upcoming-event .event-right p { font-size: 0.74rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .dashboard-metric-card,
            .dashboard-metric-card .metric-card-icon::after {
                animation: none !important;
            }
            .dashboard-metric-card {
                transition: none !important;
            }
        }
        .event-title-link {
            transition: color 0.15s ease-in-out;
        }
        .event-title-link:hover {
            color: var(--primary_color) !important;
            text-decoration: underline !important;
        }
    </style>

    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center bg-white-primary p-3 text-white mb-4" style="border-radius: 4px;">
            <h1 class="text-black mb-0" style="font-size: 1.5rem;"> {{ __('Dashboard') }}</h1>
            <div class="d-flex align-items-center">
                
            </div>
        </div>

        <div class="section-body">
            <!-- <div class="row m-0 mb-2 justify-content-end" style="font-size: 12px;">
                <span class="text-muted">Dashboard > Order > <strong class="text-dark">Order Details</strong></span>
            </div> -->

            <div class="row dashboard-stats-row">
                <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 mb-4 dashboard-stat-primary">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="card-stats-title d-flex align-items-center mb-3">
                                <form method="GET" action="{{ url()->current() }}" class="order-stat-filter" id="order-stat-filter">
                                    @foreach (request()->except('stats_month') as $key => $value)
                                        @if (!is_array($value))
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <h6 class="mb-0 font-weight-bold text-muted">
                                        {{ __('Order Statistics') }} -
                                        <select name="stats_month" class="order-stat-select" id="order-stat-month">
                                            <option value="">{{ __('All') }}</option>
                                            @foreach ($master['order_month_options'] as $month)
                                                <option value="{{ $month['value'] }}" @selected($master['selected_order_month'] === $month['value'])>{{ __($month['label']) }}</option>
                                            @endforeach
                                        </select>
                                    </h6>
                                </form>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-4 border-right">
                                    <div class="text-small text-muted"><i class="fas fa-user text-primary mr-1"></i> {{ __('Pending') }}</div>
                                    <div class="font-weight-bold text-dark" id="order-stat-pending" style="font-size: 16px;">{{ $master['pending_order'] }}</div>
                                </div>
                                <div class="col-4 border-right">
                                    <div class="text-small text-muted"><i class="fas fa-check text-success mr-1"></i> {{ __('Success') }}</div>
                                    <div class="font-weight-bold text-dark" id="order-stat-complete" style="font-size: 16px;">{{ $master['complete_order'] }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-small text-muted"><i class="fas fa-times text-danger mr-1"></i> {{ __('Cancel') }}</div>
                                    <div class="font-weight-bold text-dark" id="order-stat-cancel" style="font-size: 16px;">{{ $master['cancel_order'] }}</div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <div class="text-small text-muted font-weight-bold">{{ __('Total orders') }}</div>
                                    <h4 class="font-weight-bold mb-0 text-dark" id="order-stat-total">{{ $master['total_order'] }}</h4>
                                </div>
                                @can('event_access')
                                    <a href="{{ url('events') }}" class="btn btn-red  px-3 py-2 text-small d-flex align-items-center">
                                        <i class="fas fa-calendar-alt mr-2"></i> {{ __('View Events') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-6 mb-4 dashboard-stat-card">
                    <div class="card dashboard-metric-card h-100" style="--card-delay: 0.05s;">
                        <div class="card-body d-flex flex-column ">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Audience') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-user-friends"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['users'] }}</h4>
                                <div class="metric-card-label">{{ __('Customers') }}</div>
                                <div class="metric-card-note">
                                    <i class="fas fa-star"></i>
                                    <span>{{ __('Registered users on platform') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-6 mb-4 dashboard-stat-card">
                    <div class="card dashboard-metric-card h-100" style="--card-delay: 0.12s;">
                        <div class="card-body d-flex flex-column ">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Network') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-sitemap"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['organizations'] }}</h4>
                                <div class="metric-card-label">{{ __('Organizations') }}</div>
                                <div class="metric-card-note">
                                    <i class="fas fa-layer-group"></i>
                                    <span>{{ __('Active organizer accounts') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-6 mb-4 dashboard-stat-card">
                    <div class="card dashboard-metric-card h-100" style="--card-delay: 0.19s;">
                        <div class="card-body d-flex flex-column ">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Revenue') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-chart-line"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['total_order_amount'] }}</h4>
                                <div class="metric-card-label">{{ __('Total Sales') }}</div>
                                <div class="metric-card-note">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>{{ __('Gross order value tracked') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-6 mb-4 dashboard-stat-card">
                    <div class="card dashboard-metric-card h-100" style="--card-delay: 0.26s;">
                        <div class="card-body d-flex flex-column ">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Margin') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-hand-holding-usd"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['total_order_tax'] }}</h4>
                                <div class="metric-card-label">{{ __('Admin Earnings') }}</div>
                                <div class="metric-card-note">
                                    <i class="fas fa-wallet"></i>
                                    <span>{{ __('Platform earnings summary') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-start">
                <div class="col-xl-9 col-lg-8 col-md-12 col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-calendar-check text-red-primary mr-2"></i> {{ __('Events') }}</h5>
                            <a href="{{ url('events') }}" class="btn btn-sm btn-outline-danger btn-red text-white px-3">{{ __('See all') }}</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0 admin-dashboard-events-table">
                                    <thead>
                                        <tr class="text-muted" style="font-size: 13px; background-color: #fcfcfc;">
                                            <th class="pl-4">{{ __('Image') }}</th>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Allowed') }}</th>
                                            <th>{{ __('Sold') }}</th>
                                            <th>{{ __('Pcs left') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($events as $item)
                                            <tr>
                                                <td class="pl-4">
                                                    <img class="table-img rounded" src="{{ url('images/upload/' . $item->image) }}" style="width: 70px; height: 45px; object-fit: cover;">
                                                </td>
                                                <td style="width: 35%">
                                                    <h6 class="font-weight-bold mb-1" style="font-size: 14px;">
                                                        <a href="{{ url('/events_details', $item->id) }}" class="text-dark event-title-link" style="text-decoration: none;">{{ $item->name }}</a>
                                                    </h6>
                                                    <!-- @if ($item->type == 'online')
                                                        <span class="badge badge-light text-muted">{{ __('Online Event') }}</span>
                                                    @else
                                                        <span class="text-muted small"><i class="fas fa-map-marker-alt mr-1"></i>{{ $item->address }}</span>
                                                    @endif -->
                                                </td>
                                                <td>
                                                    <span class="tbl-icon-bg"><i class="fas fa-user-friends"></i></span>
                                                    <span class="tbl-info text-dark font-weight-600 small">{{ ($item->capacity ?? $item->people) . ' allowed' }}</span>
                                                </td>
                                                <td>
                                                    <span class="tbl-icon-bg"><i class="fas fa-shopping-cart"></i></span>
                                                    <span class="tbl-info text-dark font-weight-600 small">{{ (($item->capacity ?? $item->people ?? 0) - $item->avaliable) . ' sold' }}</span>
                                                </td>
                                                <td>
                                                    <span class="tbl-icon-bg"><i class="fas fa-ticket-alt"></i></span>
                                                    <span class="tbl-info text-dark font-weight-600 small">{{ $item->avaliable }} {{ __('Pcs left') }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-12 col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 font-weight-bold text-dark">
                                <i class="fas fa-history text-red-primary mr-2"></i> {{ __('Activity') }}
                            </h5>
                            <a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-outline-danger btn-red text-white px-3">{{ __('See all') }}</a>
                        </div>
                        <div class="card-body p-3">
                            @if (isset($latestActivityLogs) && $latestActivityLogs->count())
                                @foreach ($latestActivityLogs as $activity)
                                    @php
                                        $actor = $activity->actor;
                                        $actorName = $actor
                                            ? trim(($actor->organization_name ?: '') . ' ' . ($actor->first_name ?: '') . ' ' . ($actor->last_name ?: ''))
                                            : '';
                                    @endphp
                                    <div class="d-flex align-items-start mb-3">
                                        <span class="tbl-icon-bg mr-2"><i class="fas fa-user-clock"></i></span>
                                        <div class="flex-fill">
                                            <p class="mb-0 text-dark font-weight-bold small">{{ $activity->title }}</p>
                                            <div class="text-muted small">{{ $actorName ?: __('Unknown') }}</div>
                                            <div class="text-muted small">{{ optional($activity->created_at)->format('Y-m-d H:i') }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-3 text-muted small">{{ __('No activity found') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body calender-event p-3">
                            <input type="hidden" name="eventDate" id="eventDate" value="{{ json_encode($master['eventDate']) }}">
                            <div id="home_calender" class="mb-3"></div>

                            <h6 class="text-dark font-weight-bold mb-3 border-bottom pb-2">{{ $master['current_month'] . __(' Event') }}</h6>
                            <div class="home-upcoming-event">
                                @if (count($monthEvent) == 0)
                                    <div class="text-center py-4">
                                        <div class="card-icon bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <h6 class="text-muted small">{{ __('No events found') }}</h6>
                                    </div>
                                @else
                                    @foreach ($monthEvent as $item)
                                        <div class="row align-items-center mb-3">
                                            <div class="col-3 pr-0">
                                                <div class="date-left text-center bg-light p-2 rounded border-danger" style="border-left: 3px solid var(--primary_color);">
                                                    <h4 class="mb-0 font-weight-bold text-red-primary" style="font-size: 16px;">{{ $item->start_time->format('d') }}</h4>
                                                    <p class="mb-0 text-muted uppercase small font-weight-bold">{{ $item->start_time->format('D') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-9 event-right">
                                                <p class="mb-0 text-dark font-weight-bold small text-truncate" title="{{ $item->name }}">{{ $item->name }}</p>
                                                <div class="d-flex justify-content-between text-muted small mt-1">
                                                    <span>{{ __('Ticket Sold') }}</span>
                                                    <span class="font-weight-bold text-dark">{{ $item->sold_ticket }}/{{ $item->capacity }}</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 6px; border-radius: 4px;">
                                                    <div class="progress-bar progress-bar-red" role="progressbar"
                                                        style="width: {{ $item->average }}%;"
                                                        aria-valuenow="{{ $item->average }}" aria-valuemin="0"
                                                        aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var filter = document.getElementById('order-stat-filter');
            var monthSelect = document.getElementById('order-stat-month');

            if (!filter || !monthSelect) {
                return;
            }

            monthSelect.addEventListener('change', function () {
                var params = new URLSearchParams(new FormData(filter));
                var url = filter.action + (params.toString() ? '?' + params.toString() : '');

                monthSelect.disabled = true;

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Unable to load order statistics.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        document.getElementById('order-stat-pending').textContent = data.pending_order;
                        document.getElementById('order-stat-complete').textContent = data.complete_order;
                        document.getElementById('order-stat-cancel').textContent = data.cancel_order;
                        document.getElementById('order-stat-total').textContent = data.total_order;
                    })
                    .catch(function () {
                        filter.submit();
                    })
                    .finally(function () {
                        monthSelect.disabled = false;
                    });
            });
        });
    </script>
@endsection
