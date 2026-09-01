<style>
@scope (.order-success-modal-scope) {

     /* body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f8f0f7;
    } */

   .ticket {
        display: flex;
        flex-direction: row;
        background: white;
        border: 2px solid #f55a8c;
        border-radius: 8px;
        overflow: hidden;
        /*box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);*/
        max-width: 800px;
        width: 100%;
    }

     .left-section,
    .center-section,
    .right-section {
        padding: 20px;
    }

    .left-section {
        background: linear-gradient(135deg, #4a154b, #ec407a);
        color: white;
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .left-section img {
        border-radius: 8px;
        width: 100%;
        max-width: 120px;
        height: auto;
        margin-bottom: 20px;
    }

    .center-section {
        flex: 3;
        text-align: center;
    }

    .center-section h2,
    .center-section h3 {
        margin: 5px 0;
    }

    .center-section .event-location {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10px;
        font-size: 14px;
    }

    .center-section .event-location span {
        margin: 0 10px;
    }

    .right-section {
        flex: 1;
        background: #f7e8ee;
        text-align: center;
        border-left: 2px dashed #f55a8c;
    }

    .right-section img {
        width: 100px;
        height: 100px;
    }

    .date-section {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        font-weight: semi-bold;
        margin-bottom: 0.2px;
    }

    button {
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #ec407a;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #d03468;
    }

    @media (max-width: 768px) {
        .ticket {
            flex-direction: column;
            max-width: 100%;
        }

        .left-section,
        .center-section,
        .right-section {
            padding: 15px;
            text-align: center;
        }

        .date-section {
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .center-section .event-location {
            flex-direction: column;
        }
    }

    @media (max-width: 480px) {
        .center-section h2 {
            font-size: 24px;
        }

        .center-section h3 {
            font-size: 18px;
        }

        .center-section p,
        .right-section .ticket-number {
            font-size: 14px;
        }
    }


    .banner {
        position: relative;
        height: 210px; /* Adjust the height as needed */
        background-color: #ffffff;
        background-image: none;
        background-size: cover;
        background-position: center;
    }

    .overlay {
        position: absolute;
        inset: 0;
        background-color: transparent;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .banner-text {
        color: #dc2626;
        font-size: 2em;
        font-weight: bold;
        padding: 20px;
        border-radius: 8px;
    }

    /* Mobile-friendly styles */
    @media (max-width: 768px) {
        .banner {
            height: 180px; /* Adjust the height for smaller screens */
        }

        .banner-text {
            font-size: 1.5em;
            padding: 10px;
        }
    }

    body.modal-open {
            overflow: hidden;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            right:0;
            bottom:0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
            outline: 0;
        }

        .modal-dialog {
            position: relative;
            margin: auto;
            top: 15%;
            max-width: 725px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
            outline: 0;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 1rem;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header .close {
            padding: 0;
            background-color: transparent;
            border: 0;
            font-size: 1.5rem;
            line-height: 1;
            color: #000;
        }

        .modal-title {
            margin: 0;
            line-height: 1.5;
        }

        .modal-body {
            position: relative;
            overflow-y:auto;
            flex: 1 1 auto;
            padding: 1rem;
            height:300px;
        }

        .modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid #dee2e6;
        }

        .modal-footer > * {
            margin: 0.25rem;
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            color: #212529;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            background-color: transparent;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            color: #fff;
            background-color: #0056b3;
            border-color: #004085;
        }

        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            color: #fff;
            background-color: #5a6268;
            border-color: #545b62;
        }
        .text{
            font-size:18px;

        }

        /* ---- Payment success intro animation (plays before the Thank You hero) ---- */
        #paymentIntroOverlay {
            position: fixed;
            inset: 0;
            z-index: 100000;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.4s ease;
        }

        #paymentIntroOverlay.is-hidden {
            opacity: 0;
            pointer-events: none;
        }

        #paymentIntroLottie {
            width: 420px;
            height: 420px;
            max-width: 85vw;
            max-height: 85vw;
        }

        /* ---- Payment success animation ---- */
        .success-hero {
            position: relative;
            overflow: hidden;
        }

        .success-confetti-img {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
            z-index: 40;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .success-hero-ready .success-confetti-img,
        .success-confetti-img.is-visible {
            opacity: 1;
        }

        .success-check-wrap {
            position: relative;
            z-index: 6;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-top: 44px;
        }

        .success-checkmark {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            display: block;
            transform: scale(0);
        }

        .success-checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke: #16a34a;
            fill: rgba(22, 163, 74, 0.08);
        }

        .success-checkmark__check {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke-width: 4;
            stroke: #16a34a;
        }

        .success-hero-title {
            opacity: 0;
            transform: translateY(8px);
        }

        /* These stay dormant (drawn at rest) until the intro animation finishes and
           JS adds .success-hero-ready - matches the "play intro, then reveal" order. */
        .success-hero-ready .success-checkmark {
            animation: successPopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s forwards;
        }

        .success-hero-ready .success-checkmark__circle {
            animation: successCircleDraw 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.15s forwards;
        }

        .success-hero-ready .success-checkmark__check {
            animation: successCheckDraw 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.7s forwards;
        }

        .success-hero-ready .success-hero-title {
            animation: successTextIn 0.5s ease-out 0.9s forwards;
        }

        @keyframes successPopIn {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        @keyframes successCircleDraw {
            100% { stroke-dashoffset: 0; }
        }

        @keyframes successCheckDraw {
            100% { stroke-dashoffset: 0; }
        }

        @keyframes successTextIn {
            100% { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .success-checkmark,
            .success-checkmark__circle,
            .success-checkmark__check,
            .success-hero-title {
                animation: none !important;
                transform: none !important;
                opacity: 1 !important;
                stroke-dashoffset: 0 !important;
            }

            .success-confetti-img {
                display: none !important;
            }

            #paymentIntroOverlay {
                display: none !important;
            }
        }
        /* ---- Order confirmation summary (fresh, minimal layout) ---- */
        .success-summary-wrap {
            max-width: 600px;
            margin: 0 auto;
            padding-bottom: 40px;
        }

        .success-summary-card {
            background: #ffffff;
            border: 1px solid #eceff3;
            border-radius: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            padding: 32px;
        }

        @media (max-width: 640px) {
            .success-summary-card {
                padding: 22px;
                border-radius: 16px;
            }
        }

        .success-summary-event-name {
            margin: 0 0 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.3rem;
            color: #0f172a;
            text-align: center;
        }

        .success-summary-meta-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            margin-bottom: 24px;
        }

        .success-summary-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            color: #64748b;
            text-align: center;
        }

        .success-summary-meta-item i {
            color: #dc2626;
            font-size: 0.8rem;
        }

        .success-email-notice {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .success-email-notice-icon {
            flex: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #16a34a;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .success-email-notice-title {
            margin: 0 0 4px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            color: #14532d;
        }

        .success-email-notice-text {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 0.82rem;
            color: #166534;
            line-height: 1.5;
        }

        .success-summary-actions {
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .success-primary-btn {
            display: block;
            width: 100%;
            text-align: center;
            border: none;
            border-radius: 12px;
            background: #dc2626;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 14px 20px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease;
        }

        .success-primary-btn:hover {
            background: #b91c1c;
            color: #ffffff;
        }
}
</style>

<div class="order-success-modal-scope" id="orderSuccessModalScope">
<img src="{{ asset('images/confetti-success.svg') }}" class="success-confetti-img" alt="" aria-hidden="true">
<div id="paymentIntroOverlay">
    <div id="paymentIntroLottie"></div>
</div>
<div class="banner success-hero" id="successHero">
    <div class="overlay">
        <div class="success-check-wrap">
            <svg class="success-checkmark" viewBox="0 0 52 52">
                <circle class="success-checkmark__circle" cx="26" cy="26" r="24" fill="none"/>
                <path class="success-checkmark__check" fill="none" d="M14 27l7 7 17-17"/>
            </svg>
            <div class="banner-text success-hero-title">{{ __('Thank You') }} - Tickets Booked Successfully</div>
        </div>
    </div>
</div>
<script>
    (function () {
        var overlay = document.getElementById('paymentIntroOverlay');
        var hero = document.getElementById('successHero');
        var scope = document.getElementById('orderSuccessModalScope');
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function reveal() {
            if (overlay) {
                overlay.classList.add('is-hidden');
                setTimeout(function () {
                    if (overlay && overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 450);
            }
            if (hero) {
                hero.classList.add('success-hero-ready');
            }
            if (scope) {
                scope.classList.add('success-hero-ready');
            }
            var confettiImg = document.querySelector('.success-confetti-img');
            if (confettiImg) {
                confettiImg.classList.add('is-visible');
            }
        }

        if (reduceMotion || !overlay) {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            if (hero) {
                hero.classList.add('success-hero-ready');
            }
            if (scope) {
                scope.classList.add('success-hero-ready');
            }
            var confettiImgReduced = document.querySelector('.success-confetti-img');
            if (confettiImgReduced) {
                confettiImgReduced.classList.add('is-visible');
            }
            return;
        }

        var container = document.getElementById('paymentIntroLottie');
        var settled = false;

        function settle() {
            if (settled) return;
            settled = true;
            reveal();
        }

        function startLottie() {
            if (typeof lottie === 'undefined' || !container) {
                settle();
                return;
            }

            try {
                var anim = lottie.loadAnimation({
                    container: container,
                    renderer: 'svg',
                    loop: false,
                    autoplay: true,
                    path: '{{ asset('animations/lottery-tap.json') }}'
                });
                anim.addEventListener('complete', settle);
                anim.addEventListener('data_failed', settle);
            } catch (e) {
                settle();
            }

            // Safety net in case the animation never fires 'complete'.
            setTimeout(settle, 5000);
        }

        if (typeof lottie !== 'undefined') {
            startLottie();
        } else {
            var script = document.createElement('script');
            script.src = '{{ asset('js/lottie.min.js') }}';
            script.onload = startLottie;
            script.onerror = settle;
            document.head.appendChild(script);
        }
    })();
</script>
<div>
    <div class="mt-5 3xl:mx-52 2xl:mx-28 1xl:mx-28 xl:mx-36 xlg:mx-32 lg:mx-36 xxmd:mx-24 xmd:mx-32 md:mx-28 sm:mx-20 msm:mx-16 xsm:mx-10 xxsm:mx-5 z-10 relative">
        <div class="success-summary-wrap">
            <div class="success-summary-card">
                <p class="success-summary-event-name">{{ $order->event->name }}</p>
                <div class="success-summary-meta-list">
                    <span class="success-summary-meta-item">
                        <i class="fa fa-calendar" aria-hidden="true"></i>
                        {{ Carbon\Carbon::parse($order->event->start_time)->format('M j, Y') }} &middot; {{ Carbon\Carbon::parse($order->event->start_time)->format('g:i A') }}
                    </span>
                    <span class="success-summary-meta-item">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                        {{ $order->event->type == 'online' ? __('Online Event') : $order->event->address }}
                    </span>
                </div>

                <div class="success-email-notice">
                    <span class="success-email-notice-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                    <div>
                        <p class="success-email-notice-title">{{ __('Your tickets are on the way') }}</p>
                        <p class="success-email-notice-text">
                            @if(!empty($userDetail->email))
                                {{ __('We\'ve emailed your tickets to') }} <strong>{{ $userDetail->email }}</strong>. {{ __('Check your inbox (and spam folder) shortly.') }}
                            @else
                                {{ __('Your tickets have been emailed to you and will arrive shortly.') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="success-summary-actions">
                    <button type="button" class="success-primary-btn" onclick="closeTicketFlowModal()">{{ __('Done') }}</button>
                </div>
            </div>
        </div>
    </div>
{{-- Ticket layout removed - tickets are generated server-side and sent via email immediately --}}
</div>
</div>
