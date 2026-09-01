@extends('frontend.master', ['activePage' => 'checkout'])
@section('title', __('Checkout'))
@section('content')
    {{-- content --}}
    <style>
               /* Custom Modal Styles */
body.modal-open {
            overflow: hidden !important;
            height: 100vh;
            touch-action: none;
            overscroll-behavior: none;
        }

        /* Checkout mobile: block unwanted left/right page sliding */
        @media (max-width: 768px) {
            html,
            body {
                max-width: 100%;
                overflow-x: hidden !important;
                overscroll-behavior-x: none;
            }

            body.loaded {
                overflow-x: hidden !important;
                overflow-y: auto;
            }

            .site-wrapper {
                max-width: 100%;
                overflow-x: hidden;
            }

            body.hide-checkout-header-footer {
                overflow-y: hidden !important;
            }
        }

        /* Prevent mobile horizontal swipe / page */

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
            outline: 0;
        }
        .hideclass {
            padding-left: 10px;
            padding-bottom: 10px;
        }

.modal-dialog {
            position: relative;
            margin: auto;
            top: 15%;
            max-width: 725px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
        }
/* 1. Bulletproof Centering for the Modal */
#couponModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 1050;
    background-color: rgba(0, 0, 0, 0.5);
}

#couponModal .modal-dialog {
    /* Bulletproof centering: override the earlier generic .modal-dialog rule */
    
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;

    width: calc(100% - 32px) !important;
    max-width: 400px !important;
    margin: 0 !important;

    border-radius: 12px;
    overflow: hidden;
    background-color: #fff;
    z-index: 1051;
}

@media (max-width: 480px) {
    #couponModal .modal-dialog {
        width: calc(100% - 16px) !important;
        max-width: 400px !important;
    }
}

#couponModal .modal-header {
    background-color: var(--primary_color);
    color: white;
    border-bottom: none;
    padding: 1rem 1.5rem;
    text-align: center;
}

#couponModal .modal-body {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

/* 2. 4-Column Grid container for coupons */
#couponModal .coupon-container {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    align-items: stretch;
    justify-items: stretch;
}

/* 3. Coupon Button Styles */
#couponModal .coupon-select-item {
    background-color: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    text-align: center;
    min-width: 0;
}

#couponModal .coupon-select-item:hover {
    border-color: var(--primary_color);
    background-color: var(--light_primary_color);
}

#couponModal .coupon-code-text {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 1rem;
    color: #374151;
    word-break: break-word;
    transition: color 0.2s;
}

/* 4. Mobile Adjustments (No vertical scrolling; keep 4-grid) */
@media (max-width: 480px) {
    #couponModal .modal-dialog {
        top: 50% !important;
        width: calc(100% - 16px) !important;
        max-width: 400px !important;
    }

    #couponModal .modal-body {
        /* prevent slide/scroll up/down on mobile */
        max-height: none !important;
        overflow-y: visible !important;
        padding: 1rem !important;
    }

    #couponModal {
        overflow: hidden !important;
    }

    #couponModal .coupon-container {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    #couponModal .coupon-code-text {
        font-size: 0.95rem;
    }
}



        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.6rem;
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
            flex: 1 1 auto;
            padding: 1rem;
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

        .guestcheckoutbutton:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }


        .guestemailverifybuttondisabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        /* Custom Modal Styles for otp*/
