<style>
    .venue-seat-modal-inner {
        padding: 20px;
    }

    .venue-seat-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 18px;
        padding-right: 40px;
    }

    .venue-seat-header-icon {
        flex: none;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.28);
    }

    .venue-seat-title {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 22px;
        color: #0f172a;
        margin: 0;
        line-height: 1.25;
    }

    .venue-seat-subtitle {
        font-family: 'Poppins', sans-serif;
        color: #64748b;
        margin: 4px 0 0;
        font-size: 14px;
    }

    .venue-seat-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        align-items: start;
    }

    .venue-seat-main {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 18px;
        min-width: 0;
        border: 1px solid #f1f5f9;
    }

    .venue-seat-sidebar {
        min-width: 0;
    }

    .venue-seat-summary {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 20px;
        color: #334155;
        border: 1px solid #f1f5f9;
    }

    .venue-seat-summary-head {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 15px;
        color: #0f172a;
        margin: 0 0 14px;
    }

    .venue-seat-summary-head i {
        color: #10b981;
        font-size: 14px;
    }

    .venue-seat-ticket-list {
        list-style: none;
        margin: 0 0 14px;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .venue-seat-ticket-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 8px;
        padding: 8px 10px;
    }

    .venue-seat-ticket-list li span:last-child {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    .venue-seat-limit {
        font-size: 13px;
        color: #64748b;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        margin-bottom: 14px;
    }

    .venue-seat-count-badge {
        display: inline-block;
        max-width: 100%;
        font-weight: 700;
        font-size: 13px;
        line-height: 1.4;
        color: #b45309;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 14px;
        padding: 8px 14px;
        margin-bottom: 16px;
    }

    .seat-map-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .seat-map-toolbar-hint {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
    }

    .seat-map-toolbar-hint i {
        font-size: 11px;
        color: #94a3b8;
    }

    .seat-map-zoom-controls {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        background: #0f172a;
        border-radius: 999px;
        padding: 4px;
        margin-left: auto;
    }

    .seat-zoom-btn {
        min-width: 30px;
        height: 30px;
        border: none;
        border-radius: 999px;
        background: transparent;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        padding: 0 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .seat-zoom-btn:hover {
        background: rgba(255, 255, 255, 0.18);
    }

    .seat-zoom-btn:active {
        background: rgba(255, 255, 255, 0.3);
    }

    .seat-map-stage,
    .frontend-seat-map-shell {
        position: relative;
        overflow: auto;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: #f8f9fa;
        max-height: 56vh;
        display: flex;
        justify-content: center;
    }

    .seat-map-canvas,
    .frontend-seat-map-stage {
        position: relative;
        width: 900px;
        min-width: 320px;
        flex: none;
        line-height: 0;
        transition: width 0.2s ease, min-width 0.2s ease;
    }

    .seat-map-canvas img,
    .frontend-seat-map-stage img {
        display: block;
        width: 100%;
        height: auto;
        user-select: none;
        pointer-events: none;
    }

    .frontend-seat-map-stage.no-background {
        background:
            linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px),
            linear-gradient(0deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px),
            #fafafa;
        background-size: 30px 30px;
    }

    .seat-section-guide {
        position: absolute;
        top: 8%;
        bottom: 8%;
        border: none;
        background: transparent;
        pointer-events: none;
        z-index: 1;
    }

    .seat-section-guide span {
        display: none;
    }

    .seat-row-label {
        position: absolute;
        color: #34395e;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 2;
    }

    .frontend-row-label {
        position: absolute;
        transform: translate(-50%, -50%) !important;
        width: 19px;
        height: 19px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 800;
        color: #475569;
        background: #f1f5f9;
        border: 1.5px solid #ffffff !important;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.15), inset 0 -2px 0 rgba(15, 23, 42, 0.06);
        pointer-events: none;
        white-space: nowrap;
        z-index: 10;
        line-height: 1;
    }

    .seat-section-guide {
        background: rgba(15, 23, 42, 0.025) !important;
        border-radius: 14px !important;
    }

    .frontend-seat-dot {
        position: absolute;
        width: 16px;
        height: 14px;
        padding: 0 !important;
        box-sizing: border-box;
        border-radius: 16% 16% 50% 50% / 22% 22% 65% 65%;
        color: #ffffff;
        font-size: 0;
        font-weight: 700;
        line-height: 14px;
        text-align: center;
        transform: translate(-50%, -50%) !important;
        border: 1.25px solid rgba(255, 255, 255, 0.6);
        box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.14), 0 2px 5px rgba(15, 23, 42, 0.28), inset 0 -1px 0 rgba(255, 255, 255, 0.4);
        cursor: pointer;
        z-index: 3;
        transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease;
    }

    .seat-leg {
        position: absolute;
        top: -3px;
        width: 3px;
        height: 4px;
        background: inherit;
        border: inherit;
        border-bottom: none;
        box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.14);
        border-radius: 3px 3px 0 0;
        pointer-events: none;
        z-index: -1;
    }

    .seat-leg-l { left: 1px; }
    .seat-leg-r { right: 1px; }

    .frontend-seat-dot.shape-circle,
    .frontend-seat-dot.shape-square,
    .frontend-seat-dot.shape-arch,
    .frontend-seat-dot.shape-horseshoe {
        width: 17px !important;
        height: 15px !important;
        line-height: 15px !important;
    }

    .frontend-seat-dot.shape-rectangle {
        width: 20px !important;
        height: 13px !important;
        line-height: 13px !important;
    }

    .frontend-seat-dot:hover:not(:disabled) {
        transform: translate(-50%, -50%) scale(1.25) rotate(-4deg) !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.35);
        z-index: 10;
    }

    .frontend-seat-dot:focus-visible {
        outline: 2px solid #0f172a;
        outline-offset: 2px;
    }

    .frontend-seat-dot[data-seat-price]:not([data-seat-price=""]):hover::after {
        content: attr(data-seat-price);
        position: absolute;
        left: 50%;
        top: auto;
        right: auto;
        bottom: calc(100% + 8px);
        width: max-content;
        height: auto;
        transform: translateX(-50%);
        padding: 4px 6px;
        border: none;
        border-radius: 4px;
        background: rgba(15, 23, 42, 0.92);
        color: #ffffff;
        font-size: 10px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.3);
        pointer-events: none;
        z-index: 20;
    }

    @media (hover: none) {
        .frontend-seat-dot:hover:not(:disabled) {
            transform: translate(-50%, -50%) !important;
        }
    }

    .frontend-seat-dot.available {
        background: #10b981;
        color: #ffffff;
    }
    .frontend-seat-dot.blocked { background: #94a3b8; color: #ffffff; cursor: not-allowed; box-shadow: none; }
    .frontend-seat-dot.held {
        background: #f59e0b;
        color: #111827;
        cursor: not-allowed;
    }
    .frontend-seat-dot.booked { background: #ef4444; color: #ffffff; cursor: not-allowed; box-shadow: none; }
    .frontend-seat-dot.selected {
        background: #2563eb;
        color: #ffffff;
        box-shadow: inset 0 3px 0 rgba(0, 0, 0, 0.18), inset 0 -1px 0 rgba(255, 255, 255, 0.4), 0 0 0 3px rgba(37, 99, 235, 0.28), 0 4px 10px rgba(15, 23, 42, 0.28);
    }

    .seat-check-icon {
        display: none;
        position: absolute;
        top: -6px;
        left: -6px;
        width: 13px;
        height: 13px;
        background: #0f172a;
        color: #ffffff;
        border: 1.5px solid #ffffff;
        border-radius: 50%;
        font-size: 7px;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
        z-index: 11;
        pointer-events: none;
    }

    .frontend-seat-dot.selected .seat-check-icon {
        display: flex;
    }

    /* Theatre category (category_id=8) overrides */
    .theatre-seat-map .frontend-seat-dot.held {
        background: #94a3b8;
        color: #ffffff;
        cursor: not-allowed;
        box-shadow: none;
    }
    .theatre-seat-map .frontend-seat-dot.theatre-unconnected {
        background: #ef4444;
        color: #ffffff;
        cursor: not-allowed;
        box-shadow: none;
    }

    .frontend-seat-dot.is-syncing {
        opacity: 0.65;
        pointer-events: none;
    }

    .accessible-micro-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background: #0284c7;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 7px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
        border: 1.5px solid #ffffff;
        z-index: 10;
        pointer-events: none;
        line-height: 1;
    }

    .frontend-seat-map-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
    }

    .frontend-seat-map-legend span.legend-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 6px 12px;
    }

    .frontend-seat-map-legend i {
        width: 13px;
        height: 11px;
        border-radius: 50% 50% 16% 16% / 65% 65% 22% 22%;
        display: inline-block;
    }

    .book-seat-button {
        width: 100%;
        border: none;
        border-radius: 10px;
        background: #ef4444;
        color: #ffffff;
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        font-weight: 700;
        padding: 14px 18px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background 0.2s ease, transform 0.15s ease;
    }

    .book-seat-button:hover:not(:disabled) {
        background: #dc2626;
    }

    .book-seat-button:active:not(:disabled) {
        transform: translateY(1px);
    }

    .book-seat-button:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }

    @media (min-width: 992px) {
        .venue-seat-layout {
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 24px;
        }
    }

    @media (max-width: 640px) {
        .venue-seat-modal-inner {
            padding: 14px;
        }

        .venue-seat-main {
            padding: 12px;
        }

        .venue-seat-summary {
            padding: 14px 16px;
        }

        .venue-seat-ticket-list {
            display: none;
        }

        .venue-seat-limit {
            display: none;
        }

        .venue-seat-summary-head {
            margin-bottom: 10px;
        }

        .venue-seat-count-badge {
            margin-bottom: 10px;
        }

        .seat-map-toolbar-hint span {
            display: none;
        }

        .frontend-seat-map-stage {
            min-width: 320px;
        }

        .frontend-seat-dot,
        .frontend-seat-dot.shape-circle,
        .frontend-seat-dot.shape-square,
        .frontend-seat-dot.shape-arch,
        .frontend-seat-dot.shape-horseshoe {
            width: 14px !important;
            height: 12px !important;
            font-size: 0;
            line-height: 12px !important;
        }

        .frontend-seat-dot.shape-rectangle {
            width: 18px !important;
            height: 11px !important;
            font-size: 0;
            line-height: 11px !important;
        }

        .seat-leg {
            width: 2px;
            height: 3px;
            top: -3px;
        }

        .frontend-row-label {
            width: 16px;
            height: 16px;
            font-size: 9px;
        }

        .seat-row-prefix {
            display: none !important;
        }

        .venue-seat-title {
            font-size: 19px;
        }

        .venue-seat-header-icon {
            width: 36px;
            height: 36px;
            font-size: 15px;
        }
    }
