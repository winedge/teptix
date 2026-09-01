<!doctype HTML>
<html>
<head>
    <title>{{ __('Login') }} | The Event Palette</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/select2.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    @php $favicon = \App\Models\Setting::find(1)->favicon; @endphp
    <link rel="icon" type="image/png" href="{{ $favicon ? url('images/upload/' . $favicon) : asset('images/logo.png') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://unpkg.com/flowbite@1.5.5/dist/flowbite.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
      /* Preloader styles */
      .grecaptcha-badge {
        display:none;
      }
      .preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 1;
        transition: opacity 0.5s ease; /* Add a transition for opacity */
      }
      .preloader img {
        max-width: 200px;
        max-height: 200px;
      }

      /* Hide the preloader with animation */
      body.loaded .preloader {
        opacity: 0;
        pointer-events: none; /* Make it non-interactable */
      }

      /* Add this class to the body tag when the page is fully loaded */
      body.loaded {
          overflow: auto; /* Show the scrollbar when content overflows */
      }
      .success-msg {
          position: fixed;
          top: 20px;
          left: 50%;
          transform: translateX(-50%);
          margin: 10px 0;
          padding: 15px 20px;
          border-radius: 5px;
          z-index: 10000;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          color: #270;
          background-color: #DFF2BF;
          border: 1px solid #4CAF50;
          font-weight: 500;
          max-width: 90%;
          text-align: center;
          transition: opacity 0.5s ease;
      }
      @property --angle {
        syntax: "<angle>";
        initial-value: 0deg;
        inherits: false;
        border-radius: 12px;
      }
      /* Add this style to create the moving border animation */
        .moving-border {
            position: relative;
            overflow: hidden;
        }

        .moving-border::before,