body.modal-openotp {
            overflow: hidden !important;
            height: 100vh;
            touch-action: none;
        }

        .modalotp {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
            outline: 0;
        }

        .modal-dialogotp {
            position: relative;
            margin: auto;
            top: 15%;
            max-width: 400px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
        }

        .modal-contentotp {
            position: relative;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
            outline: 0;
        }

        .modal-headerotp,
        .modal-bodyotp,
        .modal-footerotp {
            padding: 1rem;
        }

        .modal-headerotp {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-headerotp .closeotp {
            padding: 0;
            background-color: transparent;
            border: 0;
            font-size: 1.5rem;
            line-height: 1;
            color: #000;
        }

        .modal-titleotp {
            margin: 0;
            line-height: 1.5;
        }

        .modal-bodyotp {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }

        .modal-footerotp {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid #dee2e6;
        }

        .modal-footerotp > * {
            margin: 0.25rem;
        }

        /* Responsive: Event Title (replaces inline: font-size: 1.475rem !important;) */
        .checkout-event-title{
            font-size: 1.115rem; /* desktop */
            line-height: 1.25;
        }
        
        @media (max-width: 1024px){
            .checkout-event-title{ font-size: 1rem; }
        }
        @media (max-width: 640px){
            .checkout-event-title{ font-size: 0.9rem; }
        }
        @media (max-width: 360px){
            .checkout-event-title{ font-size: 0.80rem; }
        }

        .otp-input-container {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            margin: 0 10px;
            font-size: 24px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .otp-input:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn-disabled {
  pointer-events: none;
  opacity: 0.6;
  cursor: not-allowed;
}

        .donation-box-responsive {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            margin-bottom: 10px;
        }

        @media (max-width: 640px) {
            .donation-box-responsive {
                max-width: 100%;
            }
        }
    .t-header{
        background-color: var(--primary_color) !important;
        text-color: #fff !important;
    }

    .checkout-header-inner {
        width: 100%;
        max-width: 1100px;
    }

    .checkout-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 28px 16px 80px;
        position: relative;
        z-index: 10;
    }

    .checkout-hold-timer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid #fde68a;
        border-top: 4px solid #f59e0b;
        border-radius: 12px;
        background: #fffbeb;
        color: #92400e;
        font-family: 'Poppins', sans-serif;
    }

    .checkout-hold-timer span {
        font-size: 0.88rem;
        font-weight: 700;
    }

    .checkout-hold-timer strong {
        min-width: 58px;
        color: #111827;
        font-size: 1.05rem;
        font-variant-numeric: tabular-nums;
        font-weight: 900;
        text-align: right;
    }

    .checkout-hold-timer.is-expired {
        border-color: #fecaca;
        border-left-color: #dc2626;
        background: #fef2f2;
        color: #991b1b;
    }

    .checkout-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 430px;
        gap: 28px;
        align-items: start;
    }

    .checkout-main,
    .checkout-side {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 0;
    }

    .checkout-side {
        position: sticky;
        top: 24px;
    }

    .checkout-panel {
        background: #ffffff;
        border: 1px solid #eceff3;
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        padding: 14px;
        overflow: hidden;
    }

    .checkout-panel-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f3f6;
    }

    .checkout-panel-title {
        margin: 0;
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 1.08rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .checkout-step-badge {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: var(--primary_color);
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 800;
        box-shadow: 0 3px 1px var(--middle_light_primary_color)
    }

    .checkout-ticket-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .checkout-ticket-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px;
        border: 1px solid #edf0f4;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .checkout-ticket-row:hover {
        border-color: var(--middle_light_primary_color);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
    }

    .checkout-seat-selection-card {
        padding: 18px;
        border: 1px solid var(--middle_light_primary_color);
        border-top: 4px solid var(--primary_color);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .checkout-seat-selection-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .checkout-seat-selection-title {
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 850;
        line-height: 1.25;
        margin: 0;
    }

    .checkout-seat-selection-count {
        padding: 4px 9px;
        border-radius: 999px;
        border: 1px solid var(--primary_color);
        background: #ffffff;
        color: #111827;
        font-size: 0.74rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .checkout-seat-section-row {
        display: grid;
        grid-template-columns: minmax(120px, 0.36fr) minmax(0, 1fr);
        align-items: start;
        gap: 12px;
        padding: 11px 0;
        border-top: 1px solid var(--middle_light_primary_color);
    }

    .checkout-seat-section-row:first-of-type {
        border-top: 0;
        padding-top: 0;
    }

    .checkout-seat-section-name {
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 0.86rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .checkout-seat-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .checkout-seat-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        min-height: 30px;
        padding: 5px 10px;
        border: 1px solid var(--primary_color);
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 0.84rem;
        font-weight: 900;
        line-height: 1;
    }

    .checkout-ticket-info {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .checkout-ticket-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--primary_color);
        transform:rotate(-35deg); 
    }
    .checkout-ticket-icon .fa-ticket,
    .checkout-trust-icon .fa-ticket {
        font-size: 1.6rem;
        line-height: 1;
    }

    .checkout-ticket-name {
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.35;
        margin: 0;
    }

    .checkout-ticket-pill {
        display: inline-flex;
        align-items: center;
        margin-top: 6px;
        padding: 4px 9px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 700;
    }

    .checkout-ticket-price {
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 700;
        margin-top: 6px;
    }

    .checkout-ticket-price strong,
    .checkout-ticket-price span {
        color: #111827;
    }

    .checkout-ticket-price .checkout-free {
        color: var(--primary_color);
        font-weight: 900;
    }

    .checkout-qty-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }

    .checkout-qty-label {
        color: #64748b;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .checkout-qty-control {
        display: inline-grid;
        grid-template-columns: 40px 44px 40px;
        height: 40px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .checkout-qty-control .qtybtn {
        width: 40px !important;
        height: 40px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        border: 0 !important;
        background: transparent;
        color: #334155;
        font-size: 1.1rem;
        font-weight: 900;
        cursor: pointer;
    }

    .checkout-qty-control .qtybtn:hover {
        background: var(--light_primary_color);
        color: var(--primary_color);
    }

    .checkout-qty-control.is-locked {
        border-color: #d1d5db;
        background: #f1f5f9;
    }

    .checkout-qty-control.is-locked .qtybtn,
    .checkout-qty-control .qtybtn:disabled {
        color: #94a3b8 !important;
        cursor: not-allowed;
        pointer-events: none;
    }

    .checkout-qty-control.is-locked .qtybtn:hover {
        background: transparent;
        color: #94a3b8 !important;
    }

    .checkout-qty-control.is-locked input {
        background: #f8fafc !important;
        color: #64748b !important;
    }

    .checkout-qty-control input {
        width: 44px !important;
        height: 40px !important;
        border: 0 !important;
        background: #ffffff !important;
        color: #111827 !important;
        text-align: center;
        font-size: 0.95rem;
        font-weight: 900;
        outline: none;
    }

    .checkout-note {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
        padding: 13px 14px;
        border: 1px solid #bbf7d0;
        border-radius: 14px;
        background: #f0fdf4;
        color: #166534;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .checkout-refund-note {
        display: flex;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--middle_light_primary_color);
        border-radius: 14px;
        background: var(--light_primary_color);
        margin-bottom: 16px;
    }

    .checkout-refund-content {
        min-width: 0;
    }

    .checkout-alert-mark {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: var(--light_primary_color);
        color: var(--primary_color);
        font-size: 0.85rem;
        font-weight: 900;
    }

    .checkout-refund-note h4 {
        margin: 0;
        color: var(--primary_color);
        font-family: 'Poppins', sans-serif;
        font-size: 0.94rem;
        font-weight: 800;
    }

    .checkout-refund-note p {
        margin: 4px 0 0;
        color: var(--primary_color);
        font-size: 0.8rem;
        line-height: 1.55;
    }

    .checkout-terms-agree {
        display: inline-flex;
        align-items: center;
        column-gap: 12px;
        cursor: pointer;
    }

    .checkout-terms-agree input {
        flex: 0 0 auto;
        margin: 0;
        accent-color: var(--primary_color);
    }

    .checkout-terms-agree span {
        line-height: 1.45;
    }

    .checkout-shell input[type="checkbox"],
    .checkout-shell input[type="radio"] {
        accent-color: var(--primary_color);
    }

    .checkout-shell .text-danger {
        color: var(--primary_color) !important;
    }

    #stripe_message,
    .checkout-shell .bg-danger {
        background-color: var(--primary_color) !important;
    }

    .checkout-auth-actions,
    .checkout-guest-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 20px;
        border-top: 1px solid #f1f3f6;
    }

    .checkout-guest-actions.checkout-verified-actions {
        display: flex !important;
        justify-content: center !important;
    }

    .checkout-guest-actions.checkout-verified-actions .guestcheckoutbutton {
        width: 300px !important;
    }

    .checkout-otp-actions {
        gap: 16px;
    }

    .checkout-mobile-auth-actions {
        display: none;
    }

    .checkout-primary-btn,
    .checkout-secondary-btn,
    .checkout-danger-btn {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        border: 1px solid transparent;
        padding: 12px 16px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.2;
        transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    }

    .checkout-primary-btn {
        background: var(--primary_color);
        color: #ffffff;
    }

    .checkout-primary-btn:hover {
        background: var(--primary_color);
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .checkout-guest-continue-btn {
        background: #16a34a;
    }

    .checkout-guest-continue-btn:hover {
        background: #15803d;
        opacity: 1;
    }

    .checkout-blue-btn {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
    }

    .checkout-blue-btn:hover {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        opacity: 1;
    }

    .checkout-shell .btn-primary {
        background-color: var(--primary_color) !important;
        border-color: var(--primary_color) !important;
        color: #ffffff !important;
    }

    .checkout-secondary-btn {
        background: #ffffff;
        border-color: #dbe2ea;
        color: #334155;
    }

    .checkout-secondary-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .checkout-danger-btn {
        background: var(--primary_color);
        color: #ffffff;
    }

    .checkout-danger-btn:hover {
        background: var(--primary_color);
        opacity: 0.9;
    }

    .checkout-guest-form {
        margin-top: 22px;
        padding: 20px;
        border: 1px solid #edf0f4;
        border-radius: 16px;
        background: #fbfdff;
    }

    .checkout-guest-modal-card {
        width: 100%;
    }

    .mobile-guest-modal-header,
    .mobile-otp-modal-header {
        display: none;
    }

    .checkout-guest-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .checkout-field-full {
        grid-column: 1 / -1;
    }

    .checkout-guest-form label {
        display: block;
        color: #334155;
        font-family: 'Poppins', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .checkout-guest-form input,
    .checkout-guest-form select,
    .checkout-summary input,
    #onetime {
        width: 100%;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #ffffff;
        color: #111827;
        padding: 11px 12px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .checkout-guest-form input:focus,
    .checkout-guest-form select:focus,
    .checkout-summary input:focus,
    #onetime:focus {
        border-color: var(--primary_color);
        box-shadow: 0 0 0 3px var(--light_primary_color);
    }

    .checkout-payment-options {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .checkout-payment-options > div {
        border: 1px solid #e5e7eb !important;
        border-radius: 14px !important;
        background: #ffffff;
        color: #334155 !important;
        padding: 16px !important;
    }

    .checkout-stripe-card {
        margin-top: 18px;
        border: 1px solid #e6ebf1;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .checkout-stripe-card .card-body {
        padding: 18px;
    }

    .checkout-stripe-form {
        width: 100%;
        max-width: 680px;
    }

    .checkout-stripe-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .checkout-stripe-field {
        margin-bottom: 14px;
    }

    .checkout-stripe-field label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #334155;
        font-family: 'Poppins', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .checkout-stripe-field label i {
        color: var(--primary_color);
        font-size: 0.92rem;
    }

    .checkout-stripe-input,
    .checkout-stripe-element {
        width: 100%;
        min-height: 48px;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #ffffff;
        color: #111827;
        padding: 14px 13px;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .checkout-stripe-input:focus,
    .checkout-stripe-element.is-focused {
        border-color: var(--primary_color);
        box-shadow: 0 0 0 3px var(--light_primary_color);
    }

    .checkout-stripe-element.is-invalid {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .checkout-stripe-pay-button {
        width: 100%;
        min-height: 48px;
        border: 0;
        border-radius: 12px;
        background: var(--primary_color);
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 18px;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .checkout-stripe-pay-button:hover {
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.16);
        transform: translateY(-1px);
    }

    .checkout-stripe-pay-button:disabled {
        cursor: not-allowed;
        opacity: 0.65;
        box-shadow: none;
        transform: none;
    }

    @media (max-width: 640px) {
        .checkout-stripe-card .card-body {
            padding: 14px;
        }

        .checkout-stripe-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }

    .checkout-addons-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .checkout-addon-card {
        border: 1px solid #edf0f4;
        border-radius: 16px;
        padding: 18px;
        background: #ffffff;
    }

    .checkout-event-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .checkout-event-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        min-width: 0;
    }

    .checkout-event-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        margin-top: 0;
        border: 1px solid var(--middle_light_primary_color);
        border-radius: 14px;
        background: var(--light_primary_color);
        color: var(--primary_color);
        box-shadow: 0 10px 22px var(--light_primary_color);
    }

    .checkout-event-icon svg {
        width: 22px;
        height: 22px;
        stroke-width: 1.8;
    }

    .checkout-event-label {
        color: #94a3b8;
        font-size: 0.74rem;
        font-weight: 900;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        margin: 0;
    }

    .checkout-event-value {
        color: #334155;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.55;
        margin: 4px 0 0;
    }

    .checkout-summary {
        border-top: 5px solid var(--primary_color);
    }

    .checkout-summary-title {
        margin: 0 0 16px;
        border-bottom: 1px solid #f1f3f6;
        color: #111827;
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        font-weight: 900;
    }

    .checkout-summary-stack {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .checkout-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .checkout-summary-row strong,
    .checkout-summary-row span:last-child {
        color: #111827;
        font-weight: 800;
        text-align: right;
    }

    .checkout-summary-total {
        margin-top: 4px;
        padding-top: 16px;
        border-top: 1px solid #edf0f4;
    }

    .checkout-summary-total span:first-child {
        color: #111827;
        font-size: 1rem;
        font-weight: 900;
    }

    .checkout-summary-total .subtotal {
        color: #16a34a !important;
        font-size: 1.35rem;
        font-weight: 950;
    }

    .checkout-summary-mobile-total {
        display: none;
    }

    .checkout-status-pill {
        margin-top: 16px;
        padding: 10px 12px;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        background: #f0fdf4;
        color: #166534;
        text-align: center;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .checkout-event-title-icon {
        font-size: 40px;
        color: #1d4ed8;
        line-height: 1;
        margin-right: 6px;
        vertical-align: middle;
    }

    #otp_verify_span:empty,
    #otp_verify_span:not(.otp-result-message) {
        display: none !important;
    }

    #otp_verify_span.text-success,
    #otp_verify_span.text-danger {
        width: fit-content;
        max-width: 100%;
        margin: 0 auto 10px;
        padding: 10px 14px;
        border-radius: 12px;
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 9px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.82rem;
        font-weight: 800;
        line-height: 1.35;
    }

    #otp_verify_span.text-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534 !important;
    }

    #otp_verify_span.text-danger {
        border: 1px solid #fecaca;
        background: #fff1f2;
        color: #b91c1c !important;
    }

    #otp_verify_span.text-success::before,
    #otp_verify_span.text-danger::before {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 900;
        line-height: 1;
    }

    #otp_verify_span.text-success::before {
        content: "\2713";
        background: #16a34a;
    }

    #otp_verify_span.text-danger::before {
        content: "\00d7";
        background: #dc2626;
    }

    .checkout-coupon-box {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 14px;
        border: 1px solid #edf0f4;
        border-radius: 14px;
        background: #f8fafc;
    }

    .checkout-coupon-link {
        color: var(--primary_color);
        font-size: 0.82rem;
        font-weight: 800;
        text-align: center;
        text-decoration: underline;
    }

    .checkout-donation {
        border: 1px solid #fcd34d;
        border-radius: 18px;
        background: linear-gradient(180deg, #fffbeb 0%, #fff7ed 100%);
        box-shadow: 0 18px 42px rgba(245, 158, 11, 0.12);
        padding: 22px;
        text-align: center;
    }

    .checkout-donation-title {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: #92400e;
        font-family: 'Poppins', sans-serif;
        font-size: 0.86rem;
        font-weight: 950;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0px;
    }

    .checkout-donation p {
        color: #374151;
        font-size: 0.82rem;
        line-height: 1.7;
        text-align: justify;
        margin: 0 0 3px;
    }

    .checkout-trust-footer {
        margin-top: 28px;
        padding: 20px;
        border-radius: 18px;
        background: rgb(255 255 255 / 82%);
        box-shadow: 0px 6px 6px 0px rgb(15 23 42 / 26%);
    }

    .checkout-trust-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .checkout-trust-item {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        padding: 0 18px;
        border-left: 1px solid rgba(203, 213, 225, 0.75);
    }

    .checkout-trust-item:first-child {
        border-left: 0;
        padding-left: 0;
    }

    .checkout-trust-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border-radius: 14px;
        border: 1px solid #dbe2ea;
        background: #ffffff;
        color: #111827;
    }

    .checkout-trust-icon svg {
        width: 24px;
        height: 24px;
    }

    .checkout-trust-title {
        margin: 0;
        color: #1f2937;
        font-family: 'Poppins', sans-serif;
        font-size: 0.88rem;
        font-weight: 850;
        line-height: 1.25;
    }

    .checkout-trust-text {
        margin: 4px 0 0;
        color: #64748b;
        font-family: 'Poppins', sans-serif;
        font-size: 0.76rem;
        font-weight: 500;
        line-height: 1.35;
    }

    @media (max-width: 1024px) {
        .checkout-header-inner {
            max-width: 100%;
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        .checkout-layout {
            grid-template-columns: 1fr;
        }

        .checkout-side {
            position: static;
        }
    }

    @media (max-width: 640px) {
        .checkout-shell {
            padding: 0px 5px 20px;
        }

        .checkout-hold-timer {
            margin: 6px 0 8px;
            padding: 10px 12px;
        }

        .checkout-hold-timer span {
            font-size: 0.8rem;
        }

        .checkout-header-inner {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }

        .checkout-layout {
            gap: 8px;
        }

        .checkout-main {
            order: 1;
        }

        .checkout-side {
            display: contents;
        }

        .checkout-side > * {
            order: 4;
        }

        .checkout-hold-timer {
            order: 0;
        }

        .checkout-donation {
            order: 2;
        }

        .checkout-summary {
            order: 3;
            border-top: 0;
        }

        .checkout-panel {
            padding: 5px 15px;
            border-radius: 16px;
        }

        .checkout-event-panel {
            padding: 16px !important;
        }

        .checkout-event-panel .checkout-summary-title {
            font-size: 0.9rem;
            margin-bottom: 14px !important;
        }

        .checkout-event-panel .checkout-event-title-icon {
            font-size: 36px;
            margin-right: 5px;
        }

        .checkout-event-panel .checkout-event-meta {
            gap: 16px !important;
        }

        .checkout-event-panel .checkout-event-item {
            gap: 13px !important;
        }

        .checkout-event-panel .checkout-event-icon {
            width: 20px;
            height: 20px;
            border-radius: 13px;
            border: none;
        }

        .checkout-event-panel .checkout-event-icon svg {
            width: 20px;
            height: 20px;
        }

        .checkout-event-panel .checkout-event-label {
            display: none;
            font-size: 0.67rem;
        }

        .checkout-event-panel .checkout-event-value {
            font-size: 0.81rem;
            line-height: 1.4;
            margin-top: 3px;
        }

        .checkout-coupon-box {
            gap: 6px;
            padding: 8px;
        }

        .checkout-coupon-box input,
        .checkout-coupon-box button {
            min-height: 34px;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            line-height: 1.2 !important;
        }

        .checkout-coupon-box .checkout-coupon-link {
            line-height: 1.2;
        }

        .checkout-donation {
            padding: 8px;
        }

        .checkout-ticket-row,
        .checkout-ticket-info,
        .checkout-qty-wrap {
            align-items: stretch;
            width: 100%;
        }

        .checkout-ticket-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: stretch;
            gap: 10px;
            padding: 8px;
            border: 1px solid #edf0f400;
        }

        .checkout-ticket-icon {
            display: none !important;
        }

        .checkout-seat-selection-card {
            padding: 10px;
            border-radius: 12px;
        }

        .checkout-seat-selection-head {
            margin-bottom: 8px;
        }

        .checkout-seat-selection-title {
            font-size: 0.9rem;
        }

        .checkout-seat-section-row {
            grid-template-columns: 1fr;
            gap: 7px;
            padding: 9px 0;
        }

        .checkout-seat-chip-list {
            justify-content: flex-start;
            gap: 6px;
        }

        .checkout-seat-chip {
            min-width: 34px;
            min-height: 28px;
            padding: 5px 8px;
            font-size: 0.78rem;
        }

        .checkout-ticket-info {
            align-items: flex-start;
            min-width: 0;
            width: auto;
        }

        .checkout-ticket-info > div:last-child {
            min-width: 0;
        }

        .checkout-ticket-name  {
              white-space: nowrap;
          }

        .checkout-qty-wrap {
            flex-direction: column;
            align-items: flex-end;
            width: auto;
            justify-content: flex-end;
            justify-self: end;
            align-self: stretch;
            gap: 4px;
        }

        .checkout-qty-control {
            height: 24px;
            border-radius: 7px;
        }

        .checkout-qty-control .qtybtn,
        .checkout-qty-control input {
            height: 24px !important;
        }
	
        .checkout-auth-actions,
        .checkout-guest-actions,
        .checkout-guest-grid,
        .checkout-addons-grid,
        .checkout-event-meta,
        .checkout-trust-grid {
            grid-template-columns: 1fr;
        }

        .checkout-mobile-auth-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 6px;
        }

        .checkout-mobile-auth-actions .checkout-primary-btn,
        .checkout-mobile-auth-actions .checkout-secondary-btn {
            min-height: 0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.5;
        }

        #guestcheckoutbutton.mobile-checkout-processing,
        #guestcheckoutbutton.mobile-checkout-processing:hover,
        .mobile-guestcheckoutbutton.mobile-checkout-processing,
        .mobile-guestcheckoutbutton.mobile-checkout-processing:hover,
        #guestcheckoutbutton:disabled {
            background-color: #cccccc !important;
            border-color: #cccccc !important;
            color: #ffffff !important;
            cursor: not-allowed;
            opacity: 0.75;
            pointer-events: none;
        }

        .checkout-mobile-auth-separator {
            color: #64748b;
            font-family: 'Poppins', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            text-align: center;
        }

        .checkout-mobile-secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #166534;
            font-family: 'Poppins', sans-serif;
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1.35;
            text-align: center;
        }

        .checkout-mobile-secure-note i {
            color: #16a34a;
            font-size: 0.78rem;
            line-height: 1;
        }

        .checkout-mobile-secure-note .fa-shield-check {
            position: relative;
            width: 1rem;
            height: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .checkout-mobile-secure-note .fa-shield-check::before {
            content: "\f3ed";
            font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", FontAwesome;
            font-weight: 900;
            font-size: 0.95rem;
        }

        .checkout-mobile-secure-note .fa-shield-check::after {
            content: "\f00c";
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", FontAwesome;
            font-weight: 900;
            color: #ffffff;
            font-size: 0.42rem;
            line-height: 1;
        }

        .checkout-trust-item {
            padding: 16px 0 0;
            border-left: 0;
            padding-bottom: 9px;
            border-top: 1px solid rgba(203, 213, 225, 0.75);
        }

        .checkout-trust-item:first-child {
            padding-top: 0;
            border-top: 0;
        }
        .checkout-ticket-panel > .checkout-panel-header,
        .checkout-terms-panel > .checkout-panel-header,
        .checkout-note,
        .checkout-auth-actions,
        .checkout-status-pill,
        .checkout-trust-footer {
            display: none !important;
        }

        .checkout-refund-desktop-text {
            display: none !important;
        }

        .checkout-refund-mobile-text {
            display: block !important;
        }

        .checkout-summary-title {
            margin: 0 0 4px !important;
            padding-bottom: 0 !important;
        }

        .checkout-summary-stack {
            gap: 6px;
        }

        .checkout-summary-total {
            display: none !important;
        }

        .checkout-summary-mobile-total {
            display: flex !important;
            font-size: 0.78rem;
            line-height: 1.25;
            color: #475569;
        }

        .checkout-summary-mobile-total span:first-child {
            color: #64748b;
            font-weight: 700;
        }

        .checkout-summary-mobile-total .subtotal {
            color: #16a34a !important;
            font-size: 0.86rem;
            font-weight: 900;
        }

        .checkout-summary-ticket-row {
            display: none !important;
        }

        .checkout-summary-free-mobile-textless .checkout-summary-title,
        .checkout-summary-free-mobile-textless .checkout-summary-stack,
        .checkout-summary-free-mobile-textless .checkout-status-pill {
            display: none !important;
        }

        .checkout-main,
        .checkout-side {
            gap: 5px;
        }

        .checkout-refund-note {
            padding: 5px !important;
            margin-bottom: 4px !important;
            background: #fe00000d;
        }

        .checkout-refund-note h4 {
            font-size: 0.81rem;
        }

        #guestCheckoutForm.mobile-guest-modal-open {
            position: fixed;
            inset: 0;
            z-index: 1060;
            display: flex !important;
            align-items: flex-end;
            justify-content: center;
            margin: 0 !important;
            padding: 14px 10px 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: rgba(15, 23, 42, 0.58) !important;
        }

        #guestCheckoutForm.mobile-guest-modal-open .checkout-guest-modal-card {
            max-height: 88vh;
            overflow-y: auto;
            border-radius: 18px 18px 0 0;
            background: #ffffff;
            padding: 16px;
            box-shadow: 0 -16px 40px rgba(15, 23, 42, 0.28);
        }

        .mobile-guest-modal-header,
        .mobile-otp-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef2f7;
        }

        .mobile-guest-modal-header h3,
        .mobile-otp-modal-header h5 {
            margin: 0;
            color: #111827;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 900;
        }

        .mobile-modal-close {
            width: 34px;
            height: 34px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #ffffff;
            color: #111827;
            font-size: 1.25rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #otpCard.mobile-otp-modal-open {
            position: fixed;
            inset: 0;
            z-index: 1070;
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: none;
            margin: 0 !important;
            padding: 18px !important;
            border-radius: 0 !important;
            background: rgba(15, 23, 42, 0.58) !important;
            box-shadow: none !important;
        }

        #otpCard.mobile-otp-modal-open .checkout-otp-modal-card {
            width: 100%;
            max-width: 360px;
            border-radius: 18px;
            background: #ffffff;
            padding: 18px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.28);
        }

        #otpCard.mobile-otp-modal-open .desktop-otp-title {
            display: none;
        }

        #otpCard.mobile-otp-modal-open .checkout-otp-modal-card > .flex,
        #otpCard.mobile-otp-modal-open .checkout-otp-actions {
            width: 100%;
        }

        #otpCard.mobile-otp-modal-open .otp-input {
            width: 46px;
            height: 46px;
            margin: 0 4px;
        }
    }
    </style>
    @section('head_section')
    <style>
        /* Checkout-only: hide header/footer via CSS */
        body.hide-checkout-header-footer .site-wrapper > *:first-child,
        body.hide-checkout-header-footer footer.mt-auto {
            display: none !important;
        }
        body.hide-checkout-header-footer .min-h-screen {
            min-height: auto !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.add('hide-checkout-header-footer');
        });
    </script>

    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', "{{ $data['main']['event'][0]['meta_pixel_id']??0 }}");
        fbq('track', 'InitiateCheckout');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id={{ $data['main']['event'][0]['meta_pixel_id']??0 }}&ev=PageView&noscript=1"/>
    </noscript>
    @endsection

    {{-- Checkout Header --}}
    <div class="sticky top-0  z-40 backdrop-blur  border-gray-200">
        <div class="shadow-lg t-header" >
            <div class="checkout-header-inner mx-auto px-4 py-5 flex items-center justify-between gap-3">
            <button
                type="button"
                onclick="window.history.back()"
                class="inline-flex items-center gap-2 text-white hover:text-white font-poppins font-medium"
                aria-label="Back"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M12.707 15.707a1 1 0 0 1-1.414 0l-5-5a1 1 0 0 1 0-1.414l5-5a1 1 0 1 1 1.414 1.414L8.414 10l4.293 4.293a1 1 0 0 1 0 1.414z" clip-rule="evenodd" />
                </svg>
                <span>{{ __('Back') }}</span>
            </button>

            <div class="flex-1 text-center text-white font-poppins font-high">
                    {{ $data['main']['event'][0]['name'] ?? '' }}
                </div>
            </div>
            </div>   
            
        </div>
    </div>

    <div class="pb-20 bg-scroll min-h-screen" style="background-image: url('images/events.png')">
        {{-- scroll --}}
        <div id="stripe_message" class="bg-danger text-white text-center p-2 hidden"></div>
        <div class="mr-4 flex justify-end z-30">
            <a type="button" href="{{ url('#') }}"
                class="scroll-up-button bg-primary rounded-full p-4 fixed z-20  2xl:mt-[49%] xl:mt-[59%] xlg:mt-[68%] lg:mt-[75%] xxmd:mt-[83%] md:mt-[90%]
                xmd:mt-[90%] sm:mt-[117%] msm:mt-[125%] xsm:mt-[160%]">
                <img src="{{ asset('images/downarrow.png') }}" alt="" class="w-3 h-3 z-20">
            </a>
        </div>
        <input type="hidden" name="totalAmountTax" id="totalAmountTax" value="{{ $data['totalAmountTax'] }}">
        <input type="hidden" name="totalPersTax" id="totalPersTax" value="{{ $data['totalPersTax'] }}">
        <input type="hidden" name="flutterwave_key" value="{{ \App\Models\PaymentSetting::find(1)->ravePublicKey }}">
        @if (Auth::guard('appuser')->user())
            <input type="hidden" name="email" value="{{ auth()->guard('appuser')->user()->email }}">
            <input type="hidden" name="phone" value="{{ auth()->guard('appuser')->user()->phone }}">
            <input type="hidden" name="name" value="{{ auth()->guard('appuser')->user()->name }}">
            <input type="hidden" name="is_auth" id="is_auth" value="1">
        @else
            <input type="hidden" name="is_auth" id="is_auth" value="0">
        @endif
        <input type="hidden" name="flutterwave_key" value="{{ \App\Models\PaymentSetting::find(1)->ravePublicKey }}">
        <div id="ticketorder">
            @csrf
            <input type="hidden" id="razor_key" name="razor_key"
                value="{{ \App\Models\PaymentSetting::find(1)->razorPublishKey }}">

            <input type="hidden" id="stripePublicKey" name="stripePublicKey"
                value="{{ \App\Models\PaymentSetting::find(1)->stripePublicKey }}">

                <input type="hidden" name="currency_code" id="currency_code" value="{{ $data['currency_code'] }}">
                <input type="hidden" name="currency" id="currency" value="{{ $data['currency'] }}">
                <input type="hidden" name="payment_token" id="payment_token">
                <input type="hidden" name="selectedSeats" id="selectedSeats">
                <input type="hidden" name="selectedSeatsId[]" id="selectedSeatsId">
                <input type="hidden" name="selectedVenueSeatIds" id="selectedVenueSeatIds" value="{{ implode(',', $data['venueSeatIds'] ?? []) }}">
                <input type="hidden" name="selectedVenueSeats" id="selectedVenueSeats" value="{{ e(json_encode($data['venueSeatDetails'] ?? [])) }}">
