@extends('master')

@push('css')
    <style>
        .venue-map-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .venue-map-stat {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            background: #ffffff;
        }

        .venue-map-stat span {
            display: block;
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .venue-map-stat strong {
            color: #34395e;
            font-size: 20px;
            line-height: 1;
        }

        .seat-map-stage {
            position: relative;
            overflow: auto;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            background: #f8f9fa;
            max-height: 72vh;
        }

        .seat-map-canvas {
            position: relative;
            min-width: 720px;
            line-height: 0;
        }

        .seat-map-canvas img {
            display: block;
            width: 100%;
            height: auto;
            user-select: none;
            pointer-events: none;
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

        .seat-dot {
            position: absolute;
            width: clamp(18px, 2.2vw, 28px);
            height: clamp(18px, 2.2vw, 28px);
            padding: 0 !important;
            box-sizing: border-box;
            border: 2px solid #d1d5db;
            border-radius: 5px;
            background: #ffffff;
            color: #111827;
            font-size: clamp(9px, 1.1vw, 10px);
            font-weight: 700;
            line-height: calc(clamp(18px, 2.2vw, 28px) - 4px);
            text-align: center;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            cursor: grab;
            z-index: 3;
            touch-action: none;
        }

        @media (max-width: 575.98px) {
            .seat-map-canvas {
                min-width: 520px;
            }

            .seat-dot {
                width: 22px;
                height: 22px;
                font-size: 10px;
                line-height: 18px;
            }
        }

        .seat-dot.shape-circle {
            border-radius: 50% !important;
        }

        .seat-dot.shape-square {
            border-radius: 4px !important;
        }

        .seat-dot.shape-rectangle {
            border-radius: 4px !important;
            width: clamp(24px, 3.0vw, 36px) !important;
            height: clamp(17px, 2.1vw, 24px) !important;
            line-height: calc(clamp(17px, 2.1vw, 24px) - 4px) !important;
        }

        .seat-dot.shape-arch,
        .seat-dot.shape-horseshoe {
            border-radius: 14px 14px 4px 4px !important;
            width: clamp(20px, 2.5vw, 32px) !important;
            height: clamp(20px, 2.5vw, 32px) !important;
        }

        .focal-point-marker {
            position: absolute;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #6f42c1, #4e2a84);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.45), 0 4px 14px rgba(0, 0, 0, 0.35);
            z-index: 25;
            white-space: nowrap;
            letter-spacing: 0.5px;
            border: 2px solid #ffffff;
            cursor: grab;
            user-select: none;
            touch-action: none;
            transition: box-shadow 0.15s ease, background 0.15s ease;
        }

        .focal-point-marker:hover {
            background: linear-gradient(135deg, #7e4ccf, #5a32a3);
            box-shadow: 0 0 0 5px rgba(111, 66, 193, 0.55), 0 6px 18px rgba(0, 0, 0, 0.4);
        }

        .focal-point-marker:active {
            cursor: grabbing;
            background: linear-gradient(135deg, #5a32a3, #3b1d66);
            box-shadow: 0 0 0 6px rgba(111, 66, 193, 0.7), 0 8px 22px rgba(0, 0, 0, 0.5);
        }

        .seat-dot.unplotted {
            background: #f59f00;
        }

        .seat-dot.dirty {
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.35), 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        .seat-dot.selected {
            outline: 3px solid #4c6ef5;
            outline-offset: 2px;
            box-shadow: 0 0 0 5px rgba(76, 110, 245, 0.22), 0 2px 8px rgba(0, 0, 0, 0.25);
            z-index: 4;
        }

        .seat-dot.locked {
            cursor: default;
            background: #6c757d;
        }

        .accessible-badge {
            font-size: 8px;
            margin-right: 1px;
        }

        .seat-dot:active {
            cursor: grabbing;
        }

        .seat-map-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .seat-map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            color: #6c757d;
        }

        .seat-map-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: flex-end;
        }

        .seat-selection-status {
            color: #6c757d;
            font-size: 12px;
            line-height: 1;
        }

        .seat-selection-box {
            position: absolute;
            border: 1px solid #4c6ef5;
            background: rgba(76, 110, 245, 0.14);
            pointer-events: none;
            z-index: 5;
        }

        .legend-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: -1px;
        }

        .section-list {
            max-height: 72vh;
            overflow: auto;
        }

        .seat-row-item {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 16px 16px 0;
            background: #fbfbfc;
        }

        .section-row-edit {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 12px;
            background: #fbfbfc;
        }
    </style>
@endpush

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Edit Venue Seat Map'),
            'headerData' => __('Venue Seat Maps'),
            'url' => 'venue-seat-maps',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ $errors->first() }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <div id="seat-map-message" class="alert d-none" role="alert"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-7">
                                    <h2 class="section-title mt-0">
                                        {{ $template->venue->name }}
                                        @if ($template->name)
                                            <small class="text-muted">- {{ $template->name }}</small>
                                        @endif
                                    </h2>
                                </div>
                                <div class="col-lg-5 text-right">
                                    @if ($template->status !== 'published')
                                        <button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-1" data-toggle="modal" data-target="#editTemplateSettingsModal">
                                            <i class="fas fa-cog"></i> {{ __('Edit Template Options') }}
                                        </button>
                                    @endif
                                    <span class="badge {{ $template->status === 'published' ? 'badge-success' : 'badge-warning' }} p-2">
                                        {{ ucfirst($template->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="venue-map-summary">
                                <div class="venue-map-stat">
                                    <span>{{ __('Version') }}</span>
                                    <strong>{{ $template->version }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Numbering Mode') }}</span>
                                    <strong>{{ $template->numbering_mode === 'continuous' ? __('Continuous') : __('Section-wise') }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Counting Direction') }}</span>
                                    <strong>{{ $template->counting_direction === 'right_to_left' ? __('Right to Left') : __('Left to Right') }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Layout Display') }}</span>
                                    <strong>{{ $template->layout_style === 'straight' ? __('Straight Line') : ($template->layout_style === 'curved_rotated' ? __('Curved (Rotated Seats)') : __('Curved')) }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Display Row Name') }}</span>
                                    <strong>{{ $template->show_row_name ? __('Yes') : __('No') }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Seat Shape') }}</span>
                                    <strong>{{ ucfirst($template->seat_shape) }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Focal Point (X, Y)') }}</span>
                                    <strong>{{ $template->focal_x ?? 50 }}%, {{ $template->focal_y ?? 10 }}%</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Expected Seats') }}</span>
                                    <strong>{{ $template->expected_seat_count }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Generated Seats') }}</span>
                                    <strong id="generated-seat-count">{{ $totalSeats }}</strong>
                                </div>
                                <div class="venue-map-stat">
                                    <span>{{ __('Plotted Seats') }}</span>
                                    <strong id="plotted-seat-count">{{ $plottedSeats }}</strong>
                                </div>
                            </div>

                            @if ($template->status !== 'published')
                                <form method="post" action="{{ route('admin.venue-maps.background.update', $template) }}" enctype="multipart/form-data" class="mt-4">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-lg-8">
                                            <div class="form-group mb-lg-0">
                                                <label>{{ __('Edit Background Map Image') }}</label>
                                                <input type="file" name="background_image" accept="image/*" class="form-control @error('background_image') is-invalid @enderror" required>
                                                @error('background_image')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-image"></i> {{ __('Update Background') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-info mt-4 mb-0">
                                    {{ __('To edit the background image, click Keep Draft first.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($template->status !== 'published')
                @php
                    $sectionOptions = collect([
                        'Left Section',
                        'Center Section',
                        'Right Section',
                    ])->merge($template->sections->pluck('name'))->unique()->values();
                    $seatRows = old('rows', [[
                        'section_name' => 'Left Section',
                        'section_code' => '',
                        'row_name' => '',
                        'seat_count' => '',
                    ]]);
                    $remainingSeatCount = max(0, (int) $template->expected_seat_count - (int) $totalSeats);
                    $newRowSeatMax = min(1000, $remainingSeatCount);
                @endphp
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if ($remainingSeatCount <= 0)
                                    <div class="alert alert-info">
                                        {{ __('Expected seat count has already been reached. Edit existing rows to change seats.') }}
                                    </div>
                                @endif
                                <form method="post" action="{{ route('admin.venue-maps.sections.store', $template) }}" id="generate-seat-form" data-remaining-seats="{{ $remainingSeatCount }}">
                                    @csrf
                                    <div id="seat-row-list">
                                        @foreach ($seatRows as $rowIndex => $seatRow)
                                            <div class="seat-row-item">
                                                <div class="row align-items-end">
                                                    <div class="col-lg-5">
                                                        <div class="form-group">
                                                            <label>{{ __('Section Name') }}</label>
                                                            <select name="rows[{{ $rowIndex }}][section_name]" class="form-control select2" required>
                                                                <option value="">{{ __('Select Section') }}</option>
                                                                @foreach ($sectionOptions as $sectionName)
                                                                    <option value="{{ $sectionName }}" {{ ($seatRow['section_name'] ?? '') === $sectionName ? 'selected' : '' }}>
                                                                        {{ $sectionName }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <div class="form-group">
                                                            <label>{{ __('Row Name') }}</label>
                                                            <input type="text" name="rows[{{ $rowIndex }}][row_name]" value="{{ $seatRow['row_name'] ?? '' }}" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <div class="form-group">
                                                            <label>{{ __('Seat Count') }}</label>
                                                            <input type="number" min="1" max="{{ $newRowSeatMax }}" name="rows[{{ $rowIndex }}][seat_count]" value="{{ $seatRow['seat_count'] ?? '' }}" class="form-control" {{ $remainingSeatCount <= 0 ? 'disabled' : 'required' }}>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-1">
                                                        <div class="form-group">
                                                            <button type="button" class="btn btn-danger btn-block remove-seat-row" title="{{ __('Remove Row') }}">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                                        <button type="button" id="add-seat-row" class="btn btn-light mb-2" {{ $remainingSeatCount <= 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-plus"></i> {{ __('Add More') }}
                                        </button>
                                        <button type="submit" class="btn btn-primary mb-2" {{ $remainingSeatCount <= 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-save"></i> {{ __('Generate Seats') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="seat-map-toolbar">
                                <div class="seat-map-legend">
                                    <span><i class="legend-dot" style="background:#ffffff;border:1px solid #d1d5db"></i>{{ __('Plotted') }}</span>
                                    <span><i class="legend-dot" style="background:#f59f00"></i>{{ __('Unplotted') }}</span>
                                    <span><i class="legend-dot" style="background:#007bff"></i>{{ __('Unsaved') }}</span>
                                    <span><i class="legend-dot" style="background:#4c6ef5"></i>{{ __('Selected') }}</span>
                                    <span><i class="legend-dot" style="background:#0284c7;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-wheelchair" style="font-size:8px;color:#ffffff;"></i></i>{{ __('Accessible (Wheelchair)') }}</span>
                                </div>
                                <div class="seat-map-actions">
                                    @if ($template->status !== 'published')
                                        <span id="seat-selection-count" class="seat-selection-status">{{ __('0 selected') }}</span>
                                        <button type="button" id="toggle-accessible-seats" class="btn btn-outline-info btn-sm" disabled title="{{ __('Reserve / Toggle Wheelchair Disability Seat for selected seats') }}">
                                            <i class="fas fa-wheelchair"></i> {{ __('Reserve Accessible') }}
                                        </button>
                                        <button type="button" id="clear-seat-selection" class="btn btn-light btn-sm" disabled>
                                            <i class="fas fa-times"></i> {{ __('Clear') }}
                                        </button>
                                        <button type="button" id="save-seat-coordinates" class="btn btn-primary">
                                            <i class="fas fa-save"></i> {{ __('Save Coordinates') }}
                                        </button>
                                        <form method="post" action="{{ route('admin.venue-maps.auto-arrange', $template) }}" class="d-inline" onsubmit="return confirm('{{ __('Auto arrange all seats into curved rows? This will replace current draft seat positions.') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-info">
                                                <i class="fas fa-magic"></i> {{ __('Auto Arrange Seats') }}
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('admin.venue-maps.publish', $template) }}" class="d-inline" onsubmit="return confirm('{{ __('Publish this template? Published templates cannot be modified.') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i> {{ __('Publish Template') }}
                                            </button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('admin.venue-maps.keep-draft', $template) }}" class="d-inline" onsubmit="return confirm('{{ __('Move this published template back to draft so it can be edited?') }}')">
                                            @csrf
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-edit"></i> {{ __('Keep Draft') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="seat-map-stage">
                                @php
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
                                    $rowSeatCounts = $seats->groupBy('venue_map_row_id')->map->count();
                                    $seatsByLayout = $seats->groupBy(function ($layoutSeat) use ($sectionLayoutKey) {
                                        return $sectionLayoutKey(optional($layoutSeat->section)->name);
                                    });
                                    $rowIndexesBySection = [];
                                    $seatFallbackPositions = [];
                                    $seatRowLabels = [];

                                    foreach ($seatsByLayout as $layoutKey => $layoutSeats) {
                                        $rowIndexesBySection[$layoutKey] = $layoutSeats
                                            ->groupBy('venue_map_row_id')
                                            ->map(function ($group) {
                                                return strtoupper(trim(optional($group->first()->row)->name ?: 'A'));
                                            })
                                            ->sort()
                                            ->keys()
                                            ->values()
                                            ->flip()
                                            ->all();
                                    }

                                    $localSeatIndices = [];
                                    foreach ($seats->groupBy('venue_map_row_id') as $rowId => $groupSeats) {
                                        $sortedGroup = $groupSeats->sortBy('sort_order')->sortBy('id')->values();
                                        foreach ($sortedGroup as $idx => $s) {
                                            $localSeatIndices[$s->id] = $idx;
                                        }
                                    }

                                    foreach ($seats as $layoutSeat) {
                                        $layoutKey = $sectionLayoutKey(optional($layoutSeat->section)->name ?: 'Center Section');
                                        $rowId = $layoutSeat->venue_map_row_id;

                                        $rowIndex = $rowIndexesBySection[$layoutKey][$rowId] ?? 0;
                                        $rowCount = max(1, (int) ($rowSeatCounts[$rowId] ?? 1));
                                        $seatIndex = $localSeatIndices[$layoutSeat->id] ?? 0;
                                        $layout = $sectionLayouts[$layoutKey];
                                        $progress = $rowCount === 1 ? 0.5 : ($seatIndex / max(1, $rowCount - 1));
                                        $normalized = ($progress - 0.5) * 2;
                                        $left = $layout['left'] + ($progress * $layout['width']);
                                        $top = $layout['base_top'] + ($rowIndex * $layout['row_gap']);

                                        if ($template->layout_style !== 'straight') {
                                            if ($layoutKey === 'center') {
                                                $top += (1 - abs($normalized)) * 1.4;
                                            } else {
                                                $top += $normalized * $layout['tilt'];
                                            }
                                        }

                                        $left = max(1, min(99, $left));
                                        $top = max(2, min(96, $top));

                                        $seatFallbackPositions[$layoutSeat->id] = [
                                            'left' => round($left, 5),
                                            'top' => round($top, 5),
                                        ];

                                        if (!isset($seatRowLabels[$layoutKey][$rowId])) {
                                            $seatRowLabels[$layoutKey][$rowId] = [
                                                'label' => optional($layoutSeat->row)->name ?: __('Row'),
                                                'top' => round($top, 5),
                                            ];
                                        }
                                    }
                                @endphp
                                <div id="seat-map-canvas" class="seat-map-canvas" data-locked="{{ $template->status === 'published' ? '1' : '0' }}">
                                    <img src="{{ url('images/upload/' . $template->background_image) }}" alt="{{ $template->venue->name }}">
                                    @if ($template->layout_style === 'curved_rotated')
                                        <div id="focal-point-marker" class="focal-point-marker" style="left: {{ $template->focal_x ?? 50 }}%; top: {{ $template->focal_y ?? 10 }}%;" title="{{ __('Stage Focal Point (Rotation Focus)') }}">
                                            <i class="fas fa-crosshairs mr-1"></i> {{ __('STAGE FOCAL POINT') }} ({{ $template->focal_x ?? 50 }}%, {{ $template->focal_y ?? 10 }}%)
                                        </div>
                                    @endif
                                    @foreach ($sectionLayouts as $layoutKey => $layout)
                                        <div class="seat-section-guide" style="left: {{ $layout['left'] - 2 }}%; width: {{ $layout['width'] + 4 }}%;"></div>

                                        @foreach (($seatRowLabels[$layoutKey] ?? []) as $rowLabel)
                                            <span class="seat-row-label" style="left: {{ $layout['label_left'] }}%; top: {{ $rowLabel['top'] }}%;">
                                                {{ $rowLabel['label'] }}
                                            </span>
                                        @endforeach
                                    @endforeach

                                    @foreach ($seats as $seat)
                                        @php
                                            $isPlotted = $seat->x_percent !== null && $seat->y_percent !== null;
                                            $fallbackPosition = $seatFallbackPositions[$seat->id] ?? ['left' => 50, 'top' => 50];
                                            $left = $isPlotted ? $seat->x_percent : $fallbackPosition['left'];
                                            $top = $isPlotted ? $seat->y_percent : $fallbackPosition['top'];

                                            $focalX = (float) ($template->focal_x ?? 50.0);
                                            $focalY = (float) ($template->focal_y ?? 10.0);
                                            $rotateDeg = 0;
                                            if ($template->layout_style === 'curved_rotated') {
                                                $diffX = $focalX - $left;
                                                $diffY = $top - $focalY;
                                                $rotateDeg = round(rad2deg(atan2($diffX, $diffY)), 2);
                                            }
                                        @endphp
                                        <button
                                            type="button"
                                            class="seat-dot {{ $template->seat_shape ? 'shape-'.$template->seat_shape : '' }} {{ $isPlotted ? '' : 'unplotted' }} {{ $template->status === 'published' ? 'locked' : '' }} {{ $seat->is_accessible ? 'is-accessible' : '' }}"
                                            data-seat-id="{{ $seat->id }}"
                                            data-plotted="{{ $isPlotted ? '1' : '0' }}"
                                            data-dirty="0"
                                            data-is-accessible="{{ $seat->is_accessible ? '1' : '0' }}"
                                            title="{{ $seat->seat_label }}{{ $seat->is_accessible ? ' (' . __('Accessible / Wheelchair Reserved') . ')' : '' }}"
                                            style="left: {{ $left }}%; top: {{ $top }}%; transform: translate(-50%, -50%) rotate({{ $rotateDeg }}deg); --rotate-deg: {{ $rotateDeg }}deg;"
                                        >@if($seat->is_accessible)<i class="fas fa-wheelchair accessible-badge"></i>@endif{{ $template->show_row_name ? (optional($seat->row)->name . $seat->seat_number) : $seat->seat_number }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body section-list">
                            <h4 class="section-title mt-0">{{ __('Sections') }}</h4>
                            @forelse ($template->sections as $section)
                                <div class="mb-4">
                                    <h6 class="mb-2 d-flex justify-content-between align-items-center">
                                        <span>{{ $section->name }}</span>
                                        @if ($section->code)
                                            <span class="badge badge-light text-muted font-weight-normal ml-1" style="font-size: 10px;">{{ $section->code }}</span>
                                        @endif
                                    </h6>
                                    @if ($template->status !== 'published')
                                        @foreach ($section->rows as $row)
                                            <form method="post" action="{{ route('admin.venue-maps.rows.update', [$template, $row]) }}" class="section-row-edit">
                                                @csrf
                                                <div class="form-group mb-2">
                                                    <label>{{ __('Section Name') }}</label>
                                                    <input type="text" name="section_name" value="{{ $section->name }}" list="section-options-{{ $row->id }}" class="form-control form-control-sm" required>
                                                    <datalist id="section-options-{{ $row->id }}">
                                                        @foreach ($sectionOptions as $sectionName)
                                                            <option value="{{ $sectionName }}">
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                                <div class="form-row align-items-end">
                                                    <div class="form-group col-5 mb-0">
                                                        <label>{{ __('Row Name') }}</label>
                                                        <input type="text" name="row_name" value="{{ $row->name }}" class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="form-group col-4 mb-0">
                                                        <label>{{ __('Seats') }}</label>
                                                        <input type="number" min="1" max="{{ min(1000, ((int) $template->expected_seat_count - ((int) $totalSeats - (int) $row->seat_count))) }}" name="seat_count" value="{{ $row->seat_count }}" class="form-control form-control-sm" required>
                                                    </div>
                                                    <div class="form-group col-3 mb-0">
                                                        <label>{{ __('Start #') }}</label>
                                                        <input type="number" min="1" max="99999" name="start_seat_number" value="{{ $row->start_seat_number ?: 1 }}" class="form-control form-control-sm" required title="{{ __('Starting seat number for this row (e.g. 15)') }}">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm btn-block mt-2">
                                                    <i class="fas fa-save"></i> {{ __('Save Row') }}
                                                </button>
                                            </form>
                                        @endforeach
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('Row') }}</th>
                                                        <th>{{ __('Seats') }}</th>
                                                        <th>{{ __('Start #') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($section->rows as $row)
                                                        <tr>
                                                            <td>
                                                                {{ $row->name }}
                                                            </td>
                                                            <td>{{ $row->seat_count }}</td>
                                                            <td>{{ $row->start_seat_number ?: 1 }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ __('No sections created yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($template->status !== 'published')
        <div class="modal fade" id="editTemplateSettingsModal" tabindex="-1" role="dialog" aria-labelledby="editTemplateSettingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.venue-maps.settings.update', $template) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTemplateSettingsModalLabel">
                                <i class="fas fa-cog text-primary"></i> {{ __('Edit Template Details & Seat Options') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <h6 class="section-title mt-0 mb-3">{{ __('Template Details') }}</h6>
                            <div class="form-group">
                                <label>{{ __('Template Name') }}</label>
                                <input type="text" name="template_name" value="{{ old('template_name', $template->name) }}" class="form-control">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Version') }}</label>
                                        <input type="text" name="version" value="{{ old('version', $template->version) }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Expected Seat Count') }}</label>
                                        <input type="number" min="1" name="expected_seat_count" value="{{ old('expected_seat_count', $template->expected_seat_count) }}" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-4 mb-4">
                            <h6 class="section-title mt-0 mb-3">{{ __('Seat Numbering & Layout Options') }}</h6>

                            <div class="form-group">
                                <label class="d-block font-weight-bold">{{ __('Seat Numbering Mode') }}</label>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_numbering_mode_section" name="numbering_mode" value="section" class="custom-control-input" {{ old('numbering_mode', $template->numbering_mode) === 'section' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_numbering_mode_section">
                                        {{ __('Section-wise numbering (default)') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Left: P1–P10 | Center: P1–P10 | Right: P1–P10') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modal_numbering_mode_continuous" name="numbering_mode" value="continuous" class="custom-control-input" {{ old('numbering_mode', $template->numbering_mode) === 'continuous' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_numbering_mode_continuous">
                                        {{ __('Continuous numbering across all sections') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Left: P1–P10, Center: P11–P20, Right: P21–P30') }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="d-block font-weight-bold">{{ __('Counting Direction') }}</label>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_counting_direction_ltr" name="counting_direction" value="left_to_right" class="custom-control-input" {{ old('counting_direction', $template->counting_direction) === 'left_to_right' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_counting_direction_ltr">
                                        {{ __('Left to Right') }}
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modal_counting_direction_rtl" name="counting_direction" value="right_to_left" class="custom-control-input" {{ old('counting_direction', $template->counting_direction) === 'right_to_left' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_counting_direction_rtl">
                                        {{ __('Right to Left') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="d-block font-weight-bold">{{ __('Seat Layout Display') }}</label>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_layout_style_curved" name="layout_style" value="curved" class="custom-control-input" {{ old('layout_style', $template->layout_style) === 'curved' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_layout_style_curved">
                                        {{ __('Curved Display (Standard)') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats are arranged in curved, arched rows for Left, Center, and Right sections.') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_layout_style_curved_rotated" name="layout_style" value="curved_rotated" class="custom-control-input" {{ old('layout_style', $template->layout_style) === 'curved_rotated' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_layout_style_curved_rotated">
                                        <i class="fas fa-sync-alt mr-1 text-primary"></i> {{ __('Curved with Rotated Seats (Focal Point)') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Middle seats remain straight, left and right seats dynamically rotate facing stage center point.') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modal_layout_style_straight" name="layout_style" value="straight" class="custom-control-input" {{ old('layout_style', $template->layout_style) === 'straight' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_layout_style_straight">
                                        {{ __('Straight Line Display') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats are arranged in straight horizontal lines for all rows.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" id="modal_show_row_name" name="show_row_name" value="1" class="custom-control-input" {{ old('show_row_name', $template->show_row_name) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_show_row_name">
                                        {{ __('Display Row Name on Seats (e.g. P1, P2 instead of 1, 2)') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="d-block font-weight-bold">{{ __('Seat Design / Shape') }}</label>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_seat_shape_circle" name="seat_shape" value="circle" class="custom-control-input" {{ old('seat_shape', $template->seat_shape) === 'circle' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_seat_shape_circle">
                                        <i class="fas fa-circle mr-1 text-primary"></i> {{ __('Circle (default)') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats rendered as round circles.') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_seat_shape_square" name="seat_shape" value="square" class="custom-control-input" {{ old('seat_shape', $template->seat_shape) === 'square' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_seat_shape_square">
                                        <i class="fas fa-square mr-1 text-primary"></i> {{ __('Square') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats rendered as square tiles.') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="modal_seat_shape_rectangle" name="seat_shape" value="rectangle" class="custom-control-input" {{ old('seat_shape', $template->seat_shape) === 'rectangle' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_seat_shape_rectangle">
                                        <i class="fas fa-vector-square mr-1 text-primary"></i> {{ __('Rectangle') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats rendered as wide rectangular tiles.') }}
                                    </small>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="modal_seat_shape_arch" name="seat_shape" value="arch" class="custom-control-input" {{ old('seat_shape', $template->seat_shape) === 'arch' || old('seat_shape', $template->seat_shape) === 'horseshoe' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-600" for="modal_seat_shape_arch">
                                        <i class="fas fa-archway mr-1 text-primary"></i> {{ __('Arch / Horseshoe Shape') }}
                                    </label>
                                    <small class="form-text text-muted mt-0 ml-1">
                                        {{ __('Seats rendered as domed arch / horseshoe tiles.') }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="d-block font-weight-bold">{{ __('Stage Focal Point Coordinates (for Seat Rotation)') }}</label>
                                <div class="form-row">
                                    <div class="form-group col-md-6 mb-0">
                                        <label class="font-weight-600">{{ __('Focal Point X (%)') }}</label>
                                        <input type="number" step="0.5" min="0" max="100" name="focal_x" value="{{ old('focal_x', $template->focal_x ?? 50) }}" class="form-control">
                                        <small class="form-text text-muted mt-1">{{ __('Center X % position of stage focal point (default 50%).') }}</small>
                                    </div>
                                    <div class="form-group col-md-6 mb-0">
                                        <label class="font-weight-600">{{ __('Focal Point Y (%)') }}</label>
                                        <input type="number" step="0.5" min="0" max="100" name="focal_y" value="{{ old('focal_y', $template->focal_y ?? 10) }}" class="form-control">
                                        <small class="form-text text-muted mt-1">{{ __('Stage Y % position (default 10% for top stage).') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-whitesmoke br">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('js')
    <template id="seat-row-template">
        <div class="seat-row-item">
            <div class="row align-items-end">
                <div class="col-lg-5">
                    <div class="form-group">
                        <label>{{ __('Section Name') }}</label>
                        <select name="rows[__INDEX__][section_name]" class="form-control seat-section-select" required>
                            <option value="">{{ __('Select Section') }}</option>
                            @foreach ($sectionOptions ?? collect() as $sectionName)
                                <option value="{{ $sectionName }}">{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>{{ __('Row Name') }}</label>
                        <input type="text" name="rows[__INDEX__][row_name]" class="form-control" required>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label>{{ __('Seat Count') }}</label>
                        <input type="number" min="1" max="{{ $newRowSeatMax ?? 1000 }}" name="rows[__INDEX__][seat_count]" class="form-control" required>
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-group">
                        <button type="button" class="btn btn-danger btn-block remove-seat-row" title="{{ __('Remove Row') }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        (function () {
            var seatRowList = document.getElementById('seat-row-list');
            var addSeatRowButton = document.getElementById('add-seat-row');
            var seatRowTemplate = document.getElementById('seat-row-template');
            var generateSeatForm = document.getElementById('generate-seat-form');
            var nextSeatRowIndex = seatRowList ? seatRowList.querySelectorAll('.seat-row-item').length : 0;

            function refreshRemoveButtons() {
                if (!seatRowList) {
                    return;
                }

                var buttons = seatRowList.querySelectorAll('.remove-seat-row');
                var disabled = buttons.length <= 1;

                buttons.forEach(function (button) {
                    button.disabled = disabled;
                });
            }

            function initializeSeatSelect(row) {
                if (window.jQuery && $.fn.select2) {
                    $(row).find('.seat-section-select').select2();
                }
            }

            if (seatRowList) {
                seatRowList.addEventListener('click', function (event) {
                    var button = event.target.closest('.remove-seat-row');

                    if (!button || button.disabled) {
                        return;
                    }

                    button.closest('.seat-row-item').remove();
                    refreshRemoveButtons();
                });
            }

            if (addSeatRowButton && seatRowTemplate && seatRowList) {
                addSeatRowButton.addEventListener('click', function () {
                    var markup = seatRowTemplate.innerHTML.replace(/__INDEX__/g, nextSeatRowIndex);
                    var wrapper = document.createElement('div');

                    wrapper.innerHTML = markup.trim();
                    var row = wrapper.firstElementChild;
                    seatRowList.appendChild(row);
                    initializeSeatSelect(row);
                    nextSeatRowIndex++;
                    refreshRemoveButtons();
                });
            }

            if (generateSeatForm) {
                generateSeatForm.addEventListener('submit', function (event) {
                    var remainingSeats = parseInt(generateSeatForm.getAttribute('data-remaining-seats'), 10);
                    var requestedSeats = 0;

                    generateSeatForm.querySelectorAll('input[name$="[seat_count]"]').forEach(function (input) {
                        requestedSeats += parseInt(input.value || '0', 10);
                    });

                    if (requestedSeats > remainingSeats) {
                        event.preventDefault();
                        alert(@json(__('Seat count cannot exceed the expected seat count.')));
                    }
                });
            }

            refreshRemoveButtons();

            var canvas = document.getElementById('seat-map-canvas');
            if (!canvas) {
                return;
            }

            var locked = canvas.getAttribute('data-locked') === '1';
            var selectedSeats = new Set();
            var activeDrag = null;
            var activeSelection = null;
            var saveButton = document.getElementById('save-seat-coordinates');
            var clearSelectionButton = document.getElementById('clear-seat-selection');
            var toggleAccessibleButton = document.getElementById('toggle-accessible-seats');
            var selectionCounter = document.getElementById('seat-selection-count');
            var messageBox = document.getElementById('seat-map-message');
            var plottedCounter = document.getElementById('plotted-seat-count');
            var saveUrl = @json(route('admin.venue-maps.coordinates.store', $template));
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var supportsPointerEvents = window.PointerEvent !== undefined;
            var seatMapDebug = true;

            function logSeatMap(action, data) {
                if (!seatMapDebug || !window.console) {
                    return;
                }

                console.log('[SeatMap] ' + action, data || {});
            }

            function clamp(value, min, max) {
                return Math.min(Math.max(value, min), max);
            }

            function getDragPointerId(event) {
                return event.pointerId !== undefined ? event.pointerId : 'mouse';
            }

            function showMessage(type, message) {
                if (!messageBox) {
                    return;
                }

                messageBox.className = 'alert alert-' + type;
                messageBox.textContent = message;
                messageBox.classList.remove('d-none');
            }

            function updateSelectionState() {
                if (selectionCounter) {
                    selectionCounter.textContent = selectedSeats.size + ' ' + @json(__('selected'));
                }

                if (clearSelectionButton) {
                    clearSelectionButton.disabled = selectedSeats.size === 0;
                }

                if (toggleAccessibleButton) {
                    toggleAccessibleButton.disabled = selectedSeats.size === 0 || locked;
                }
            }

            function setSeatSelected(seat, selected) {
                if (selected) {
                    selectedSeats.add(seat);
                    seat.classList.add('selected');
                } else {
                    selectedSeats.delete(seat);
                    seat.classList.remove('selected');
                }

                updateSelectionState();
            }

            function clearSeatSelection() {
                selectedSeats.forEach(function (seat) {
                    seat.classList.remove('selected');
                });

                selectedSeats.clear();
                updateSelectionState();
            }

            function markSeatDirty(seat) {
                seat.setAttribute('data-dirty', '1');
                seat.setAttribute('data-plotted', '1');
                seat.classList.remove('unplotted');
                seat.classList.add('dirty');
            }

            function setSeatPosition(seat, x, y) {
                seat.style.left = clamp(x, 0, 100).toFixed(5) + '%';
                seat.style.top = clamp(y, 0, 100).toFixed(5) + '%';
                markSeatDirty(seat);
            }

            function snapshotSelectedSeatPositions() {
                var seats = [];

                selectedSeats.forEach(function (selectedSeat) {
                    seats.push(selectedSeat);
                });

                return seats.map(function (selectedSeat) {
                    return {
                        element: selectedSeat,
                        left: parseFloat(selectedSeat.style.left) || 0,
                        top: parseFloat(selectedSeat.style.top) || 0
                    };
                });
            }

            function getPointerLocal(event) {
                var rect = canvas.getBoundingClientRect();

                return {
                    x: clamp(event.clientX - rect.left, 0, rect.width),
                    y: clamp(event.clientY - rect.top, 0, rect.height),
                    width: rect.width,
                    height: rect.height
                };
            }

            function beginSeatDrag(seat, event) {
                if (event.button !== undefined && event.button !== 0) {
                    return;
                }

                var additive = event.shiftKey || event.ctrlKey || event.metaKey;
                var wasSelected = selectedSeats.has(seat);
                var pointerId = getDragPointerId(event);

                event.preventDefault();

                logSeatMap('beginSeatDrag', {
                    seatId: seat.getAttribute('data-seat-id'),
                    eventType: event.type,
                    pointerId: pointerId,
                    button: event.button,
                    wasSelected: wasSelected,
                    additive: additive,
                    selectedCount: selectedSeats.size,
                    clientX: event.clientX,
                    clientY: event.clientY
                });

                activeDrag = {
                    pointerId: pointerId,
                    source: seat,
                    additive: additive,
                    wasSelected: wasSelected,
                    startClientX: event.clientX,
                    startClientY: event.clientY,
                    moved: false,
                    thresholdLogged: false,
                    seats: wasSelected ? snapshotSelectedSeatPositions() : null
                };

                if (activeDrag.seats) {
                    logSeatMap('dragGroupCapturedOnDown', {
                        seatId: seat.getAttribute('data-seat-id'),
                        seatsInDrag: activeDrag.seats.length
                    });
                }

                if (event.pointerId !== undefined && seat.setPointerCapture) {
                    seat.setPointerCapture(event.pointerId);
                    logSeatMap('pointerCaptured', {
                        seatId: seat.getAttribute('data-seat-id'),
                        pointerId: event.pointerId
                    });
                }
            }

            function prepareSeatDrag() {
                if (!activeDrag || activeDrag.seats) {
                    return;
                }

                if (activeDrag.wasSelected) {
                    activeDrag.seats = snapshotSelectedSeatPositions();
                    logSeatMap('dragGroupCapturedFromSelection', {
                        seatsInDrag: activeDrag.seats.length
                    });
                    return;
                }

                if (!activeDrag.additive) {
                    clearSeatSelection();
                }

                setSeatSelected(activeDrag.source, true);
                activeDrag.seats = snapshotSelectedSeatPositions();
                logSeatMap('dragGroupCapturedAfterSelectingSource', {
                    seatId: activeDrag.source.getAttribute('data-seat-id'),
                    seatsInDrag: activeDrag.seats.length
                });
            }

            function moveSeatDrag(event) {
                if (!activeDrag || activeDrag.pointerId !== getDragPointerId(event)) {
                    if (activeDrag) {
                        logSeatMap('moveIgnoredPointerMismatch', {
                            expectedPointerId: activeDrag.pointerId,
                            eventPointerId: getDragPointerId(event),
                            eventType: event.type
                        });
                    }

                    return;
                }

                var rect = canvas.getBoundingClientRect();
                var deltaX = ((event.clientX - activeDrag.startClientX) / rect.width) * 100;
                var deltaY = ((event.clientY - activeDrag.startClientY) / rect.height) * 100;

                if (!activeDrag.moved && Math.abs(event.clientX - activeDrag.startClientX) < 2 && Math.abs(event.clientY - activeDrag.startClientY) < 2) {
                    if (!activeDrag.thresholdLogged) {
                        activeDrag.thresholdLogged = true;
                        logSeatMap('moveBelowThreshold', {
                            pointerId: activeDrag.pointerId,
                            deltaClientX: event.clientX - activeDrag.startClientX,
                            deltaClientY: event.clientY - activeDrag.startClientY
                        });
                    }

                    return;
                }

                activeDrag.moved = true;
                prepareSeatDrag();
                event.preventDefault();

                logSeatMap('dragMove', {
                    pointerId: activeDrag.pointerId,
                    seatsInDrag: activeDrag.seats.length,
                    deltaXPercent: deltaX,
                    deltaYPercent: deltaY,
                    firstSeatId: activeDrag.seats[0] ? activeDrag.seats[0].element.getAttribute('data-seat-id') : null
                });

                activeDrag.seats.forEach(function (item) {
                    setSeatPosition(item.element, item.left + deltaX, item.top + deltaY);
                });
            }

            function endSeatDrag(event) {
                if (!activeDrag || activeDrag.pointerId !== getDragPointerId(event)) {
                    if (activeDrag) {
                        logSeatMap('endIgnoredPointerMismatch', {
                            expectedPointerId: activeDrag.pointerId,
                            eventPointerId: getDragPointerId(event),
                            eventType: event.type
                        });
                    }

                    return;
                }

                if (event.pointerId !== undefined && activeDrag.source && activeDrag.source.releasePointerCapture) {
                    try {
                        activeDrag.source.releasePointerCapture(event.pointerId);
                        logSeatMap('pointerReleased', {
                            seatId: activeDrag.source.getAttribute('data-seat-id'),
                            pointerId: event.pointerId
                        });
                    } catch (error) {
                        // Pointer capture may already be released by the browser.
                        logSeatMap('pointerReleaseFailed', {
                            message: error.message
                        });
                    }
                }

                logSeatMap('endSeatDrag', {
                    moved: activeDrag.moved,
                    sourceSeatId: activeDrag.source.getAttribute('data-seat-id'),
                    selectedCount: selectedSeats.size,
                    seatsInDrag: activeDrag.seats ? activeDrag.seats.length : 0
                });

                if (!activeDrag.moved) {
                    setSeatSelected(activeDrag.source, !activeDrag.wasSelected);
                    logSeatMap('seatToggledOnClick', {
                        seatId: activeDrag.source.getAttribute('data-seat-id'),
                        selected: selectedSeats.has(activeDrag.source),
                        selectedCount: selectedSeats.size
                    });
                }

                activeDrag = null;
            }

            function beginBoxSelection(event) {
                if (event.button !== undefined && event.button !== 0) {
                    return;
                }

                var additive = event.shiftKey || event.ctrlKey || event.metaKey;
                var pointer = getPointerLocal(event);
                var pointerId = getDragPointerId(event);
                var box = document.createElement('div');

                event.preventDefault();

                logSeatMap('beginBoxSelection', {
                    eventType: event.type,
                    pointerId: pointerId,
                    additive: additive,
                    selectedCountBefore: selectedSeats.size
                });

                if (!additive) {
                    clearSeatSelection();
                }

                box.className = 'seat-selection-box';
                box.style.left = pointer.x + 'px';
                box.style.top = pointer.y + 'px';
                box.style.width = '0px';
                box.style.height = '0px';
                canvas.appendChild(box);

                if (event.pointerId !== undefined && canvas.setPointerCapture) {
                    canvas.setPointerCapture(event.pointerId);
                }

                activeSelection = {
                    pointerId: pointerId,
                    additive: additive,
                    startX: pointer.x,
                    startY: pointer.y,
                    currentX: pointer.x,
                    currentY: pointer.y,
                    box: box
                };
            }

            function moveBoxSelection(event) {
                if (!activeSelection || activeSelection.pointerId !== getDragPointerId(event)) {
                    return;
                }

                var pointer = getPointerLocal(event);
                var left = Math.min(activeSelection.startX, pointer.x);
                var top = Math.min(activeSelection.startY, pointer.y);
                var width = Math.abs(pointer.x - activeSelection.startX);
                var height = Math.abs(pointer.y - activeSelection.startY);

                event.preventDefault();

                activeSelection.currentX = pointer.x;
                activeSelection.currentY = pointer.y;
                activeSelection.box.style.left = left + 'px';
                activeSelection.box.style.top = top + 'px';
                activeSelection.box.style.width = width + 'px';
                activeSelection.box.style.height = height + 'px';
            }

            function endBoxSelection(event) {
                if (!activeSelection || activeSelection.pointerId !== getDragPointerId(event)) {
                    return;
                }

                var rect = canvas.getBoundingClientRect();
                var left = Math.min(activeSelection.startX, activeSelection.currentX);
                var right = Math.max(activeSelection.startX, activeSelection.currentX);
                var top = Math.min(activeSelection.startY, activeSelection.currentY);
                var bottom = Math.max(activeSelection.startY, activeSelection.currentY);
                var hasArea = (right - left) > 3 && (bottom - top) > 3;

                if (hasArea) {
                    canvas.querySelectorAll('.seat-dot').forEach(function (seat) {
                        var seatLeft = ((parseFloat(seat.style.left) || 0) / 100) * rect.width;
                        var seatTop = ((parseFloat(seat.style.top) || 0) / 100) * rect.height;

                        if (seatLeft >= left && seatLeft <= right && seatTop >= top && seatTop <= bottom) {
                            setSeatSelected(seat, true);
                        }
                    });
                }

                logSeatMap('endBoxSelection', {
                    hasArea: hasArea,
                    selectedCount: selectedSeats.size
                });

                activeSelection.box.remove();
                activeSelection = null;
            }

            if (!locked) {
                logSeatMap('editorReady', {
                    supportsPointerEvents: supportsPointerEvents,
                    seatCount: canvas.querySelectorAll('.seat-dot').length
                });

                canvas.querySelectorAll('.seat-dot').forEach(function (seat) {
                    if (supportsPointerEvents) {
                        seat.addEventListener('pointerdown', function (event) {
                            beginSeatDrag(seat, event);
                        });
                    } else {
                        seat.addEventListener('mousedown', function (event) {
                            beginSeatDrag(seat, event);
                        });
                    }
                });

                if (supportsPointerEvents) {
                    canvas.addEventListener('pointerdown', function (event) {
                        if (event.target.closest('.seat-dot')) {
                            return;
                        }

                        beginBoxSelection(event);
                    });

                    window.addEventListener('pointermove', function (event) {
                        moveSeatDrag(event);
                        moveBoxSelection(event);
                    });

                    window.addEventListener('pointerup', function (event) {
                        endSeatDrag(event);
                        endBoxSelection(event);
                    });

                    window.addEventListener('pointercancel', function (event) {
                        endSeatDrag(event);
                        endBoxSelection(event);
                    });
                } else {
                    canvas.addEventListener('mousedown', function (event) {
                        if (event.target.closest('.seat-dot')) {
                            return;
                        }

                        beginBoxSelection(event);
                    });

                    window.addEventListener('mousemove', function (event) {
                        moveSeatDrag(event);
                        moveBoxSelection(event);
                    });

                    window.addEventListener('mouseup', function (event) {
                        endSeatDrag(event);
                        endBoxSelection(event);
                    });
                }

                if (clearSelectionButton) {
                    clearSelectionButton.addEventListener('click', function () {
                        clearSeatSelection();
                    });
                }

                if (toggleAccessibleButton) {
                    toggleAccessibleButton.addEventListener('click', function () {
                        if (selectedSeats.size === 0 || locked) {
                            return;
                        }

                        var allAccessible = true;
                        selectedSeats.forEach(function (seat) {
                            if (seat.getAttribute('data-is-accessible') !== '1') {
                                allAccessible = false;
                            }
                        });

                        var newAccessibleValue = allAccessible ? '0' : '1';

                        selectedSeats.forEach(function (seat) {
                            seat.setAttribute('data-is-accessible', newAccessibleValue);

                            var existingBadge = seat.querySelector('.accessible-badge');
                            if (newAccessibleValue === '1') {
                                seat.classList.add('is-accessible');
                                if (!existingBadge) {
                                    var badge = document.createElement('i');
                                    badge.className = 'fas fa-wheelchair accessible-badge';
                                    seat.insertBefore(badge, seat.firstChild);
                                }
                            } else {
                                seat.classList.remove('is-accessible');
                                if (existingBadge) {
                                    existingBadge.remove();
                                }
                            }

                            markSeatDirty(seat);
                        });

                        showMessage('info', newAccessibleValue === '1'
                            ? @json(__('Selected seats reserved for Wheelchair / Accessible. Click Save Coordinates to apply.'))
                            : @json(__('Wheelchair / Accessible reservation removed from selected seats. Click Save Coordinates to apply.'))
                        );
                    });
                }
            }

            updateSelectionState();

            if (saveButton) {
                saveButton.addEventListener('click', function () {
                    var changedSeats = Array.prototype.slice.call(canvas.querySelectorAll('.seat-dot[data-dirty="1"]'));
                    var focalMarker = document.getElementById('focal-point-marker');
                    var inputX = document.querySelector('input[name="focal_x"]');
                    var inputY = document.querySelector('input[name="focal_y"]');
                    var focalX = focalMarker ? parseFloat(focalMarker.style.left) : (inputX ? parseFloat(inputX.value) : null);
                    var focalY = focalMarker ? parseFloat(focalMarker.style.top) : (inputY ? parseFloat(inputY.value) : null);
                    var focalChanged = window.__focalPointDirty || false;

                    if (changedSeats.length === 0 && !focalChanged) {
                        showMessage('warning', @json(__('No changed seat positions or focal point to save.')));
                        return;
                    }

                    saveButton.disabled = true;

                    var payload = {
                        seats: changedSeats.map(function (seat) {
                            return {
                                id: seat.getAttribute('data-seat-id'),
                                x_percent: parseFloat(seat.style.left),
                                y_percent: parseFloat(seat.style.top),
                                is_accessible: seat.getAttribute('data-is-accessible') === '1' ? 1 : 0
                            };
                        })
                    };

                    if (focalX !== null && !isNaN(focalX) && focalY !== null && !isNaN(focalY)) {
                        payload.focal_x = focalX;
                        payload.focal_y = focalY;
                    }

                    fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw data;
                                }

                                return data;
                            });
                        })
                        .then(function (data) {
                            changedSeats.forEach(function (seat) {
                                seat.setAttribute('data-dirty', '0');
                                seat.classList.remove('dirty');
                            });
                            window.__focalPointDirty = false;

                            if (plottedCounter && data.plotted_seats !== undefined) {
                                plottedCounter.textContent = data.plotted_seats;
                            }

                            showMessage('success', data.message || @json(__('Coordinates saved.')));
                        })
                        .catch(function (error) {
                            var message = error.message || @json(__('Unable to save coordinates.'));

                            if (error.errors) {
                                var keys = Object.keys(error.errors);
                                if (keys.length && error.errors[keys[0]].length) {
                                    message = error.errors[keys[0]][0];
                                }
                            }

                            showMessage('danger', message);
                        })
                        .finally(function () {
                            saveButton.disabled = false;
                        });
                });
            }

            // Interactive Draggable Focal Point Marker
            (function () {
                var focalMarker = document.getElementById('focal-point-marker');
                if (!focalMarker || !canvas) return;

                var isDraggingFocalPoint = false;

                function startFocalDrag(e) {
                    if (locked) return;
                    e.stopPropagation();
                    e.preventDefault();
                    isDraggingFocalPoint = true;
                    focalMarker.style.cursor = 'grabbing';
                }

                if (supportsPointerEvents) {
                    focalMarker.addEventListener('pointerdown', startFocalDrag);
                } else {
                    focalMarker.addEventListener('mousedown', startFocalDrag);
                }

                function moveFocalDrag(e) {
                    if (!isDraggingFocalPoint) return;
                    var rect = canvas.getBoundingClientRect();
                    var pageX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
                    var pageY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);

                    var fx = ((pageX - rect.left) / rect.width) * 100;
                    var fy = ((pageY - rect.top) / rect.height) * 100;

                    fx = Math.max(0, Math.min(100, Math.round(fx * 10) / 10));
                    fy = Math.max(0, Math.min(100, Math.round(fy * 10) / 10));

                    focalMarker.style.left = fx + '%';
                    focalMarker.style.top = fy + '%';
                    focalMarker.innerHTML = '<i class="fas fa-crosshairs mr-1"></i> STAGE FOCAL POINT (' + fx + '%, ' + fy + '%)';

                    var inputX = document.querySelector('input[name="focal_x"]');
                    var inputY = document.querySelector('input[name="focal_y"]');
                    if (inputX) inputX.value = fx;
                    if (inputY) inputY.value = fy;

                    canvas.querySelectorAll('.seat-dot').forEach(function (seat) {
                        var leftPos = parseFloat(seat.style.left);
                        var topPos = parseFloat(seat.style.top);
                        var diffX = fx - leftPos;
                        var diffY = topPos - fy;
                        var rotateDeg = Math.round((Math.atan2(diffX, diffY) * 180 / Math.PI) * 100) / 100;
                        seat.style.transform = 'translate(-50%, -50%) rotate(' + rotateDeg + 'deg)';
                        seat.style.setProperty('--rotate-deg', rotateDeg + 'deg');
                    });
                }

                function stopFocalDrag() {
                    if (isDraggingFocalPoint) {
                        isDraggingFocalPoint = false;
                        focalMarker.style.cursor = 'grab';
                        window.__focalPointDirty = true;
                        showMessage('info', @json(__('Stage Focal Point repositioned! Click Save Coordinates to save.')));
                    }
                }

                if (supportsPointerEvents) {
                    window.addEventListener('pointermove', moveFocalDrag);
                    window.addEventListener('pointerup', stopFocalDrag);
                    window.addEventListener('pointercancel', stopFocalDrag);
                } else {
                    window.addEventListener('mousemove', moveFocalDrag);
                    window.addEventListener('mouseup', stopFocalDrag);
                }
            })();
        })();
    </script>
@endpush