</style>

<div class="venue-seat-modal-inner">
    <form method="post" action="{{ url('checkout') }}" id="venue-seat-checkout-form">
        @csrf
        <div id="venue-seat-hidden-fields"></div>

        <div class="venue-seat-header">
            <span class="venue-seat-header-icon"><i class="fas fa-chair"></i></span>
            <div>
                <p class="venue-seat-title">{{ $data->name }}</p>
                <p class="venue-seat-subtitle">{{ __('Select available seats, then book seat to continue checkout.') }}</p>
            </div>
        </div>

        @php
            $maximumOrderLimit = $selectedTickets->sum(function ($ticket) {
                return max(1, (int) $ticket->ticket_per_order);
            });
        @endphp

        <div class="venue-seat-layout">
            <div class="venue-seat-main">
                @php
                    $backgroundImage = optional($liveVenueMap->template)->background_image;

                    $sectionLayouts = [
                        'left' => ['label' => 'Left Section', 'left' => 6, 'width' => 25, 'label_left' => 4, 'base_top' => 10, 'row_gap' => 4.6, 'tilt' => 1.8],
                        'center' => ['label' => 'Center Section', 'left' => 36, 'width' => 28, 'label_left' => 35, 'base_top' => 12, 'row_gap' => 4.6, 'tilt' => 0],
                        'right' => ['label' => 'Right Section', 'left' => 69, 'width' => 25, 'label_left' => 66, 'base_top' => 10, 'row_gap' => 4.6, 'tilt' => -1.8],
                    ];
                    $sectionLayoutKey = function ($sectionName) {
                        $sectionName = strtolower((string) $sectionName);

                        if (strpos($sectionName, 'left') !== false) {
                            return 'left';
                        }

                        if (strpos($sectionName, 'right') !== false) {
                            return 'right';
                        }

                        return 'center';
                    };
                    $rowSeatCounts = $venueSeatMapSeats->groupBy('venue_map_row_id')->map->count();
                    $seatsByLayout = $venueSeatMapSeats->groupBy(function ($layoutSeat) use ($sectionLayoutKey) {
                        return $sectionLayoutKey($layoutSeat->section_name);
                    });
                    $rowIndexesBySection = [];
                    $seatFallbackPositions = [];
                    $seatRowLabels = [];

                    foreach ($seatsByLayout as $layoutKey => $layoutSeats) {
                        $rowIndexesBySection[$layoutKey] = $layoutSeats
                            ->groupBy('venue_map_row_id')
                            ->sortKeys()
                            ->map(function ($group) {
                                return strtoupper(trim($group->first()->row_name ?: 'A'));
                            })
                            ->keys()
                            ->values()
                            ->flip()
                            ->all();
                    }

                    foreach ($venueSeatMapSeats as $layoutSeat) {
                        $layoutKey = $sectionLayoutKey($layoutSeat->section_name ?: 'Center Section');
                        $rowId = $layoutSeat->venue_map_row_id;
                        $rowIndex = $rowIndexesBySection[$layoutKey][$rowId] ?? 0;
                        $rowCount = max(1, (int) ($rowSeatCounts[$rowId] ?? 1));
                        $seatIndex = max(0, ((int) $layoutSeat->seat_number) - 1);
                        $layout = $sectionLayouts[$layoutKey];
                        $progress = $rowCount === 1 ? 0.5 : ($seatIndex / max(1, $rowCount - 1));
                        $normalized = ($progress - 0.5) * 2;
                        $left = $layout['left'] + ($progress * $layout['width']);
                        $top = $layout['base_top'] + ($rowIndex * $layout['row_gap']);

                        if ($layoutKey === 'center') {
                            $top += (1 - abs($normalized)) * 1.4;
                        } else {
                            $top += $normalized * $layout['tilt'];
                        }

                        $seatFallbackPositions[$layoutSeat->id] = [
                            'left' => round(max(1, min(99, $left)), 5),
                            'top' => round(max(2, min(96, $top)), 5),
                        ];

                        if (!isset($seatRowLabels[$layoutKey][$rowId])) {
                            $seatRowLabels[$layoutKey][$rowId] = [
                                'label' => $layoutSeat->row_name ?: __('Row'),
                                'top' => round($top, 5),
                            ];
                        }
                    }
                @endphp
                @php
                    $frontendAisleRowLabels = [];
                    if (!empty($venueSeatMapSeats) && count($venueSeatMapSeats) > 0) {
                        $templateObj = optional($liveVenueMap->template);
                        $layoutStyle = $templateObj->layout_style;
                        $focalX = (float) ($templateObj->focal_x ?? 50.0);
                        $focalY = (float) ($templateObj->focal_y ?? 10.0);

                        $groupedByRow = collect($venueSeatMapSeats)->groupBy(function ($s) {
                            return trim($s->row_name ?: (optional($s->row)->name ?: ''));
                        });

                        foreach ($groupedByRow as $rowName => $rowSeats) {
                            if (!$rowName) continue;

                            $leftSeats = $rowSeats->filter(function ($s) {
                                $sec = strtolower((string) $s->section_name);
                                return strpos($sec, 'left') !== false || (float) $s->x_percent < 33;
                            });
                            $centerSeats = $rowSeats->filter(function ($s) {
                                $sec = strtolower((string) $s->section_name);
                                return strpos($sec, 'center') !== false || (strpos($sec, 'left') === false && strpos($sec, 'right') === false && (float) $s->x_percent >= 33 && (float) $s->x_percent <= 67);
                            });
                            $rightSeats = $rowSeats->filter(function ($s) {
                                $sec = strtolower((string) $s->section_name);
                                return strpos($sec, 'right') !== false || (float) $s->x_percent > 67;
                            });

                            $hasLeftCenter = false;
                            $x1 = null;
                            if ($leftSeats->count() > 0 && $centerSeats->count() > 0) {
                                $x1 = ($leftSeats->max(fn($s) => (float) $s->x_percent) + $centerSeats->min(fn($s) => (float) $s->x_percent)) / 2;
                                $hasLeftCenter = true;
                            } elseif ($leftSeats->count() > 0) {
                                $x1 = $leftSeats->max(fn($s) => (float) $s->x_percent) + 3.2;
                                $hasLeftCenter = true;
                            }

                            $hasCenterRight = false;
                            $x2 = null;
                            if ($centerSeats->count() > 0 && $rightSeats->count() > 0) {
                                $x2 = ($centerSeats->max(fn($s) => (float) $s->x_percent) + $rightSeats->min(fn($s) => (float) $s->x_percent)) / 2;
                                $hasCenterRight = true;
                            } elseif ($rightSeats->count() > 0) {
                                $x2 = $rightSeats->min(fn($s) => (float) $s->x_percent) - 3.2;
                                $hasCenterRight = true;
                            }

                            $y = (float) $rowSeats->avg(fn($s) => (float) $s->y_percent);

                            if ($hasLeftCenter && $x1 !== null) {
                                $frontendAisleRowLabels[] = ['name' => $rowName, 'x' => round($x1, 3), 'y' => round($y, 3), 'rot' => 0];
                            }
                            if ($hasCenterRight && $x2 !== null) {
                                $frontendAisleRowLabels[] = ['name' => $rowName, 'x' => round($x2, 3), 'y' => round($y, 3), 'rot' => 0];
                            }
                        }
                    }
                @endphp
                @php $isTheatreCategory = (int) $data->category_id === 8; @endphp

                <div class="seat-map-toolbar">
                    <span class="seat-map-toolbar-hint"><i class="fas fa-arrows-alt"></i> <span>{{ __('Drag to pan the map') }}</span></span>
                    <div class="seat-map-zoom-controls" role="group" aria-label="{{ __('Zoom seat map') }}">
                        <button type="button" class="seat-zoom-btn" id="seatZoomOut" aria-label="{{ __('Zoom out') }}"><i class="fas fa-minus"></i></button>
                        <button type="button" class="seat-zoom-btn" id="seatZoomReset" aria-label="{{ __('Reset zoom') }}">{{ __('Reset') }}</button>
                        <button type="button" class="seat-zoom-btn" id="seatZoomIn" aria-label="{{ __('Zoom in') }}"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <div class="seat-map-stage frontend-seat-map-shell {{ $isTheatreCategory ? 'theatre-seat-map' : '' }}">
                    <div class="seat-map-canvas frontend-seat-map-stage {{ $backgroundImage ? '' : 'no-background' }}">
                        @if($backgroundImage)
                            <img src="{{ url('images/upload/' . $backgroundImage) }}" alt="{{ __('Venue map') }}">
                        @endif

                        @foreach($frontendAisleRowLabels as $rLabel)
                            <span class="frontend-row-label" style="left: {{ $rLabel['x'] }}%; top: {{ $rLabel['y'] }}%; transform: translate(-50%, -50%) rotate({{ $rLabel['rot'] }}deg); --rotate-deg: {{ $rLabel['rot'] }}deg;">
                                {{ $rLabel['name'] }}
                            </span>
                        @endforeach

                        @foreach ($sectionLayouts as $layoutKey => $layout)
                            <div class="seat-section-guide" style="left: {{ $layout['left'] - 2 }}%; width: {{ $layout['width'] + 4 }}%;"></div>
                        @endforeach

                        @foreach($venueSeatMapSeats as $venueSeat)
                            @php
                                $seatTicketId = (int) ($venueSeat->ticket_id ?? 0);
                                $status = $venueSeat->status ?: 'available';
                                $ticket = $venueSeat->ticket;
                                if ($ticket && (int) $ticket->allow_to_user === 0) {
                                    $status = \App\Models\EventVenueSeat::STATUS_BOOKED;
                                }

                                $isUnconnectedSeat = $isTheatreCategory && $seatTicketId === 0;

                                $isHeldByCurrentSession = $status === \App\Models\EventVenueSeat::STATUS_HELD
                                    && $venueSeat->held_by_session_id === session()->getId()
                                    && $venueSeat->hold_expires_at
                                    && $venueSeat->hold_expires_at->isFuture();
                                $isAvailable = ($status === \App\Models\EventVenueSeat::STATUS_AVAILABLE) && !$isUnconnectedSeat;
                                $isAvailableForCurrentUser = $isAvailable || $isHeldByCurrentSession;
                                $matchesSelectedTicket = !$venueMapUsesTicketRows || ($seatTicketId > 0 && in_array($seatTicketId, $selectedTicketIds, true));
                                $isSelectable = $isAvailableForCurrentUser && $matchesSelectedTicket && !$isUnconnectedSeat;

                                if ($isTheatreCategory) {
                                    $seatClassStatus = $isUnconnectedSeat
                                        ? 'theatre-unconnected'
                                        : ($isHeldByCurrentSession
                                            ? \App\Models\EventVenueSeat::STATUS_AVAILABLE
                                            : (!$isSelectable && $isAvailable
                                                ? \App\Models\EventVenueSeat::STATUS_BLOCKED
                                                : $status));
                                } else {
                                    $seatClassStatus = $isHeldByCurrentSession
                                        ? \App\Models\EventVenueSeat::STATUS_AVAILABLE
                                        : (!$isSelectable && $isAvailable
                                            ? \App\Models\EventVenueSeat::STATUS_BLOCKED
                                            : $status);
                                }

                                $fallback = $seatFallbackPositions[$venueSeat->id] ?? ['left' => 50, 'top' => 50];
                                $leftPos = $venueSeat->x_percent !== null ? $venueSeat->x_percent : $fallback['left'];
                                $topPos = $venueSeat->y_percent !== null ? $venueSeat->y_percent : $fallback['top'];
                                $seatLabel = $venueSeat->seat_label ?: trim($venueSeat->section_name . ' ' . $venueSeat->row_name . ' Seat ' . $venueSeat->seat_number);
                                $seatPriceLabel = null;

                                if ($venueSeat->ticket) {
                                    $seatPriceLabel = $venueSeat->ticket->type === 'paid'
                                        ? (($currency->currency_sybmol ?? '$') . number_format($venueSeat->ticket->price, 2))
                                        : __('Free');
                                }

                                $availabilityLabel = 'Available';
                                if ($isHeldByCurrentSession) {
                                    $availabilityLabel = 'Selected';
                                } elseif ($status === 'booked') {
                                    $availabilityLabel = 'Sold';
                                } elseif ($status === 'held') {
                                    $availabilityLabel = 'Held';
                                } elseif ($status === 'blocked') {
                                    $availabilityLabel = 'Blocked';
                                }

                                $parts = [];
                                $parts[] = $seatLabel;
                                if ($seatPriceLabel !== null) {
                                    $parts[] = $seatPriceLabel;
                                }
                                $parts[] = $availabilityLabel;

                                $seatTitle = implode(' - ', $parts);
                                $seatHoverPrice = $seatTitle . ($venueSeat->is_accessible ? ' (' . __('Accessible / Wheelchair Reserved') . ')' : '');
                                $ariaLabel = "Row " . $venueSeat->row_name . ", Seat " . $venueSeat->seat_number . ", " . $availabilityLabel;

                                $templateObj = optional($liveVenueMap->template);
                                $layoutStyle = $templateObj->layout_style;
                                $focalX = (float) ($templateObj->focal_x ?? 50.0);
                                $focalY = (float) ($templateObj->focal_y ?? 10.0);
                                $rotateDeg = 0;
                                if ($layoutStyle === 'curved_rotated') {
                                    $diffX = $focalX - (float) $leftPos;
                                    $diffY = (float) $topPos - $focalY;
                                    $rotateDeg = round(rad2deg(atan2($diffX, $diffY)), 2);
                                }
                            @endphp
                            <button
                                type="button"
                                class="frontend-seat-dot {{ optional($liveVenueMap->template)->seat_shape ? 'shape-' . optional($liveVenueMap->template)->seat_shape : '' }} {{ $seatClassStatus }} {{ $isHeldByCurrentSession && $isSelectable ? 'selected' : '' }} {{ $venueSeat->is_accessible ? 'is-accessible' : '' }}"
                                style="left: {{ (float) $leftPos }}%; top: {{ (float) $topPos }}%; transform: translate(-50%, -50%) rotate({{ $rotateDeg }}deg); --rotate-deg: {{ $rotateDeg }}deg;"
                                data-seat-id="{{ $venueSeat->id }}"
                                data-seat-label="{{ $seatLabel }}"
                                data-section="{{ $venueSeat->section_name }}"
                                data-row="{{ $venueSeat->row_name }}"
                                data-seat-number="{{ $venueSeat->seat_number }}"
                                data-ticket-id="{{ $venueSeat->ticket_id }}"
                                data-seat-price="{{ $seatHoverPrice }}"
                                data-raw-price="{{ $seatPriceLabel }}"
                                data-selectable="{{ $isSelectable ? '1' : '0' }}"
                                data-initial-selected="{{ $isHeldByCurrentSession && $isSelectable ? '1' : '0' }}"
                                title=""
                                aria-label="{{ $ariaLabel }}"
                                {{ $isSelectable ? '' : 'disabled' }}>
                                <span class="seat-leg seat-leg-l" aria-hidden="true"></span>
                                <span class="seat-leg seat-leg-r" aria-hidden="true"></span>
                                @if($venueSeat->is_accessible)
                                    <span class="accessible-micro-badge" title="{{ __('Accessible / Disability Reserved Seat') }}">
                                        <i class="fas fa-wheelchair"></i>
                                    </span>
                                @endif
                                <i class="fas fa-check seat-check-icon" aria-hidden="true"></i>
                                @if(optional($liveVenueMap->template)->show_row_name && !empty($venueSeat->row_name))<span class="seat-row-prefix">{{ $venueSeat->row_name }}-</span>@endif{{ $venueSeat->seat_number }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="frontend-seat-map-legend">
                    <span class="legend-chip"><i style="background:#10b981;"></i>{{ __('Available') }}</span>
                    <span class="legend-chip"><i style="background:#2563eb;"></i>{{ __('Selected') }}</span>
                    <span class="legend-chip"><i style="background:#0284c7;border-radius:50%;width:12px;height:12px;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-wheelchair" style="font-size:8px;color:#ffffff;"></i></i>{{ __('Accessible (Wheelchair)') }}</span>
                    @if($isTheatreCategory)
                        <span class="legend-chip"><i style="background:#94a3b8;"></i>{{ __('Hold / Blocked') }}</span>
                        <span class="legend-chip"><i style="background:#ef4444;"></i>{{ __('Booked / Unavailable') }}</span>
                    @else
                        <span class="legend-chip"><i style="background:#f59e0b;"></i>{{ __('Temporarily locked') }}</span>
                        <span class="legend-chip"><i style="background:#ef4444;"></i>{{ __('Sold') }}</span>
                        <span class="legend-chip"><i style="background:#94a3b8;"></i>{{ __('Blocked') }}</span>
                    @endif
                </div>
            </div>

            <aside class="venue-seat-sidebar">
                <div class="venue-seat-summary">
                    <p class="venue-seat-summary-head"><i class="fas fa-ticket-alt"></i>{{ __('Selected Tickets') }}</p>
                    <ul class="venue-seat-ticket-list">
                        @foreach($selectedTickets as $ticket)
                            <li>
                                <span>{{ $ticket->name }}</span>
                                <span>{{ $ticket->type === 'paid' ? ($currency->currency_sybmol ?? '$') . number_format($ticket->price, 2) : __('Free') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="venue-seat-limit">
                        {{ __('Maximum order limit') }}: <strong>{{ $maximumOrderLimit }}</strong>
                    </div>
                    <div class="venue-seat-count-badge" id="venue-seat-selected-count">{{ __('0 selected') }}</div>
                    <button type="submit" id="book-seat-button" class="book-seat-button">
                        {{ __('Book Seat') }}
                    </button>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
(function () {
    if (window.__venueSeatMapPoll) {
        clearInterval(window.__venueSeatMapPoll);
        window.__venueSeatMapPoll = null;
    }

    const selectedTicketIds = @json($selectedTicketIds);
    const selectedTicketLimits = @json($selectedTickets->mapWithKeys(function ($ticket) {
        return [(int) $ticket->id => max(1, (int) $ticket->ticket_per_order)];
    }));
    const venueMapUsesTicketRows = @json($venueMapUsesTicketRows);
    const isTheatreCategory = @json($isTheatreCategory);
    const selectedVenueSeats = [];
    const selectedCount = document.getElementById('venue-seat-selected-count');
    const submitButton = document.getElementById('book-seat-button');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    const liveSeatStatusUrl = '{{ route("event.venueSeatStatuses", ["id" => $data->id]) }}';
    let seatHoldSyncInProgress = false;

    function seatDataFromButton(button) {
        return {
            id: button.dataset.seatId,
            label: button.dataset.seatLabel,
            section: button.dataset.section,
            row: button.dataset.row,
            seat_number: button.dataset.seatNumber,
            ticket_id: button.dataset.ticketId
        };
    }

    function replaceSelectedVenueSeats(seats) {
        selectedVenueSeats.splice(0, selectedVenueSeats.length);
        seats.forEach(function (seat) {
            selectedVenueSeats.push(seat);
        });
        refreshSelectedCount();
    }

    function setSeatHoldSyncing(isSyncing) {
        seatHoldSyncInProgress = isSyncing;
        document.querySelectorAll('.frontend-seat-dot[data-selectable="1"]').forEach(function (seatButton) {
            seatButton.classList.toggle('is-syncing', isSyncing);
        });
        if (submitButton) {
            submitButton.disabled = isSyncing;
        }
    }

    function seatButtonById(seatId) {
        return Array.from(document.querySelectorAll('.frontend-seat-dot')).find(function (seatButton) {
            return seatButton.dataset.seatId === String(seatId);
        });
    }

    function ticketIdForSeat(seat) {
        const seatTicketId = parseInt(seat.ticket_id, 10);
        if (seatTicketId > 0) {
            return seatTicketId;
        }

        if (selectedTicketIds.length === 1) {
            return parseInt(selectedTicketIds[0], 10);
        }

        return null;
    }

    function ticketIdsForCheckout() {
        return selectedVenueSeats.map(ticketIdForSeat).filter(function (ticketId) {
            return ticketId;
        });
    }

    function exceedsTicketLimit(seats) {
        const counts = {};

        for (const seat of seats) {
            const ticketId = ticketIdForSeat(seat);
            if (!ticketId) {
                return true;
            }

            counts[ticketId] = (counts[ticketId] || 0) + 1;
            const limit = parseInt(selectedTicketLimits[ticketId], 10) || 1;
            if (counts[ticketId] > limit) {
                return true;
            }
        }

        return false;
    }

    function refreshSelectedCount() {
        if (!selectedCount) {
            return;
        }

        if (selectedVenueSeats.length === 0) {
            selectedCount.textContent = '{{ __("0 selected") }}';
            return;
        }

        var codes = selectedVenueSeats.map(function (seat) {
            if (seat.row && seat.seat_number) {
                return seat.row + '-' + seat.seat_number;
            }
            return seat.label || '';
        }).filter(Boolean);

        var suffix = selectedVenueSeats.length === 1 ? '{{ __("seat selected") }}' : '{{ __("seats selected") }}';
        selectedCount.textContent = codes.join(', ') + ' ' + suffix;
    }

    function syncVenueSeatHolds(seats) {
        return fetch('{{ route("storeSessionTickets") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
            },
            body: JSON.stringify({
                multitickets: seats.map(ticketIdForSeat).filter(function (ticketId) { return ticketId; }),
                venue_seat_ids: seats.map(function (seat) { return seat.id; }),
                venue_seat_details: seats
            })
        })
        .then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok || !data.success) {
                    throw new Error(data.message || '{{ __("Unable to hold selected seats. Please try again.") }}');
                }

                return data;
            });
        });
    }

    function updateSelectedSeatClasses() {
        const selectedIds = selectedVenueSeats.map(function (seat) {
            return seat.id;
        });

        document.querySelectorAll('.frontend-seat-dot').forEach(function (seatButton) {
            seatButton.classList.toggle('selected', selectedIds.includes(seatButton.dataset.seatId));
        });
    }

    function liveSeatStatusRequestUrl() {
        const params = new URLSearchParams();
        selectedTicketIds.forEach(function (ticketId) {
            params.append('tickets[]', ticketId);
        });

        return liveSeatStatusUrl + (params.toString() ? '?' + params.toString() : '');
    }

    function replaceSeatStatusClass(seatButton, classStatus) {
        seatButton.classList.remove('available', 'held', 'blocked', 'booked', 'selected');
        seatButton.classList.add(classStatus);
    }

    function sameSeatIds(firstSeats, secondSeats) {
        const firstIds = firstSeats.map(function (seat) { return String(seat.id); }).sort();
        const secondIds = secondSeats.map(function (seat) { return String(seat.id); }).sort();

        return firstIds.length === secondIds.length && firstIds.every(function (id, index) {
            return id === secondIds[index];
        });
    }

    function applyLiveSeatStatuses(statuses) {
        if (seatHoldSyncInProgress) {
            return;
        }

        const currentHoldSeats = [];

        statuses.forEach(function (seatStatus) {
            const seatButton = seatButtonById(seatStatus.id);
            if (!seatButton) {
                return;
            }

            replaceSeatStatusClass(seatButton, seatStatus.class_status);
            seatButton.dataset.selectable = seatStatus.selectable ? '1' : '0';
            seatButton.disabled = Boolean(seatStatus.disabled);

            if (seatStatus.title) {
                seatButton.title = '';

                var statusText = seatStatus.status || 'available';
                var availabilityLabel = 'Available';
                if (seatStatus.held_by_current_session) {
                    availabilityLabel = 'Selected';
                } else if (statusText === 'booked') {
                    availabilityLabel = 'Sold';
                } else if (statusText === 'held') {
                    availabilityLabel = 'Held';
                } else if (statusText === 'blocked') {
                    availabilityLabel = 'Blocked';
                }
                var seatAriaLabel = 'Row ' + (seatButton.dataset.row || '') + ', Seat ' + (seatButton.dataset.seatNumber || '') + ', ' + availabilityLabel;
                seatButton.setAttribute('aria-label', seatAriaLabel);

                var rawPrice = seatButton.dataset.rawPrice;
                var seatLabel = seatButton.dataset.seatLabel;
                var parts = [seatLabel];
                if (rawPrice && rawPrice !== 'null' && rawPrice !== '') {
                    parts.push(rawPrice);
                }
                parts.push(availabilityLabel);

                var isAccessible = seatButton.classList.contains('is-accessible');
                var seatHoverText = parts.join(' - ');
                if (isAccessible) {
                    seatHoverText += ' (Accessible / Wheelchair Reserved)';
                }
                seatButton.dataset.seatPrice = seatHoverText;
            }

            if (seatStatus.held_by_current_session && seatStatus.selectable) {
                currentHoldSeats.push(seatDataFromButton(seatButton));
            }
        });

        if (!sameSeatIds(selectedVenueSeats, currentHoldSeats)) {
            replaceSelectedVenueSeats(currentHoldSeats);
        }

        updateSelectedSeatClasses();
    }

    function refreshLiveSeatStatuses() {
        if (seatHoldSyncInProgress) {
            return;
        }

        fetch(liveSeatStatusRequestUrl(), {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success && Array.isArray(data.seats)) {
                applyLiveSeatStatuses(data.seats);
            }
        })
        .catch(function () {
            // Keep the current map state if a live refresh fails.
        });
    }

    document.querySelectorAll('.frontend-seat-dot[data-initial-selected="1"]').forEach(function (seatButton) {
        selectedVenueSeats.push(seatDataFromButton(seatButton));
    });
    updateSelectedSeatClasses();
    refreshSelectedCount();

    document.querySelectorAll('.frontend-seat-dot').forEach(function (seatButton) {
        seatButton.addEventListener('click', function () {
            if (seatHoldSyncInProgress || this.dataset.selectable !== '1' || this.disabled) {
                return;
            }

            const seatId = this.dataset.seatId;
            const existingIndex = selectedVenueSeats.findIndex(function (seat) {
                return seat.id === seatId;
            });
            let nextSelectedSeats;

            if (existingIndex >= 0) {
                nextSelectedSeats = selectedVenueSeats.filter(function (seat) {
                    return seat.id !== seatId;
                });
            } else {
                const nextSeat = seatDataFromButton(this);

                if (exceedsTicketLimit(selectedVenueSeats.concat([nextSeat]))) {
                    alert('{{ __("You cannot select more seats than the selected ticket allows per order.") }}');
                    return;
                }

                nextSelectedSeats = selectedVenueSeats.concat([nextSeat]);
            }

            const previousSelectedSeats = selectedVenueSeats.slice();
            replaceSelectedVenueSeats(nextSelectedSeats);
            updateSelectedSeatClasses();
            setSeatHoldSyncing(true);
            syncVenueSeatHolds(nextSelectedSeats)
                .then(function () {
                    replaceSelectedVenueSeats(nextSelectedSeats);
                    updateSelectedSeatClasses();
                })
                .catch(function (error) {
                    replaceSelectedVenueSeats(previousSelectedSeats);
                    updateSelectedSeatClasses();
                    alert(error.message || '{{ __("Unable to hold selected seats. Please try again.") }}');
                })
                .finally(function () {
                    setSeatHoldSyncing(false);
                });
        });
    });

    const seatCheckoutForm = document.getElementById('venue-seat-checkout-form');
    if (seatCheckoutForm) {
        seatCheckoutForm.addEventListener('submit', function (event) {
            if (seatHoldSyncInProgress) {
                event.preventDefault();
                return false;
            }

            if (selectedVenueSeats.length === 0) {
                event.preventDefault();
                alert('{{ __("Please select at least one available seat.") }}');
                return false;
            }

            if (exceedsTicketLimit(selectedVenueSeats)) {
                event.preventDefault();
                alert('{{ __("You cannot select more seats than the selected ticket allows per order.") }}');
                return false;
            }

            const checkoutTicketIds = ticketIdsForCheckout();
            if (checkoutTicketIds.length !== selectedVenueSeats.length) {
                event.preventDefault();
                alert('{{ __("Selected seats do not match the selected ticket.") }}');
                return false;
            }

            event.preventDefault();
            setSeatHoldSyncing(true);
            syncVenueSeatHolds(selectedVenueSeats)
                .then(function () {
                    if (window.__venueSeatMapPoll) {
                        clearInterval(window.__venueSeatMapPoll);
                        window.__venueSeatMapPoll = null;
                    }
                    // loadCheckoutIntoModal() already shows its own error inline in the
                    // modal body on failure - don't let that rejection fall through to
                    // the seat-hold-sync catch below and pop a confusing alert on top of it.
                    return window.loadCheckoutIntoModal().catch(function () {});
                })
                .catch(function (error) {
                    alert((error && error.message) || '{{ __("Unable to hold selected seats. Please try again.") }}');
                })
                .finally(function () {
                    setSeatHoldSyncing(false);
                });

            return false;
        });
    }

    window.__venueSeatMapPoll = window.setInterval(refreshLiveSeatStatuses, 2000);
    refreshLiveSeatStatuses();

    // Seat map zoom controls (additive - purely visual, does not affect seat selection logic above).
    const stageEl = document.querySelector('.seat-map-stage');
    const canvasEl = document.querySelector('.seat-map-canvas');
    const zoomInBtn = document.getElementById('seatZoomIn');
    const zoomOutBtn = document.getElementById('seatZoomOut');
    const zoomResetBtn = document.getElementById('seatZoomReset');

    if (stageEl && canvasEl) {
        const naturalWidth = 900;
        const minUsableWidth = 320;
        const maxScale = 2;
        const step = 0.25;

        // Fit the whole map inside the stage on first load - zoomed out (never zoomed further
        // in than 100%), centered via the stage's flex centering. The user can zoom in from here.
        const availableWidth = Math.max(minUsableWidth, stageEl.clientWidth - 4);
        const fitScale = Math.min(1, Math.round((availableWidth / naturalWidth) * 100) / 100);
        const minScale = fitScale;
        let scale = fitScale;

        function applyZoom() {
            const width = Math.round(naturalWidth * scale);
            canvasEl.style.width = width + 'px';
            canvasEl.style.minWidth = width + 'px';
        }

        applyZoom();

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function () {
                scale = Math.min(maxScale, Math.round((scale + step) * 100) / 100);
                applyZoom();
            });
        }

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function () {
                scale = Math.max(minScale, Math.round((scale - step) * 100) / 100);
                applyZoom();
            });
        }

        if (zoomResetBtn) {
            zoomResetBtn.addEventListener('click', function () {
                scale = fitScale;
                applyZoom();
                stageEl.scrollLeft = 0;
            });
        }
    }
})();
</script>