<input type="hidden" name="coupon_id" id="coupon_id_hidden" value="">
                <input type="hidden" name="coupon_discount" id="coupon_discount" value="0">
                <input type="hidden" name="subtotal" id="subtotal" value="">
                <input type="hidden" name="add_ticket" value="">
                <!-- <input type="hidden" class="tax_data" id="tax_data" name="tax_data" value=""> -->
                <input type="hidden" name="event_id" id="event_id" value="{{ $data['main']['ticket'][0]['event_id'] }}">
                <input type="hidden" name="max_allowed" id="max_allowed" value="{{ $data['max_allowed'] ?? 0 }}">

            {{-- Main Checkout Content Area --}}
            <div class="checkout-shell mt-6 px-4 max-w-7xl mx-auto z-10 relative">
                <input type="hidden" value="{{ $data['main']['event'][0]['name']??0 }}" id="eventName">
                <input type="hidden" value="{{ $data['main']['event'][0]['meta_pixel_id']??0 }}" id="metaPixelId">

                @php
                    $i = 0;
                    $typefree = 0;
                    $typepaid = 0;
                    $totdataprice = 0;
                    $ticket_id_data = [];
                    $ttqty = 0;
                    $admin_revenue = 0;
                    $org_revenue = 0;
                    $mainkey = 0;
                    $hasVenueSeatCheckout = !empty($data['venueSeatIds'] ?? []);
                    $venueSeatGroups = collect($data['venueSeatDetails'] ?? [])
                        ->filter(function ($seat) {
                            return !empty(data_get($seat, 'id')) || !empty(data_get($seat, 'label')) || !empty(data_get($seat, 'seat_number'));
                        })
                        ->groupBy(function ($seat) {
                            return data_get($seat, 'section') ?: data_get($seat, 'section_name') ?: __('Section');
                        });
                @endphp

                <div class="checkout-layout grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {{-- LEFT COLUMN: Step Panels (Occupies 2 columns on desktop) --}}
                    <div class="checkout-main lg:col-span-2 space-y-6">
                        {{-- PANEL 1: Select Your Tickets --}}
                        <div class="checkout-panel checkout-ticket-panel bg-white rounded-xl shadow-sm border border-gray-100">
                            <div class="checkout-panel-header flex items-center gap-3 ">
                                <span class="checkout-step-badge flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold">1</span>
                                <h2 class="checkout-panel-title text-lg font-bold text-gray-900 font-poppins">{{ __('Select Your Tickets') }}</h2>
                            </div>

                            <div class="checkout-ticket-list space-y-4">
                                @if($hasVenueSeatCheckout)
                                    <div class="checkout-seat-selection-card">
                                        <div class="checkout-seat-selection-head">
                                            <h3 class="checkout-seat-selection-title">{{ __('Selected Seats') }}</h3>
                                            <span class="checkout-seat-selection-count">{{ count($data['venueSeatIds'] ?? []) }} {{ __('Seats') }}</span>
                                        </div>

                                        @if($venueSeatGroups->isNotEmpty())
                                            @foreach($venueSeatGroups as $sectionName => $sectionSeats)
                                                <div class="checkout-seat-section-row">
                                                    <div class="checkout-seat-section-name">{{ $sectionName }}</div>
                                                    <div class="checkout-seat-chip-list">
                                                        @foreach($sectionSeats as $venueSeat)
                                                            @php
                                                                $seatRow = trim((string) (data_get($venueSeat, 'row') ?: data_get($venueSeat, 'row_name')));
                                                                $seatNumber = trim((string) data_get($venueSeat, 'seat_number'));
                                                                $seatLabel = data_get($venueSeat, 'label') ?: trim($seatRow . ' Seat ' . $seatNumber);
                                                                $compactSeatLabel = '';
                                                                if ($seatRow !== '' && $seatNumber !== '') {
                                                                    $compactSeatLabel = preg_match('/^[\-_]/', $seatNumber)
                                                                        ? trim($seatRow . $seatNumber)
                                                                        : trim($seatRow . '-' . $seatNumber);
                                                                }
                                                                if ($compactSeatLabel === '') {
                                                                    $sectionText = trim((string) $sectionName);
                                                                    $seatText = trim((string) $seatLabel);
                                                                    $compactSeatLabel = $seatText;
                                                                    if ($sectionText !== '' && strpos($seatText, $sectionText) === 0) {
                                                                        $compactSeatLabel = trim(substr($seatText, strlen($sectionText)));
                                                                    }
                                                                }
                                                                if ($compactSeatLabel === '') {
                                                                    $compactSeatLabel = $seatLabel ?: __('Seat');
                                                                }
                                                            @endphp
                                                            <span class="checkout-seat-chip" title="{{ $compactSeatLabel }}">{{ $compactSeatLabel }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="checkout-seat-section-row">
                                                <div class="checkout-seat-section-name">{{ __('Selected Seats') }}</div>
                                                <div class="checkout-seat-chip-list">
                                                    @foreach(($data['venueSeatIds'] ?? []) as $venueSeatId)
                                                        <span class="checkout-seat-chip">{{ __('Seat') }} #{{ $venueSeatId }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @foreach ($data['main']['ticket'] as $key => $ticket)
                                    @php
                                        $selectedQuantity = max(1, (int) ($ticket['selected_quantity'] ?? 1));
                                        $isSeatMapQuantityLocked = !empty($data['venueSeatIds'] ?? []) || !empty(($data['seat_selections'] ?? [])[$ticket['id']] ?? null);
                                        $ttqty += $selectedQuantity;
                                        $mainkey = $key;
                                        $availableSeats = 0;
                                        $existingOrders = 0;
                                        $hasSeatData = false;
                                        $seatTable = null;
                                        $ticketId = $ticket['id'];
                                        $seatSelections = Session::get('seat_selections', []);

                                        if (isset($seatSelections[$ticketId])) {
                                            $seatSelection = $seatSelections[$ticketId];

                                            if (isset($seatSelection['seat_table_id']) && isset($seatSelection['sponser_id'])) {
                                                $seatTableId = $seatSelection['seat_table_id'];
                                                $sponserId = $seatSelection['sponser_id'];
                                                $seatTable = \App\Models\SeatTable::find($seatTableId);

                                                if ($seatTable) {
                                                    $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $sponserId)
                                                        ->where('seat_id', $seatTableId)
                                                        ->count();
                                                    $availableSeats = max(0, $seatTable->number_seat - $existingOrders);
                                                    $hasSeatData = true;
                                                }
                                            }
                                        }

                                        if (!$hasSeatData && isset($ticket['SeatTable_id']) && !empty($ticket['SeatTable_id'])) {
                                            $seatTableIds = explode(',', $ticket['SeatTable_id']);
                                            $minAvailableSeats = PHP_INT_MAX;

                                            foreach ($seatTableIds as $seatTableId) {
                                                $seatTableId = trim($seatTableId);

                                                if (!empty($seatTableId)) {
                                                    $seatTable = \App\Models\SeatTable::find($seatTableId);

                                                    if ($seatTable) {
                                                        $existingOrders = \App\Models\OrderChild::where('SeatDetails_id', $seatTable->sponsership_id)
                                                            ->where('seat_id', $seatTable->id)
                                                            ->count();
                                                        $minAvailableSeats = min($minAvailableSeats, max(0, $seatTable->number_seat - $existingOrders));
                                                    }
                                                }
                                            }

                                            if ($minAvailableSeats !== PHP_INT_MAX) {
                                                $availableSeats = $minAvailableSeats;
                                                $hasSeatData = true;
                                            }
                                        }

                                        if (!$hasSeatData) {
                                            $availableSeats = $ticket['available_qty'] ?? 0;
                                        }

                                        if (($data['main']['available_qty'][$key] ?? 0) > 0) {
                                            $i++;
                                        }

                                        if ($ticket['type'] == 'paid') {
                                            $typepaid++;
                                            $totdataprice += ($ticket['price'] * $selectedQuantity);
                                        } else {
                                            $typefree++;
                                        }

                                        $ticket_id_data[] = $ticket['id'];
                                    @endphp

                                    <input type="hidden" value="{{ $ticket['ticket_per_order'] }}" name="tpo{{$key}}" id="tpo{{$key}}">
                                    <input type="hidden" value="{{ $availableSeats }}" name="available_seat{{$key}}" id="available_seat{{$key}}">
                                    <input type="hidden" value="{{ $ticket['available_qty'] }}" name="available{{$key}}" id="available{{$key}}">
                                    <input type="hidden" name="price{{$key}}" id="ticket_price{{$key}}" value="{{ $ticket['price'] }}">
                                    <input type="hidden" name="ticketname{{$key}}" id="ticketname{{$key}}" value="{{ $ticket['name'] }}">
                                    <input type="hidden" name="ticket_id{{$key}}" id="ticket_id{{$key}}" value="{{ $ticket['id'] }}">
                                    <input type="hidden" name="ticket_type{{$key}}" id="ticket_type{{$key}}" value="{{ $ticket['type'] }}">
                                    <input type="hidden" name="ticket_qty{{$key}}" class="ticketqtyseprate" id="ticket_qty{{$ticket['id']}}" value="{{ $selectedQuantity }}">
                                    <input type="hidden" name="multitickets[]" class="hiddenInput" value="{{$ticket['id']}}">
                                    @if($hasVenueSeatCheckout)
                                        <input type="hidden" id="quantity-{{ $ticket['id'] }}" class="totalqty" value="{{ $selectedQuantity }}">
                                    @endif

                                    @unless($hasVenueSeatCheckout)
                                        <div class="checkout-ticket-row flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 border border-gray-100 rounded-xl bg-white hover:border-gray-200 transition-all gap-4">
                                            {{-- Ticket Info with Representative Visual Indicator --}}
                                            <div class="checkout-ticket-info flex items-center gap-4">
                                                <div class="checkout-ticket-icon flex items-center justify-center w-14 h-14 rounded-xl shrink-0">
                                                     <i class="fa-solid fa-ticket"></i>
                                                </div>
                                                <div>
                                                    <h3 class="checkout-ticket-name text-base font-bold text-gray-900 font-poppins">{{ $ticket['name'] }}</h3>
                                                    <span class="checkout-ticket-pill inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                        {{ $ticket['type'] == 'paid' ? __('Paid Admission') : __('General Admission') }}
                                                    </span>
                                                    <p class="checkout-ticket-price text-sm font-semibold mt-1 text-gray-500">
                                                        {{ __('Price:') }} <span class="{{ $ticket['type'] == 'paid' ? 'text-gray-900' : 'checkout-free font-bold' }}">{{ $ticket['type'] == 'paid' ? $data['currency'] . number_format($ticket['price'], 2) : __('FREE') }}</span>
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- Stepper Counter Box --}}
                                            <div class="checkout-qty-wrap flex items-center gap-3 self-end sm:self-auto">
                                                <span class="checkout-qty-label text-sm font-medium text-gray-500 mr-1 font-poppins">{{ __('Quantity') }}</span>
                                                <div class="checkout-qty-control flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50 pro-qty {{ $isSeatMapQuantityLocked ? 'is-locked' : '' }}"
                                                    data-seat-map-locked="{{ $isSeatMapQuantityLocked ? 1 : 0 }}">
                                                    <button type="button" id="dec-{{ $ticket['id'] }}" data-id="{{$key}}" data-action="decrement"
                                                        class="dec qtybtn w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors font-bold text-lg"
                                                        @if($isSeatMapQuantityLocked) disabled aria-disabled="true" title="{{ __('Quantity is fixed by selected seats.') }}" @endif>
                                                        &minus;
                                                    </button>
                                                    <input type="number" id="quantity-{{ $ticket['id'] }}" readonly name="quantity" value="{{ $selectedQuantity }}"
                                                        class="w-10 text-center bg-transparent border-none text-sm font-bold text-gray-900 focus:outline-none focus:ring-0 pointer-events-none totalqty">
                                                    <button type="button" id="inc-{{ $ticket['id'] }}" data-id="{{$key}}" data-action="increment"
                                                        class="inc qtybtn w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors font-bold text-lg"
                                                        @if($isSeatMapQuantityLocked) disabled aria-disabled="true" title="{{ __('Quantity is fixed by selected seats.') }}" @endif>
                                                        &plus;
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endunless

                                    @if ($ticket['type'] == 'paid')
                                        <div id="ticket-{{ $ticket['id'] }}" class="collapse-content hidden">
                                            @foreach ($data['main']['taxmulti'][$ticket['id']] as $taxKey => $items)
                                                <input type="hidden" id="{{$ticket['id']}}type{{$taxKey}}" value="{{ $items[1]['type'] }}">
                                                <input type="hidden" id="{{$ticket['id']}}price{{$taxKey}}" value="{{ $items[2]['price'] }}">
                                                <input type="hidden" id="{{$ticket['id']}}createdby{{$taxKey}}" value="{{ $items[4]['createdby'] }}">
                                                <input type="hidden" class="totalamountofpercentage" id="totalamountofpercentage" value="2.5">
                                                <input type="hidden" class="amount_type" name="amount_type" value="{{ $items[1]['type'] }}">
                                                @php
                                                    if ($items[4]['createdby'] == 0) {
                                                        $admin_revenue += $items[3]['amount'];
                                                    }
                                                    if ($items[4]['createdby'] == 1) {
                                                        $org_revenue += $items[3]['amount'];
                                                    }
                                                @endphp
                                                <span id="{{$ticket['id']}}taxval{{$taxKey}}" class="hidden">{{ $currency }}{{ number_format($items[3]['amount'], 2) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Dynamic Banner Note --}}
                            @if ($typepaid == 0)
                                <div class="checkout-note mt-4 flex items-center gap-2 p-3 bg-green-50 rounded-xl text-green-700 text-xs font-medium border border-green-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-green-600">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span><strong>{{ __('Good news!') }}</strong> {{ __("You don't need to pay for free registration items.") }}</span>
                                </div>
                            @endif
                        </div>

                        @foreach($data['main']['event'] as $eventKey => $row)
                            @if ($data['main']['seat_map'] != null && $data['main']['module'][$eventKey]['is_install'] == 1 && $data['main']['module'][$eventKey]['is_enable'] == 1)
                                @include('seatmap::seatmapView', [
                                    'seat_map' => $data['main']['seat_map'][$eventKey],
                                    'rows' => $data['main']['rows'][$eventKey],
                                    'seatsByRow' => $data['main']['seatsByRow'][$eventKey],
                                    'seatLimit' => $data['main']['ticket'][$eventKey]['ticket_per_order'],
                                ])
                            @endif
                        @endforeach

                        <input type="hidden" name="ticket_id" id="ticket_id" value="{{ implode(',', $ticket_id_data) }}">
                        <input type="hidden" name="quantityall" id="quantity" value="{{ $ttqty }}">

                        @if ($i > 0)
                            {{-- PANEL 2: Terms & Conditions --}}
                            <div class="checkout-panel checkout-terms-panel bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <div class="checkout-panel-header flex items-center gap-3 mb-4">
                                    <span class="checkout-step-badge flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold">2</span>
                                    <h2 class="checkout-panel-title text-lg font-bold text-gray-900 font-poppins">{{ __('Terms & Conditions') }}</h2>
                                </div>

                                <div class="checkout-refund-note border rounded-xl p-4 mb-4 flex gap-3">
                                    <div class="checkout-alert-mark w-5 h-5 rounded-full flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">!</div>
                                    <div>
                                        <h4 class="text-sm font-bold font-poppins">{{ __('All Sales Final - No Refunds') }}</h4>
                                        <p class="checkout-refund-desktop-text text-xs mt-0.5 leading-relaxed">{{ __('By proceeding, you agree that all ticket sales are final. No refunds or exchanges will be provided.') }}</p>
                                        <p class="checkout-refund-mobile-text hidden text-xs mt-0.5 leading-relaxed">{{ __('No refund or exchange') }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="checkout-terms-agree inline-flex items-center gap-2.5 cursor-pointer">
                                        <input type="checkbox" id="agreeTerms" name="agree_terms" class="rounded border-gray-300 w-4 h-4 transition-colors">
                                        <span class="text-sm text-gray-700 font-poppins">I agree to the <a href="https://teptix.com/terms-and-conditions" target="_blank" class="checkout-coupon-link font-semibold underline">{{ __('Terms & Conditions') }}</a>.</span>
                                    </label>
                                    <div id="termsWarning" class="checkout-coupon-link text-xs font-medium hidden pl-6">* You must accept the terms framework to continue your transaction validation.</div>
                                </div>

                                @php $classvar = ''; @endphp
                                {{-- Action Authentication Row --}}
                                @if(!Auth::guard('appuser')->user())
                                    @php $classvar = 'hidden'; @endphp
                                    <div class="checkout-auth-actions grid grid-cols-1 sm:grid-cols-2 gap-4  border-t border-gray-100">
                                        <button type="button" id="form_submit" onclick="openGuestCheckoutModal()" class="checkout-primary-btn checkout-guest-continue-btn flex items-center justify-center gap-2 font-poppins font-semibold text-sm text-white bg-green-600 hover:bg-green-700 active:bg-green-800 w-full rounded-xl py-3.5 transition-all shadow-sm">
                                            <i class="fas fa-lock"></i>
                                            <span>{{ __('Continue as Guest') }}</span>
                                        </button>
                                        <button type="button" onclick="loginforuser()" id="form_login" class="checkout-secondary-btn flex items-center justify-center gap-2 font-poppins font-semibold text-sm text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 active:bg-gray-200 w-full rounded-xl py-3.5 transition-all" style="border-color: var(--primary_color) !important;">
                                            <i class="fas fa-user"></i>
                                            <span>{{ __('Login') }}</span>
                                        </button>
                                    </div>

                                    <div id="guestCheckoutForm" class="checkout-guest-form hidden mt-6">
                                        <div class="checkout-guest-modal-card">
                                            <div class="mobile-guest-modal-header">
                                                <h3>{{ __('Guest Checkout') }}</h3>
                                                <button type="button" class="mobile-modal-close" onclick="closeGuestCheckoutModal()" aria-label="{{ __('Close') }}">&times;</button>
                                            </div>
                                            <div class="checkout-guest-grid grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <div>
                                                    <label for="first_name" class="block font-poppins font-medium text-base text-black">{{ __('First Name') }}</label>
                                                    <input type="text" name="first_name" id="first_name" required class="w-full p-3 rounded-lg border border-gray-300 text-sm" placeholder="{{ __('First Name') }}" maxlength="20">
                                                    <span id="first_name_span" class="text-danger text-sm"></span>
                                                </div>

                                                <div>
                                                    <label for="last_name" class="block font-poppins font-medium text-base text-black">{{ __('Last Name') }}</label>
                                                    <input type="text" name="last_name" id="last_name" required class="w-full p-3 rounded-lg border border-gray-300 text-sm" placeholder="{{ __('Last Name') }}" maxlength="20">
                                                    <span id="last_name_span" class="text-danger text-sm"></span>
                                                </div>

                                                <div class="checkout-field-full col-span-1 md:col-span-2">
                                                    <label for="number" class="block font-poppins font-medium text-base text-black">{{ __('Contact Number') }}</label>
                                                    <div class="flex flex-col sm:flex-row gap-3">
                                                        <div class="w-full sm:w-[35%]">
                                                            <select id="countries" name="Countrycode" class="select2 w-full p-2.5 border border-gray-300 rounded-lg text-sm">
                                                                <option value="" disabled selected>{{ __('Select Country') }}</option>
                                                                @foreach ($data['phone'] as $item)
                                                                    <option value="{{ $item['phonecode'] }}" {{ ($item['iso'] . '(+' . $item['phonecode'] . ')') == "US(+1)" ? 'selected' : '' }}>
                                                                        {{ $item['iso'] . '(+' . $item['phonecode'] . ')' }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <span id="countries_span" class="text-danger text-sm"></span>
                                                        </div>
                                                        <div class="w-full">
                                                            <input type="tel" name="phone" id="phone" class="w-full p-3 rounded-lg border border-gray-300 text-sm" placeholder="{{ __('Number') }}" maxlength="10" oninput="validatePhoneNumber(this)" onkeypress="return /[0-9]/g.test(event.key)">
                                                            <span id="phone_span" class="text-danger text-sm"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="checkout-field-full col-span-1 md:col-span-2">
                                                    <label for="email" class="block font-poppins font-medium text-base text-black">{{ __('Email Address') }}</label>
                                                    <input type="email" name="email" id="email" required class="w-full p-3 rounded-lg border border-gray-300 text-sm" placeholder="{{ __('Email Address') }}">
                                                    <span id="email_span" class="text-danger text-sm"></span>
                                                </div>
                                            </div>
                                            <br><br>
                                            <span id="otp_verify_span" class="text-center text-sm mb-2"></span>

                                            <div class="checkout-guest-actions flex flex-col sm:flex-row justify-end mt-5 gap-4">
                                                <button type="button" onclick="verifyemail()" class="checkout-primary-btn btn btn-primary w-full sm:w-auto guestemailverifybutton" id="guestemailverifybutton" data-state="idle" aria-disabled="false">
                                                    <div id="verifyemailtext">Verify Email</div>
                                                    <div id="verifyemailloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7"></div>
                                                </button>

                                                <button type="button" onclick="guestcheckout()" class="checkout-danger-btn checkout-blue-btn w-full sm:w-auto guestcheckoutbutton btn-disabled" id="guestcheckoutbutton" disabled>
                                                    <div id="formtext">Checkout</div>
                                                </button>
                                                <div id="formloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7"></div>
                                            </div>

                                            <div id="otpCard" class="hidden mt-6 p-4 bg-white rounded shadow w-full max-w-md mx-auto">
                                                <div class="checkout-otp-modal-card">
                                                    <div class="mobile-otp-modal-header">
                                                        <h5>{{ __('Verify OTP') }}</h5>
                                                        <button type="button" class="mobile-modal-close" onclick="closeGuestOtpModal()" aria-label="{{ __('Close') }}">&times;</button>
                                                    </div>
                                                    <h5 class="desktop-otp-title font-poppins font-semibold text-lg mb-2">{{ __('Verify OTP') }}</h5>
                                                    <div class="flex justify-center gap-2 mb-2">
                                                        <input type="text" maxlength="1" id="otpinput1" class="otp-input border rounded text-center w-10 h-10" />
                                                        <input type="text" maxlength="1" id="otpinput2" class="otp-input border rounded text-center w-10 h-10" />
                                                        <input type="text" maxlength="1" id="otpinput3" class="otp-input border rounded text-center w-10 h-10" />
                                                        <input type="text" maxlength="1" id="otpinput4" class="otp-input border rounded text-center w-10 h-10" />
                                                    </div>

                                                    <div class="checkout-otp-actions flex justify-end">
                                                        <button type="button" onclick="resendOtp()" class="btn btn-secondary">Resend</button>
                                                        <button type="button" onclick="verifyotp()" class="checkout-blue-btn btn">Verify OTP</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <?php $setting = App\Models\PaymentSetting::find(1); ?>
                                @if (Auth::guard('appuser')->user())
                                    <div id="paymentOptions" class="checkout-payment-options {{$classvar}} mt-6 flex md:space-x-5 md:flex-row md:space-y-0 sm:flex-col sm:space-x-0 sm:space-y-5 xxsm:flex-col xxsm:space-x-0 xxsm:space-y-5 mb-5 payments disabled:opacity-50">
                                        @if ($typepaid > 0)
                                            @if ($setting->stripe == 1)
                                                <div class="border border-gray-light p-5 rounded-lg text-gray-100 w-full font-normal font-poppins text-base leading-6 flex">
                                                    <input id="Stripe" required type="radio" value="STRIPE" name="payment_type" class="h-5 w-5 mr-2 border border-gray-light hover:border-gray-light focus:outline-none">
                                                    <label for="Stripe"><img src="{{ url('images/payment-logos (4).png') }}" alt="" style="width: 350px;" class="object-contain"></label>
                                                </div>
                                            @endif
                                        @else
                                            <div class="border border-gray-light p-5 rounded-lg text-gray-100 w-full font-normal font-poppins text-base leading-6 flex">
                                                {{ __('FREE') }}
                                                <input id="default-radio-1" type="radio" value="FREE" name="payment_type" checked class="ml-2 h-5 w-5 mr-2 border border-gray-light hover:border-gray-light focus:outline-none">
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div id="paymentOptions" class="hidden payments">
                                        <input type="radio" value="{{ $typepaid > 0 ? 'STRIPE' : 'FREE' }}" name="payment_type" checked>
                                    </div>
                                @endif

                                <div class="paypal-button-section mt-4 mx-auto">
                                    <div id="paypal-button-container" class="hidden"></div>
                                </div>

                                <div class="card stripeCard checkout-stripe-card hidden" id="stripeform">
                                    <div class="bg-danger text-white hidden stripe_alert rounded-lg py-5 px-6 mb-3 text-base inline-flex items-center w-full" role="alert">
                                        <div class="stripeText"></div>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" class="require-validation customform checkout-stripe-form" data-cc-on-file="false" id="stripe-payment-form">
                                            @csrf
                                            <div>
                                                <div class="checkout-stripe-field">
                                                    <div class="form-group">
                                                        <label for="card_email"><i class="fa fa-envelope"></i>{{ __('Email') }}</label>
                                                        <input id="card_email" type="email" name="card_email" title="Enter Your Email" placeholder="Email" class="email required checkout-stripe-input" />
                                                    </div>
                                                </div>
                                                <div class="checkout-stripe-field">
                                                    <div class="form-group">
                                                        <label for="card-number"><i class="fa fa-credit-card"></i>{{ __('Card Information') }}</label>
                                                        <div id="card-number" class="checkout-stripe-element"></div>
                                                    </div>
                                                </div>
                                                <div class="checkout-stripe-grid">
                                                    <div class="checkout-stripe-field">
                                                        <div class="form-group">
                                                            <label for="card-expiry"><i class="fa fa-calendar"></i>{{ __('Expiry') }}</label>
                                                            <div id="card-expiry" class="checkout-stripe-element"></div>
                                                            <input type="hidden" class="card-expiry-month required form-control" name="card-expiry-month" />
                                                            <input type="hidden" class="card-expiry-year required form-control" name="card-expiry-year" />
                                                        </div>
                                                    </div>
                                                    <div class="checkout-stripe-field">
                                                        <div class="form-group">
                                                            <label for="card-cvc"><i class="fa fa-lock"></i>{{ __('CVC') }}</label>
                                                            <div id="card-cvc" class="checkout-stripe-element"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="checkout-stripe-field">
                                                    <div class="form-group">
                                                        <label for="card_name"><i class="fa fa-user"></i>{{ __('Name on card') }}</label>
                                                        <input id="card_name" type="text" class="required checkout-stripe-input" name="card_name" placeholder="Name" title="Name on Card" required />
                                                    </div>
                                                </div>
                                                <div class="form-group text-start">
                                                    <button type="submit" class="checkout-stripe-pay-button btn-submit"><i class="fa fa-lock"></i>{{ __('Pay with stripe') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                @if (Auth::guard('appuser')->user())
                                    <div class="mt-3">
                                        <button type="submit" id="form_submit" class="checkout-danger-btn font-poppins font-medium text-lg leading-6 text-white bg-primary w-full rounded-md py-3">
                                            <div id="formtext"><i class="fa pr-2 fa-check-square"></i>{{ __('Click To Book Ticket') }}</div>
                                            <div id="formloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7"></div>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <input type="hidden" name="tax" id="tax_total" value="{{ $typepaid > 0 && is_numeric($data['tax_total'] ?? null) ? $data['tax_total'] : 0 }}">
                        <input type="hidden" name="admin_revenue" id="admin_revenue" value="{{$admin_revenue}}">
                        <input type="hidden" name="org_revenue" id="org_revenue" value="{{$org_revenue}}">

                        @if (count($data['add_ons']) != 0)
                            <div class="checkout-panel checkout-addons-panel bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="checkout-summary-title text-sm font-bold uppercase tracking-wider text-gray-400  font-poppins">{{ __('Add Ons') }}</h3>
                                <div class="checkout-addons-grid grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach ($data['add_ons'] as $item)
                                        <?php
                                            $mainkey++;
                                            if ($item->type == 'paid') {
                                                $typeval = 'Paid';
                                                $pricetext = $currency . number_format($item->price, 2);
                                            } else {
                                                $typeval = 'Free';
                                                $pricetext = 'Free';
                                            }
                                        ?>
                                        <div class="checkout-addon-card relative rounded-lg border border-gray-light p-5">
                                            <div class="!h-auto" style="height: auto;">
                                                <div class="flex justify-center">
                                                    <p class="font-poppins font-medium text-sm leading-4 text-danger text-center rounded-full bg-danger-light w-16 py-1">{{ $typeval }}</p>
                                                </div>
                                                <p class="text-ellipsis overflow-hidden ... font-poppins font-medium leading-7 text-primary text-center py-1" title="{{ $item->name }}">{{ $item->name }}</p>
                                                <div class="flex justify-center">
                                                    <p class="font-poppins font-medium text-2xl leading-10 text-black text-center">{{ $pricetext }}</p>
                                                </div>
                                            </div>
                                            @foreach ($item->taxinfo[$item->id] as $taxKey => $ite)
                                                <input type="hidden" id="{{$item->id}}type{{$taxKey}}" value="{{ $ite[1]['type'] }}">
                                                <input type="hidden" id="{{$item->id}}price{{$taxKey}}" value="{{ $ite[2]['price'] }}">
                                                <input type="hidden" id="{{$item->id}}createdby{{$taxKey}}" value="{{ $ite[4]['createdby'] }}">
                                            @endforeach
                                            <div class="flex justify-center" style="width: 85%; margin:auto;">
                                                @if ($item->quantity < 1)
                                                    <div class="mt-7 w-full border border-primary rounded-lg flex justify-center">
                                                        <a href="#" class="font-poppins font-medium text-base leading-6 text-primary py-3">{{ __('Sold Out') }}</a>
                                                    </div>
                                                @else
                                                    <?php
                                                        $tpqao = $item->ticket_per_order;
                                                        $qtyao = $item->ticket_per_order;
                                                        $priceao = $item->price;
                                                        $typeao = $item->type;
                                                        $availableSeatsAddon = null;

                                                        if (isset($item->number_seat) && $item->number_seat > 0) {
                                                            if (isset($item->sponsership_id)) {
                                                                $existingOrdersAddon = \App\Models\OrderChild::where('SeatDetails_id', $item->sponsership_id)
                                                                    ->where('seat_id', $item->id)
                                                                    ->count();
                                                            } else if (isset($item->event_id)) {
                                                                $existingOrdersAddon = \App\Models\OrderChild::where('event_id', $item->event_id)
                                                                    ->where('ticket_id', $item->id)
                                                                    ->count();
                                                            } else {
                                                                $existingOrdersAddon = \App\Models\OrderChild::where('ticket_id', $item->id)->count();
                                                            }

                                                            $availableSeatsAddon = max(0, $item->number_seat - $existingOrdersAddon);
                                                        } else {
                                                            $availableSeatsAddon = $item->quantity ?? $item->available_qty ?? null;
                                                        }
                                                    ?>
                                                    <div class="mt-7 w-full bg-primary text-white rounded-lg flex justify-center" id="maindivaddon{{$item->id}}">
                                                        <a href="javascript:void(0);" onclick="addons('{{$item->id}}','{{$mainkey}}','{{$tpqao}}','{{$qtyao}}','{{$priceao}}','{{$typeao}}','{{$availableSeatsAddon}}')" class="checkout-danger-btn font-poppins font-medium text-base leading-6 py-3">{{ __('Add') }}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- PANEL 3: Event Details Component --}}
                        <div class="checkout-panel checkout-event-panel bg-white rounded-xl shadow-sm border border-gray-100 p-6">

                            <h3 class="checkout-summary-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4 font-poppins"><i class="fa-solid fa-calendar-star checkout-event-title-icon"></i>{{ __('Event Details') }}</h3>
                            <div class="checkout-event-meta grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="checkout-event-item flex gap-3">
                                    <div class="checkout-event-icon mt-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="checkout-event-label text-xs font-bold text-gray-400 uppercase tracking-tight">{{ __('Date') }}</h5>
                                        <p class="checkout-event-value text-sm font-medium text-gray-800 mt-0.5">
                                            {{ isset($data['main']['event'][0]['start_time']) ? \Carbon\Carbon::parse($data['main']['event'][0]['start_time'])->format('F j, Y') : '' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="checkout-event-item flex gap-3">
                                    <div class="checkout-event-icon mt-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1115 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="checkout-event-label text-xs font-bold text-gray-400 uppercase tracking-tight">{{ __('Location') }}</h5>
                                        <p class="checkout-event-value text-sm font-medium text-gray-800 mt-0.5 leading-relaxed">
                                            {{ $data['main']['event'][0]['address'] ?? '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if (($data['main']['ticket'][0]['allday'] ?? 0) == 0)
                                <div class="mt-5">
                                    <input type="date" name="ticket_date" id="onetime" data-date="{{ $data['main']['ticket'][0]['end_time'] ?? '' }}" placeholder="mm/dd/yy" class="mt-3 border p-2 border-gray-light">
                                    @if ($errors->has('ticket_date'))
                                        <div class="text-danger">{{ $errors->first('ticket_date') }}</div>
                                    @endif
                                    <div class="ticket_date text-danger"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: Pricing Calculations & Donation Addon --}}
                    <div class="checkout-side space-y-6">
                        @if(!empty($data['venueSeatHoldExpiresAt']))
                            <div id="checkout-seat-hold-timer"
                                class="checkout-hold-timer"
                                role="status"
                                data-expires-at="{{ $data['venueSeatHoldExpiresAt'] }}"
                                data-release-url="{{ route('checkout.releaseSeatHold') }}"
                                data-redirect-url="{{ $data['checkoutExpireRedirectUrl'] ?? route('home') }}"
                                data-event-id="{{ $data['main']['ticket'][0]['event_id'] ?? '' }}"
                                data-venue-seat-ids="{{ implode(',', $data['venueSeatIds'] ?? []) }}">
                                <span>{{ __('Seat hold expires in') }}</span>
                                <strong id="checkout-seat-hold-countdown">10:00</strong>
                            </div>
                        @endif

                        {{-- Order Summary Block --}}
                        <div class="checkout-panel checkout-summary bg-white rounded-xl shadow-sm border border-gray-100 p-6 {{ $typepaid == 0 ? 'checkout-summary-free-mobile-textless' : '' }}">
                            <h3 class="checkout-summary-title text-base font-bold text-gray-900 mb-4 font-poppins border-b border-gray-50 pb-3">{{ __('Order Summary') }}</h3>
                            <div class="checkout-summary-stack space-y-3">
                                <div class="checkout-summary-row checkout-summary-ticket-row flex justify-between items-center text-sm gap-4">
                                    <span class="text-gray-500 font-poppins">{{ __('Tickets') }}</span>
                                    <span class="font-medium text-gray-900 text-right">{{ $data['main']['ticket'][0]['name'] }} (x<span class="summary-qty">{{ $ttqty }}</span>)</span>
                                </div>

                                @if ($typepaid > 0 && $data['tax_total'] > 0)
                                    <div class="checkout-summary-row flex justify-between items-center text-sm">
                                        <span class="text-gray-500 font-poppins">{{ __('Fees and charges') }}</span>
                                        <span class="font-medium text-gray-900 totaltax">{{ $currency }} {{ number_format($data['tax_total'], 2) }}</span>
                                    </div>
                                    <div class="checkout-summary-row flex justify-between items-center text-sm">
                                        <span class="text-gray-500 font-poppins">{{ __('Tickets amount') }}</span>
                                        <span class="font-medium text-gray-900 totticketprice">{{ $currency }} {{ number_format($totdataprice, 2) }}</span>
                                    </div>
                                    <div class="checkout-summary-row flex justify-between items-center text-sm">
                                        <span class="text-gray-500 font-poppins">{{ __('Coupon discount') }}</span>
                                        <span class="font-medium text-gray-900 discount">00.00</span>
                                    </div>
                                    <div class="checkout-summary-row checkout-summary-mobile-total justify-between items-center">
                                        <span class="font-poppins">{{ __('Total') }}</span>
                                        <span class="font-poppins subtotal">
                                            {{ $currency . ' ' . number_format($totdataprice + $data['tax_total'], 2) }}
                                        </span>
                                    </div>
                                @endif

                                @if ($typepaid > 0)
                                    <div class="checkout-coupon-box flex flex-col gap-2 border border-gray-100 rounded-xl p-3">
                                        <input type="text" name="coupon_code" id="coupon_id" class="w-full px-3 py-2 focus:outline-none font-poppins font-normal text-sm leading-6 text-gray-800 placeholder-gray-400 border border-gray-200 rounded-lg" placeholder="{{ __('Coupon Code') }}">
                                        <button type="button" id="apply" name="apply" class="checkout-danger-btn w-full px-4 py-2 font-poppins font-semibold text-sm leading-6 text-white focus:outline-none whitespace-nowrap rounded-lg transition-all">
                                            {{ __('Apply') }}
                                        </button>
                                        <a href="javascript:void(0);" class="checkout-coupon-link font-poppins text-xs underline text-center" onclick="openCouponModal()">{{ __('Available Coupon') }}</a>
                                    </div>
                                    <div class="couponerror checkout-coupon-link text-sm -mt-1"></div>
                                @endif

                                <div class="checkout-summary-row checkout-summary-total pt-3 border-t border-gray-100 flex justify-between items-center">
                                    <span class="text-base font-bold text-gray-900 font-poppins">{{ __('Total') }}</span>
                                    <span class="text-xl font-black text-green-600 font-poppins subtotal">
                                        {{ $typepaid > 0 ? $currency . ' ' . number_format($totdataprice + $data['tax_total'], 2) : __('FREE') }}
                                    </span>
                                </div>
                            </div>
                            <div class="checkout-status-pill mt-4 text-center px-3 py-2 bg-green-50 text-green-700 text-xs font-semibold rounded-lg border border-green-100">
                                {{ $typepaid > 0 ? __('Secure payment available') : __('No payment required') }}
                            </div>
                            @if(!Auth::guard('appuser')->user())
                                <div class="checkout-mobile-auth-actions">
                                    <button type="button" onclick="openGuestCheckoutModal()" class="checkout-primary-btn checkout-guest-continue-btn w-full">
                                        <i class="fas fa-lock"></i>
                                        <span>{{ __('Continue as Guest') }}</span>
                                    </button>
                                    <div class="checkout-mobile-auth-separator">{{ __('Already have an account?') }}</div>
                                    <button type="button" onclick="loginforuser()" class="checkout-secondary-btn w-full" style="border-color: var(--primary_color) !important;">
                                        <i class="fas fa-user"></i>
                                        <span>{{ __('Login') }}</span>
                                    </button>
                                    <div class="checkout-mobile-secure-note">
                                        <i class="fa-solid fa-shield-check" style="color: #18b86b;"></i>
                                        <span>{{ __('Your Information is secure and encrypted') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($typepaid > 0)
                            {{-- Coupon Modal --}}
                            <div id="couponModal" class="modal" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background-color: var(--primary_color); text-align: center; color: white; border-radius:25px;">
                                            <h3 class="modal-title" style="text-align:center;font-weight: 900;">{{ __('Coupon') }} </h3>
                                            <button type="button" class="close" onclick="closeCouponModal()" aria-label="Close">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="coupon-container">
                                                @forelse(($data['coupons'] ?? []) as $c)
                                                    <button type="button" class="coupon-select-item" onclick="selectCouponFromList('{{ $c->coupon_code }}')" title="{{ $c->coupon_code }}">
                                                        <div class="coupon-code-text">{{ $c->coupon_code }}</div>
                                                    </button>
                                                @empty
                                                    <div class="w-full text-center text-sm text-gray-500 py-4">{{ __('No coupons available') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" onclick="closeCouponModal()">{{ __('Close') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <input type="hidden" id="totalamountofpercentage" value="">
                        <input type="hidden" id="totalamountofprice" value="">
                        <input type="hidden" id="grandtotalofalltax" value="{{ $data['tax_total'] }}">
                        <input type="hidden" id="totticketprice" value="{{ $totdataprice }}">
                        <input type="hidden" id="grandtotalofall" value="{{ $totdataprice + $data['tax_total'] }}">

                        {{-- Sponsorship Support Module --}}
                        @if(isset($data['main']['ticket'][0]['event_id']) && $data['main']['ticket'][0]['event_id'] == 46)
                            <div class="checkout-donation bg-amber-50/60 rounded-xl shadow-sm border-2 border-amber-200 p-6 text-center space-y-4">
                                <div class="checkout-donation-title flex items-center justify-center gap-1.5 text-amber-800">
                                    <span class="text-xl">🙏</span>
                                    <h4 class="text-sm font-black uppercase tracking-wider font-poppins">{{ __('Support Our Divine Celebration') }}</h4>
                                    <span class="text-xl">🙏</span>
                                </div>
                                <p class="text-xs text-gray-700 leading-relaxed text-justify font-poppins">
                                    We warmly invite you to be part of this divine community celebration through your generous sponsorship and donations. Since this is a <strong class="text-amber-900">FREE event</strong> for all devotees and families, your support plays a vital role in helping us manage event production, decor, prasadam, food service, venue arrangements, audio-visual setup, hospitality, and overall event operations. Together, let us create a spiritually uplifting and unforgettable experience for the community. 🙏
                                </p>
                                <button type="button" onclick="openDonationModal()" class="checkout-danger-btn w-full text-white font-semibold text-sm py-3 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path d="M9.653 16.815a.75.75 0 01-.096-1.054l4.5-5.25a.75.75 0 111.13.986l-3.954 4.613 1.012 1.012a.75.75 0 11-1.06 1.06l-1.533-1.533z" />
                                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ __('Make a Donation') }}</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="checkout-trust-footer">
                    <div class="checkout-trust-grid">
                        <div class="checkout-trust-item">
                            <div class="checkout-trust-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="checkout-trust-title">{{ __('Secure Checkout') }}</h4>
                                <p class="checkout-trust-text">{{ __('Your data is protected') }}</p>
                            </div>
                        </div>

                        <div class="checkout-trust-item">
                            <div class="checkout-trust-icon">
                                <i class="fa-solid fa-ticket"></i>
                            </div>
                            <div>
                                <h4 class="checkout-trust-title">{{ __('Instant Confirmation') }}</h4>
                                <p class="checkout-trust-text">{{ __('Get your ticket instantly') }}</p>
                            </div>
                        </div>

                        <div class="checkout-trust-item">
                            <div class="checkout-trust-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a9 9 0 00-9 9v3a3 3 0 003 3h1a1 1 0 001-1v-4a1 1 0 00-1-1H4v-1a8 8 0 1116 0v1h-2a1 1 0 00-1 1v4a1 1 0 001 1h1a3 3 0 003-3v-3a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="checkout-trust-title">{{ __('Need Help?') }}</h4>
                                <p class="checkout-trust-text">{{ __('Contact our support team') }}</p>
                            </div>
                        </div>

                        <div class="checkout-trust-item">
                            <div class="checkout-trust-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.172-.435.789-.435.961 0l1.54 3.903a.75.75 0 00.565.455l4.192.408c.47.046.658.625.294.936l-3.177 2.716a.75.75 0 00-.24.739l.995 4.102c.112.46-.38.823-.77.589l-3.565-2.162a.75.75 0 00-.73 0l-3.566 2.162c-.39.234-.882-.13-.77-.589l.995-4.102a.75.75 0 00-.24-.739L3.463 9.412c-.364-.31-.176-.89.294-.936l4.191-.408a.75.75 0 00.566-.455l1.54-3.903z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="checkout-trust-title">{{ __('100% Safe & Secure') }}</h4>
                                <p class="checkout-trust-text">{{ __('We never share your data') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="payment" id="payment" value="{{ $typepaid > 0 ? $totdataprice + $data['tax_total'] : 0 }}">
                @php
                    $pricestripe = $totdataprice + $data['tax_total'];
                    if ($data['currency_code'] == 'USD' || $data['currency_code'] == 'EUR' || $data['currency_code'] == 'INR') {
                        $pricestripe = $pricestripe * 100;
                    }
                @endphp
                <input type="hidden" name="stripe_payment" id="stripe_payment" value="{{ $typepaid > 0 ? $pricestripe : 0 }}">
            </div>
        </div>
    </div>
    @if (!Auth::guard('appuser')->user())
        <!-- The Modal -->
        <!--<div id="myModal" class="modal">-->
        <!--    <div class="modal-dialog">-->
        <!--        <div class="modal-content">-->

                    <!-- Modal Header -->
        <!--            <div class="modal-header">-->
        <!--                <h5 class="modal-title">Guest Checkout</h5>-->
        <!--                <button type="button" class="close" id="closeModalBtn">&times;</button>-->
        <!--            </div>-->

                    <!-- Modal Body -->
        <!--            <div class="modal-body">-->
        <!--                <div class="grid grid-cols-2 gap-5 sm:grid-cols-2 msm:grid-cols-2 xxsm:grid-cols-1">-->
        <!--                    <div class="pt-5">-->
        <!--                        <label for="name"-->
        <!--                            class="font-poppins font-medium text-base leading-6 text-black">{{ __('First Name') }}</label>-->
        <!--                        <input type="text" name="first_name" required-->
        <!--                            id="first_name"class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-lg border border-gray-light focus:outline-none"-->
        <!--                            placeholder="{{ __('First Name') }}">-->
        <!--                            <span id="first_name_span" class="text-danger"></span>-->
        <!--                    </div>-->
        <!--                    <div class="pt-5">-->
        <!--                        <label for="last_name"-->
        <!--                            class="font-poppins font-medium text-base leading-6 text-black">{{ __('Last Name') }}</label>-->
        <!--                        <input type="text" name="last_name" id="last_name" required-->
        <!--                            class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-lg border border-gray-light focus:outline-none"-->
        <!--                            placeholder="{{ __('Last Name') }}">-->
        <!--                            <span id="last_name_span" class="text-danger"></span>-->
        <!--                    </div>-->
        <!--                    <div class="">-->
        <!--                        <label for="number"-->
        <!--                            class="font-poppins font-medium text-base leading-6 text-black">{{ __('Contact Number') }}</label>-->
                                <!--<div class="flex space-x-3">-->
                                <!--    <div class="container">-->
                                <!--        <div class="row justify-content-center">-->
                                <!--            <div class="col-12 col-md-2">-->
                                <!--                <div class="mb-3">-->

                                <!--                    <select id="countries" name="Countrycode" class="form-select select2" >-->
                                                        <!--<option value="" disabled selected>{{ __('Select Country') }}</option>-->
                                <!--                        @foreach ($data['phone'] as $item)-->
                                <!--                            <option  value="{{ $item['phonecode'] }} "-->
                                <!--                                {{ ($item['name'] . '(+' . $item['phonecode'] . ')') == 'UNITED STATES(+1)' ? 'selected' : '' }}>-->
                                <!--                                {{ $item['name'] . '(+' . $item['phonecode'] . ')' }}-->
                                <!--                            </option>-->
                                <!--                        @endforeach-->
                                <!--                    </select>-->
                                <!--                    <style>-->
                                <!--                    .select2-container--default .select2-selection--single .select2-selection__rendered {width:150px;}-->
                                <!--                    </style>-->
                                <!--                </div>-->
                                <!--            </div>-->
                                <!--        </div>-->
                                <!--    </div>-->

                                <!--    <div class="w-[100%]">-->
                                <!--        <input type="number" name="phone" id="phone"-->
                                <!--            class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-md border border-gray-light focus:outline-none"-->
                                <!--            placeholder="{{ __('Number') }}">-->
                                <!--            <span id="phone_span" class="text-danger"></span>-->
                                <!--    </div>-->
                                <!--</div>-->
        <!--                        <div class="flex space-x-3">-->
        <!--                            <div class="w-[35%]">-->
        <!--                                <select class="select2" id="countries" name="Countrycode"-->
        <!--                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">-->
        <!--                                    <option value="" disabled selected>{{ __('Select Country') }}</option>-->
        <!--                                    @foreach ($data['phone'] as $item)-->
        <!--                                        <option class=" " value="{{ $item['phonecode'] }}" {{($item['name'] . '(+' . $item['phonecode'] . ')')=="UNITED STATES(+1)"?"selected":''}}>-->
        <!--                                            {{ $item['name'] . '(+' . $item['phonecode'] . ')' }}-->
        <!--                                        </option>-->
        <!--                                    @endforeach-->
        <!--                                </select>-->
        <!--                                <style>-->
        <!--                                .select2-container--default .select2-selection--single .select2-selection__rendered {width:150px;}-->
        <!--                                </style>-->
        <!--                                <span id="countries_span" class="text-danger"></span>-->
        <!--                            </div>-->
        <!--                            <div class="w-[100%]">-->
        <!--                                <input type="number" name="phone" id="phone"-->
        <!--                                    class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-md border border-gray-light focus:outline-none"-->
        <!--                                    placeholder="{{ __('Number') }}">-->
        <!--                                    <span id="phone_span" class="text-danger"></span>-->
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                    <div class=" ">-->
        <!--                        <label for="email"-->
        <!--                            class="font-poppins font-medium text-base leading-6 text-black">{{ __('Email Address') }}</label>-->
        <!--                        <input type="email" name="email" id="email" required-->
        <!--                            class="w-full text-sm font-poppins font-normal text-black block p-3 z-20 rounded-lg border border-gray-light focus:outline-none"-->
        <!--                            placeholder="{{ __('Email Address') }}">-->
        <!--                        <span id="email_span" class="text-danger"></span>-->
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>-->

                    <!-- Modal Footer -->
        <!--            <div class="modal-footer">-->
        <!--                <button type="button" onclick="verifyemail()" class="btn btn-primary guestemailverifybutton" id="guestemailverifybutton">-->
        <!--                <div id="verifyemailtext">-->
        <!--                        Verify Email-->
        <!--                    </div>-->
        <!--                    <div id="verifyemailloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7">-->
        <!--                    </div></button>-->
        <!--                <button type="button" class="btn btn-secondary" id="closeModalBtnFooter">Close</button>-->
        <!--                <button type="button" onclick="this.disabled=true;document.getElementById('formtext').style.display='none';document.getElementById('formloader').classList.remove('hidden');guestcheckout()" class="btn btn-primary guestcheckoutbutton" id="guestcheckoutbutton" disabled>-->
        <!--                    <div id="formtext">-->
        <!--                        Checkout-->
        <!--                    </div>-->
        <!--                    <div id="formloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-7 w-7">-->
        <!--                    </div>-->
        <!--                </button>-->
        <!--            </div>-->

        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->

        <!-- The Modal otp -->
        <!--<div id="myModalotp" class="modalotp">-->
        <!--    <div class="modal-dialogotp">-->
        <!--        <div class="modal-contentotp">-->

                    <!-- Modal Header -->
        <!--            <div class="modal-headerotp">-->
        <!--                <h5 class="modal-titleotp">Verify Otp</h5>-->
        <!--                <button type="button" class="closeotp" id="closeModalBtnotp">&times;</button>-->
        <!--            </div>-->

                    <!-- Modal Body -->
        <!--            <div class="modal-bodyotp">-->
        <!--                <div class="otp-input-container">-->
        <!--                    <input type="text" maxlength="1" id="otpinput1" class="otp-input" />-->
        <!--                    <input type="text" maxlength="1" id="otpinput2" class="otp-input" />-->
        <!--                    <input type="text" maxlength="1" id="otpinput3" class="otp-input" />-->
        <!--                    <input type="text" maxlength="1" id="otpinput4" class="otp-input" />-->
        <!--                </div>-->
        <!--            </div>-->
        <!--            <div style="display: flex; justify-content: center;">-->
        <!--                <span id="otp_verify_span" class="text-danger"></span>-->
        <!--            </div>-->

                    <!-- Modal Footer -->
        <!--            <div class="modal-footerotp">-->
        <!--                <button type="button" onclick="resendOtp()" class="btn btn-secondary">Resend</button>-->
        <!--                <button type="button" onclick="verifyotp()" class="btn btn-primary">Verify Otp</button>-->
        <!--            </div>-->

        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->
        @endif
        <script>
    window.guestEmailVerified = false;

    function updateGuestCheckoutState(isVerified) {
      const button = document.getElementById('guestcheckoutbutton');
      if (!button) return;

      button.disabled = !isVerified;
      button.classList.toggle('btn-disabled', !isVerified);
      const actions = button.closest('.checkout-guest-actions');
      if (actions) {
        actions.classList.toggle('checkout-verified-actions', isVerified);
      }
    }

    window.addEventListener('DOMContentLoaded', function () {
      updateGuestCheckoutState(false);
    });
  </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const agreeTermsCheckbox = document.getElementById('agreeTerms');
    const paymentOptionsWrapper = document.getElementById('paymentOptions');
    const termsWarning = document.getElementById('termsWarning');
    if (!agreeTermsCheckbox || !paymentOptionsWrapper || !termsWarning) {
        return;
    }
    const paymentOptions = paymentOptionsWrapper.getElementsByTagName('input');

    // Initially disable all payment methods
    for (let i = 0; i < paymentOptions.length; i++) {
        paymentOptions[i].disabled = true;
    }

    // Listen for changes on the Terms and Conditions checkbox
    agreeTermsCheckbox.addEventListener('change', function() {
        if (this.checked) {
            termsWarning.classList.add('hidden');
            // Checkbox is checked - enable payment options
            for (let i = 0; i < paymentOptions.length; i++) {
                paymentOptions[i].disabled = false;
            }
            const isAuthInput = document.getElementById('is_auth');
            if (isAuthInput && isAuthInput.value === '1') {
                const checkedPayment = paymentOptionsWrapper.querySelector('input[name="payment_type"]:checked');
                if (checkedPayment) {
                    checkedPayment.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        } else {
            // Checkbox is not checked - disable payment options and show a warning
            termsWarning.classList.remove('hidden');
            for (let i = 0; i < paymentOptions.length; i++) {
                paymentOptions[i].disabled = true;
            }
        }
    });

    if (agreeTermsCheckbox.checked) {
        agreeTermsCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const bookingButton = document.getElementById('form_submit');
    const isAuthInput = document.getElementById('is_auth');
    if (bookingButton && isAuthInput && isAuthInput.value === '1') {
        bookingButton.addEventListener('click', function(event) {
            if (!agreeTermsCheckbox.checked) {
                event.preventDefault();
                event.stopImmediatePropagation();
                termsWarning.classList.remove('hidden');
                agreeTermsCheckbox.focus();
            }
        }, true);
    }

    const checkoutBackUrl = sessionStorage.getItem('checkoutBackUrlAfterLogin');
    if (checkoutBackUrl && isAuthInput && isAuthInput.value === '1' && checkoutBackUrl !== window.location.href) {
        history.pushState({ checkoutAfterLogin: true }, '', window.location.href);
        window.addEventListener('popstate', function() {
            const targetUrl = sessionStorage.getItem('checkoutBackUrlAfterLogin');
            if (targetUrl) {
                sessionStorage.removeItem('checkoutBackUrlAfterLogin');
                window.location.replace(targetUrl);
            }
        }, { once: true });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const timer = document.getElementById('checkout-seat-hold-timer');
    if (!timer) {
        return;
    }

    const countdown = document.getElementById('checkout-seat-hold-countdown');
    const expiresAt = Date.parse(timer.dataset.expiresAt || '');
    const releaseUrl = timer.dataset.releaseUrl;
    const redirectUrl = timer.dataset.redirectUrl || '/';
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    let releaseStarted = false;
    let timerInterval = null;

    if (Number.isNaN(expiresAt)) {
        return;
    }

    function formatRemaining(milliseconds) {
        const totalSeconds = Math.max(0, Math.ceil(milliseconds / 1000));
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    }

    function disableCheckoutActions() {
        document.querySelectorAll('#form_submit, #guestcheckoutbutton, .mobile-guestcheckoutbutton, .btn-submit').forEach(function (button) {
            button.disabled = true;
            button.classList.add('btn-disabled');
        });
    }

    function releaseHoldAndRedirect() {
        if (releaseStarted) {
            return;
        }

        releaseStarted = true;
        timer.classList.add('is-expired');
        if (countdown) {
            countdown.textContent = '00:00';
        }
        disableCheckoutActions();
        sessionStorage.removeItem('checkoutBackUrlAfterLogin');

        fetch(releaseUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
            },
            body: JSON.stringify({
                event_id: timer.dataset.eventId,
                venue_seat_ids: timer.dataset.venueSeatIds
            })
        })
        .then(function (response) {
            return response.json().catch(function () {
                return {};
            });
        })
        .then(function (data) {
            window.location.replace(data.redirect_url || redirectUrl);
        })
        .catch(function () {
            window.location.replace(redirectUrl);
        });
    }

    function tickHoldTimer() {
        const remaining = expiresAt - Date.now();

        if (remaining <= 0) {
            if (timerInterval) {
                window.clearInterval(timerInterval);
            }
            if (countdown) {
                countdown.textContent = '00:00';
            }
            releaseHoldAndRedirect();
            return;
        }

        if (countdown) {
            countdown.textContent = formatRemaining(remaining);
        }
    }

    tickHoldTimer();
    timerInterval = window.setInterval(tickHoldTimer, 1000);
});

	    function isMobileCheckoutView() {
	        return window.matchMedia('(max-width: 640px)').matches;
	    }
	
	    function openGuestCheckoutModal() {
	        const agreeTermsCheckbox = document.getElementById('agreeTerms');
	        const termsWarning = document.getElementById('termsWarning');
	        const guestForm = document.getElementById('guestCheckoutForm');
	        const guestButton = document.getElementById('form_submit');
	
	        if (agreeTermsCheckbox && agreeTermsCheckbox.checked === false) {
	            if (termsWarning) {
	                termsWarning.classList.remove('hidden');
	            }
	            agreeTermsCheckbox.focus();
	            return;
	        }
	
	        if (!guestForm) {
	            return;
	        }
	
	        guestForm.classList.remove('hidden');
	        if (isMobileCheckoutView()) {
	            guestForm.classList.add('mobile-guest-modal-open');
	            document.body.classList.add('modal-open');
	        } else {
	            guestForm.classList.remove('mobile-guest-modal-open');
	            if (guestButton) {
	                guestButton.classList.add('hidden');
	            }
	            guestForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
	        }
	
	        window.setTimeout(function () {
	            const firstName = document.getElementById('first_name');
	            if (firstName) {
	                firstName.focus();
	            }
	        }, 100);
	    }
	
	    function closeGuestCheckoutModal() {
	        const guestForm = document.getElementById('guestCheckoutForm');
	        if (!guestForm) {
	            return;
	        }
	
	        guestForm.classList.remove('mobile-guest-modal-open');
	        if (isMobileCheckoutView()) {
	            guestForm.classList.add('hidden');
	            document.body.classList.remove('modal-open');
	        }
	    }
	
	    function openGuestOtpModal() {
	        const otpCard = document.getElementById('otpCard');
	        if (!otpCard) {
	            return;
	        }
	
	        otpCard.classList.remove('hidden');
	        if (isMobileCheckoutView()) {
	            otpCard.classList.add('mobile-otp-modal-open');
	            document.body.classList.add('modal-openotp');
	        } else {
	            otpCard.classList.remove('mobile-otp-modal-open');
	            otpCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
	        }
	
	        window.setTimeout(function () {
	            const firstOtp = document.getElementById('otpinput1');
	            if (firstOtp) {
	                firstOtp.focus();
	            }
	        }, 100);
	    }
	
	    function closeGuestOtpModal(isVerified) {
	        const otpCard = document.getElementById('otpCard');
	        if (!otpCard) {
	            return;
	        }
	
	        otpCard.classList.remove('mobile-otp-modal-open');
	        otpCard.classList.add('hidden');
	        document.body.classList.remove('modal-openotp');
	        if (!isVerified) {
	            const verifyButton = document.getElementById('guestemailverifybutton');
	            if (verifyButton) {
	                verifyButton.disabled = false;
	                verifyButton.classList.remove('btn-secondary');
	                verifyButton.classList.add('btn-primary');
	            }
	        }
	    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Ensure the input value is a digit
                if (!/^\d*$/.test(e.target.value)) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                }
                // Move to the next input if a digit is entered
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && index > 0 && !e.target.value) {
                    inputs[index - 1].focus();
                }
            });
        });
    });
// $("#form_submit").click(function(){

// })
function loginforuser(){
    var fallbackEventUrl = '{{ url('event/' . ($data['main']['ticket'][0]['event_id'] ?? '') . '/' . rawurlencode($data['main']['event'][0]['name'] ?? 'event')) }}';
    var referrerUrl = document.referrer || '';
    var backUrl = referrerUrl;

    if (!backUrl || backUrl.includes('/user/login') || backUrl.includes('/checkout') || backUrl.includes('/ordersuccessuser')) {
        backUrl = fallbackEventUrl;
    }

    sessionStorage.setItem('checkoutBackUrlAfterLogin', backUrl);

    var hiddenValues = [];
    $('.hiddenInput').each(function() {
        var ticketId = $(this).val();
        var quantity = parseInt($('#ticket_qty' + ticketId).val(), 10) || 1;

        for (var i = 0; i < quantity; i++) {
            hiddenValues.push(ticketId);
        }
    });

    var venueSeatIds = $('#selectedVenueSeatIds').val() || '';
    var venueSeatDetails = $('#selectedVenueSeats').val() || '';
    var ajaxData = {
        'multitickets': hiddenValues,
        'preserve_existing_hold': 1
    };

    if (venueSeatIds) {
        ajaxData.venue_seat_ids = venueSeatIds;
    }

    if (venueSeatDetails) {
        try {
            ajaxData.venue_seat_details = JSON.parse(venueSeatDetails);
        } catch (error) {
            ajaxData.venue_seat_details = venueSeatDetails;
        }
    }

    $.ajax({
        url: "/user/storesessiontickets",
        method: 'POST',
        data: ajaxData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            window.location.href='{{ route("ordersuccessuser") }}'
        },
        error: function(xhr, status, error) {
            console.error('Error submitting input: ', error);
        }
    });

}

