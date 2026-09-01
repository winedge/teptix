@php
    $logo = \App\Models\Setting::find(1)->logo;
    $favicon = \App\Models\Setting::find(1)->favicon;
    $modules = \App\Models\Module::all();
@endphp
<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="#">
                <img src="{{ $logo ? asset('/images/upload/' . $logo) : asset('/images/logo.png') }}"
                    class="header-logo w-full  ">
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="#">
                <img src="{{ $favicon ? asset('/images/upload/' . $favicon) : asset('/images/logo.png') }}"
                     alt="Logo"
                     style="max-height: 40px; max-width: 100%;"
                     class="img-fluid">
            </a>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-header">{{ __('Menu') }}</li>
            
            @if(Auth::user()->hasRole('scanner'))
                <li class="{{ request()->is('organizer/ticket-verification*') ? 'active' : '' }}" id="tour-scanner-verify">
                    <a class="nav-link" href="{{ url('organizer/ticket-verification') }}">
                        <i class="fas fa-qrcode"></i> <span>{{ __('Ticket Verification') }}</span>
                    </a>
                </li>
            @else
                @if(Auth::user()->hasRole('Manager'))
                    {{-- Manager Custom Permissions Sidebar --}}
                    @if(Auth::user()->hasManagerPermission('dashboard_access'))
                        <li class="{{ request()->is('manager-home') ? 'active' : '' }}" id="tour-manager-dashboard">
                            <a class="nav-link" href="{{ url('manager-home') }}">
                                <i class="fas fa-chart-pie"></i> <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasManagerPermission('order_view'))
                        <li class="{{ request()->is('orders') || request()->is('orders/*') ? 'active' : '' }}" id="tour-manager-orders">
                            <a class="nav-link" href="{{ url('orders') }}">
                                <i class="fas fa-columns"></i><span>{{ __('Orders') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasManagerPermission('order_create'))
                        <li class="{{ request()->is('orders-create-for-user') ? 'active' : '' }}" id="tour-manager-orders-create">
                            <a class="nav-link" href="{{ url('orders-create-for-user') }}">
                                <i class="fas fa-ticket-alt"></i><span>{{ __('Orders Create') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasManagerPermission('scanner_create'))
                        <li class="{{ request()->is('scanner*') ? 'active' : '' }}" id="tour-manager-scanners">
                            <a class="nav-link" href="{{ url('scanner') }}">
                                <i class="fas fa-id-card"></i> <span>{{ __('Scanner') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasManagerPermission('ticket_verify'))
                        <li class="{{ request()->is('organizer/ticket-verification*') ? 'active' : '' }}" id="tour-manager-verify">
                            <a class="nav-link" href="{{ url('organizer/ticket-verification') }}">
                                <i class="fas fa-qrcode"></i> <span>{{ __('Ticket Verification') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasManagerPermission('revenue_view'))
                        <li class="{{ request()->is('organization-income') ? 'active' : '' }}" id="tour-manager-revenue">
                            <a class="nav-link" href="{{ url('organization-income') }}">
                                <i class="fa-solid fa-money-bill-wave"></i> <span>{{ __('Revenue') }}</span>
                            </a>
                        </li>
                    @endif
                @else
                    {{-- Admin & Organizer Sidebar --}}
                    @role('admin')
                        @can('admin_dashboard')
                            <li class="{{ request()->is('admin/home') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ url('admin/home') }}">
                                    <i class="fas fa-chart-pie"></i> <span>{{ __('Dashboard') }}</span>
                                </a>
                            </li>
                        @endcan
                        <li class="{{ request()->is('admin/activity*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.activity.index') }}">
                                <i class="fas fa-history"></i> <span>{{ __('Activity') }}</span>
                            </a>
                        </li>
                    @endrole

                    @role('Organizer')
                        @if(!Auth::user()->hasRole('scanner'))
                            @can('organization_dashboard')
                                <li class="{{ request()->is('organization-home') ? 'active' : '' }}" id="tour-org-dashboard">
                                    <a class="nav-link" href="{{ url('organization-home') }}">
                                        <i class="fas fa-chart-pie"></i> <span>{{ __('Dashboard') }}</span>
                                    </a>
                                </li>
                            @endcan
                            <li class="{{ request()->is('admin/activity*') ? 'active' : '' }}" id="tour-org-activity">
                                <a class="nav-link" href="{{ route('admin.activity.index') }}">
                                    <i class="fas fa-history"></i> <span>{{ __('Activity') }}</span>
                                </a>
                            </li>
                            <li class="{{ request()->is('managers*') ? 'active' : '' }}" id="tour-org-managers">
                                <a class="nav-link" href="{{ url('managers') }}">
                                    <i class="fas fa-users-cog"></i> <span>{{ __('Managers') }}</span>
                                </a>
                            </li>
                        @endif
                    @endrole

                    @role('Organizer')
                        @can('Book_tickets')
                            <li class="{{ request()->is('book-ticket') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ url('book-ticket') }}">
                                    <i class="fas fa-ticket-alt"></i> <span>{{ __('Book Ticket') }}</span>
                                </a>
                            </li>
                        @endcan
                    @endrole
                    @can('role_access')
                        <li class="{{ request()->is('roles*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('roles') }}">
                                <i class="fas fa-user-secret"></i> <span>{{ __('Role') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('user_access')
                        <li class="{{ request()->is('users*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('users') }}">
                                <i class="fas fa-user-friends"></i> <span>{{ __('Organizers') }}</span>
                            </a>
                        </li>
                    @endcan
                    @if (Auth::user()->hasRole('Organizer') && !Auth::user()->onboarding_completed_at)
                        <li class="{{ request()->is('user/onboarding') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('users.onboarding') }}">
                                <i class="fas fa-id-card"></i> <span>{{ __('Onboarding') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasRole('admin'))
                        <li class="{{ request()->is('app-user*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('app-user') }}">
                                <i class="fas fa-users"></i> <span>{{ __('App Users') }}</span>
                            </a>
                        </li>
                    @endif
                    <li class="{{ request()->is('orders/*') ? 'active' : '' }}" id="tour-org-orders">
                        <a class="nav-link" href="{{ url('orders') }}">
                            <i class="fas fa-columns"></i><span>{{ __('Orders') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('orders-create-for-user') ? 'active' : '' }}" id="tour-org-orders-create">
                        <a class="nav-link" href="{{ url('orders-create-for-user') }}">
                            <i class="fas fa-ticket-simple"></i><span>{{ __('Orders Create') }}</span>
                        </a>
                    </li>
                    @can('category_access')
                        <li class="{{ request()->is('category*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('category') }}">
                                <i class="fas fa-glass-cheers"></i> <span>{{ __('Category') }}</span>
                            </a>
                        </li>
                    @endcan

                    <li class="{{ request()->is('get-notification*') ? 'active' : '' }}" id="tour-org-send-notification">
                        <a class="nav-link" href="{{ url('get-notification') }}">
                            <i class="fas fa-bell"></i> <span>{{ __('Send Notification') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('organizer/ticket-verification*') ? 'active' : '' }}" id="tour-org-verify">
                        <a class="nav-link" href="{{ url('organizer/ticket-verification') }}">
                            <i class="fas fa-qrcode"></i> <span>{{ __('Ticket Verification') }}</span>
                        </a>
                    </li>

                    @can('event_access')
                        <li class="{{ request()->is('events*') ? 'active' : '' }}" id="tour-org-events">
                            <a class="nav-link" href="{{ url('events') }}">
                                <i class="fas fa-calendar-alt"></i> <span>{{ __('Events') }}</span>
                            </a>
                        </li>
                    @endcan
                    @if(Auth::user()->hasRole('admin'))
                        <li class="{{ request()->is('venue-seat-maps*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('admin.venue-maps.index') }}">
                                <i class="fas fa-map-marked-alt"></i> <span>{{ __('Venue Seat Maps') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (!Auth::user()->hasRole('Organizer'))
                        <li class="{{ request()->is('wallet-transactions*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('allTransactions') }}">
                                <i class="fas fa-wallet"></i><span>{{ __('Wallet Transactions') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasRole('admin'))
                        <li class="{{ request()->is('stripe-transactions*') || request()->is('stripe-transaction/*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('stripeTransactions') }}">
                                <i class="fab fa-stripe-s"></i><span>{{ __('Stripe Transactions') }}</span>
                            </a>
                        </li>
                    @endif
                    @if(Auth::user()->hasRole('admin'))
                        <li class="{{ request()->is('donations*') || request()->is('donation/*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('donations') }}">
                                <i class="fas fa-heart"></i><span>{{ __('Donations') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (Auth::user()->hasRole('Organizer'))
                        <li class="{{ request()->is('scanner*') ? 'active' : '' }}" id="tour-org-scanners">
                            <a class="nav-link" href="{{ url('scanner') }}">
                                <i class="fas fa-id-card"></i> <span>{{ __('Scanner') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (Auth::user()->hasRole('Organizer'))
                        <li class="{{ request()->is('/organization-income') ? 'active' : '' }}" id="tour-org-revenue">
                            <a class="nav-link" href="{{ url('/organization-income') }}">
                                <i class="fa-solid fa-money-bill-wave"></i> <span>{{ __('Income') }}</span>
                            </a>
                        </li>
                    @endif
                    @can('blog_access')
                        <li class="{{ request()->is('blog*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('blog') }}">
                                <i class="fas fa-file-alt"></i><span>{{ __('Blog') }}</span>
                            </a>
                        </li>
                    @endcan
                    @if(Auth::user()->hasRole('Organizer') || Gate::check('coupon_access'))
                        <li class="{{ request()->is('coupon*') ? 'active' : '' }}" id="tour-org-promotions">
                            <a class="nav-link" href="{{ url('coupon') }}">
                                <i class="fas fa-tags"></i> <span>{{ Auth::user()->hasRole('Organizer') ? __('Promotion') : __('Coupon') }}</span>
                            </a>
                        </li>
                    @endif
                    @can('banner_access')
                        <li class="{{ request()->is('banner*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('banner') }}">
                                <i class="fas fa-images"></i><span>{{ __('Banner') }}</span>
                            </a>
                        </li>
                    @endcan
                    @role('admin')
                        <li class="{{ request()->is('event-review') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('event-review') }}">
                                <i class="fas fa  fa-flag"></i> <span>{{ __('Reported Events') }}</span>
                            </a>
                        </li>
                    @endrole
                    @role('admin')
                        @can('admin_report')
                            <li class="nav-item dropdown {{ request()->is('admin-report*') ? 'active' : '' }}">
                                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-chart-bar"></i>
                                    <span>{{ __('Reports') }}</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="{{ url('admin-report/customer') }}">{{ __('Customer Report') }}</a></li>
                                    <li><a class="nav-link" href="{{ url('admin-report/organization') }}">{{ __('Organization Report') }}</a></li>
                                    <li><a class="nav-link" href="{{ url('admin-report/revenue') }}">{{ __('Revenue Report') }}</a></li>
                                    <li><a class="nav-link" href="{{ url('admin-report/settlement') }}">{{ __('Settlement Report') }}</a></li>
                                </ul>
                            </li>
                        @endcan
                    @endrole
                    @php
                        $bankModule = \App\Models\Module::where('module','BankPayout')->first();
                    @endphp
                    @if ($bankModule->is_enable == 1 && $bankModule->is_install == 1)
                        @role('admin')
                            <li class="{{ request()->is('bank-details') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ url('bank-details') }}">
                                    <i class="fa-solid fa-building-columns"></i><span>{{ __('Bank Details') }}</span>
                                </a>
                            </li>
                        @endrole
                    @endif
                    @role('Organizer')
                        @can('organization_report')
                            <li class="nav-item dropdown {{ request()->is('organization-report*') ? 'active' : '' }}">
                                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-chart-bar"></i>
                                    <span>{{ __('Reports') }}</span></a>
                                <ul class="dropdown-menu">
                                    <li><a class="nav-link" href="{{ url('organization-report/customer') }}">{{ __('Customer Report') }}</a></li>
                                    <li><a class="nav-link" href="{{ url('organization-report/orders') }}">{{ __('Orders Report') }}</a></li>
                                    <li><a class="nav-link" href="{{ url('organization-report/revenue') }}">{{ __('Revenue Report') }}</a></li>
                                </ul>
                            </li>
                        @endcan
                    @endrole

                    @can('notification_template_access')
                        <li class="{{ request()->is('notification-template*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('notification-template') }}">
                                <i class="fas fa-bell"></i><span>{{ __('Notification Template') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('tax_access')
                        @if (Auth::user()->hasRole('admin'))
                            <li class="nav-item dropdown {{ request()->is('tax*') || request()->is('fee-type*') || request()->is('event-fee*') ? 'active' : '' }}">
                                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                                    <i class="fas fa-hand-holding-usd"></i><span>{{ __('Tax & Fee') }}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="{{ request()->is('tax*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ url('tax') }}">{{ __('Tax Type') }}</a>
                                    </li>
                                    <li class="{{ request()->is('fee-type*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ url('fee-type') }}">{{ __('Fee Type') }}</a>
                                    </li>
                                    <li class="{{ request()->is('event-fee*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ url('event-fee') }}">{{ __('Event Fee') }}</a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item dropdown {{ request()->is('tax*') ? 'active' : '' }}">
                                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                                    <i class="fas fa-hand-holding-usd"></i><span>{{ __('Tax & Fee') }}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="{{ request()->is('tax*') ? 'active' : '' }}">
                                        <a class="nav-link" href="{{ url('tax') }}">{{ __('Tax Type') }}</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endcan
                    @can('feedback_access')
                        <li class="{{ request()->is('feedback*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('feedback') }}">
                                <i class="fas fa-comments"></i><span>{{ __('Feedback') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('faq_access')
                        <li class="{{ request()->is('faq*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('faq') }}">
                                <i class="fas fa-question-circle"></i><span>{{ __('FAQs') }}</span>
                            </a>
                        </li>
                    @endcan
                    @can('language_access')
                        <li class="{{ request()->is('language*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('language') }}">
                                <i class="fas fa-language"></i><span>{{ __('Language') }}</span>
                            </a>
                        </li>
                    @endcan
                    @if (Auth::user()->hasRole('admin'))
                        <li class="{{ request()->is('admin-setting') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url('admin-setting') }}">
                                <i class="fas fa-cogs"></i><span>{{ __('Setting') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (Auth::user()->hasRole('Organizer'))
                        <li class="{{ request()->is('organizer-setting') ? 'active' : '' }}" id="tour-org-settings">
                            <a class="nav-link" href="{{ url('organizer-setting') }}">
                                <i class="fas fa-cogs"></i><span>{{ __('Setting') }}</span>
                            </a>
                        </li>
                    @endif
                    @role('admin')
                        @can('module_access')
                            <li class="{{ request()->is('module*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ url('module') }}">
                                    <i class="fas fa-tasks"></i><span>{{ __('Module') }}</span>
                                </a>
                            </li>
                        @endcan
                    @endrole
                    @if ($bankModule->is_enable == 1 && $bankModule->is_install == 1)
                        @role('Organizer')
                            <li class="{{ request()->is('bank-details') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ url('bank-details') }}">
                                    <i class="fa-solid fa-building-columns"></i><span>{{ __('Bank Details') }}</span>
                                </a>
                            </li>
                        @endrole
                    @endif
                    @foreach ($modules as $module)
                        @if ($module->is_install && $module->is_enable === 1)
                            @if ($module->module === 'Seatmap')
                                <li class="">
                                    <a class="nav-link" href="{{ url('seatmap/index') }}">
                                        <i class="fas fa-wheelchair"></i><span>{{ __($module->module) }}</span>
                                    </a>
                                </li>
                            @endif
                        @endif
                    @endforeach
                @endif
            @endif
        </ul>
    </aside>
</div>