.moving-border::after {
    content: "";
    position: absolute;
    inset: -2px;
    z-index: -1;
    border: 2px solid transparent;
    border-image: linear-gradient(var(--angle), #032146, #C3F2FF, #b00) 12; /* Add border-radius here */
    border-image-slice: 1;
    animation: rotate 10s linear infinite;
}

        .moving-border::before {
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-image: linear-gradient(var(--angle), #032146, #C3F2FF, #b00);
            border-image-slice: 1;

            border-radius: 12px;
        }

        .moving-border::after {
            filter: blur(10px);
        }

        @keyframes rotate {
            0% {
                --angle: 0deg;
            }
            100% {
                --angle: 360deg;
            }
        }
        button {
          position: relative;
          padding: 12px 35px;
          background: #ff0000;
          font-size: 17px;
          font-weight: 500;
          color: #fff;
          border: 3px solid #ff0000;
          border-radius: 8px;
          box-shadow: 0 0 0 #fec1958c;
          transition: all 0.3s ease-in-out;
          cursor: pointer;
        }

        .googleLoginButton{
            background: #fffdef;
            color: #000;
            border: 3px solid #f9f9f9;
            box-shadow: 0 0 0 #fec1958c;
        }

        /* Google login button container transitions */
        #user-google-login,
        #organizer-google-login {
            transition: opacity 0.3s ease, display 0.3s ease;
        }

        /* Radio button label styling for better UX */
        .radio-label {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .radio-label:hover {
            transform: translateY(-2px);
            box-shadow: rgba(0, 0, 0, 0.2) 0px 5px 10px, rgba(0, 0, 0, 0.15) 0px 3px 6px !important;
        }

.star-1 {
  position: absolute;
  top: 20%;
  left: 20%;
  width: 25px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 1s cubic-bezier(0.05, 0.83, 0.43, 0.96);
}

.star-2 {
  position: absolute;
  top: 45%;
  left: 45%;
  width: 15px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 1s cubic-bezier(0, 0.4, 0, 1.01);
}

.star-3 {
  position: absolute;
  top: 40%;
  left: 40%;
  width: 5px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 1s cubic-bezier(0, 0.4, 0, 1.01);
}

.star-4 {
  position: absolute;
  top: 20%;
  left: 40%;
  width: 8px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 0.8s cubic-bezier(0, 0.4, 0, 1.01);
}

.star-5 {
  position: absolute;
  top: 25%;
  left: 45%;
  width: 15px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 0.6s cubic-bezier(0, 0.4, 0, 1.01);
}

.star-6 {
  position: absolute;
  top: 5%;
  left: 50%;
  width: 5px;
  height: auto;
  filter: drop-shadow(0 0 0 #fffdef);
  z-index: -5;
  transition: all 0.8s ease;
}

button:hover {
  background: transparent;
  color: #fff;
  box-shadow: 0 0 25px #fec1958c;
}

button:hover .star-1 {
  position: absolute;
  top: -80%;
  left: -30%;
  width: 25px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

button:hover .star-2 {
  position: absolute;
  top: -25%;
  left: 10%;
  width: 15px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

button:hover .star-3 {
  position: absolute;
  top: 55%;
  left: 25%;
  width: 5px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

button:hover .star-4 {
  position: absolute;
  top: 30%;
  left: 80%;
  width: 8px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

button:hover .star-5 {
  position: absolute;
  top: 25%;
  left: 115%;
  width: 15px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

button:hover .star-6 {
  position: absolute;
  top: 5%;
  left: 60%;
  width: 5px;
  height: auto;
  filter: drop-shadow(0 0 10px #fffdef);
  z-index: 2;
}

.fil0 {
  fill: #fffdef;
}
.text-base1 {
    font-size: 0.9rem !important;
    line-height: 1.5rem;
}
    </style>
     <?php $primary_color = \App\Models\Setting::find(1)->primary_color; ?>

     <style>
         :root {
             --primary_color: <?php echo $primary_color; ?>;
             --light_primary_color: <?php echo $primary_color . '1a'; ?>;
             --profile_primary_color: <?php echo $primary_color . '52'; ?>;
             --middle_light_primary_color: <?php echo $primary_color . '85'; ?>;
         }

         .bg-primary {
             --tw-bg-opacity: 1;
             background-color: var(--primary_color);
         }

         .bg-primary-dark {
             --tw-bg-opacity: 1;
             background-color: var(--profile_primary_color);
             /* Use the profile_primary_color variable */
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
         body{
             background-image: url('{{ asset("images/loginbg.webp") }}'); /* The image used */
             background-color: #cccccc; /* Used if the image is unavailable */
             height: 500px; /* You must set a specified height */
             background-position: center; /* Center the image */
             background-repeat: no-repeat; /* Do not repeat the image */
             background-size: auto; /* Resize the background image to cover the entire container */
         }
         input[type="radio"]:checked {
    background-color: #F1EBF9 !important;
    color: #000000 !important;
    accent-color: red;
}
    ._2OcwfRx4{
        color: red;
        font-weight:800;
    }
    .mt-24 {
    margin-top: 3rem !important;
}
@media (min-width: 280px){
.xxsm\:w-full {
    /* width: 100%; */
    margin: 20px;
}
.text-3xl{
    font-size: 24px !important;
}
.pt-12 {
    padding-top: 2rem;
}
}
}
.gsi-material-button {
  -moz-user-select: none;
  -webkit-user-select: none;
  -ms-user-select: none;
  -webkit-appearance: none;
  background-color: WHITE;
  background-image: none;
  border: 1px solid #747775;
  -webkit-border-radius: 4px;
  border-radius: 4px;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  color: #1f1f1f;
  cursor: pointer;
  font-family: "Roboto", arial, sans-serif;
  font-size: 14px;
  height: 40px;
  letter-spacing: 0.25px;
  outline: none;
  overflow: hidden;
  padding: 0 12px;
  position: relative;
  text-align: center;
  -webkit-transition: background-color 0.218s, border-color 0.218s,
    box-shadow 0.218s;
  transition: background-color 0.218s, border-color 0.218s, box-shadow 0.218s;
  vertical-align: middle;
  white-space: nowrap;
  width: auto;
  max-width: 400px;
  min-width: min-content;
}

.gsi-material-button .gsi-material-button-icon {
  height: 20px;
  margin-right: 12px;
  min-width: 20px;
  width: 20px;
}

.gsi-material-button .gsi-material-button-content-wrapper {
  -webkit-align-items: center;
  align-items: center;
  display: flex;
  -webkit-flex-direction: row;
  flex-direction: row;
  -webkit-flex-wrap: nowrap;
  flex-wrap: nowrap;
  height: 100%;
  justify-content: space-between;
  position: relative;
  width: 100%;
}

.gsi-material-button .gsi-material-button-contents {
  -webkit-flex-grow: 1;
  flex-grow: 1;
  font-family: "Roboto", arial, sans-serif;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: top;
}

.gsi-material-button .gsi-material-button-state {
  -webkit-transition: opacity 0.218s;
  transition: opacity 0.218s;
  bottom: 0;
  left: 0;
  opacity: 0;
  position: absolute;
  right: 0;
  top: 0;
}

.gsi-material-button:disabled {
  cursor: default;
  background-color: #ffffff61;
  border-color: #1f1f1f1f;
}

.gsi-material-button:disabled .gsi-material-button-contents {
  opacity: 38%;
}

.gsi-material-button:disabled .gsi-material-button-icon {
  opacity: 38%;
}

.gsi-material-button:not(:disabled):active .gsi-material-button-state,
.gsi-material-button:not(:disabled):focus .gsi-material-button-state {
  background-color: #303030;
  opacity: 12%;
}

.gsi-material-button:not(:disabled):hover {
  -webkit-box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3),
    0 1px 3px 1px rgba(60, 64, 67, 0.15);
  box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.3),
    0 1px 3px 1px rgba(60, 64, 67, 0.15);
}

.gsi-material-button:not(:disabled):hover .gsi-material-button-state {
  background-color: #303030;
  opacity: 8%;
}

     </style>
</head>

<body>
    @if (Session::has('checkMail'))
        <div class="success-msg">
            <i class="fa fa-check"></i>
            {{ Session::get('checkMail') }}
        </div>
    @endif
    @if (Session::has('success'))
        <div class="success-msg">
            <i class="fa fa-check"></i>
            {{ Session::get('success') }}
        </div>
    @endif
    @if (Session::has('error_msg'))
        <div class="error-msg" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); margin: 10px 0; padding: 15px 20px; border-radius: 5px; z-index: 10000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; font-weight: 500; max-width: 90%; text-align: center; transition: opacity 0.5s ease;">
            <i class="fa fa-exclamation-triangle"></i>
            {{ Session::get('error_msg') }}
        </div>
    @endif
    @php
        $setting = \App\Models\Setting::find(1);
    @endphp
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{ \App\Models\Setting::find(1)->logo ? url('images/upload/' . \App\Models\Setting::find(1)->logo) : asset('images/logo.png') }}" alt="Loading..." />
    </div>
    <div class="flex justify-center mt-24">

        <div
            class="bg-white shadow-2xl rounded-md p-5 mt-10 1xl:w-[28%] xl:w-[35%] lg:w-[40%] xmd:w-[50%] md:w-[60%] sm:w-[70%] xxsm:w-full moving-border" style="box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;backdrop-filter: blur(6px) saturate(100%); -webkit-backdrop-filter: blur(6px) saturate(100%); background-color: rgba(17, 25, 40, 0.75); border-radius: 1px;">
            <div class="flex justify-center mt-5">
                <img src="https://teptix.com/images/teptixlogo.png" style="height: 60px;" alt="" class="w-auto">
            </div>
            <p class="font-poppins font-bold text-3xl leading-9 text-white text-center pt-6">
                {{ __('Welcome Back!') }}</p>
            <form action="{{ url('user/login') }}" method="post" data-qa="form-login" id="form-login" name="login">
                @csrf
                <input type="hidden" value="{{ url()->previous() }}" name="url">

                <div class="pt-12">
                    <div
                        class="flex sm:space-x-7 justify-center  xxsm:space-y-5 msm:space-y-0 msm:space-x-5 xsm:space-x-0 xxsm:space-x-0 xxsm:mx-10.0 xxsm:flex-wrap xsm:flex-wrap msm:flex-nowrap">
                        <label for="default-radio-1" class="w-full radio-label">
                            <div
                                class="border border-gray-light py-3.5 px-5 rounded-lg text-gray-100 w-full font-normal font-poppins text-base leading-6 flex" style="box-shadow: rgba(0, 0, 0, 0.16) 0px 3px 6px, rgba(0, 0, 0, 0.23) 0px 3px 6px;">
                                <input id="default-radio-1" type="radio" value="user" checked name="type"
                                    class="h-5 w-5 mr-2 border border-white-light  hover:border-red-light focus:outline-none">
                                <b style="color:white;">{{ __('User') }}</b>
                            </div>
                        </label>
                        <label for="default-radio-2" class="w-full radio-label">
                            <div
                                class="border border-gray-light py-3.5 px-5 rounded-lg text-gray-100  w-full font-normal font-poppins text-base leading-6 flex" style="box-shadow: rgba(0, 0, 0, 0.16) 0px 3px 6px, rgba(0, 0, 0, 0.23) 0px 3px 6px;">
                                <input id="default-radio-2" type="radio" value="org" name="type"
                                    class="select w-5 h-5 mr-2 border border-gray-light hover:border-gray-light focus:outline-none">
                                <b style="color:white;">{{ __('Organizer') }}</b>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-5">
                    <label for="email"
                        class="font-poppins font-medium text-base leading-6 text-white">{{ __('Email') }}</label>
                    <input type="email" name="email" id=""
                        class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-lg border border-gray-light focus:outline-none"
                        placeholder="{{__('Your Email')}}">
                    @error('email')
                        <div class="_2OcwfRx4" data-qa="email-status-message">{{ $message }}</div>
                    @enderror
                    @if (Session::has('error_msg'))
                        <div class="_2OcwfRx4 text-danger mt-1" data-qa="email-status-message">
                            <strong>{{ Session::get('error_msg') }}</strong>
                        </div>
                    @endif
                </div>
                <div class=" pt-5">
                    <label for="password"
                        class="font-poppins font-medium text-base leading-6 text-white">{{ __('Password') }}</label>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                            class="w-full focus:outline-none text-sm font-poppins font-normal text-black block p-3 z-30 rounded-lg border border-gray-light"
                            placeholder="{{__('Password')}}">
                        <span class="absolute right-2.5 bottom-2.5 text-xl font-poppins font-medium text-gray px-2"><i
                                class="fa-regular fa-eye text-primary" id="togglePassword"></i></span>
                        @error('password')
                            <div class="_2OcwfRx4" data-qa="email-status-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="flex justify-between pt-4">
                    <div class="flex">
                        <input id="default-radio-1" type="checkbox" value="true" name="remember" class="mr-2">
                        <label for=""
                            class="font-poppins font-medium text-xs leading-5 text-white pt-0.5">{{ __('Remember me') }}</label>
                    </div>
                    <div>
                        <a href="{{ url('/user/resetPassword') }}"
                            class="font-poppins font-medium text-xs leading-5 text-primary"><b>{{ __('Forgot your password?') }}</b></a>
                    </div>
                </div>

                <div class="pt-7" style="text-align:center";>
                    <button class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"
                data-callback='onSubmit'
                data-action='submit'>
  {{ __('Sign In') }}

  <div class="star-1">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
  <div class="star-2">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
  <div class="star-3">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
  <div class="star-4">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
  <div class="star-5">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
  <div class="star-6">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      xml:space="preserve"
      version="1.1"
      style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
      viewBox="0 0 784.11 815.53"
      xmlns:xlink="http://www.w3.org/1999/xlink"
    >
      <defs></defs>
      <g id="Layer_x0020_1">
        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
        <path
          class="fil0"
          d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"
        ></path>
      </g>
    </svg>
  </div>
</button>

@if ($errors->has('g-recaptcha-response'))
<div class="_2OcwfRx4" data-qa="email-status-message">{{ $errors->first('g-recaptcha-response') }}</div>
        @endif
                </div>
            </form>
            <div class="pt-6 flex justify-center" id="user-google-login">
              <a href="{{ route('google.redirect', ['type' => 'user']) }}">
                {{-- <button class="g-recaptcha" style="display: flex; align-items: center; gap: 8px; border: none;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.7 0 6.8 1.4 9.2 3.6l6.9-6.9C35.7 2.4 30.2 0 24 0 14.6 0 6.5 5.8 2.6 14.1l7.9 6.2C12.2 13.3 17.7 9.5 24 9.5z"></path>
                    <path fill="#34A853" d="M46.2 24.5c0-1.8-.2-3.7-.6-5.5H24v10.5h12.6c-.5 2.5-2.1 4.7-4.4 6.2l7 5.4c4.1-3.8 6.5-9.3 6.5-16.6z"></path>
                    <path fill="#4A90E2" d="M10.5 24c0-1.5.3-3 .8-4.3L3.5 13.5C1.3 17.4 0 21.6 0 24s1.3 6.6 3.5 10.5l7.8-6.2c-.5-1.3-.8-2.8-.8-4.3z"></path>
                    <path fill="#FBBC05" d="M24 48c6.5 0 12-2.1 16-5.8l-7.9-6.2c-2.1 1.4-4.8 2.3-8.1 2.3-6.3 0-11.7-4.3-13.6-10.2L3.5 34.5C7.5 42.3 15.3 48 24 48z"></path>
                    <path fill="none" d="M0 0h48v48H0z"></path>
                  </svg>
                    User Login with Google

                  <div class="star-1">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-3">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-4">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-5">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-6">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                </button> --}}
                <button class="gsi-material-button googleLoginButton">
                  <div class="gsi-material-button-state"></div>
                  <div class="gsi-material-button-content-wrapper">
                    <div class="gsi-material-button-icon">
                      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                        <path fill="none" d="M0 0h48v48H0z"></path>
                      </svg>
                    </div>
                    <span class="gsi-material-button-contents">User Login with Google</span>
                    <span style="display: none;">User Login with Google</span>
                  </div>
                </button>
              </a>
            </div>
            <div class="pt-6 flex justify-center" id="organizer-google-login" style="display: none;">
              <a href="{{ route('google.redirect', ['type' => 'org']) }}">
                {{-- <button class="g-recaptcha" style="display: flex; align-items: center; gap: 8px; border: none;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.7 0 6.8 1.4 9.2 3.6l6.9-6.9C35.7 2.4 30.2 0 24 0 14.6 0 6.5 5.8 2.6 14.1l7.9 6.2C12.2 13.3 17.7 9.5 24 9.5z"></path>
                    <path fill="#34A853" d="M46.2 24.5c0-1.8-.2-3.7-.6-5.5H24v10.5h12.6c-.5 2.5-2.1 4.7-4.4 6.2l7 5.4c4.1-3.8 6.5-9.3 6.5-16.6z"></path>
                    <path fill="#4A90E2" d="M10.5 24c0-1.5.3-3 .8-4.3L3.5 13.5C1.3 17.4 0 21.6 0 24s1.3 6.6 3.5 10.5l7.8-6.2c-.5-1.3-.8-2.8-.8-4.3z"></path>
                    <path fill="#FBBC05" d="M24 48c6.5 0 12-2.1 16-5.8l-7.9-6.2c-2.1 1.4-4.8 2.3-8.1 2.3-6.3 0-11.7-4.3-13.6-10.2L3.5 34.5C7.5 42.3 15.3 48 24 48z"></path>
                    <path fill="none" d="M0 0h48v48H0z"></path>
                  </svg>
                  Organizer Login with Google
                  <div class="star-1">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-2">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-3">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-4">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-5">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                  <div class="star-6">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" version="1.1" style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd" viewBox="0 0 784.11 815.53" xmlns:xlink="http://www.w3.org/1999/xlink">
                      <defs></defs>
                      <g id="Layer_x0020_1">
                        <metadata id="CorelCorpID_0Corel-Layer"></metadata>
                        <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.96,29.37 371.12,197.68 392.05,407.74 20.93,-210.06 184.09,-378.37 392.05,-407.74 -207.98,-29.38 -371.16,-197.69 -392.06,-407.78z"></path>
                      </g>
                    </svg>
                  </div>
                </button> --}}
                <button class="gsi-material-button googleLoginButton">
                  <div class="gsi-material-button-state"></div>
                  <div class="gsi-material-button-content-wrapper">
                    <div class="gsi-material-button-icon">
                      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                        <path fill="none" d="M0 0h48v48H0z"></path>
                      </svg>
                    </div>
                    <span class="gsi-material-button-contents">Organizer Login with Google</span>
                    <span style="display: none;">Organizer Login with Google</span>
                  </div>
                </button>
              </a>
            </div>
            <div class="pt-6 flex justify-center">
               <a href="https://teptix.com/"><img src="https://teptix.com/images/backicon.png" style="height:20px;margin-right:10px;"></a><h1 class="font-poppins font-medium text-base1 text-white"><strong><a href="https://teptix.com/">Go Back To Homepage</a></strong></h1>
            </div>
            <div class="pt-6 flex justify-center" style="margin-top:-15px;">
                <h1 class="font-poppins font-medium text-base1 leading-5 pt-4 text-left text-white">
                    {{ __('Don’t have an account?') }}
                    <a href="{{ url('/user/register') }}"
                        class="text-primary text-medium text-base1"><b>{{ __('Create An Account') }}</b></a>
                </h1>
            </div>
        </div>

    </div>

</body>
<script>
     // JavaScript to handle preloader
        window.addEventListener("DOMContentLoaded", function() {
            const preloader = document.querySelector(".preloader");
            const body = document.querySelector("body");

            // Simulate page loading
            setTimeout(function() {
                preloader.style.opacity = "0";
                body.classList.add("loaded");
            }, 2000); // Adjust the duration as needed
        });


    window.addEventListener("DOMContentLoaded", function() {
        const togglePassword = document.querySelector("#togglePassword");

        togglePassword.addEventListener("click", function(e) {
            // toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // toggle the eye / eye slash icon
            this.classList.toggle("fa-eye-slash");
        });
    });

    // Auto-hide success messages after 5 seconds
    window.addEventListener("DOMContentLoaded", function() {
        const successMsg = document.querySelector(".success-msg");
        if (successMsg) {
            setTimeout(function() {
                successMsg.style.opacity = "0";
                setTimeout(function() {
                    successMsg.style.display = "none";
                }, 500); // Allow fade out transition
            }, 5000); // Hide after 5 seconds
        }
    });

    // Handle Google login button toggle based on user type selection
    window.addEventListener("DOMContentLoaded", function() {
        const userRadio = document.getElementById("default-radio-1");
        const organizerRadio = document.getElementById("default-radio-2");
        const userGoogleLogin = document.getElementById("user-google-login");
        const organizerGoogleLogin = document.getElementById("organizer-google-login");

        // Function to toggle Google login buttons with smooth transitions
        function toggleGoogleLoginButtons() {
            if (userRadio.checked) {
                // Show user, hide organizer
                userGoogleLogin.style.opacity = "1";
                userGoogleLogin.style.display = "flex";
                organizerGoogleLogin.style.opacity = "0";
                setTimeout(() => {
                    organizerGoogleLogin.style.display = "none";
                }, 300);
            } else if (organizerRadio.checked) {
                // Show organizer, hide user
                organizerGoogleLogin.style.opacity = "1";
                organizerGoogleLogin.style.display = "flex";
                userGoogleLogin.style.opacity = "0";
                setTimeout(() => {
                    userGoogleLogin.style.display = "none";
                }, 300);
            }
        }

        // Set initial state (user selected by default)
        toggleGoogleLoginButtons();

        // Add event listeners to radio buttons
        userRadio.addEventListener("change", toggleGoogleLoginButtons);
        organizerRadio.addEventListener("change", toggleGoogleLoginButtons);
    });
</script>
 <script src="https://unpkg.com/flowbite@1.5.5/dist/flowbite.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function onSubmit(token) {
            document.getElementById("form-login").submit();
        }
    </script>
</html>
