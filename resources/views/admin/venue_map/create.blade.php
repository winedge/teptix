@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Create Venue Seat Map'),
            'headerData' => __('Venue Seat Maps'),
            'url' => 'venue-seat-maps',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title">{{ __('Create Master Template') }}</h2>
                </div>
            </div>

            <form method="post" action="{{ route('admin.venue-maps.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="section-title mt-0">{{ __('Venue Details') }}</h4>

                                <div class="form-group">
                                    <label>{{ __('Venue Name') }}</label>
                                    <input type="text" name="venue_name" value="{{ old('venue_name') }}" class="form-control @error('venue_name') is-invalid @enderror" required>
                                    @error('venue_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Location') }}</label>
                                    <input type="text" name="location" value="{{ old('location') }}" class="form-control @error('location') is-invalid @enderror">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Address') }}</label>
                                    <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="section-title mt-0">{{ __('Template Details') }}</h4>

                                <div class="form-group">
                                    <label>{{ __('Template Name') }}</label>
                                    <input type="text" name="template_name" value="{{ old('template_name') }}" class="form-control @error('template_name') is-invalid @enderror">
                                    @error('template_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('Version') }}</label>
                                            <input type="text" name="version" value="{{ old('version', 'v1') }}" class="form-control @error('version') is-invalid @enderror" required>
                                            @error('version')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('Expected Seat Count') }}</label>
                                            <input type="number" min="1" name="expected_seat_count" value="{{ old('expected_seat_count') }}" class="form-control @error('expected_seat_count') is-invalid @enderror" required>
                                            @error('expected_seat_count')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Background Map Image') }}</label>
                                    <input type="file" name="background_image" accept="image/*" class="form-control @error('background_image') is-invalid @enderror" required>
                                    @error('background_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr class="mt-4 mb-4">
                                <h4 class="section-title mt-0">{{ __('Seat Numbering Options') }}</h4>

                                <div class="form-group">
                                    <label class="d-block font-weight-bold">{{ __('Seat Numbering Mode') }}</label>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="numbering_mode_section" name="numbering_mode" value="section" class="custom-control-input" {{ old('numbering_mode', 'section') === 'section' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="numbering_mode_section">
                                            {{ __('Section-wise numbering (default)') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Left: P1–P10 | Center: P1–P10 | Right: P1–P10') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="numbering_mode_continuous" name="numbering_mode" value="continuous" class="custom-control-input" {{ old('numbering_mode') === 'continuous' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="numbering_mode_continuous">
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
                                        <input type="radio" id="counting_direction_ltr" name="counting_direction" value="left_to_right" class="custom-control-input" {{ old('counting_direction', 'left_to_right') === 'left_to_right' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="counting_direction_ltr">
                                            {{ __('Left to Right') }}
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="counting_direction_rtl" name="counting_direction" value="right_to_left" class="custom-control-input" {{ old('counting_direction') === 'right_to_left' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="counting_direction_rtl">
                                            {{ __('Right to Left') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="d-block font-weight-bold">{{ __('Seat Layout Display') }}</label>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="layout_style_curved" name="layout_style" value="curved" class="custom-control-input" {{ old('layout_style', 'curved') === 'curved' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="layout_style_curved">
                                            {{ __('Curved Display (Standard)') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats are arranged in curved, arched rows for Left, Center, and Right sections.') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="layout_style_curved_rotated" name="layout_style" value="curved_rotated" class="custom-control-input" {{ old('layout_style') === 'curved_rotated' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="layout_style_curved_rotated">
                                            <i class="fas fa-sync-alt mr-1 text-primary"></i> {{ __('Curved with Rotated Seats (Focal Point)') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Middle seats remain straight, left and right seats dynamically rotate facing the stage center point.') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="layout_style_straight" name="layout_style" value="straight" class="custom-control-input" {{ old('layout_style') === 'straight' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="layout_style_straight">
                                            {{ __('Straight Line Display') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats are arranged in straight horizontal lines for all rows.') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" id="show_row_name" name="show_row_name" value="1" class="custom-control-input" {{ old('show_row_name') ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="show_row_name">
                                            {{ __('Display Row Name on Seats (e.g. P1, P2 instead of 1, 2)') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="d-block font-weight-bold">{{ __('Seat Design / Shape') }}</label>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="seat_shape_circle" name="seat_shape" value="circle" class="custom-control-input" {{ old('seat_shape', 'circle') === 'circle' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="seat_shape_circle">
                                            <i class="fas fa-circle mr-1 text-primary"></i> {{ __('Circle (default)') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats rendered as round circles.') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="seat_shape_square" name="seat_shape" value="square" class="custom-control-input" {{ old('seat_shape') === 'square' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="seat_shape_square">
                                            <i class="fas fa-square mr-1 text-primary"></i> {{ __('Square') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats rendered as square tiles.') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" id="seat_shape_rectangle" name="seat_shape" value="rectangle" class="custom-control-input" {{ old('seat_shape') === 'rectangle' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="seat_shape_rectangle">
                                            <i class="fas fa-vector-square mr-1 text-primary"></i> {{ __('Rectangle') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats rendered as wide rectangular tiles.') }}
                                        </small>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="seat_shape_arch" name="seat_shape" value="arch" class="custom-control-input" {{ old('seat_shape') === 'arch' ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-600" for="seat_shape_arch">
                                            <i class="fas fa-archway mr-1 text-primary"></i> {{ __('Arch / Horseshoe Shape') }}
                                        </label>
                                        <small class="form-text text-muted mt-0 ml-1">
                                            {{ __('Seats rendered as domed arch / horseshoe tiles.') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> {{ __('Create Template') }}
                                    </button>
                                    <a href="{{ route('admin.venue-maps.index') }}" class="btn btn-light">
                                        {{ __('Cancel') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