function addons(id,key,ticket_per_order,quantity,price,type,available_seats){
    // alert(id);
    var newbutton='';
    newbutton += '<div class="flex flex-col items-center" id="divid'+id+'">';
    newbutton +='<div class="flex flex-row h-10 w-full rounded-lg relative bg-transparent mt-1 pro-qty">';
        newbutton +='<button data-mdb-ripple="true" id="dec-'+id+'" data-id="'+key+'" data-mdb-ripple-color="light" data-action="decrement" type="button"';
            newbutton +='class="border-l dec qtybtn addons border-t border-b border-primary bg-primary-light text-primary hover:text-black-700 h-8 w-9 cursor-pointer">';
            newbutton +='<span class="m-auto text-2xl font-thin">−</span>';
        newbutton +='</button>';
        newbutton +='<div class="text-center">';
            newbutton +='<input type="number" id="quantity-'+id+'" readonly name="quantity"';
                newbutton +='value="0" class="bg-primary-light outline-none focus:outline-none text-center w-8 font-semibold text-md hover:text-black focus:text-black md:text-base cursor-default flex items-center text-primary h-8 totalqty"';
                newbutton +='name="custom-input-number">';
        newbutton +='</div>';
        newbutton +='<button data-mdb-ripple="true" data-mdb-ripple-color="light" data-id="'+key+'" data-action="increment" id="inc-'+id+'" type="button"';
            newbutton +='class="border-r inc qtybtn border-t border-b border-primary bg-primary-light text-primary hover:text-black-700 h-8 w-9 cursor-pointer">';
            newbutton +='<span class="m-auto text-2xl font-thin">+</span>';
        newbutton +='</button>';
    newbutton +='</div>';
    newbutton += '<a href="javascript:void(0);" onclick="removeaddons(\'' + id + '\', \'' + key + '\', \'' + ticket_per_order + '\', \'' + quantity + '\', \'' + price + '\', \'' + type + '\', \'' + available_seats + '\')" class="text-primary mt-2 removeaddonsbutton" data-check="1"><i class="fa fa-remove"></i></a>';
    newbutton += '<p class=" text-primary" style="font-size:0.6rem" id="'+id+'taxaddon">Tax & Charges: $ 10.00</p><input type="hidden" id="'+id+'taxaddoninp" value="0">';
    newbutton += '</div>';
    newbutton +='<input type="hidden" value="'+ticket_per_order+'" name="tpo'+key+'" id="tpo'+key+'">';
    // Add available seats input if available_seats is provided and not undefined/null
    if(available_seats !== undefined && available_seats !== null && available_seats !== '') {
        newbutton +='<input type="hidden" value="'+available_seats+'" name="available_seat'+key+'" id="available_seat'+key+'">';
    }
    newbutton +='<input type="hidden" value="'+quantity+'" name="available'+key+'" id="available'+key+'">';
    newbutton +='<input type="hidden" name="price'+key+'" id="ticket_price'+key+'" value="'+price+'">';
    newbutton +='<input type="hidden" name="ticket_id'+key+'" id="ticket_id'+key+'" value="'+id+'">';
    newbutton +='<input type="hidden" name="ticket_type'+key+'" id="ticket_type'+key+'" value="'+type+'">';
    newbutton +='<input type="hidden" name="ticket_qty'+key+'" class="ticketqtyseprate" id="ticket_qty'+id+'" value="1">';
    newbutton +='<input type="hidden" name="multitickets[]" class="hiddenInput" value="'+id+'">';
    newbutton += '<a href="javascript:void(0);" id="loaderatag'+id+'" class=" hidden font-poppins font-medium text-base leading-6  py-3">';
    newbutton +='<div id="removeaddonemailloader" class="hidden mx-auto animate-spin rounded-full border-t-2 border-blue-500 border-solid h-10 w-10"></a>';

    var ticketIdsao = $('#ticket_id').val();
    var valueToAdd = id;
    var newTicketIds = ticketIdsao.length > 0 ? ticketIdsao + ',' + valueToAdd : valueToAdd;
    $('#ticket_id').val(newTicketIds);

    $("#maindivaddon"+id).removeClass('bg-primary');
    $("#maindivaddon"+id).html(newbutton);

    $("#inc-" + id).trigger('click');

}

