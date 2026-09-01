{{-- Donation Modal --}}
@php
    $stripePublicKey = \App\Models\PaymentSetting::first()->stripePublicKey ?? '';
    $isDonationUserAuth = Auth::guard('appuser')->check();
@endphp

<style>
/* Donation modal responsive styles using admin theme color */
#donation-modal-overlay { padding: 16px; box-sizing: border-box; }
#donation-modal-box { width: 100%; max-width: 480px; }
.donation-preset-btn {
    width: 100%; padding: 12px 8px; font-size: 1rem; font-weight: 600;
    border: 2px solid var(--primary_color); color: var(--primary_color);
    background: #fff; border-radius: 8px; cursor: pointer;
    transition: background 0.2s, color 0.2s;
    font-family: 'Poppins', sans-serif;
}
.donation-preset-btn:hover, .donation-preset-btn.selected {
    background: var(--primary_color); color: #fff;
}
.donation-main-btn {
    width: 100%; padding: 13px; font-size: 1rem; font-weight: 600;
    background: var(--primary_color); color: #fff; border: none;
    border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif;
    transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.donation-main-btn:hover { opacity: 0.88; }
.donation-main-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.donation-custom-input {
    width: 100%; box-sizing: border-box; padding: 10px 12px 10px 28px;
    border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 1rem;
    font-family: 'Poppins', sans-serif; outline: none; transition: border-color 0.2s;
}
.donation-custom-input:focus { border-color: var(--primary_color); box-shadow: 0 0 0 3px var(--light_primary_color, rgba(0,0,0,0.08)); }
.donation-icon-primary { fill: var(--primary_color); }
.donation-text-primary { color: var(--primary_color); }
@media (max-width: 480px) {
    #donation-modal-box { padding: 20px 16px !important; }
    .donation-preset-grid { gap: 8px !important; }
    .donation-preset-btn { font-size: 0.9rem; padding: 10px 4px; }
    #donation-modal-box h2 { font-size: 1.2rem !important; }
}
</style>

<!-- Donation Modal Overlay -->
<div id="donation-modal-overlay"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:99999; align-items:center; justify-content:center;"
    onclick="closeDonationModalOnOverlay(event)">

    <div id="donation-modal-box" style="background:#fff; border-radius:14px; box-shadow:0 25px 60px rgba(0,0,0,0.25); margin:0 auto; padding:28px 24px; position:relative; max-height:92vh; overflow-y:auto;">

        <!-- Accent top bar using theme color -->
        <div style="position:absolute; top:0; left:0; right:0; height:4px; background:var(--primary_color); border-radius:14px 14px 0 0;"></div>

        <!-- Close button -->
        <button onclick="closeDonationModal()" type="button"
            style="position:absolute; top:14px; right:16px; font-size:22px; font-weight:700; line-height:1; color:#9ca3af; background:none; border:none; cursor:pointer;"
            onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">&times;</button>

        <!-- Header -->
        <div style="text-align:center; margin-bottom:20px; margin-top:6px;">
            <div style="display:flex; justify-content:center; margin-bottom:10px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:44px;height:44px;" viewBox="0 0 20 20">
                    <path class="donation-icon-primary" fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h2 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:1.4rem; color:#1f2937; margin:0 0 4px;">{{ __('Support This Event') }}</h2>
            <p style="font-family:'Poppins',sans-serif; font-size:0.875rem; color:#6b7280; margin:0;">{{ __('Your donation helps make this event possible.') }}</p>
        </div>

        <!-- Step 0: Guest email OTP verification (shown only for guests) -->
        <div id="donation-step-0" style="display:none;">
            <!-- 0a: Email input -->
            <div id="donation-otp-email-section">
                <p style="font-family:'Poppins',sans-serif; font-size:0.875rem; color:#6b7280; margin:0 0 16px; text-align:center;">
                    {{ __('Please verify your email to proceed with the donation.') }}
                </p>
                <label style="font-family:'Poppins',sans-serif; font-size:0.85rem; color:#4b5563; display:block; margin-bottom:6px; font-weight:500;">{{ __('Your Email Address') }}</label>
                <input type="email" id="donation-guest-email"
                    class="donation-custom-input" style="padding-left:12px;"
                    placeholder="you@example.com">
                <div id="donation-email-error" style="display:none; color:#ef4444; font-size:0.82rem; font-family:'Poppins',sans-serif; margin-top:6px;"></div>
                <button type="button" id="donation-send-otp-btn" onclick="donationSendOtp()" class="donation-main-btn" style="margin-top:14px;">
                    <span id="donation-send-otp-text">{{ __('Send OTP') }}</span>
                    <span id="donation-send-otp-loader" style="display:none;">
                        <svg style="width:20px;height:20px;animation:donation-spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- 0b: OTP input (shown after OTP is sent) -->
            <div id="donation-otp-verify-section" style="display:none;">
                <p style="font-family:'Poppins',sans-serif; font-size:0.875rem; color:#374151; margin:0 0 4px; font-weight:600;">{{ __('Enter OTP') }}</p>
                <p style="font-family:'Poppins',sans-serif; font-size:0.8rem; color:#6b7280; margin:0 0 14px;" id="donation-otp-sent-to"></p>
                <div style="display:flex; justify-content:center; gap:10px; margin-bottom:12px;">
                    <input type="text" maxlength="1" id="donation-otp1" class="donation-custom-input" style="width:48px;height:48px;text-align:center;padding:0;font-size:1.2rem;font-weight:700;">
                    <input type="text" maxlength="1" id="donation-otp2" class="donation-custom-input" style="width:48px;height:48px;text-align:center;padding:0;font-size:1.2rem;font-weight:700;">
                    <input type="text" maxlength="1" id="donation-otp3" class="donation-custom-input" style="width:48px;height:48px;text-align:center;padding:0;font-size:1.2rem;font-weight:700;">
                    <input type="text" maxlength="1" id="donation-otp4" class="donation-custom-input" style="width:48px;height:48px;text-align:center;padding:0;font-size:1.2rem;font-weight:700;">
                </div>
                <div id="donation-otp-error" style="display:none; color:#ef4444; font-size:0.82rem; font-family:'Poppins',sans-serif; text-align:center; margin-bottom:10px;"></div>
                <button type="button" onclick="donationVerifyOtp()" class="donation-main-btn">
                    <span id="donation-verify-otp-text">{{ __('Verify OTP') }}</span>
                    <span id="donation-verify-otp-loader" style="display:none;">
                        <svg style="width:20px;height:20px;animation:donation-spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </span>
                </button>
                <button type="button" onclick="donationResendOtp()" style="width:100%;margin-top:8px;background:none;border:none;color:#6b7280;font-family:'Poppins',sans-serif;font-size:0.85rem;cursor:pointer;text-decoration:underline;">{{ __('Resend OTP') }}</button>
            </div>
        </div>

        <!-- Step 1: Amount selection -->
        <div id="donation-step-1">
            <p style="font-family:'Poppins',sans-serif; font-weight:600; font-size:0.9rem; color:#374151; margin:0 0 10px; text-transform:uppercase; letter-spacing:0.05em;">{{ __('Choose an amount') }}</p>

            <div class="donation-preset-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px;">
                @foreach ([21, 51, 101] as $preset)
                    <button type="button" class="donation-preset-btn" data-amount="{{ $preset }}"
                        onclick="selectDonationAmount({{ $preset }}, this)">
                        ${{ $preset }}
                    </button>
                @endforeach
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-family:'Poppins',sans-serif; font-size:0.85rem; color:#4b5563; display:block; margin-bottom:6px; font-weight:500;">{{ __('Custom Amount ($)') }}</label>
                <div style="position:relative;">
                    <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#6b7280; font-weight:600; font-size:0.95rem;">$</span>
                    <input type="number" id="donation-custom-amount" min="1" step="0.01"
                        class="donation-custom-input"
                        placeholder="0.00"
                        oninput="handleCustomAmountInput(this)">
                </div>
            </div>

            <div id="donation-amount-error" style="display:none; color:#ef4444; font-size:0.85rem; font-family:'Poppins',sans-serif; margin-bottom:10px;">
                {{ __('Please enter or select a valid amount (minimum $1).') }}
            </div>

            <button type="button" id="donation-proceed-btn" onclick="proceedToDonationPayment()" class="donation-main-btn">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                </svg>
                <span>{{ __('Donate') }}</span> <span id="donation-amount-preview"></span>
            </button>
        </div>

        <!-- Step 2: Card payment (Stripe Elements) -->
        <div id="donation-step-2" style="display:none;">
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:16px; cursor:pointer;" onclick="backToDonationStep1()">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;color:#6b7280;" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                </svg>
                <span style="font-family:'Poppins',sans-serif; font-size:0.875rem; color:#6b7280;">{{ __('Back') }}</span>
            </div>

            <div style="background:var(--light_primary_color, #f9fafb); border-radius:8px; padding:12px 14px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-family:'Poppins',sans-serif; font-size:0.875rem; color:#374151;">{{ __('Donating') }}</span>
                <span id="donation-selected-amount-display" class="donation-text-primary" style="font-weight:700; font-size:1.1rem; font-family:'Poppins',sans-serif;"></span>
            </div>

            <label style="font-family:'Poppins',sans-serif; font-size:0.85rem; color:#4b5563; display:block; margin-bottom:8px; font-weight:500;">{{ __('Card Details') }}</label>
            <div id="donation-card-element" style="border:1.5px solid #d1d5db; border-radius:8px; padding:12px; margin-bottom:8px; background:#f9fafb;"></div>
            <div id="donation-card-errors" style="color:#ef4444; font-size:0.85rem; font-family:'Poppins',sans-serif; margin-bottom:10px; min-height:20px;"></div>

            <button type="button" id="donation-pay-btn" onclick="submitDonationPayment()" class="donation-main-btn">
                <span id="donation-pay-text">{{ __('Confirm & Pay') }}</span>
                <span id="donation-pay-loader" style="display:none;">
                    <svg style="width:20px;height:20px;animation:donation-spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </span>
            </button>

            <!-- Secure payment note -->
            <p style="text-align:center; font-size:0.75rem; color:#9ca3af; font-family:'Poppins',sans-serif; margin-top:10px;">
                🔒 {{ __('Secured by Stripe') }}
            </p>
        </div>

        <!-- Step 3: Success -->
        <div id="donation-step-3" style="display:none; text-align:center;">
            <div style="display:flex; justify-content:center; margin-bottom:16px;">
                <div style="width:72px; height:72px; border-radius:50%; background:var(--light_primary_color, #f0fdf4); display:flex; align-items:center; justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:40px;height:40px;" viewBox="0 0 20 20">
                        <path class="donation-icon-primary" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <h3 style="font-family:'Poppins',sans-serif; font-weight:700; font-size:1.3rem; color:#1f2937; margin:0 0 8px;">{{ __('Thank You!') }}</h3>
            <p style="font-family:'Poppins',sans-serif; color:#6b7280; font-size:0.9rem; margin:0 0 20px;" id="donation-success-message"></p>
            <button type="button" onclick="closeDonationModal()" class="donation-main-btn" style="padding:11px 32px; width:auto; display:inline-flex;">
                {{ __('Close') }}
            </button>
        </div>

    </div>
</div>

<style>
@keyframes donation-spin { to { transform: rotate(360deg); } }
</style>

<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    // Move modal to <body> to escape stacking context
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('donation-modal-overlay');
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }

        // OTP input auto-advance for donation modal
        ['donation-otp1','donation-otp2','donation-otp3','donation-otp4'].forEach(function(id, idx, arr) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 1);
                if (this.value && idx < arr.length - 1) {
                    document.getElementById(arr[idx + 1]).focus();
                }
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    document.getElementById(arr[idx - 1]).focus();
                }
            });
        });
    });

    const STRIPE_PUBLIC_KEY = '{{ $stripePublicKey }}';
    const EVENT_ID = {{ $event->id ?? $data->id ?? 0 }};
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const IS_AUTH = {{ $isDonationUserAuth ? 'true' : 'false' }};

    let stripe = null;
    let elements = null;
    let cardElement = null;
    let selectedAmount = null;
    let currentPaymentIntentId = null;
    let currentClientSecret = null;
    let donationGuestEmail = null; // verified guest email

    function initStripe() {
        if (!stripe && STRIPE_PUBLIC_KEY) {
            stripe = Stripe(STRIPE_PUBLIC_KEY);
        }
    }

    window.openDonationModal = function () {
        var overlay = document.getElementById('donation-modal-overlay');
        if (!overlay) return;
        overlay.style.display = 'flex';
        // App user: go straight to amount; guest: show OTP step first
        showDonationStep(IS_AUTH ? 1 : 0);
        initStripe();
    };

    window.closeDonationModal = function () {
        document.getElementById('donation-modal-overlay').style.display = 'none';
        resetDonationModal();
    };

    window.closeDonationModalOnOverlay = function (e) {
        if (e.target === document.getElementById('donation-modal-overlay')) {
            closeDonationModal();
        }
    };

    // ── Guest OTP functions ──────────────────────────────────────────────────

    window.donationSendOtp = function () {
        var email = (document.getElementById('donation-guest-email').value || '').trim();
        var errEl = document.getElementById('donation-email-error');
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errEl.textContent = '{{ __("Please enter a valid email address.") }}';
            errEl.style.display = 'block';
            return;
        }
        errEl.style.display = 'none';

        document.getElementById('donation-send-otp-text').style.display = 'none';
        document.getElementById('donation-send-otp-loader').style.display = 'inline-flex';
        document.getElementById('donation-send-otp-btn').disabled = true;

        fetch('{{ url("user/donation/verify-guest-email") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ email: email }),
        })
        .then(r => {
            if (!r.ok) {
                return r.json().then(json => {
                    throw new Error(json.error || `HTTP ${r.status}`);
                }).catch(err => {
                    if (err.message) throw err;
                    throw new Error(`HTTP ${r.status}: Server Error`);
                });
            }
            return r.json();
        })
        .then(data => {
            document.getElementById('donation-send-otp-text').style.display = '';
            document.getElementById('donation-send-otp-loader').style.display = 'none';
            document.getElementById('donation-send-otp-btn').disabled = false;

            if (data.success) {
                document.getElementById('donation-otp-sent-to').textContent =
                    '{{ __("OTP sent to") }} ' + email;
                document.getElementById('donation-otp-email-section').style.display = 'none';
                document.getElementById('donation-otp-verify-section').style.display = 'block';
                document.getElementById('donation-otp1').focus();
            } else {
                errEl.textContent = data.error ?? '{{ __("Failed to send OTP.") }}';
                errEl.style.display = 'block';
            }
        })
        .catch((error) => {
            console.error('Donation OTP Error:', error);
            document.getElementById('donation-send-otp-text').style.display = '';
            document.getElementById('donation-send-otp-loader').style.display = 'none';
            document.getElementById('donation-send-otp-btn').disabled = false;
            errEl.textContent = error.message || '{{ __("An error occurred. Please try again.") }}';
            errEl.style.display = 'block';
        });
    };

    window.donationResendOtp = function () {
        document.getElementById('donation-otp-verify-section').style.display = 'none';
        document.getElementById('donation-otp-email-section').style.display = 'block';
        ['donation-otp1','donation-otp2','donation-otp3','donation-otp4'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('donation-otp-error').style.display = 'none';
    };

    window.donationVerifyOtp = function () {
        var otp = ['donation-otp1','donation-otp2','donation-otp3','donation-otp4']
            .map(function(id) { return (document.getElementById(id).value || ''); })
            .join('');
        var email = (document.getElementById('donation-guest-email').value || '').trim();
        var errEl = document.getElementById('donation-otp-error');

        if (otp.length < 4) {
            errEl.textContent = '{{ __("Please enter the 4-digit OTP.") }}';
            errEl.style.display = 'block';
            return;
        }
        errEl.style.display = 'none';

        document.getElementById('donation-verify-otp-text').style.display = 'none';
        document.getElementById('donation-verify-otp-loader').style.display = 'inline-flex';

        fetch('{{ url("user/donation/verify-guest-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ email: email, otp: otp }),
        })
        .then(r => {
            if (!r.ok) {
                return r.json().then(json => {
                    throw new Error(json.error || `HTTP ${r.status}`);
                }).catch(err => {
                    if (err.message) throw err;
                    throw new Error(`HTTP ${r.status}: Server Error`);
                });
            }
            return r.json();
        })
        .then(data => {
            document.getElementById('donation-verify-otp-text').style.display = '';
            document.getElementById('donation-verify-otp-loader').style.display = 'none';

            if (data.success) {
                donationGuestEmail = email; // store for payment requests
                showDonationStep(1);        // proceed to amount selection
            } else {
                errEl.textContent = data.error ?? '{{ __("Invalid OTP.") }}';
                errEl.style.display = 'block';
            }
        })
        .catch((error) => {
            console.error('Donation OTP Verification Error:', error);
            document.getElementById('donation-verify-otp-text').style.display = '';
            document.getElementById('donation-verify-otp-loader').style.display = 'none';
            errEl.textContent = error.message || '{{ __("An error occurred. Please try again.") }}';
            errEl.style.display = 'block';
        });
    };

    // ── Donation flow ────────────────────────────────────────────────────────

    window.selectDonationAmount = function (amount, btn) {
        selectedAmount = amount;
        document.getElementById('donation-custom-amount').value = '';
        document.querySelectorAll('.donation-preset-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        updateAmountPreview();
        document.getElementById('donation-amount-error').style.display = 'none';
    };

    window.handleCustomAmountInput = function (input) {
        selectedAmount = parseFloat(input.value) || null;
        document.querySelectorAll('.donation-preset-btn').forEach(b => b.classList.remove('selected'));
        updateAmountPreview();
    };

    function updateAmountPreview() {
        const preview = document.getElementById('donation-amount-preview');
        if (selectedAmount && selectedAmount > 0) {
            preview.textContent = '– $' + parseFloat(selectedAmount).toFixed(2);
        } else {
            preview.textContent = '';
        }
    }

    window.proceedToDonationPayment = function () {
        if (!selectedAmount || selectedAmount < 1) {
            document.getElementById('donation-amount-error').style.display = 'block';
            return;
        }
        document.getElementById('donation-amount-error').style.display = 'none';

        const btn = document.getElementById('donation-proceed-btn');
        btn.disabled = true;
        btn.querySelector('span:nth-child(2)').textContent = '{{ __("Loading...") }}';
        document.getElementById('donation-amount-preview').textContent = '';

        fetch('{{ url("user/donation/create-payment-intent") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ event_id: EVENT_ID, amount: selectedAmount, guest_email: donationGuestEmail }),
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.querySelector('span:nth-child(2)').textContent = '{{ __("Donate") }}';
            updateAmountPreview();

            if (data.error) { alert(data.error); return; }

            currentClientSecret = data.client_secret;
            currentPaymentIntentId = data.payment_intent_id;
            document.getElementById('donation-selected-amount-display').textContent = '$' + parseFloat(selectedAmount).toFixed(2);
            showDonationStep(2);
            mountCardElement();
        })
        .catch(() => {
            btn.disabled = false;
            btn.querySelector('span:nth-child(2)').textContent = '{{ __("Donate") }}';
            alert('{{ __("An error occurred. Please try again.") }}');
        });
    };

    function mountCardElement() {
        if (!stripe) return;
        elements = stripe.elements();
        cardElement = elements.create('card', {
            style: {
                base: { fontSize: '16px', color: '#32325d', fontFamily: 'Poppins, sans-serif', '::placeholder': { color: '#aab7c4' } },
                invalid: { color: '#fa755a' },
            },
        });
        const container = document.getElementById('donation-card-element');
        container.innerHTML = '';
        cardElement.mount('#donation-card-element');
        cardElement.on('change', function (event) {
            document.getElementById('donation-card-errors').textContent = event.error ? event.error.message : '';
        });
    }

    window.submitDonationPayment = async function () {
        if (!stripe || !cardElement || !currentClientSecret) return;

        const payBtn = document.getElementById('donation-pay-btn');
        document.getElementById('donation-pay-text').textContent = '';
        document.getElementById('donation-pay-loader').style.display = 'inline-flex';
        payBtn.disabled = true;

        try {
            const { error, paymentIntent } = await stripe.confirmCardPayment(currentClientSecret, {
                payment_method: { card: cardElement },
            });

            if (error) {
                document.getElementById('donation-card-errors').textContent = error.message;
                document.getElementById('donation-pay-text').textContent = '{{ __("Confirm & Pay") }}';
                document.getElementById('donation-pay-loader').style.display = 'none';
                payBtn.disabled = false;
                return;
            }

            const res = await fetch('{{ url("user/donation/process") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ event_id: EVENT_ID, amount: selectedAmount, payment_intent_id: paymentIntent.id, guest_email: donationGuestEmail }),
            });
            const result = await res.json();

            document.getElementById('donation-pay-text').textContent = '{{ __("Confirm & Pay") }}';
            document.getElementById('donation-pay-loader').style.display = 'none';
            payBtn.disabled = false;

            if (result.success) {
                document.getElementById('donation-success-message').textContent =
                    '{{ __("Your donation of") }} $' + parseFloat(selectedAmount).toFixed(2) + ' {{ __("has been received. Reference:") }} ' + (result.donation_id ?? '');
                showDonationStep(3);
            } else {
                alert(result.error ?? '{{ __("Payment processed but record failed. Contact support.") }}');
            }
        } catch (err) {
            document.getElementById('donation-card-errors').textContent = '{{ __("An unexpected error occurred.") }}';
            document.getElementById('donation-pay-text').textContent = '{{ __("Confirm & Pay") }}';
            document.getElementById('donation-pay-loader').style.display = 'none';
            payBtn.disabled = false;
        }
    };

    window.backToDonationStep1 = function () {
        if (cardElement) { cardElement.unmount(); cardElement = null; }
        showDonationStep(1); // back to amount (step index 1)
    };

    function showDonationStep(step) {
        // step: 0=guest OTP, 1=amount, 2=payment, 3=success
        ['donation-step-0', 'donation-step-1', 'donation-step-2', 'donation-step-3'].forEach((id, i) => {
            const el = document.getElementById(id);
            if (el) el.style.display = (i === step) ? 'block' : 'none';
        });
    }

    function resetDonationModal() {
        selectedAmount = null;
        currentPaymentIntentId = null;
        currentClientSecret = null;
        // Reset guest OTP state (but keep donationGuestEmail if already verified)
        document.getElementById('donation-otp-email-section').style.display = 'block';
        document.getElementById('donation-otp-verify-section').style.display = 'none';
        document.getElementById('donation-email-error').style.display = 'none';
        document.getElementById('donation-otp-error').style.display = 'none';
        ['donation-otp1','donation-otp2','donation-otp3','donation-otp4'].forEach(function(id) {
            var el = document.getElementById(id); if (el) el.value = '';
        });
        document.querySelectorAll('.donation-preset-btn').forEach(b => b.classList.remove('selected'));
        const customInput = document.getElementById('donation-custom-amount');
        if (customInput) customInput.value = '';
        const preview = document.getElementById('donation-amount-preview');
        if (preview) preview.textContent = '';
        document.getElementById('donation-amount-error').style.display = 'none';
        const cardErrors = document.getElementById('donation-card-errors');
        if (cardErrors) cardErrors.textContent = '';
        if (cardElement) { cardElement.unmount(); cardElement = null; }
        // Reopen at correct step
        showDonationStep((IS_AUTH || donationGuestEmail) ? 1 : 0);
    }
})();
</script>
