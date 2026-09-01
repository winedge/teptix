<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->name }} - Seat Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        @php
            $resolvedPrimaryColor = ltrim(trim((string) ($primaryColor ?? '#047857')), '#');

            if (preg_match('/^[0-9A-Fa-f]{3}$/', $resolvedPrimaryColor)) {
                $resolvedPrimaryColor = $resolvedPrimaryColor[0] . $resolvedPrimaryColor[0]
                    . $resolvedPrimaryColor[1] . $resolvedPrimaryColor[1]
                    . $resolvedPrimaryColor[2] . $resolvedPrimaryColor[2];
            }

            if (preg_match('/^[0-9A-Fa-f]{6}$/', $resolvedPrimaryColor)) {
                $resolvedPrimaryColor = '#' . $resolvedPrimaryColor;
            } else {
                $resolvedPrimaryColor = '#047857';
            }
        @endphp

        :root {
            --primary_color: {{ $resolvedPrimaryColor }};
            --primary_color_strong: color-mix(in srgb, var(--primary_color) 78%, #111827);
            --primary_color_soft: color-mix(in srgb, var(--primary_color) 12%, #ffffff);
            --primary_color_border: color-mix(in srgb, var(--primary_color) 28%, #ffffff);
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page {
            width: min(1180px, calc(100% - 24px));
            margin: 18px auto;
        }

        .seat-map-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
        }

        .seat-map-title {
            min-width: 0;
        }

        .seat-map-kicker {
            margin: 0 0 5px;
            color: var(--primary_color);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .seat-map-title h1 {
            margin: 0;
            color: #111827;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }

        .seat-map-title p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
        }

        .seat-map-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .selection-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--primary_color);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .selection-chip strong {
            color: var(--primary_color);
            font-size: 14px;
        }

        .selection-chip svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            flex-shrink: 0;
        }

        .venue-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .ticket-menu {
            display: flex;
            justify-content: flex-end;
        }

        .ticket-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--primary_color);
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            box-shadow: none;
        }

        .ticket-toggle svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            flex-shrink: 0;
        }

        .quantity-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(17, 24, 39, 0.4);
        }

        .quantity-modal-backdrop[hidden] {
            display: none;
        }

        .quantity-modal {
            position: fixed; 
            left: 50%;
            bottom: 16px;
            transform: translateX(-50%);

            width: min(440px, calc(100% - 24px));
            padding: 32px 8px 24px;
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .quantity-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--primary_color);
            cursor: pointer;
        }

        .quantity-modal-close svg {
            width: 100%;
            height: 100%;
        }

        .quantity-modal-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            padding-right: 32px;
        }

        .quantity-modal-icon {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--primary_color_soft);
            color: var(--primary_color);
        }

        .quantity-modal-icon svg {
            width: 22px;
            height: 22px;
        }

        .quantity-modal-text {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .quantity-modal-title {
            margin: 0;
            color: var(--primary_color);
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        .quantity-modal-subtitle {
            margin: 0;
            color: #4b5563;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.35;
        }

        .quantity-inner-box {
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .quantity-box-label {
            margin: 0 0 14px;
            color: #111827;
            font-size: 12px;
            font-weight: 700;
        }

        .quantity-options {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 18px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .quantity-option {
            flex: 1 1 auto;
            min-width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: var(--primary_color_soft);
            color: var(--primary_color);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .quantity-option.is-active {
            background: var(--primary_color);
            color: #ffffff;
        }

        .quantity-book-button {
            width: 100%;
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            background: var(--primary_color);
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        .quantity-book-button:hover {
            opacity: 0.9;
        }

        .ticket-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
            margin: -4px 0 14px;
        }

        .ticket-option {
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #ffffff;
            color: #111827;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            text-align: left;
            cursor: pointer;
        }

        .ticket-option small {
            display: block;
            margin-top: 3px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }

        .ticket-option.is-active {
            border-color: var(--primary_color);
            background: var(--primary_color_soft);
            color: var(--primary_color_strong);
        }

        .seat.ticket-row-active.available {
            border: none !important;
            background: #16a34a;
            color: #ffffff;
            box-shadow: 0 0 0 1px rgba(22, 163, 74, 0.25), 0 2px 8px rgba(17, 24, 39, 0.25);
        }

        .seat.ticket-row-blocked {
            border-color: #6b7280;
            background: #9ca3af;
            color: #ffffff;
            cursor: not-allowed;
        }

        .selected-summary {
            display: none;
            margin: -4px 0 14px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            overflow-x: auto;
        }

        .selected-summary.is-visible {
            display: block;
        }

        .selected-summary-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 0 8px;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
        }

        .selected-summary-title span {
            color: var(--primary_color);
            white-space: nowrap;
        }

        .selected-summary-list {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            margin: 0;
            padding: 0 0 4px;
            list-style: none;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .selected-summary-list::-webkit-scrollbar {
            height: 6px;
        }

        .selected-summary-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #9ca3af;
        }

        .selected-summary-list li {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            padding: 7px 9px;
            border-radius: 6px;
            background: var(--primary_color_soft);
            color: var(--primary_color_strong);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
        }

        .selected-summary-list small {
            color: var(--primary_color);
            font-size: 12px;
            font-weight: 700;
        }

        .stage-wrapper {
            position: relative;
            width: 100%;
        }

        .stage-shell {
            position: relative;
            overflow: auto;
            max-height: 78vh;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
        }

        .zoom-controls {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(6px);
            padding: 6px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            pointer-events: auto;
        }

        .zoom-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #ffffff;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .zoom-btn:hover {
            background: var(--primary_color);
            color: #ffffff;
            border-color: var(--primary_color);
        }

        .zoom-btn svg {
            width: 16px;
            height: 16px;
        }

        .stage {
            position: relative;
            transform-origin: 0 0;
            transition: transform 0.2s cubic-bezier(0.25, 1, 0.5, 1);
            min-width: {{ max(720, (int) ($stage['background_width'] ?? 750)) }}px;
            aspect-ratio: {{ (int) ($stage['background_width'] ?? 750) }} / {{ (int) ($stage['background_height'] ?? 550) }};
            line-height: 0;
            background:
                linear-gradient(90deg, rgba(17, 24, 39, 0.05) 1px, transparent 1px),
                linear-gradient(0deg, rgba(17, 24, 39, 0.05) 1px, transparent 1px),
                #f9fafb;
            background-size: 30px 30px;
        }

        .stage img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            pointer-events: none;
            user-select: none;
        }

        .seat {
            position: absolute;
            width: clamp(16px, 2.4vw, 28px);
            height: clamp(16px, 2.4vw, 28px);
            padding: 0 2px;
            transform: translate(-50%, -50%);
            border: 2px solid #d1d5db;
            border-radius: 4px;
            background: #ffffff;
            color: #111827;
            font-size: clamp(8px, 0.95vw, 10px);
            font-weight: 900;
            line-height: calc(clamp(16px, 2.4vw, 28px) - 4px);
            text-align: center;
            box-shadow: 0 2px 2px rgba(17, 24, 39, 0.25);
            z-index: 2;
            cursor: default;
            white-space: nowrap;
            overflow: visible !important;
        }

        .seat.shape-circle {
            border-radius: 50% !important;
            width: clamp(18px, 2.2vw, 30px) !important;
            height: clamp(18px, 2.2vw, 30px) !important;
        }

        .seat.shape-square {
            border-radius: 4px !important;
            width: clamp(18px, 2.2vw, 30px) !important;
            height: clamp(18px, 2.2vw, 30px) !important;
        }

        .seat.shape-rectangle {
            border-radius: 4px !important;
            width: clamp(24px, 3.0vw, 34px) !important;
            height: clamp(17px, 2.1vw, 22px) !important;
            line-height: calc(clamp(17px, 2.1vw, 22px) - 4px) !important;
        }

        .seat.shape-arch,
        .seat.shape-horseshoe {
            border-radius: 14px 14px 4px 4px !important;
            width: clamp(18px, 2.2vw, 30px) !important;
            height: clamp(18px, 2.2vw, 30px) !important;
        }

        .accessible-micro-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #0284c7;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.35);
            border: 1.5px solid #ffffff;
            z-index: 10;
            pointer-events: none;
            line-height: 1;
        }

        .accessible-micro-badge i {
            font-size: 8px;
            color: #ffffff;
        }

        .seat.available {
            border-color: #22c55e;
            background: #22c55e;
            color: #ffffff;
            cursor: pointer;
        }

        .seat.selected {
            border-color: #047857;
            background: #ffffff;
            color: #047857;
            cursor: pointer;
        }

        .seat.held {
            border-color: #f59e0b;
            background: #facc15;
            color: #111827;
        }

        .seat.booked {
            border-color: #ef4444;
            background: #ef4444;
            color: #ffffff;
        }

        .seat.ticket-row-blocked {
            border-color: #6b7280 !important;
            background: #9ca3af !important;
            color: #ffffff !important;
            cursor: not-allowed !important;
        }

        .seat.blocked {
            border-color: #6b7280 !important;
            background: #9ca3af !important;
            color: #ffffff !important;
            cursor: not-allowed !important;
        }

        /* Theatre category (category_id=8) overrides */
        .theatre-seat-map .seat.held {
            border-color: #6b7280 !important;
            background: #9ca3af !important;   /* grey - not available */
            color: #ffffff !important;
            cursor: not-allowed !important;
        }

        .seat[disabled] {
            cursor: not-allowed;
            opacity: 0.72;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 14px;
            color: #4b5563;
            font-size: 12px;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .swatch {
            width: 11px;
            height: 11px;
            flex-shrink: 0;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 50%;
        }

        @media (max-width: 640px) {
            .legend {
                font-size: 10px;
                gap: 5px 8px;
            }
            .swatch {
                width: 9px;
                height: 9px;
            }
        }

        /* Dedicated legend bar above the seat map header */
        .seat-map-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 20px;
            padding: 10px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .seat-map-legend span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .seat-map-legend .swatch {
            width: 15px;
            height: 15px;
        }

        @media (max-width: 640px) {
            .seat-map-legend {
                font-size: 12px;
                gap: 7px 12px;
                padding: 8px 14px;
            }
            .seat-map-legend .swatch {
                width: 12px;
                height: 12px;
            }
        }


        @media (max-width: 640px) {
            .seat-map-header {
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
                padding: 12px;
            }

            .seat-map-title h1 {
                font-size: 16px;
            }

            .seat-map-actions {
                justify-content: space-between;
            }

            /* .ticket-menu {
                flex: 1 1 auto;
            } */

            .ticket-toggle {
                width: 100%;
            }

            .ticket-options {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .ticket-option {
                min-height: 46px;
                padding: 7px 6px;
                font-size: 11px;
                text-align: center;
            }

            .ticket-option small {
                font-size: 10px;
            }
        }

    </style>
</head>
<body>
    @php
        $seats = collect($stage['seats'] ?? []);
        $selected = $seats->where('selected', true)->count();
        $selectedTicketQuantity = (int) ($payload['selected_ticket_quantity'] ?? 0);
        $tickets = collect($payload['tickets'] ?? []);
        $initialActiveTicket = $tickets->firstWhere('selected_quantity', '>', 0) ?: $tickets->first();
        $initialActiveTicketId = (int) ($initialActiveTicket['id'] ?? 0);
    @endphp

    <main class="page">

        {{-- Legend bar: separate section BEFORE the header/title --}}
        @php $isTheatre = !empty($payload['is_theatre']); @endphp
        <section class="seat-map-legend">
            <span><i class="swatch" style="background:#22c55e;"></i>{{ __('Available') }}</span>
            <span><i class="swatch" style="background:#ffffff; border-color:#047857;"></i>{{ __('Selected') }}</span>
            <span><i class="swatch" style="background:#0284c7;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-wheelchair" style="font-size:6px;color:#fff;"></i></i>{{ __('Accessible') }}</span>
            @if($isTheatre)
                <span><i class="swatch" style="background:#9ca3af;"></i>{{ __('Hold/Blocked') }}</span>
            @else
                <span><i class="swatch" style="background:#facc15;"></i>{{ __('Temp. locked') }}</span>
            @endif
        </section>


        <section class="seat-map-header">
            <!-- <div class="seat-map-title">
                <p class="seat-map-kicker">Seat selection</p>
                <h1>{{ $event->name }}</h1>
                <p>{{ $stage['venue_name'] ?? 'Venue' }}</p>
            </div> -->
            <div class="seat-map-actions">
                <span class="selection-chip">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke-width="1.8" stroke-linejoin="round"></path>
                        <path d="M8 9h8" stroke-width="1.8" stroke-linecap="round"></path>
                        <path d="M8 13h5" stroke-width="1.8" stroke-linecap="round"></path>
                    </svg>
                    <strong><span id="selected-count">{{ $selected }}</span>/<span id="selection-limit">{{ max(1, $selectedTicketQuantity ?: 1) }}</span></strong>
                    <span>selected</span>
                </span>
                <span class="venue-pill">{{ $stage['venue_name'] ?? 'Venue' }}</span>
                <div class="ticket-menu">
                    <button type="button" class="ticket-toggle" id="ticket-quantity-toggle" aria-expanded="false" aria-controls="ticket-quantity-modal">
                        
                        <span>Tickets</span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 20h9" stroke-width="2" stroke-linecap="round"></path>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <section class="quantity-modal-backdrop" id="ticket-quantity-modal" hidden>
            <div class="quantity-modal" role="dialog" aria-modal="true" aria-labelledby="ticket-quantity-title">
                <button type="button" class="quantity-modal-close" id="ticket-quantity-close" aria-label="Close ticket quantity">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"></path>
                    </svg>
                </button>

                <div class="quantity-modal-header">
                    <div class="quantity-modal-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 4H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-7 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.5c0-1.99 4-3 6-3s6 1.01 6 3V19z"></path>
                        </svg>
                    </div>
                    <div class="quantity-modal-text">
                        <h2 class="quantity-modal-title" id="ticket-quantity-title">Book Your Seats</h2>
                        <p class="quantity-modal-subtitle">Select number of seats you want to book</p>
                    </div>
                </div>

                <div class="quantity-inner-box">
                    <p class="quantity-box-label">Number of seats</p>
                    <div class="quantity-options">
                        @for($quantityOption = 1; $quantityOption <= 10; $quantityOption++)
                            <button
                                type="button"
                                class="quantity-option {{ max(1, $selectedTicketQuantity ?: 1) === $quantityOption ? 'is-active' : '' }}"
                                data-quantity="{{ $quantityOption }}"
                                aria-pressed="{{ max(1, $selectedTicketQuantity ?: 1) === $quantityOption ? 'true' : 'false' }}">
                                {{ $quantityOption }}
                            </button>
                        @endfor
                    </div>
                    <button type="button" class="quantity-book-button" id="ticket-quantity-book-now">Book Now</button>
                </div>
            </div>
        </section>

        @if($tickets->isNotEmpty())
            <section class="ticket-options" aria-label="Ticket options">
                @foreach($tickets as $ticket)
                    <button
                        type="button"
                        class="ticket-option {{ (int) ($ticket['id'] ?? 0) === $initialActiveTicketId ? 'is-active' : '' }}"
                        data-ticket-option="{{ (int) ($ticket['id'] ?? 0) }}"
                        aria-pressed="{{ (int) ($ticket['id'] ?? 0) === $initialActiveTicketId ? 'true' : 'false' }}">
                        {{ $ticket['name'] ?? 'Ticket' }}
                        <small>{{ $ticket['price'] ?? '' }}</small>
                    </button>
                @endforeach
            </section>
        @endif

        <section class="selected-summary" id="selected-seat-summary" aria-live="polite">
            <p class="selected-summary-title">
                Selected seats
                <span id="selected-seat-total">Total: 0</span>
            </p>
            <ul class="selected-summary-list" id="selected-seat-summary-list"></ul>
        </section>

        <div class="stage-wrapper">
            <div class="zoom-controls">
                <button type="button" class="zoom-btn" id="zoom-in-btn" title="Zoom In">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                </button>
                <button type="button" class="zoom-btn" id="zoom-out-btn" title="Zoom Out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
                </button>
                <button type="button" class="zoom-btn" id="zoom-reset-btn" title="Reset Zoom">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
            </div>
            <section class="stage-shell">
                <div class="stage{{ !empty($payload['is_theatre']) ? ' theatre-seat-map' : '' }}" id="stage-canvas">
                @if(! empty($stage['background_image_url']))
                    <img src="{{ $stage['background_image_url'] }}" alt="{{ $stage['venue_name'] ?? 'Venue map' }}">
                @else
                    <img alt="" src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 750 550%22%3E%3C/svg%3E">
                @endif

                @php
                    $stageSeatShape = $stage['seat_shape'] ?? ($payload['map']['seat_shape'] ?? '');
                    $stageShowRowName = ! empty($stage['show_row_name'] ?? ($payload['map']['show_row_name'] ?? false));
                    $stageLayoutStyle = $stage['layout_style'] ?? ($payload['map']['layout_style'] ?? '');
                    $stageFocalX = (float) ($stage['focal_x'] ?? ($payload['map']['focal_x'] ?? 50.0));
                    $stageFocalY = (float) ($stage['focal_y'] ?? ($payload['map']['focal_y'] ?? 10.0));
                @endphp
                @foreach($seats as $seat)
                    @php
                        $seatStatus = $seat['status'] ?? 'available';
                        $seatTicketId = (int) ($seat['ticket_id'] ?? 0);
                        $seatSelected = ! empty($seat['selected']);
                        $blockedByInitialTicket = $initialActiveTicketId
                            && $seatTicketId > 0
                            && $seatTicketId !== $initialActiveTicketId
                            && $seatStatus === \App\Models\EventVenueSeat::STATUS_AVAILABLE
                            && ! $seatSelected;
                        $activeAvailableTicketSeat = $initialActiveTicketId
                            && $seatTicketId === $initialActiveTicketId
                            && $seatStatus === \App\Models\EventVenueSeat::STATUS_AVAILABLE
                            && ! $seatSelected;
                        $seatBaseSelectable = ! empty($seat['selectable']);
                        $seatSelectable = $seatBaseSelectable && ! $blockedByInitialTicket;
                        $seatClassStatus = $blockedByInitialTicket
                            ? 'ticket-row-blocked'
                            : ($seat['class_status'] ?? $seatStatus);

                        $leftPos = (float) ($seat['x_percent'] ?? 50);
                        $topPos = (float) ($seat['y_percent'] ?? 50);
                        $rotateDeg = 0;
                        if ($stageLayoutStyle === 'curved_rotated') {
                            $diffX = $stageFocalX - $leftPos;
                            $diffY = $topPos - $stageFocalY;
                            $rotateDeg = round(rad2deg(atan2($diffX, $diffY)), 2);
                        }
                    @endphp
                    <button
                        type="button"
                        class="seat {{ $stageSeatShape ? 'shape-' . $stageSeatShape : '' }} {{ $seatClassStatus }} {{ $activeAvailableTicketSeat ? 'ticket-row-active' : '' }}"
                        style="left: {{ $leftPos }}%; top: {{ $topPos }}%; transform: translate(-50%, -50%) rotate({{ $rotateDeg }}deg); --rotate-deg: {{ $rotateDeg }}deg;"
                        title="{{ $seat['title'] ?? $seat['seat_label'] ?? 'Seat' }}"
                        aria-label="{{ $seat['title'] ?? $seat['seat_label'] ?? 'Seat' }}"
                        data-seat-id="{{ (int) ($seat['id'] ?? 0) }}"
                        data-venue-seat-id="{{ (int) ($seat['id'] ?? 0) }}"
                        data-seat-label="{{ $seat['seat_label'] ?? '' }}"
                        data-section-name="{{ $seat['section_name'] ?? '' }}"
                        data-row-name="{{ $seat['row_name'] ?? '' }}"
                        data-seat-number="{{ $seat['seat_number'] ?? '' }}"
                        data-status="{{ $seat['status'] ?? 'available' }}"
                        data-ticket-id="{{ $seat['ticket_id'] ?? '' }}"
                        data-ticket-name="{{ $seat['ticket_name'] ?? '' }}"
                        data-price="{{ $seat['price'] ?? '' }}"
                        data-selected="{{ $seatSelected ? '1' : '0' }}"
                        data-base-selectable="{{ $seatBaseSelectable ? '1' : '0' }}"
                        data-selectable="{{ $seatSelectable ? '1' : '0' }}"
                        @if(! $seatSelectable) disabled @endif>
                        @if(!empty($seat['is_accessible']))
                            <span class="accessible-micro-badge" title="Accessible / Disability Reserved Seat">
                                <i class="fas fa-wheelchair"></i>
                            </span>
                        @endif
                        {{ ($stageShowRowName && !empty($seat['row_name'])) ? ($seat['row_name'] . '-' . $seat['seat_number']) : ($seat['seat_number'] ?? '') }}
                    </button>
                @endforeach
            </div>
        </section>
        </div>



    </main>
    <script>
        (function () {
            const initialMaxSelection = {{ max(1, $selectedTicketQuantity ?: 1) }};
            const stageCanvas = document.getElementById('stage-canvas');
            const zoomInBtn = document.getElementById('zoom-in-btn');
            const zoomOutBtn = document.getElementById('zoom-out-btn');
            const zoomResetBtn = document.getElementById('zoom-reset-btn');
            let currentScale = 1.0;

            function applyZoom(scale) {
                currentScale = Math.max(0.6, Math.min(2.5, scale));
                if (stageCanvas) {
                    stageCanvas.style.transform = `scale(${currentScale})`;
                }
            }

            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', function () {
                    applyZoom(currentScale + 0.25);
                });
            }

            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', function () {
                    applyZoom(currentScale - 0.25);
                });
            }

            if (zoomResetBtn) {
                zoomResetBtn.addEventListener('click', function () {
                    applyZoom(1.0);
                });
            }

            const selectedCount = document.getElementById('selected-count');
            const selectionLimit = document.getElementById('selection-limit');
            const quantityToggle = document.getElementById('ticket-quantity-toggle');
            const quantityModal = document.getElementById('ticket-quantity-modal');
            const quantityClose = document.getElementById('ticket-quantity-close');
            const quantityBookNow = document.getElementById('ticket-quantity-book-now');
            const quantityButtons = Array.from(document.querySelectorAll('.quantity-option'));
            const ticketOptionButtons = Array.from(document.querySelectorAll('.ticket-option'));
            const selectedSeatSummary = document.getElementById('selected-seat-summary');
            const selectedSeatTotal = document.getElementById('selected-seat-total');
            const selectedSeatSummaryList = document.getElementById('selected-seat-summary-list');
            const seats = Array.from(document.querySelectorAll('.seat'));
            const seatById = new Map(seats.map((seat) => [Number(seat.dataset.seatId), seat]));
            const eventId = {{ (int) ($payload['event_id'] ?? $event->id) }};
            const tickets = @json($payload['tickets'] ?? []);
            const queryParams = new URLSearchParams(window.location.search);
            let autoHoldTimer = null;
            let autoHoldInProgress = false;
            let activeTicketId = defaultActiveTicketId();
            let maxSelection = selectedQuantity();

            function selectedQuantity() {
                const activeButton = quantityButtons.find((button) => button.classList.contains('is-active'));
                const quantity = activeButton ? Number(activeButton.dataset.quantity) : initialMaxSelection;

                return Math.max(1, Math.min(10, quantity || 1));
            }

            function setQuantity(quantity) {
                maxSelection = Math.max(1, Math.min(10, Number(quantity) || 1));

                quantityButtons.forEach((button) => {
                    const isActive = Number(button.dataset.quantity) === maxSelection;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            }

            function setQuantityModalOpen(isOpen) {
                quantityModal.toggleAttribute('hidden', !isOpen);
                quantityToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            function defaultActiveTicketId() {
                const selectedTicket = tickets.find((ticket) => Number(ticket.selected_quantity) > 0);
                const ticket = selectedTicket || tickets[0];

                return ticket ? Number(ticket.id) : null;
            }

            function applyTicketRowHighlight() {
                seats.forEach((seat) => {
                    const seatTicketId = Number(seat.dataset.ticketId);
                    const hasSeatTicket = seatTicketId > 0;
                    const isActiveTicketSeat = activeTicketId && seatTicketId === activeTicketId;
                    const baseSelectable = seat.dataset.baseSelectable === '1';
                    const isSelected = seat.dataset.selected === '1';
                    const isAvailable = seat.dataset.status === 'available';
                    const isBlockedByTicket = activeTicketId
                        && hasSeatTicket
                        && !isActiveTicketSeat
                        && isAvailable
                        && !isSelected;
                    const isSelectable = baseSelectable && (isSelected || !activeTicketId || !hasSeatTicket || isActiveTicketSeat);

                    seat.dataset.selectable = isSelectable ? '1' : '0';
                    seat.disabled = !isSelectable;
                    seat.classList.remove('ticket-row-active', 'ticket-row-blocked');

                    if (isBlockedByTicket) {
                        seat.classList.remove('available');
                        seat.classList.add('ticket-row-blocked');
                        return;
                    }

                    if (!isSelected && isAvailable && isSelectable) {
                        seat.classList.add('available');
                    }

                    const shouldHighlight = isActiveTicketSeat && isAvailable && !isSelected;

                    seat.classList.toggle('ticket-row-active', Boolean(shouldHighlight));
                });
            }

            function setActiveTicket(ticketId) {
                activeTicketId = Number(ticketId) || null;

                ticketOptionButtons.forEach((button) => {
                    const isActive = Number(button.dataset.ticketOption) === activeTicketId;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                applyTicketRowHighlight();
            }

            function shouldAutoGuestHold() {
                const autoHoldParam = queryParams.get('auto_hold');

                if (autoHoldParam === '0' || autoHoldParam === 'false') {
                    return false;
                }

                return typeof window.SeatBridge === 'undefined';
            }

            function guestHoldKey() {
                const storageKey = 'teptix_api_guest_hold_key';
                let key = localStorage.getItem(storageKey);

                if (!key) {
                    key = window.crypto && window.crypto.randomUUID
                        ? window.crypto.randomUUID()
                        : 'guest-' + Date.now() + '-' + Math.random().toString(16).slice(2);
                    localStorage.setItem(storageKey, key);
                }

                return key;
            }

            function selectedSeats() {
                return seats.filter((seat) => seat.dataset.selected === '1');
            }

            function selectedSeatIds() {
                return selectedSeatDetails().map((seat) => Number(seat.id));
            }

            function selectedSeatDetails() {
                return selectedSeats().map((seat) => {
                    const venueSeatId = Number(seat.dataset.venueSeatId || seat.dataset.seatId || 0);

                    return {
                        id: venueSeatId,
                        seat_label: seat.dataset.seatLabel || '',
                        section_name: seat.dataset.sectionName || '',
                        row_name: seat.dataset.rowName || '',
                        seat_number: Number(seat.dataset.seatNumber) || null,
                        ticket_id: seat.dataset.ticketId ? Number(seat.dataset.ticketId) : null,
                        ticket_name: seat.dataset.ticketName || null,
                        price: seat.dataset.price !== '' ? Number(seat.dataset.price) : null,
                        status: seat.dataset.status || 'available'
                    };
                });
            }

            function formatSeatPrice(price) {
                if (price === null || price === undefined || price === '') {
                    return 'Price not set';
                }

                const amount = Number(price);

                if (Number.isNaN(amount)) {
                    return String(price);
                }

                return amount.toLocaleString(undefined, {
                    minimumFractionDigits: amount % 1 === 0 ? 0 : 2,
                    maximumFractionDigits: 2
                });
            }

            function renderSelectedSeatSummary() {
                const selected = selectedSeatDetails();

                selectedSeatSummary.classList.toggle('is-visible', selected.length > 0);
                selectedSeatSummaryList.innerHTML = '';
                selectedSeatTotal.textContent = 'Total: $' + formatSeatPrice(
                    selected.reduce((total, seat) => total + (Number(seat.price) || 0), 0)
                );

                selected.forEach((seat) => {
                    const item = document.createElement('li');
                    const label = document.createElement('span');
                    const price = document.createElement('small');
                    const seatLabel = seat.seat_label || [
                        seat.section_name,
                        seat.row_name,
                        seat.seat_number ? 'Seat ' + seat.seat_number : ''
                    ].filter(Boolean).join(' ');

                    label.textContent = seatLabel || 'Seat';
                    price.textContent = 'Price: ' + formatSeatPrice(seat.price);
                    item.append(label, price);
                    selectedSeatSummaryList.appendChild(item);
                });
            }

            function expandedTicketIds() {
                const quantity = selectedQuantity();

                if (!hasExplicitTicketSelection()) {
                    return selectedSeatDetails()
                        .map((seat) => seat.ticket_id ? Number(seat.ticket_id) : null)
                        .filter(Boolean);
                }

                if (tickets.length === 1) {
                    return Array.from({ length: quantity }, () => Number(tickets[0].id));
                }

                return tickets.flatMap((ticket) => {
                    const ticketQuantity = Number(ticket.selected_quantity) || 0;
                    return Array.from({ length: ticketQuantity }, () => Number(ticket.id));
                });
            }

            function hasExplicitTicketSelection() {
                return tickets.some((ticket) => Number(ticket.selected_quantity) > 0);
            }

            function holdRequestBody() {
                return {
                    event_id: eventId,
                    tickets: expandedTicketIds(),
                    venue_seat_ids: selectedSeatIds(),
                    venue_seat_details: selectedSeatDetails(),
                    guest_hold_key: guestHoldKey(),
                    preserve_all_ticket_rows: !hasExplicitTicketSelection()
                };
            }

            function postSelectionBridgeMessage() {
                const selected = selectedSeatDetails();
                const activeTicketValue = Number(activeTicketId || tickets[0]?.id || 0);
                const payload = {
                    event_id: eventId,
                    selected_seats: selected.map((seat) => Number(seat.id)),
                    selected_seat_ids: selected.map((seat) => Number(seat.id)),
                    selected_seat_details: selected,
                    selected_seat_quantity: selected.length,
                    selected_ticket_quantity: selected.length,
                    ticket_id: activeTicketValue,
                    ticket_ids: selected.length > 0
                        ? selected.map((seat) => Number(seat.ticket_id || activeTicketValue)).filter((value) => Number.isFinite(value) && value > 0)
                        : [activeTicketValue].filter((value) => value > 0),
                    requested_quantity: maxSelection,
                    selected_ticket_type_id: activeTicketValue
                };

                const bridgeTargets = [
                    window.SeatBridge,
                    window.parent && window.parent !== window ? window.parent.SeatBridge : null,
                    window.top && window.top !== window ? window.top.SeatBridge : null
                ].filter(Boolean);

                bridgeTargets.forEach((bridge) => {
                    if (bridge && typeof bridge.postMessage === 'function') {
                        bridge.postMessage(JSON.stringify(payload));
                    }
                });
            }

            function renderCount() {
                const selectedSeatCount = selectedSeats().length;

                selectedCount.textContent = selectedSeatCount;
                selectionLimit.textContent = maxSelection;
                renderSelectedSeatSummary();
                updateJsonDumpLocal();
                postSelectionBridgeMessage();
            }

            function seatMapStatusUrl() {
                const url = new URL('/api/user/event-seat-map/' + eventId, window.location.origin);

                queryParams.forEach(function (value, key) {
                    if (!['event_id', 'event', 'token', 'access_token', 'guest_hold_key'].includes(key)) {
                        url.searchParams.append(key, value);
                    }
                });

                url.searchParams.set('guest_hold_key', guestHoldKey());
                url.searchParams.set('quantity', maxSelection);
                url.searchParams.set('_', Date.now());

                return url.toString();
            }

            function updateJsonDumpLocal() {
                const jsonDump = document.getElementById('json-payload-dump');
                if (!jsonDump) return;

                const selected = selectedSeatDetails();
                const ticketMap = {};
                tickets.forEach(function (t) { ticketMap[t.id] = t; });

                let subtotal = 0;
                const lineItems = selected.map(function (s) {
                    const price = Number(s.price) || 0;
                    subtotal += price;
                    return {
                        seat_id: s.id,
                        seat_label: s.seat_label,
                        ticket_name: s.ticket_name || 'N/A',
                        price: price
                    };
                });

                const tax = 0;
                const total = subtotal + tax;

                const localPayload = {
                    event_id: eventId,
                    server_time: new Date().toISOString(),
                    selected_ticket_quantity: selected.length,
                    selected_seat_quantity: selected.length,
                    selected_seats: selected.map(function (s) { return s.id; }),
                    seats: Array.from(seats).map(function (seat) {
                        return {
                            id: Number(seat.dataset.seatId),
                            seat_label: seat.dataset.seatLabel || '',
                            section_name: seat.dataset.sectionName || '',
                            row_name: seat.dataset.rowName || '',
                            seat_number: Number(seat.dataset.seatNumber) || null,
                            ticket_id: seat.dataset.ticketId ? Number(seat.dataset.ticketId) : null,
                            ticket_name: seat.dataset.ticketName || null,
                            price: seat.dataset.price !== '' ? Number(seat.dataset.price) : null,
                            status: seat.dataset.status || 'available',
                            class_status: seat.dataset.selected === '1' ? 'selected' : (seat.dataset.status || 'available'),
                            selectable: seat.dataset.selectable === '1',
                            disabled: seat.disabled,
                            selected: seat.dataset.selected === '1',
                            title: seat.title || seat.dataset.seatLabel || ''
                        };
                    }),
                    tickets: tickets,
                    totals: {
                        currency: 'USD',
                        quantity: selected.length,
                        subtotal: subtotal,
                        tax: tax,
                        total: total,
                        tax_details: [],
                        line_items: lineItems
                    }
                };

                jsonDump.textContent = JSON.stringify(localPayload, null, 4);
            }

            function applySeatStatus(seat, apiSeat) {
                const localSelected = seat.dataset.selected === '1';
                const selected = Boolean(apiSeat.selected) || (localSelected && apiSeat.selectable);
                const classStatus = selected ? 'selected' : (apiSeat.class_status || apiSeat.status || 'available');

                seat.dataset.status = apiSeat.status || 'available';
                seat.dataset.selected = selected ? '1' : '0';
                seat.dataset.baseSelectable = apiSeat.selectable ? '1' : '0';
                seat.dataset.selectable = apiSeat.selectable ? '1' : '0';
                seat.dataset.ticketId = apiSeat.ticket_id || '';
                seat.dataset.ticketName = apiSeat.ticket_name || '';
                seat.dataset.price = apiSeat.price ?? '';
                seat.dataset.seatLabel = apiSeat.seat_label || seat.dataset.seatLabel || '';
                seat.dataset.sectionName = apiSeat.section_name || '';
                seat.dataset.rowName = apiSeat.row_name || '';
                seat.dataset.seatNumber = apiSeat.seat_number || seat.dataset.seatNumber || '';
                seat.disabled = !apiSeat.selectable;

                if (apiSeat.title) {
                    seat.title = apiSeat.title;
                    seat.setAttribute('aria-label', apiSeat.title);
                }

                seat.classList.remove('available', 'selected', 'held', 'booked', 'blocked', 'ticket-row-active', 'ticket-row-blocked', 'theatre-unconnected');
                seat.classList.add(classStatus);
            }

            function updateJsonDump(data) {
                const jsonDump = document.getElementById('json-payload-dump');
                if (jsonDump && data && data.data) {
                    jsonDump.textContent = JSON.stringify(data.data, null, 4);
                }
            }

            async function refreshSeatStatuses() {
                if (autoHoldInProgress) {
                    return;
                }

                try {
                    const response = await fetch(seatMapStatusUrl(), {
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
                        },
                        cache: 'no-store'
                    });
                    const data = await response.json();
                    updateJsonDump(data);
                    const apiSeats = data && data.data && Array.isArray(data.data.seats) ? data.data.seats : [];

                    apiSeats.forEach(function (apiSeat) {
                        const seat = seatById.get(Number(apiSeat.id));
                        if (seat) {
                            applySeatStatus(seat, apiSeat);
                        }
                    });

                    applyTicketRowHighlight();
                    renderCount();
                } catch (error) {
                    // Keep the current rendered map if the live status refresh fails.
                }
            }

            function setSelected(seat, isSelected) {
                seat.dataset.selected = isSelected ? '1' : '0';
                seat.classList.remove('available', 'selected', 'held', 'booked', 'blocked', 'ticket-row-active', 'ticket-row-blocked', 'theatre-unconnected');

                if (isSelected) {
                    seat.classList.add('selected');
                } else {
                    seat.classList.add(seat.dataset.status || 'available');
                }
            }

            function deselectAllSeats() {
                selectedSeats().forEach((seat) => setSelected(seat, false));
            }

            function sameRowSeats(anchorSeat) {
                return seats
                    .filter((seat) => {
                        return seat.dataset.sectionName === anchorSeat.dataset.sectionName
                            && seat.dataset.rowName === anchorSeat.dataset.rowName
                            && seat.dataset.ticketId === anchorSeat.dataset.ticketId;
                    })
                    .sort((left, right) => {
                        const leftX = parseFloat(left.style.left) || 0;
                        const rightX = parseFloat(right.style.left) || 0;

                        if (leftX !== rightX) {
                            return leftX - rightX;
                        }

                        return (Number(left.dataset.seatNumber) || 0) - (Number(right.dataset.seatNumber) || 0);
                    });
            }

            function seatCanBeSelectedInSequence(seat) {
                return seat.dataset.selectable === '1'
                    && !seat.disabled
                    && (seat.dataset.selected === '1' || seat.dataset.status === 'available');
            }

            function adjacentSeatSelection(anchorSeat, selectionLimit) {
                const rowSeats = sameRowSeats(anchorSeat);
                const anchorIndex = rowSeats.indexOf(anchorSeat);
                const nextSeats = [];

                if (selectionLimit <= 0) {
                    return [];
                }

                if (anchorIndex === -1) {
                    return [anchorSeat];
                }

                for (const seat of rowSeats.slice(anchorIndex)) {
                    if (!seatCanBeSelectedInSequence(seat)) {
                        break;
                    }

                    nextSeats.push(seat);

                    if (nextSeats.length >= selectionLimit) {
                        break;
                    }
                }

                return nextSeats;
            }

            function selectSeatGroup(anchorSeat) {
                const remainingSelection = Math.max(0, maxSelection - selectedSeats().length);
                const nextSeats = adjacentSeatSelection(anchorSeat, remainingSelection);

                if (nextSeats.length === 0) {
                    return false;
                }

                nextSeats.forEach((seat) => setSelected(seat, true));

                return true;
            }

            function applySeatMapPayload(payload) {
                const apiSeats = payload && Array.isArray(payload.seats) ? payload.seats : [];

                apiSeats.forEach(function (apiSeat) {
                    const seat = seatById.get(Number(apiSeat.id));
                    if (seat) {
                        applySeatStatus(seat, apiSeat);
                    }
                });

                applyTicketRowHighlight();
                renderCount();
            }

            function scheduleAutoHold() {
                if (!shouldAutoGuestHold()) {
                    return;
                }

                window.clearTimeout(autoHoldTimer);
                autoHoldTimer = window.setTimeout(autoHoldSelectedSeats, 150);
            }

            async function autoHoldSelectedSeats() {
                if (!shouldAutoGuestHold()) {
                    return;
                }

                const body = holdRequestBody();

                autoHoldInProgress = true;

                try {
                    const response = await fetch('/api/user/event-seat-map/guest-hold', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'Cache-Control': 'no-cache'
                        },
                        cache: 'no-store',
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();

                    if (data && data.data) {
                        updateJsonDump(data);
                        applySeatMapPayload(data.data);
                    }
                } catch (error) {
                    // Keep the current selected seats visible if auto hold fails.
                } finally {
                    autoHoldInProgress = false;
                }
            }

            seats.forEach(function (seat) {
                seat.addEventListener('click', function () {
                    if (seat.dataset.selectable !== '1' || seat.disabled) {
                        return;
                    }

                    const isSelected = seat.dataset.selected === '1';

                    if (isSelected) {
                        setSelected(seat, false);
                    } else if (!selectSeatGroup(seat)) {
                        return;
                    }

                    applyTicketRowHighlight();
                    renderCount();
                    scheduleAutoHold();
                });
            });

            quantityToggle.addEventListener('click', function () {
                setQuantityModalOpen(quantityModal.hasAttribute('hidden'));
            });

            quantityClose.addEventListener('click', function () {
                setQuantityModalOpen(false);
            });

            quantityBookNow.addEventListener('click', function () {
                setQuantityModalOpen(false);
            });

            quantityModal.addEventListener('click', function (event) {
                if (event.target === quantityModal) {
                    setQuantityModalOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !quantityModal.hasAttribute('hidden')) {
                    setQuantityModalOpen(false);
                }
            });

            quantityButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setQuantity(button.dataset.quantity);
                    deselectAllSeats();
                    renderCount();
                    scheduleAutoHold();
                });
            });

            ticketOptionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const ticketId = Number(button.dataset.ticketOption);

                    if (activeTicketId !== ticketId) {
                        setActiveTicket(ticketId);
                        renderCount();
                        return;
                    }

                    setActiveTicket(ticketId);
                });
            });

            setQuantity(initialMaxSelection);
            setActiveTicket(activeTicketId);
            applyTicketRowHighlight();

            renderCount();
            refreshSeatStatuses();
            window.setInterval(refreshSeatStatuses, 2000);
        })();
    </script>
</body>
</html>
