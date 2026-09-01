@php
    $admin = \App\Models\User::find(1);
    if (\App\Models\Category::find(1)) {
        $category = \App\Models\category::where('status', 1)->get();
    }
    $user = Auth::guard('appuser')->user();
    $logo = \App\Models\Setting::find(1)->logo;
    $wallet = \App\Models\PaymentSetting::first()->wallet;
@endphp
<style>
    @media (max-width: 1023px) {
        #desktop-navbar { display: none !important; }
    }
    @media (min-width: 1024px) {
        #mobile-navbar { display: none !important; }
    }
</style>
<nav class="navbar rounded m-0 bg-white z-30 shadow-md relative w-full">
    <!-- Desktop Navbar -->
    <div id="desktop-navbar"
        class="hidden lg:flex justify-between w-full px-4 md:px-8 lg:px-12 xl:px-16 py-3 pt-4 z-30" role="navigation" aria-label="Desktop Navigation" aria-hidden="false">

        <div class="flex lg:w-1/3 items-center justify-start">
            <a href="{{ url('/') }}" class="">
                <img class="object-contain h-[45px] w-[150px]"
                    src="{{ $logo ? url('images/upload/' . $logo) : asset('/images/logo.png') }}"
                    alt="Logo">
            </a>
        </div>

        <div class="flex lg:w-1/3 justify-center items-center">
            <ul class="navbar-nav flex justify-center flex-row space-x-6 xl:space-x-10 m-0 p-0">
                <li class="nav-item {{ $activePage == 'home' ? 'active' : '' }}">
                    <a href="{{ url('/') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray hover:text-primary transition-all">{{ __('Home') }}</a>
                </li>
                <li class="nav-item {{ Request::is('all-events') ? 'active' : '' }}">
                    <a href="{{ url('/all-events') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray hover:text-primary transition-all">{{ __('Events') }}</a>
                </li>
                <li class="nav-item {{ Request::is('all-category') ? 'active' : '' }}">
                    <div class="relative inline-block text-left">
                        <a href="#"
                            class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray flex items-center hover:text-primary transition-all focus:outline-none"
                            id="categories">{{ __('Categories') }}
                            <img src="{{ asset('images/dropdown.png') }}" alt="" class="ml-1 h-2 w-3">
                        </a>
                        <div class="categoriesClass hidden origin-top-left absolute left-0 w-44 rounded-md shadow-2xl z-30 mt-2">
                            <div class="rounded-md bg-white shadow-lg p-3">
                                <div class="">
                                    @php
                                        if (!isset($catactive)) {
                                            $catactive = null;
                                        }
                                    @endphp
                                    @if (isset($category))
                                        @foreach ($category as $item)
                                            <div class="flex items-left justify-left mb-1">
                                                <a href="{{ url('/events-category/' . $item->id . '/' . \Illuminate\Support\Str::slug($item->name)) }}"
                                                    class="flex items-left text-base font-poppins font-normal leading-5 {{ $catactive == \Illuminate\Support\Str::slug($item->name) ? ' text-primary bg-primary-light' : ' ' }} capitalize p-2 hover:bg-primary-light rounded-md w-full">{{ $item->name }}</a>
                                            </div>
                                        @endforeach
                                    @endif
                                    <div class="flex items-left justify-left mt-1">
                                        <a href="{{ url('/all-category') }}"
                                            class="flex items-left text-base font-poppins font-normal leading-5 {{ $catactive == 'all' ? 'text-primary bg-primary-light' : ' ' }} capitalize p-2 hover:bg-primary-light rounded-md w-full">{{ __('All categories') }}
                                            <img src="{{ asset('images/right-dropdown.png') }}" alt="" class="ml-2 h-2 w-2 mt-1.5">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item {{ $activePage == 'blog' ? 'active' : '' }}">
                    <a href="{{ url('/all-blogs') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray hover:text-primary transition-all">{{ __('Blog') }}</a>
                </li>
                <li class="nav-item {{ $activePage == 'contact' ? 'active' : '' }}">
                    <a href="{{ url('/contact') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray hover:text-primary transition-all">{{ __('Contact Us') }}</a>
                </li>
                <li class="nav-item {{ $activePage == 'become-organizer' ? 'active' : '' }}">
                    <a href="{{ url('/become-organizer') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray hover:text-primary transition-all">{{ __('Become an Organizer') }}</a>
                </li>
            </ul>
        </div>

        <div class="flex lg:w-1/3 justify-end items-center space-x-3">
            <div>
                <form action="{{ url('user/search_event') }}" method="post" class="m-0">
                    @csrf
                    <div class="relative">
                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                            <img src="{{ asset('images/search.svg') }}" class="w-5 h-5" alt="">
                        </div>
                        <input type="search" name="search"
                            class="block p-2 pl-10 text-gray bg-white border border-gray-light text-left font-poppins font-normal
                            text-base leading-6 rounded-md focus:outline-none w-48 xl:w-64"
                            placeholder="{{ __('Search..') }}" style="border-color: red;box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;" required>
                    </div>
                </form>
            </div>
            
            <div class="flex items-center">
                @if (Auth::guard('appuser')->check())
                    <div class="flex items-center justify-center">
                        <div class="mr-3">
                            <p class="font-poppins font-medium text-sm leading-5 text-black m-0 whitespace-nowrap">
                                {{ $user->name . ' ' . $user->last_name }}</p>
                        </div>
                        <div class="">
                            <img src="{{ asset('images/upload/' . $user->image) }}"
                                class="w-10 h-10 bg-cover object-contain border border-gray-light rounded-full cursor-pointer"
                                alt="" onclick="showmenuDesktop()">
                        </div>
                        <div class="ml-2 dropdown relative flex items-center">
                            <div class="relative inline-block text-left">
                                <button
                                    class="py-2 text-gray font-medium text-xs flex items-center focus:outline-none"
                                    type="button" onclick="showmenuDesktop()"><img
                                        src="{{ asset('images/dropdown.png') }}" alt="">
                                </button>
                                <div id="dropdownMenuClassDesktop"
                                    class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-2xl z-50">
                                    <div class="rounded-md bg-white shadow-xs">
                                        <div class="py-1">
                                            <div
                                                class="overflow-y-auto py-4 px-3 bg-gray-50 rounded pt-10 border-b border-gray-light pb-5">
                                                <ul class="space-y-6">
                                                    <li>
                                                        <a href="{{ url('/my-tickets') }}"
                                                            class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize hover:text-primary">{{ __('My tickets') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('/user/profile') }}"
                                                            class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize hover:text-primary">{{ __('Profile') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('/change-password') }}"
                                                            class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize hover:text-primary">{{ __('Change password') }}
                                                        </a>
                                                    </li>
                                                    @if ($wallet == 1)
                                                        <li>
                                                            <a href="{{ route('myWallet') }}"
                                                                class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize hover:text-primary">{{ __('My Wallet') }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                            <div class="px-3 py-5">
                                                <a href="{{ route('logoutUser') }}"
                                                    class="flex items-center font-poppins font-medium leading-4 text-danger capitalize">
                                                    <i class="fa-solid fa-right-from-bracket"></i><span id="checkDesktop"
                                                        class="flex-1 whitespace-nowrap ml-2 cursor-pointer">{{ __('Logout') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif(Auth::check() && Auth::user()->hasRole('Organizer'))
                    <a type="button" href="{{ url('organization-home') }}"
                        class="px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md whitespace-nowrap transition-all"><i class="fa fa-tachometer" aria-hidden="true"></i> {{ __('Dashboard') }}</a>
                @elseif(Auth::check() && Auth::user()->hasRole('admin'))
                    <a type="button" href="{{ url('admin/home') }}"
                        class="px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md whitespace-nowrap transition-all"><i class="fa fa-tachometer" aria-hidden="true"></i> {{ __('Dashboard') }}</a>
                @else
                    <div class="flex space-x-2">
                        <a type="button" href="{{ url('user/login') }}"
                            class="px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md whitespace-nowrap transition-all hover:bg-opacity-90">{{ __('Sign In') }}</a>
                        <a type="button" href="{{ url('user/register') }}"
                            class="px-5 py-2 text-white bg-black text-center font-poppins font-normal text-base leading-6 rounded-md whitespace-nowrap transition-all hover:bg-opacity-90">{{ __('Register') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile Navbar -->
    <div id="mobile-navbar"
        class="flex lg:hidden flex-col w-full px-4 md:px-8 py-3 pt-4 z-30" role="navigation" aria-label="Mobile Navigation" aria-hidden="true">

        <div class="flex items-center justify-between w-full">
            <div class="z-50">
                <button id="nav-toggle"
                    class="btn text-gray bg-white text-left font-poppins font-normal text-base leading-6 focus:outline-none">
                    <img src="https://teptix.com/images/eventpalettehamburgico.png" style="width: 40px;" alt="Menu">
                </button>
            </div>

            <div class="flex items-center justify-center flex-1">
                <a href="{{ url('/') }}" class="">
                    <img class="object-contain h-[45px] w-[150px]"
                        src="{{ $logo ? url('images/upload/' . $logo) : asset('/images/logo.png') }}"
                        alt="Logo">
                </a>
            </div>

            <div class="w-[40px]"></div>
        </div>

        <div class="hidden w-full flex-col mt-4 transition-all duration-300" id="nav-content">

            <div class="flex flex-col space-y-4 mb-4 border-b border-gray-200 pb-4 w-full px-2">
                
                <div class="w-full">
                    <form action="{{ url('user/search_event') }}" method="post" class="w-full m-0">
                        @csrf
                        <div class="relative w-full">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <img src="{{ asset('images/search.svg') }}" class="w-5 h-5" alt="">
                            </div>
                            <input type="search" name="search"
                                class="block w-full p-2 pl-10 text-gray bg-white border border-gray-light text-left font-poppins font-normal text-base leading-6 rounded-md focus:outline-none"
                                placeholder="{{ __('Search..') }}" style="border-color: red;box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;" required>
                        </div>
                    </form>
                </div>

                <div class="flex pt-5 justify-center w-full">
                    @if (Auth::guard('appuser')->check())
                        <div class="flex items-center justify-between w-full bg-gray-50 rounded-lg p-2 shadow-sm">
                            <div class="flex items-center">
                                <img src="{{ asset('images/upload/' . $user->image) }}"
                                    class="w-10 h-10 bg-cover object-contain border border-gray-light rounded-full"
                                    alt="">
                                <p class="ml-3 font-poppins font-medium text-sm leading-5 text-black">
                                    {{ $user->name . ' ' . $user->last_name }}</p>
                            </div>
                            <div class="dropdown relative flex">
                                <div class="relative inline-block text-left">
                                    <button
                                        class="ml-3 py-2 text-gray font-medium text-xs flex items-center focus:outline-none"
                                        type="button" onclick="showmenuMobile()"><img
                                            src="{{ asset('images/dropdown.png') }}" alt="">
                                    </button>
                                    <div id="dropdownMenuClassMobile"
                                        class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-2xl z-50">
                                        <div class="rounded-md bg-white shadow-xs">
                                            <div class="py-1">
                                                <div class="overflow-y-auto py-4 px-3 bg-gray-50 rounded pt-10 border-b border-gray-light pb-5">
                                                    <ul class="space-y-8">
                                                        <li><a href="{{ url('/my-tickets') }}" class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize">{{ __('My tickets') }}</a></li>
                                                        <li><a href="{{ url('/user/profile') }}" class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize">{{ __('Profile') }}</a></li>
                                                        <li><a href="{{ url('/change-password') }}" class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize">{{ __('Change password') }}</a></li>
                                                        @if ($wallet == 1)
                                                            <li><a href="{{ route('myWallet') }}" class="flex items-center font-normal font-poppins leading-6 text-black text-base capitalize">{{ __('My Wallet') }}</a></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                                <div class="px-3 py-5">
                                                    <a href="{{ route('logoutUser') }}" class="flex items-center font-poppins font-medium leading-4 text-danger capitalize">
                                                        <i class="fa-solid fa-right-from-bracket"></i><span id="checkMobile" class="flex-1 whitespace-nowrap ml-2">{{ __('Logout') }}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(Auth::check() && Auth::user()->hasRole('Organizer'))
                        <a type="button" href="{{ url('organization-home') }}"
                            class="w-full px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md"><i class="fa fa-tachometer" aria-hidden="true"></i> {{ __('Dashboard') }}</a>
                    @elseif(Auth::check() && Auth::user()->hasRole('admin'))
                        <a type="button" href="{{ url('admin/home') }}"
                            class="w-full px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md"><i class="fa fa-tachometer" aria-hidden="true"></i> {{ __('Dashboard') }}</a>
                    @else
                        <div class="flex flex-row gap-3 w-full">
                            <a type="button" href="{{ url('user/login') }}"
                                class="flex-1 px-5 py-2 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md">{{ __('Sign In') }}</a>
                            <a type="button" href="{{ url('user/register') }}"
                                class="flex-1 px-5 py-2 text-white bg-black text-center font-poppins font-normal text-base leading-6 rounded-md">{{ __('Register') }}</a>
                        </div>
                    @endif
                </div>
            </div>

            <ul class="list-reset flex flex-col w-full text-left space-y-4 px-2">
                <li class="nav-item {{ $activePage == 'home' ? 'active' : '' }} ">
                    <a href="{{ url('/') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray">{{ __('Home') }}</a>
                </li>
                <li class="nav-item {{ Request::is('all-events') ? 'active' : '' }}">
                    <a href="{{ url('/all-events') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray">{{ __('Events') }}</a>
                </li>
                <li class="nav-item {{ Request::is('all-category') ? 'active' : '' }} ">
                    <div class="relative inline-block text-left w-full">
                        <a type="button" href="#" id="showcattab"
                            class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray flex items-center justify-between focus:outline-none w-full">{{ __('Categories') }}
                            <img src="{{ asset('images/dropdown.png') }}" alt="" class="h-2 w-3">
                        </a>
                    </div>
                    <div id="cattab"
                        class="hidden origin-top-left left-0 rounded-md shadow-inner mt-2 z-30 w-full bg-gray-50">
                        <div class="p-3">
                            @php
                                if (!isset($catactive)) {
                                    $catactive = null;
                                }
                            @endphp
                            @if (isset($category))
                                @foreach ($category as $item)
                                    <div class="mb-2">
                                        <a href="{{ url('/events-category/' . $item->id . '/' . \Illuminate\Support\Str::slug($item->name)) }}"
                                            class="block text-base font-poppins font-normal leading-5 {{ $catactive == \Illuminate\Support\Str::slug($item->name) ? 'text-primary bg-primary-light' : '' }} capitalize p-2 hover:bg-primary-light rounded-md w-full">{{ $item->name }}</a>
                                    </div>
                                  @endforeach
                            @endif
                            <div>
                                <a href="{{ url('/all-category') }}"
                                    class="flex items-center text-base font-poppins font-normal leading-5 {{ $catactive == 'all' ? 'text-primary bg-primary-light' : '' }} capitalize p-2 hover:bg-primary-light rounded-md w-full">{{ __('All categories') }}
                                    <img src="{{ asset('images/right-dropdown.png') }}" alt="" class="ml-2 h-2 w-2">
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item {{ $activePage == 'blog' ? 'active' : '' }}">
                    <a href="{{ url('/all-blogs') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray">{{ __('Blog') }}</a>
                </li>
                <li class="nav-item {{ $activePage == 'contact' ? 'active' : '' }}">
                    <a href="{{ url('/contact') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray">{{ __('Contact Us') }}</a>
                </li>
                <li class="nav-item {{ $activePage == 'become-organizer' ? 'active' : '' }}">
                    <a href="{{ url('/become-organizer') }}"
                        class="nav-link px-2 capitalize font-poppins font-normal text-base leading-6 text-gray">{{ __('Become an Organizer') }}</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

@if(session('show_phone_popup') || (Auth::guard('appuser')->check() && empty(Auth::guard('appuser')->user()->phone)))
<div id="phoneModal" class="fixed inset-0 overflow-y-auto h-full w-full backdrop-blur-sm" style="display: none; z-index: 9999; background-color: rgb(189 195 207 / 63%);">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-lg shadow-2xl mx-auto" style="width: 440px;">
            <div class="p-6">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Complete Your Profile') }}</h3>
                    <p class="text-sm text-gray-600">
                        {{ __('Please enter your phone number to complete your profile') }}
                    </p>
                </div>

                <form id="phoneForm" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="country_code">
                            {{ __('Country Code') }}
                        </label>
                        <select id="country_code" name="country_code"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                            required>
                            <option value="+1">+1 (USA/Canada)</option>
                            <option value="+91">+91 (India)</option>
                            <option value="+44">+44 (UK)</option>
                            <option value="+61">+61 (Australia)</option>
                            <option value="+971">+971 (UAE)</option>
                            @foreach(\App\Models\Country::get() as $country)
                                <option value="{{ $country->phonecode }}">{{ $country->phonecode }} ({{ $country->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">
                            {{ __('Phone Number') }}
                        </label>
                        <input type="tel" id="phone" name="phone"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                            placeholder="{{ __('Enter your phone number') }}"
                            pattern="[0-9]+"
                            maxlength="12"
                            required>
                        <p id="phoneError" class="text-red-500 text-xs mt-2" style="display: none;"></p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="submitPhone"
                            class="w-full bg-primary hover:bg-opacity-90 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            {{ __('Submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<script>
@if(session('show_phone_popup') || (Auth::guard('appuser')->check() && empty(Auth::guard('appuser')->user()->phone)))
window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('phoneModal').style.display = 'block';
    }, 500);

    const phoneForm = document.getElementById('phoneForm');
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phoneError');
    const submitBtn = document.getElementById('submitPhone');

    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    phoneForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const phone = phoneInput.value.trim();
        const countryCode = document.getElementById('country_code').value;

        if (phone.length < 7) {
            phoneError.textContent = '{{ __("Please enter a valid phone number") }}';
            phoneError.style.display = 'block';
            return;
        }

        phoneError.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __("Submitting...") }}';

        fetch('{{ route("user.updatePhone") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                phone: phone,
                country_code: countryCode
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('phoneModal').style.display = 'none';
                alert('{{ __("Phone number updated successfully") }}');
            } else {
                phoneError.textContent = data.message || '{{ __("Error updating phone number") }}';
                phoneError.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = '{{ __("Submit") }}';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            phoneError.textContent = '{{ __("An error occurred. Please try again.") }}';
            phoneError.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = '{{ __("Submit") }}';
        });
    });
});
@endif
</script>

