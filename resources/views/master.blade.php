<!DOCTYPE html>
<html lang="en">

<head>
    
    @php
        $appSetting = \App\Models\Setting::find(1);
        $favicon = optional($appSetting)->favicon;
        $appName = optional($appSetting)->app_name ?: config('app.name', 'Teptix');
        $primary_color = optional($appSetting)->primary_color ?: '#6777ef';
        $modules = \App\Models\Module::all();
    @endphp
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>{{ $appName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- General CSS Files -->
    <link href="{{ $favicon ? url('images/upload/' . $favicon) : asset('/images/logo.png') }}" rel="icon"
        type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.21/b-1.6.2/b-flash-1.6.2/b-html5-1.6.2/b-print-1.6.2/datatables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">

    <link href="https://jvectormap.com/css/jquery-jvectormap-2.0.3.css" rel="stylesheet">
    <!-- CSS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <!-- Template CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- General CSS Files -->
    <link href="{{ $favicon ? url('images/upload/' . $favicon) : asset('/images/logo.png') }}" rel="icon"
        type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.21/b-1.6.2/b-flash-1.6.2/b-html5-1.6.2/b-print-1.6.2/datatables.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">

    <link href="https://jvectormap.com/css/jquery-jvectormap-2.0.3.css" rel="stylesheet">
    <!-- CSS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <!-- Template CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.css">
    <link href="https://cdn.datatables.net/select/1.3.1/css/select.dataTables.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ url('admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('admin/css/components.css') }}">
    <link rel="stylesheet" href="{{ url('admin/css/custom.css') }}">
    @if (session('direction') == 'rtl')
        <link rel="stylesheet" href="{{ url('admin/css/rtl.css') }}">
    @endif
    <!-- Intro.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
    <style>
        .introjs-tooltip {
            background-color: #ffffff !important;
            color: #000000 !important;
            border-radius: 8px !important;
            border: 3px solid var(--primary_color) !important;
            font-family: 'Inter', sans-serif !important;
            box-shadow: 0 6px 0px 0px rgba(0, 0, 0, 0.15), 
                        0 12px 24px 0px rgba(0, 0, 0, 0.25), 
                        inset 0 1px 0px 0px rgba(255, 255, 255, 0.8) !important;
            transform: translateZ(0);
            min-width: 350px !important;
        }
        .introjs-arrow.bottom {
            border-top-color: #ffffff !important;
        }
        .introjs-arrow.top {
            border-bottom-color: #ffffff !important;
        }
        .introjs-arrow.left {
            border-right-color: #ffffff !important;
        }
        .introjs-arrow.right {
            border-left-color: #ffffff !important;
        }
        .introjs-tooltiptext {
            color: #1e293b !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
        }
        .introjs-tooltipheader, .introjs-tooltip-header {
            font-weight: 800 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 8px 12px !important;
            border-top-left-radius: 5px !important;
            border-top-right-radius: 5px !important;
            background-color: var(--light_primary_color) !important;
            color: var(--primary_color) !important;
        }
        .introjs-button {
            text-shadow: none !important;
            box-shadow: 0 2px 0px 0px rgba(0, 0, 0, 0.1) !important;
            border-radius: 6px !important;
            background-color: #f8fafc !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            transition: all 0.2s ease !important;
        }
        .introjs-button:hover {
            background-color: #f1f5f9 !important;
            color: #000000 !important;
            border-color: #94a3b8 !important;
        }
        .introjs-nextbutton {
            background-color: var(--primary_color) !important;
            border-color: var(--primary_color) !important;
            color: #ffffff !important;
        }
        .introjs-nextbutton:hover {
            filter: brightness(0.9) !important;
            color: #ffffff !important;
        }
        .introjs-skipbutton {
            font-size: 9.6px !important;
        }
        .introjs-disabled {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
            opacity: 0.5 !important;
        }
        .introjs-bullets ul li a.active {
            background: var(--primary_color) !important;
        }
    </style>
</head>

<body>
    <style>
        :root {
            --primary_color: <?php echo $primary_color; ?>;
            --light_primary_color: <?php echo $primary_color . '1a'; ?>;
            --middle_light_primary_color: <?php echo $primary_color . '85'; ?>;
        }

        .bg-primary {
            --tw-bg-opacity: 1;
            background-color: var(--primary_color);
        }

        .bg-primary-dark {
            --tw-bg-opacity: 1;
            background-color: var(--profile_primary_color);
        }

        .navbar-nav>.active>a {
            color: var(--primary_color);
        }

        .text-primary {
            --tw-text-opacity: 1;
            color: var(--primary_color);
        }

        .border-primary {
            --tw-border-opacity: 1;
            border-color: var(--primary_color);
        }
        
    </style>
    @stack("css")
    <input type="hidden" name="currency" id="currency" value="{{ $currency??'' }}">
    <input type="hidden" name="base_url" id="base_url" value="{{ url('/') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <div class="lds-ripple">
        <div></div>
        <div></div>
    </div>
    <div id="app">
        @if (Auth::check())
            <div class="main-wrapper">
                @include('admin.layout.header')


                @include('admin.layout.sidebar')
                <div class="main-content">
                    @if (Auth::user()->hasRole('admin'))
                        <?php $status = optional($appSetting)->license_status; ?>
                        @if ($status == 1)
                            @yield('content')
                            @yield('content-license')
                        @else
                            <script>
                                url = window.location.origin + window.location.pathname;
                                var license = $('#base_url').val() + '/license-setting';
                                console.log('url:', license);
                                if (license != url) {
                                    setTimeout(() => {
                                        Swal.fire({
                                            title: 'Your License is deactivated!',
                                            icon: 'info',
                                            html: 'to get benefits of EventRight please activate your license<br><br> ' +
                                                '<a href="' + license +
                                                '" style="background:#3085d6;color:#fff;padding:8px 10px;border-radius:5px;">Activate License</a>',
                                            showCloseButton: false,
                                            showCancelButton: false,
                                            showConfirmButton: false,
                                            focusConfirm: false,
                                            onClose: () => {
                                                window.location.replace(license);
                                            }
                                        })
                                    }, 500);
                                }
                            </script>

                            @yield('content-license')
                        @endif
                    @else
                        @yield('content')
                        @yield('content-license')
                    @endif
                </div>
                @include('admin.layout.footer')
            </div>
        @else
            @yield('content')
        @endif
    </div>

    <!-- General JS Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    <script type="text/javascript"
        src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.21/b-1.6.2/b-flash-1.6.2/b-html5-1.6.2/b-print-1.6.2/datatables.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
    @auth
        <?php
        $sendbox = \App\Models\PaymentSetting::where('id', 1)->first();
        $cur = optional($appSetting)->currency ?: 'USD';
        ?>
        @if ($sendbox)
            @if ($sendbox->paypalClientId)
                <script
                    src="https://www.paypal.com/sdk/js?client-id=AWoo6mXhv6wlXhlzdWcbP2uJbWGYYKunfoqtue6mC8c1l8GmxJrfeOqi1gwMpu9x1jmi7_81JkqT4bgb&currency={{ $cur }}"
                    data-namespace="paypal_sdk"></script>
            @endif
        @endif
    @endauth




    <script src="{{ url('admin/js/jquery-jvectormap.min.js') }}"></script>
    <script src="{{ url('admin/js/jquery-jvectormap-world-mill.js') }}"></script>
    <script src="{{ url('admin/js/jquery.uploadPreview.min.js') }}"></script>
    <script src="{{ url('admin/js/bootstrap-tagsinput.min.js') }}"></script>
    <script src="{{ url('admin/js/bootstrap-colorpicker.min.js') }}"></script>
    <script src="{{ url('admin/js/dropzone.js') }}"></script>
    <script src="{{ url('admin/js/charts.js') }}"></script>
    <script src="{{ url('admin/js/stisla.js') }}"></script>
    <script src="{{ url('admin/js/scripts.js') }}"></script>
    <script src="{{ url('frontend/js/qrcode.min.js') }}"></script>
    <script src="{{ url('admin/js/myCustom.js') }}?{{ time() }}"></script>
    <script src="{{ url('admin/js/custom.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @stack("js")
    @foreach ($modules as $module)
        @if ($module->is_install === 1 && $module->is_enable === 1)
            <script src="{{ asset('js/seatmap.js') }}"></script>
        @endif
    @endforeach
 <script>
setInterval(() => {
    fetch('/clear-cache')
        .then(res => res.json())
        .then(data => console.log(data.message))
        .catch(err => console.error('❌ Error clearing cache:', err));
}, 180000); // every 3 min
</script>
    <!-- Intro.js Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @auth
                const userRole = "{{ Auth::user()->roles->first()->name ?? '' }}";
                const isVerify = parseInt("{{ Auth::user()->is_verify ?? 0 }}");
                const routeName = "{{ request()->path() }}";

                const isOrganizerHome = (userRole === 'Organizer' && routeName === 'organization-home' && isVerify === 1);
                const isManagerHome = (userRole === 'Manager' && routeName === 'manager-home');
                const isScannerHome = (userRole === 'scanner' && routeName === 'scanner-home');

                if (isOrganizerHome || isManagerHome || isScannerHome) {
                    const tourKey = 'tour_completed_' + userRole + '_{{ Auth::id() }}';
                    const hasCompletedTour = localStorage.getItem(tourKey);

                    if (!hasCompletedTour) {
                        let steps = [];

                        if (isOrganizerHome) {
                            steps = [
                                {
                                    title: 'Welcome to your Dashboard!',
                                    intro: 'This dashboard gives you a complete overview of your events, tickets, and revenue. Let\'s take a quick tour of your portal!'
                                },
                                {
                                    element: document.querySelector('#tour-org-dashboard'),
                                    title: 'Organizer Dashboard',
                                    intro: 'Access key summary metrics, event check-in ratios, and real-time statistics of all your transactions here.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-activity'),
                                    title: 'Activity Logs',
                                    intro: 'Monitor all operations, audits, and system events performed across your portal.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-managers'),
                                    title: 'Manage Sub-Users (Managers)',
                                    intro: 'Here you can add and modify your managers, assign granular features/permissions, and monitor their status.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-orders'),
                                    title: 'View Orders',
                                    intro: 'Look up customer bookings, print invoices, or manage ticket refunds.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-orders-create'),
                                    title: 'Create Offline Orders',
                                    intro: 'Manually register bookings, book seat maps, or create free/offline passes for customers directly.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-send-notification'),
                                    title: 'Send Notification',
                                    intro: 'Draft and blast push notifications or announcements to your registered event customers.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-verify'),
                                    title: 'Ticket Verification Portal',
                                    intro: 'Access the web scanning view to confirm and register attendee barcode tickets instantly.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-events'),
                                    title: 'Events Setup',
                                    intro: 'Create new events, customize event details, or configure seat maps & locations.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-scanners'),
                                    title: 'Scanner Management',
                                    intro: 'Register and manage physical device scanners to help verify tickets at event entry gates.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-promotions'),
                                    title: 'Promotions & Coupons',
                                    intro: 'Create and manage discount coupons, promotional offers, and ticket codes for your events.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-revenue'),
                                    title: 'Revenue Panel',
                                    intro: 'Check event incomes, view payment details, and track payout statuses.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-org-settings'),
                                    title: 'Organizer Settings',
                                    intro: 'Configure your company details, payment gateway credentials, and system options.',
                                    position: 'right'
                                },
                                {
                                    element: document.querySelector('#tour-header-notifications'),
                                    title: 'System Notifications',
                                    intro: 'Check system alerts, new bookings, or update notifications here in the top header.',
                                    position: 'bottom'
                                },
                                {
                                    element: document.querySelector('#tour-header-profile'),
                                    title: 'Profile & Settings',
                                    intro: 'Access your profile settings, configure language selections, or safely logout of your account from this dropdown.',
                                    position: 'bottom'
                                }
                            ];
                        } else if (isManagerHome) {
                            steps = [
                                {
                                    title: 'Welcome Manager!',
                                    intro: 'You have been assigned access to manage the organization\'s workspace. Let\'s walk through the tools available to you.'
                                },
                                {
                                    element: document.querySelector('#tour-manager-dashboard'),
                                    title: 'Manager Dashboard',
                                    intro: 'See summaries of total orders, scanners, events, and revenue assigned to your parent Organizer.',
                                    position: 'right'
                                }
                            ];

                            if (document.querySelector('#tour-manager-orders')) {
                                steps.push({
                                    element: document.querySelector('#tour-manager-orders'),
                                    title: 'Orders Management',
                                    intro: 'Review orders list, check customer credentials, and download invoices.',
                                    position: 'right'
                                });
                            }
                            if (document.querySelector('#tour-manager-orders-create')) {
                                steps.push({
                                    element: document.querySelector('#tour-manager-orders-create'),
                                    title: 'Create Booking Order',
                                    intro: 'Create offline reservations and ticket orders directly for your organizer.',
                                    position: 'right'
                                });
                            }
                            if (document.querySelector('#tour-manager-scanners')) {
                                steps.push({
                                    element: document.querySelector('#tour-manager-scanners'),
                                    title: 'Device Scanners',
                                    intro: 'Register and authorize new scanner devices for event entries.',
                                    position: 'right'
                                });
                            }
                            if (document.querySelector('#tour-manager-verify')) {
                                steps.push({
                                    element: document.querySelector('#tour-manager-verify'),
                                    title: 'Verification Desk',
                                    intro: 'Use this portal to scan barcodes and verify guest tickets.',
                                    position: 'right'
                                });
                            }
                            if (document.querySelector('#tour-manager-revenue')) {
                                steps.push({
                                    element: document.querySelector('#tour-manager-revenue'),
                                    title: 'Income & Revenue',
                                    intro: 'View sales figures, earnings charts, and checkout reports.',
                                    position: 'right'
                                });
                            }
                        } else if (isScannerHome) {
                            steps = [
                                {
                                    title: 'Welcome to the Scanner Portal!',
                                    intro: 'Your dashboard is optimized for verifying guest passes at the entry gate. Let\'s review your options!'
                                },
                                {
                                    element: document.querySelector('#tour-scanner-verify'),
                                    title: 'Verify Tickets',
                                    intro: 'Click here or on the scan button to launch the camera scanner and verify attendee barcodes.',
                                    position: 'right'
                                }
                            ];
                        }

                        if (steps.length > 0) {
                            setTimeout(() => {
                                introJs().setOptions({
                                    steps: steps,
                                    showBullets: true,
                                    showProgress: true,
                                    exitOnOverlayClick: false,
                                    exitOnEsc: false,
                                    nextLabel: 'Next &rarr;',
                                    prevLabel: '&larr; Prev',
                                    skipLabel: 'Skip',
                                    doneLabel: 'Finish'
                                }).oncomplete(function () {
                                    localStorage.setItem(tourKey, 'true');
                                }).onexit(function () {
                                    localStorage.setItem(tourKey, 'true');
                                }).start();
                            }, 1000);
                        }
                    }
                }
            @endauth
        });
    </script>
</body>

</html>
