@extends('frontend.master', ['activePage' => 'blog'])
@section('title', 'Change Password')
@section('content')
    <div class=" bg-scroll min-h-screen" style="background-image: url('images/events.png')">
        {{-- scroll --}}
        <div class="mr-4 flex justify-end z-30">
            <a type="button" href="{{ url('#') }}"
                class="scroll-up-button bg-primary rounded-full p-4 fixed z-20  2xl:mt-[49%] xl:mt-[59%] xlg:mt-[68%] lg:mt-[75%] xxmd:mt-[83%] md:mt-[90%]
                    xmd:mt-[90%] sm:mt-[117%] msm:mt-[125%] xsm:mt-[160%]">
                <img src="{{ asset('images/downarrow.png') }}" alt="" class="w-3 h-3 z-20">
            </a>
        </div>
        <form action="{{ url('user-change-password') }}" method="post" >
            @csrf
        <div
            class="mt-5 3xl:mx-52 2xl:mx-28 1xl:mx-28 xl:mx-36 xlg:mx-32 lg:mx-36 xxmd:mx-24 xmd:mx-32 md:mx-28 sm:mx-20 msm:mx-16 xsm:mx-10 xxsm:mx-5 z-10 relative">
            <p class="font-semibold font-poppins text-5xl leading-10 text-black pt-5">{{ __('Change password') }}</p>

            {{-- Success Message --}}
            @if (session('status'))
                <div class="bg-success text-white px-4 py-3 rounded relative mt-5" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-white" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none';">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </span>
                </div>
            @endif

            {{-- Error Message --}}
            @if (session('error_msg'))
                <div class="bg-danger text-white px-4 py-3 rounded relative mt-5" role="alert">
                    <span class="block sm:inline">{{ session('error_msg') }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-white" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none';">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </span>
                </div>
            @endif

            <div
                class="space-y-5 mt-10 mb-5 1xl:w-[30%] xl:w-[35%] xlg:w-[40%] lg:w-[50%] xxmd:w-[55%] xmd:w-[64%] md:w-[70%] sm:w-[80%]">
                <div class="">
                    <label for="current_password"
                        class="font-poppins font-medium text-base leading-6 text-black">{{ __('Current Password') }}</label>
                    <div class="relative">
                        <input type="password" name="old_password" id="current_password"
                            class="w-full focus:outline-none text-sm font-poppins font-normal text-black block p-3 z-30 rounded-lg border border-gray-light"
                            placeholder="current password">
                        <span class="absolute right-2.5 bottom-2.5 text-xl font-poppins font-medium text-gray px-2"><i
                                class="fa-regular fa-eye text-primary" id="toggleCurrentPassword"></i></span>
                    </div>
                    @error('old_password')
                        <div class="_2OcwfRx4" data-qa="email-status-message">{{ $message }}</div>
                    @enderror
                </div>
                <div class="">
                    <label for="new_password"
                        class="font-poppins font-medium text-base leading-6 text-black">{{ __('New Password') }}</label>
                    <div class="relative">
                        <input type="password" name="password" id="new_password"
                            class="w-full focus:outline-none text-sm font-poppins font-normal text-black block p-3 z-30 rounded-lg border border-gray-light"
                            placeholder="new password" minlength="6" maxlength="12" required>
                        <span class="absolute right-2.5 bottom-2.5 text-xl font-poppins font-medium text-gray px-2"><i
                                class="fa-regular fa-eye text-primary" id="toggleNewPassword"></i></span>
                    </div>
                    @error('password')
                        <div class="_2OcwfRx4" data-qa="email-status-message">{{ $message }}</div>
                    @enderror
                </div>
                <div class="">
                    <label for="confirm_password"
                        class="font-poppins font-medium text-base leading-6 text-black">{{ __('Confirm Password') }}</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="confirm_password"
                            class="w-full focus:outline-none text-sm font-poppins font-normal text-black block p-3 z-30 rounded-lg border border-gray-light"
                            placeholder="Confirm Password" minlength="6" maxlength="12" required>

                        <span class="absolute right-2.5 bottom-2.5 text-xl font-poppins font-medium text-gray px-2"><i
                                class="fa-regular fa-eye text-primary " id="toggleConfirmPassword"></i></span>
                    </div>
                </div>
                <div class="">
                    <button
                        class="bg-primary text-white font-poppins font-medium text-base leading-6 px-5 py-3 rounded-md w-full">{{ __('Update') }}</button>
                </div>
            </div>
        </form>
        </div>
    </div>

@endsection
