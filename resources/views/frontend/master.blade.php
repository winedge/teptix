<!DOCTYPE html>
<html lang="en">

<head>
    @php
        $favicon = \App\Models\Setting::find(1)->favicon;
    @endphp
    <meta charset="utf-8">
    <link href="{{ $favicon ? url('images/upload/' . $favicon) : asset('images/logo.png') }}" rel="icon"
        type="image/png">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    @if (!Route::current('eventDetail'))
    <meta name="description" content="Book tickets or host your own events with TEPTIX - discover concerts, workshops, and experiences near you, or list your event and start selling tickets in minutes.">
    <title>{{ \App\Models\Setting::find(1)->app_name }} | @yield('title')</title>
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <input type="hidden" name="base_url" id="base_url" value="{{ url('/') }}">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/select2.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    <!-- Preloader CSS -->
    <style>
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
            transition: opacity 0.5s ease;
        }
        .preloader img {
            max-width: 200px;
            max-height: 200px;
        }
        body.loaded .preloader {
            opacity: 0;
            pointer-events: none;
        }
        body.loaded {
            overflow: auto;
        }
        .grecaptcha-badge {
            display:none;
        }
    </style>
    
    {!! JsonLdMulti::generate() !!}
    {!! SEOMeta::generate() !!}
    {!! OpenGraph::generate() !!}
    {!! Twitter::generate() !!}
    {!! JsonLd::generate() !!}
    <!-- Favicons -->
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <!-- Vendor CSS Files -->
    <link href="{{ url('frontend/css/ionicons.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" rel="stylesheet" />
    <link href="{{ url('frontend/css/animate.min.css') }}" rel="stylesheet">
    <link href="{{ url('frontend/css/font-awesome.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="{{ url('frontend/css/owl.carousel.min.css') }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
        integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @if (session('direction') == 'rtl')
        <link rel="stylesheet" href="{{ url('frontend/css/rtl.css') }}">
    @endif
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-99YKMSF634"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-99YKMSF634');
</script>
@yield('head_section')
</head>

<body>
    <!-- Preloader HTML -->
    <div class="preloader">
    <img src="{{ \App\Models\Setting::find(1)->logo ? url('images/upload/' . \App\Models\Setting::find(1)->logo) : asset('images/logo.png') }}" alt="Loading..." />
</div>

    <div>
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

            .carousel-indicators button[aria-current=true] {
                background: var(--primary_color) !important;
            }

            .profile button[aria-selected=true] {
                background: var(--primary_color) !important;
                color: #FFFFFF !important;
            }
        </style>

        <input type="hidden" name="currency" id="currency" value="{{ $currency }}">
        <input type="hidden" name="default_lat" id="default_lat"
            value="{{ \App\Models\Setting::find(1)->default_lat }}">
        <input type="hidden" name="default_long" id="default_long"
            value="{{ \App\Models\Setting::find(1)->default_long }}">
        <div class="site-wrapper">
            @include('frontend.layout.header')
            <div class="min-h-screen flex flex-col">
                <main class="flex-grow">
                    @yield('content')
                </main>
                <footer class="mt-auto">
                    @include('frontend.layout.footer')
                </footer>
            </div>
        </div>
        <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11485063774">
</script>
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "mdys93tp08");
</script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11485063774');
</script>
 <script>
setInterval(() => {
    fetch('/clear-cache')
        .then(res => res.json())
        .then(data => console.log(data.message))
        .catch(err => console.error('❌ Error clearing cache:', err));
}, 180000); // every 3 min
</script>
     
       <script src="{{ url('frontend/js/jquery.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.min.js"></script>
<script src="{{ url('frontend/js/jquery.easing.min.js') }}"></script>
<script src="{{ url('frontend/js/validate.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ url('frontend/js/owl.carousel.min.js') }}"></script>
<script src="{{ url('frontend/js/scrollreveal.min.js') }}"></script>
<script src="{{ url('frontend/js/map.js') }}"></script>

<?php
$client_id = \App\Models\PaymentSetting::find(1)->paypalClientId;
$cur = \App\Models\Setting::find(1)->currency;
$map_key = \App\Models\Setting::find(1)->map_key;
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://unpkg.com/flowbite@1.5.5/dist/flowbite.js"></script>
<script src="{{ url('frontend/js/qrcode.min.js') }}"></script>
<script src="{{ url('frontend/js/main.js') }}"></script>
<script src="{{ url('frontend/js/custom.js') }}?{{time() }}"></script>
<script src="{{ url('js/custom.js') }}?{{time() }}"></script>
<!--<script src="./TW-ELEMENTS-PATH/dist/js/index.min.js"></script>-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.4/datepicker.min.js"></script>

        @yield('script_section')
    </div>
   
<!-- Preloader JavaScript -->
<script>
    // Wait for window load (all assets loaded)
    window.addEventListener("load", function() {
        const preloader = document.querySelector(".preloader");
        const body = document.querySelector("body");
        
        // Fade out preloader
        preloader.style.opacity = "0";
        body.classList.add("loaded");
        
        // Remove preloader from DOM after animation completes
        setTimeout(() => {
            preloader.remove();
        }, 500); // Matches the CSS transition time
    });
    
    // Fallback in case load event doesn't fire
    setTimeout(function() {
        const preloader = document.querySelector(".preloader");
        if(preloader) {
            preloader.style.opacity = "0";
            document.body.classList.add("loaded");
            setTimeout(() => {
                preloader.remove();
            }, 500);
        }
    }, 5000); // Maximum 5 seconds then force hide
</script>

<!-- start webpushr code --> <script>(function(w,d, s, id) {if(typeof(w.webpushr)!=='undefined') return;w.webpushr=w.webpushr||function(){(w.webpushr.q=w.webpushr.q||[]).push(arguments)};var js, fjs = d.getElementsByTagName(s)[0];js = d.createElement(s); js.id = id;js.async=1;js.src = "https://cdn.webpushr.com/app.min.js";fjs.parentNode.appendChild(js);}(window,document, 'script', 'webpushr-jssdk'));webpushr('setup',{'key':'BEujbYiLz1MoB5oTksNudMHYEpla4ufWrFvEwfFW_mMKsw-pfNSqZQCkro5mDR-FsL1eqsc_Q6cj0FgCDefm37E' });</script><!-- end webpushr code -->
</body>

</html>
