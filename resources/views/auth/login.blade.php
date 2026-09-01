@extends('master')
@section('content')
    @php
        $loginSetting = \App\Models\Setting::find(1);
        $logo = optional($loginSetting)->logo;
        $appName = optional($loginSetting)->app_name ?: config('app.name', 'Teptix');
    @endphp
    <style>
        ._2OcwfRx4{
            color: red;
            font-weight:800;
        }
        .grecaptcha-badge {
            display:none;
        }
    </style>
    <section class="section">
        <div class="d-flex flex-wrap align-items-stretch">
            <div class="col-lg-4 col-md-6 col-12 order-lg-1 min-vh-100 order-2 bg-white">
                <div class="p-4 m-3 w-10 h-10">
                    <img src="{{ $logo ? asset('images/upload/' . $logo) : asset('images/logo.png') }}" alt="logo"
                         height="50px" class="mb-4 mt-2 object-contain w-auto">
                    <h4 class="text-dark font-weight-normal mb-4">{{ __('Welcome to ') }}<span
                            class="font-weight-bold">{{ $appName }}</span></h4>
                    <form method="POST" action="{{ url('admin/login') }}" id="form-login" class="needs-validation" novalidate="">
                        @csrf
                        <div class="form-group">
                            <label for="email">{{ __('Email') }}</label>
                            <input id="email" type="email" class="form-control" name="email" tabindex="1" required
                                autofocus>
                            @if ($errors->has('email'))
                                <div class="invalid-feedback">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </div>
                            @endif
                            @if (Session::has('error_msg'))
                                <span class="invalid-feedback" style="display: block;">
                                    <strong>{{ Session::get('error_msg') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="d-block">
                                <label for="password" class="control-label">{{ __('Password') }}</label>
                            </div>
                            <input id="password" type="password" class="form-control" name="password" tabindex="2"
                                required>
                            @if ($errors->has('password'))
                                <div class="invalid-feedback">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="remember" class="custom-control-input" tabindex="3"
                                    id="remember-me">
                                <label class="custom-control-label" for="remember-me">{{ __('Remember Me') }}</label>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-primary btn-lg btn-icon icon-right g-recaptcha" tabindex="4" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" 
                data-callback='onSubmit' 
                data-action='submit'>
                                {{ __('Login') }}
                            </button>
                        </div>
                        @if ($errors->has('g-recaptcha-response'))
                        <div class="text-center"> <!-- Center align and apply text-danger style -->

<div class="_2OcwfRx4" data-qa="email-status-message">{{ $errors->first('g-recaptcha-response') }}</div>
                                    </div>
        @endif
                    </form>
                </div>
            </div>
            <div class="col-lg-8 col-12 order-lg-2 order-1 min-vh-100 background-walk-y position-relative overlay-gradient-bottom"
                data-background="{{ url('images/auth_image.png') }}">
                <div class="absolute-bottom-left index-2">
                    <div class="text-light p-5 pb-2">
                        <div class="mb-5 pb-3">
                            <h1 class="mb-2 display-4 font-weight-bold">{{ __('Welcome') }}</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

<script>
        function onSubmit(token) {
            document.getElementById("form-login").submit();
        }
    </script>