function removeaddons(id,mainkey,tpqao,qtyao,priceao,typeao,availableSeatsao){
    var qtvalremove=$("#quantity-"+id).val();
    $("#quantity-"+id).removeClass('totalqty');
    $("#divid"+id).addClass('hidden');
    $("#maindivaddon"+id).addClass('bg-primary');
    $("#loaderatag"+id).removeClass('hidden');
    $("#removeaddonemailloader").show(100);
    var counter = 0;  // Initialize a counter
    for (let i = 0; i < qtvalremove; i++) {
        setTimeout(function() {
            if($(".removeaddonsbutton").data("check")==1){
                $("#dec-" + id).trigger('click');
            }
            counter++;  // Increment the counter
            if(counter == qtvalremove){
                console.log(counter+' - '+qtvalremove);
                var ticketIdsao = $('#ticket_id').val();
                var arr = ticketIdsao.split(',');
                var valueToRemove = id;
                arr = arr.filter(function(item) {
                    return item.trim() != valueToRemove;
                });
                var newTicketIds = arr.join(',');
                $('#ticket_id').val(newTicketIds);
                var newbutton='';
                // Include available seats parameter in the onclick call if it exists
                if(availableSeatsao !== undefined && availableSeatsao !== null && availableSeatsao !== '') {
                    newbutton += '<a href="javascript:void(0);" onclick="addons(\'' + id + '\', \'' + mainkey + '\', \'' + tpqao + '\', \'' + qtyao + '\', \'' + priceao + '\', \'' + typeao + '\', \'' + availableSeatsao + '\')"';
                } else {
                    newbutton += '<a href="javascript:void(0);" onclick="addons(\'' + id + '\', \'' + mainkey + '\', \'' + tpqao + '\', \'' + qtyao + '\', \'' + priceao + '\', \'' + typeao + '\')"';
                }
                newbutton += 'class="font-poppins font-medium text-base leading-6  py-3">Add</a>';
                $("#maindivaddon"+id).addClass('bg-primary');
                $("#maindivaddon"+id).html('').html(newbutton);
            }
        }, i * 100);
    }
}

