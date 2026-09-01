@extends('master')

@section('content')
    <style>
        .dashboard-stats-row { align-items: stretch; }
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
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }
        .dashboard-metric-card::before,
        .dashboard-metric-card::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            z-index: -1;
        }
        .dashboard-metric-card::before {
            inset: -35% auto auto -10%;
            width: 140px;
            height: 140px;
            filter: blur(4px);
        }
        .dashboard-metric-card::after {
            right: -40px;
            bottom: -55px;
            width: 180px;
            height: 180px;
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
        }
        .dashboard-metric-card .metric-card-value {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #fff !important;
            letter-spacing: -0.03em;
        }
        .dashboard-metric-card .metric-card-label {
            margin: 0.6rem 0 0.35rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff !important;
        }
    </style>

    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center bg-white-primary p-3 text-white mb-4" style="border-radius: 4px;">
            <h1 class="text-black mb-0" style="font-size: 1.5rem;">{{ __('Manager Dashboard') }}</h1>
        </div>

        <div class="section-body">
            <div class="row dashboard-stats-row">
                <!-- Total Orders Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card dashboard-metric-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Orders') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-shopping-cart"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['total_order'] }}</h4>
                                <div class="metric-card-label">{{ __('Total Orders') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Events Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card dashboard-metric-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Events') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $master['events'] }}</h4>
                                <div class="metric-card-label">{{ __('Total Events') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Scanners Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card dashboard-metric-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Scanners') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-qrcode"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $totalScanners }}</h4>
                                <div class="metric-card-label">{{ __('Total Scanners') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card dashboard-metric-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="metric-card-top">
                                <span class="metric-card-chip">{{ __('Revenue') }}</span>
                                <span class="metric-card-icon"><i class="fas fa-chart-line"></i></span>
                            </div>
                            <div class="metric-card-content">
                                <h4 class="metric-card-value">{{ $earnings }}</h4>
                                <div class="metric-card-label">{{ __('Total Revenue') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