<script>
    function attachLogoutEvent(id) {
        let el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure to logout!!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.location = "/user/logoutuser";
                    }
                })
            });
        }
    }
    
    attachLogoutEvent("checkDesktop");
    attachLogoutEvent("checkMobile");
</script>

<script>
    document.getElementById('nav-toggle').onclick = function() {
        let navContent = document.getElementById("nav-content");
        navContent.classList.toggle("hidden");
        navContent.classList.toggle("flex");
    }

    document.getElementById('showcattab').onclick = function(e) {
        e.preventDefault();
        document.getElementById("cattab").classList.toggle("hidden");
    }

    function showmenuDesktop() {
        document.getElementById("dropdownMenuClassDesktop").classList.toggle("hidden");
    }

    function showmenuMobile() {
        document.getElementById("dropdownMenuClassMobile").classList.toggle("hidden");
    }

    function handleNavbarAria() {
        const isDesktop = window.innerWidth >= 1024;
        const desktopNav = document.getElementById('desktop-navbar');
        const mobileNav = document.getElementById('mobile-navbar');
        
        if (desktopNav && mobileNav) {
            if (isDesktop) {
                desktopNav.setAttribute('aria-hidden', 'false');
                desktopNav.style.display = 'flex';
                mobileNav.setAttribute('aria-hidden', 'true');
                mobileNav.style.display = 'none';
            } else {
                desktopNav.setAttribute('aria-hidden', 'true');
                desktopNav.style.display = 'none';
                mobileNav.setAttribute('aria-hidden', 'false');
                mobileNav.style.display = 'flex';
            }
        }
    }

    // Run on load and resize
    handleNavbarAria();
    window.addEventListener('resize', handleNavbarAria);
</script>