// Coupon modal handlers
function openCouponModal() {
    var modal = document.getElementById('couponModal');
    if (!modal) return;
    modal.style.display = 'block';
    document.body.classList.add('modal-open');

    var input = document.getElementById('coupon_code_modal');
    if (input) {
        var visibleCouponInput = document.getElementById('coupon_id');
        input.value = visibleCouponInput ? visibleCouponInput.value : '';
        input.focus();
        input.select();
    }
}

function closeCouponModal() {
    var modal = document.getElementById('couponModal');
    if (modal) modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function selectCouponFromList(code) {
    var couponInput = document.getElementById('coupon_id');
    var couponHiddenInput = document.getElementById('coupon_id_hidden');

    var normalized = (code || '').trim();

    if (couponInput) {
        couponInput.value = normalized;
    }
    if (couponHiddenInput) {
        couponHiddenInput.value = normalized;
    }

    // Trigger existing apply logic
    var applyBtn = document.getElementById('apply');
    if (applyBtn) applyBtn.click();

    closeCouponModal();
}






function applyCouponFromModal() {
    var modalInput = document.getElementById('coupon_code_modal');
    var couponInput = document.getElementById('coupon_id');
    var applyBtn = document.getElementById('apply');
    var errorEl = document.getElementById('couponModalError');

    if (!modalInput || !couponInput || !applyBtn) return;


    if (errorEl) errorEl.classList.add('hidden');

    var code = (modalInput.value || '').trim();
    if (!code) {
        if (errorEl) {
            errorEl.textContent = '{{ __('Please enter coupon code') }}';
            errorEl.classList.remove('hidden');
        }
        modalInput.focus();
        return;
    }

    couponInput.value = code;
    applyBtn.click();
    closeCouponModal();
}

</script>
<script>
    function toggleCollapse(id, ticketId) {
        const content = document.getElementById(id);
        const toggleBtn = document.getElementById('toggle-btn-' + ticketId);

        // Hide other collapsible sections
        const allContent = document.querySelectorAll('.collapse-content');
        const allButtons = document.querySelectorAll('.show-btn');

        allContent.forEach((item) => {
            if (item.id !== id) {
                item.classList.add('hidden');
            }
        });

        allButtons.forEach((button) => {
            if (button.id !== 'toggle-btn-' + ticketId) {
                button.textContent = 'Fees and charges';
            }
        });

        // Toggle current section
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            toggleBtn.textContent = 'Hide';
        } else {
            content.classList.add('hidden');
            toggleBtn.textContent = 'Fees and charges';
        }
    }
</script>

@php $checkoutEventId = $data['main']['ticket'][0]['event_id'] ?? null; @endphp
@if($checkoutEventId == 46)
    @php $checkoutEvent = \App\Models\Event::find(46); @endphp
    @if($checkoutEvent)
        @include('frontend.partials.donation-modal', ['event' => $checkoutEvent])
    @endif
@endif

@endsection
