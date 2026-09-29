@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Edit Event'),
            'headerData' => __('Event'),
            'url' => 'events',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Edit Event') }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <form method="post" class="event-form" action="{{ route('events.update', [$event->id]) }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-lg-4">
                                        <label>{{ __('Image') }}</label>
                                        <div class="row form-group center">

                                            <div id="image-preview" class="image-preview"
                                                style="background-image: url({{ url('images/upload/' . $event->image) }})">
                                                <label for="image-upload" id="image-label"> <i
                                                        class="fas fa-plus"></i></label>
                                                <input type="file" name="image" id="image-upload" accept="image/*" />
                                            </div>
                                            @error('image')
                                                <div class="invalid-feedback block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">{{ __('Supported formats: JPG, JPEG, PNG, GIF') }}</small>

                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label>{{ __('Homepage Thumbnail') }}</label>
                                        <div class="row form-group center">

                                            <div id="thumbnail-preview" class="image-preview"
                                                @if(!empty($event->thumbnail)) style="background-image: url({{ url('images/upload/' . $event->thumbnail) }})" @endif>
                                                <label for="thumbnail-upload" id="thumbnail-label"> <i
                                                        class="fas fa-plus"></i></label>
                                                <input type="file" name="thumbnail" id="thumbnail-upload" accept="image/*" />
                                            </div>
                                            @error('thumbnail')
                                                <div class="invalid-feedback block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">{{ __('Recommended: 800x500px (16:10 ratio) so it isn\'t cropped on the homepage. Falls back to the main image if left blank.') }}</small>

                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label>{{ __('Sponsor / Partner Logos') }}</label>
                                        <small class="text-muted d-block mb-2">{{ __('Add or remove logos.') }}</small>
                                        @php $existingLogos = array_filter(explode(',', $event->event_logos ?? '')); @endphp

                                        {{-- Existing single event_logo --}}
                                        @if(!empty($event->event_logo))
                                        <div class="d-flex align-items-center mb-2" style="gap:8px;">
                                            <img src="{{ url('images/upload/' . $event->event_logo) }}"
                                                 style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #ddd;"
                                                 title="{{ __('Event Logo') }}">
                                            <span class="badge badge-secondary">{{ __('Main Logo') }}</span>
                                        </div>
                                        @endif

                                        <div id="logos-upload-container">
                                            {{-- Existing multi-logos with delete button --}}
                                            @foreach($existingLogos as $logo)
                                                @if(strlen(trim($logo)) > 0)
                                                <div class="logo-entry d-flex align-items-center mb-2" style="gap:8px;">
                                                    <img src="{{ url('images/upload/' . trim($logo)) }}"
                                                         style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                                                    <a href="{{ url('events/remove-logo/' . $event->id . '/' . trim($logo)) }}"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('{{ __('Remove this logo?') }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                                @endif
                                            @endforeach

                                            {{-- New upload slot --}}
                                            <div class="logo-entry d-flex align-items-center mb-2" style="gap:8px;">
                                                <div class="image-preview" style="width:70px;height:70px;background-size:cover;background-position:center;" id="logo-preview-1">
                                                    <label for="logo-upload-1" style="cursor:pointer;width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fas fa-plus"></i>
                                                    </label>
                                                    <input type="file" name="event_logos[]" id="logo-upload-1" accept="image/*" style="display:none" onchange="previewLogo(this,'logo-preview-1')" />
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success add-logo-btn">
                                                    <i class="fas fa-plus"></i> {{ __('Add') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label>{{ __('Name') }}</label>
                                            <input type="text" name="name" value="{{ $event->name }}"
                                                placeholder="{{ __('Name') }}"
                                                class="form-control @error('name')? is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ __('Category') }}</label>
                                            <select name="category_id" class="form-control select2">
                                                <option value="">{{ __('Select Category') }}</option>
                                                @foreach ($category as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ $item->id == $event->category_id ? 'Selected' : '' }}>
                                                        {{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Start Time') }}</label>
                                            <input type="text" name="start_time" id="start_time"
                                                value="{{ $event->start_time }}"
                                                placeholder="{{ __('Choose Start time') }}"
                                                class="form-control date @error('start_time')? is-invalid @enderror">
                                            @error('start_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('End Time') }}</label>
                                            <input type="text" name="end_time" id="end_time"
                                                value="{{ $event->end_time }}" placeholder="{{ __('Choose End time') }}"
                                                class="form-control date @error('end_time')? is-invalid @enderror">
                                            @error('end_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @if (Auth::user()->hasRole('admin'))
                                    <div class="form-group">
                                        <label>{{ __('Organizer') }} <small class="text-muted">({{ __('Choose Multiple if required.') }})</small></label>
                                        <select name="organizer_ids[]" required class="form-control select2" id="org-for-event" multiple>
                                            @php
                                                $selectedOrgIds = !empty($event->user_id) ? explode(',', (string)$event->user_id) : [];
                                                if (old('organizer_ids')) {
                                                    $selectedOrgIds = (array) old('organizer_ids');
                                                }
                                                $selectedOrgIds = array_map('strval', $selectedOrgIds);
                                            @endphp
                                            @foreach ($users as $item)
                                                @php
                                                    $orgName = trim($item->first_name . ' ' . $item->last_name) ?: ($item->email ?: 'Organizer #' . $item->id);
                                                    if (!empty($item->organization_name)) {
                                                        $orgName .= ' (' . $item->organization_name . ')';
                                                    }
                                                @endphp
                                                <option value="{{ $item->id }}"
                                                    {{ in_array((string)$item->id, $selectedOrgIds) ? 'selected' : '' }}>
                                                    {{ $orgName }}</option>
                                            @endforeach
                                        </select>
                                        @error('organizer_ids')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                <div class="scanner {{ $event->type == 'online' ? 'hide' : 'demo' }}">
                                    <div class="form-group">
                                        <label>{{ __('Scanner') }} {{ __('(Required)') }} <small class="text-muted">({{ __('Choose Multiple if required.') }})</small></label>
                                        <div class="input-group">
                                            <select name="scanner_id[]" class="form-control scanner_id select2" multiple id="scanner_id">
                                                @php
                                                    $selectedScannerIds = !empty($event->scanner_id) ? explode(',', (string) $event->scanner_id) : [];
                                                    if (old('scanner_id')) {
                                                        $selectedScannerIds = is_array(old('scanner_id')) ? old('scanner_id') : explode(',', (string) old('scanner_id'));
                                                    }
                                                    $selectedScannerIds = array_map('strval', $selectedScannerIds);
                                                @endphp
                                                @foreach ($scanner as $item)
                                                    @php
                                                        $scannerName = trim($item->first_name . ' ' . $item->last_name) ?: ($item->email ?: 'Scanner #' . $item->id);
                                                        $orgName = $item->organizer ? trim($item->organizer->organization_name ?: ($item->organizer->first_name . ' ' . $item->organizer->last_name)) : '';
                                                        if (!empty($orgName) && Auth::user()->hasRole('admin')) {
                                                            $scannerName .= ' (' . $orgName . ')';
                                                        }
                                                        $isSelected = in_array((string)$item->id, $selectedScannerIds);
                                                    @endphp
                                                    <option value="{{ $item->id }}" {{ $isSelected ? 'selected' : '' }}>
                                                        {{ $scannerName }}</option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary refresh-scanners-btn" title="{{ __('Refresh Scanner List') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @error('scanner_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error('scanner_id.*')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fas fa-plus-circle"></i>
                                            <a href="{{ url('scanner/create') }}" target="_blank" class="text-primary">
                                                {{ __('If you want to add scanner') }}
                                            </a>
                                            <br>
                                            <i class="fas fa-info-circle"></i>
                                            {{ __('After adding a scanner, click the refresh button or switch back to this tab to see it in the list.') }}
                                        </small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Maximum people will join in this event') }}</label>
                                            <input type="number" name="people" id="people"
                                                value="{{ $event->people }}"
                                                placeholder="{{ __('Maximum people will join in this event') }}"
                                                class="form-control @error('people')? is-invalid @enderror">
                                            @error('people')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Venue Seat Map') }}</label>
                                            @php
                                                $selectedVenueMapTemplateId = old('venue_map_template_id', optional($liveVenueMap)->venue_map_template_id);
                                                $hasBookedVenueSeats = $liveVenueMap
                                                    ? $liveVenueMap->seats()->where('status', \App\Models\EventVenueSeat::STATUS_BOOKED)->exists()
                                                    : false;
                                            @endphp
                                            <select name="venue_map_template_id" class="form-control select2" {{ $hasBookedVenueSeats ? 'disabled' : '' }}>
                                                <option value="">{{ __('No venue seat map') }}</option>
                                                @foreach ($venueMapTemplates as $template)
                                                    <option value="{{ $template->id }}"
                                                        {{ (int) $selectedVenueMapTemplateId === (int) $template->id ? 'selected' : '' }}>
                                                        {{ $template->name }} v{{ $template->version }}
                                                        @if($template->venue)
                                                            - {{ $template->venue->name }}
                                                        @endif
                                                        ({{ $template->expected_seat_count }} {{ __('seats') }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($hasBookedVenueSeats)
                                                <input type="hidden" name="venue_map_template_id" value="{{ $selectedVenueMapTemplateId }}">
                                                <small class="text-muted">{{ __('This map cannot be changed because seats are already booked.') }}</small>
                                            @else
                                                <small class="text-muted">{{ __('Select a published master map to enable manual seat selection for this event.') }}</small>
                                            @endif
                                            @error('venue_map_template_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('status') }}</label>
                                            <select name="status" class="form-control select2">
                                                <option value="1" {{ $event->status == '1' ? 'selected' : '' }}>
                                                    {{ __('Active') }}</option>
                                                <option value="0" {{ $event->status == '0' ? 'Selected' : '' }}>
                                                    {{ __('Inactive') }}</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Featured Event') }}</label>
                                            <select name="is_featured" class="form-control select2">
                                                <option value="0" {{ !$event->is_featured ? 'selected' : '' }}>{{ __('No') }}</option>
                                                <option value="1" {{ $event->is_featured ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                            </select>
                                            <small class="text-muted">{{ __('Featured events are highlighted on the homepage.') }}</small>
                                            @error('is_featured')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Tags') }}</label>
                                            <input type="text" name="tags" value="{{ $event->tags }}"
                                                class="form-control inputtags @error('tags')? is-invalid @enderror">
                                            @error('tags')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Meta Pixel ID') }}</label>
                                            <input type="text" name="meta_pixel_id" value="{{ $event->meta_pixel_id }}"
                                                class="form-control @error('meta_pixel_id')? is-invalid @enderror">
                                            @error('meta_pixel_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Description') }}</label>
                                    <textarea name="description" Placeholder="{{ __('Description') }}"
                                        class="textarea_editor @error('description')? is-invalid @enderror">
                                {{ $event->description }}
                            </textarea>
                                    @error('description')
                                        <div class="invalid-feedback block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <h6 class="text-muted mt-4 mb-4">{{ __('Location Detail') }}</h6>
                                <div class="form-group">
                                    <div class="selectgroup">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="type"
                                                {{ $event->type == 'offline' ? 'checked' : '' }} checked value="offline"
                                                class="selectgroup-input" checked="">
                                            <span class="selectgroup-button">{{ __('Venue') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" {{ $event->type == 'online' ? 'checked' : '' }}
                                                name="type" value="online" class="selectgroup-input">
                                            <span class="selectgroup-button">{{ __('Online Event') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="location-detail {{ $event->type == 'online' ? 'hide' : '' }}">
                                    <div class="form-group">
                                        <label>{{ __('Event Address') }}</label>
                                        <input type="text" name="address" id="address"
                                            value="{{ $event->address }}" placeholder="{{ __('Event Address') }}"
                                            class="form-control @error('address')? is-invalid @enderror">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>{{ __('Latitude') }}</label>
                                                <input type="text" name="lat" id="lat"
                                                    value="{{ $event->lat }}" placeholder="{{ __('Latitude') }}"
                                                    class="form-control @error('lat')? is-invalid @enderror">
                                                @error('lat')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>{{ __('Longitude') }}</label>
                                                <input type="text" name="lang" id="lang"
                                                    value="{{ $event->lang }}" placeholder="{{ __('Longitude') }}"
                                                    class="form-control @error('lang')? is-invalid @enderror">
                                                @error('lang')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="url {{ $event->type == 'offline' ? 'hide' : '' }}">
                                    <div class="form-group">
                                        <label>{{ __('Event url') }}</label>
                                        <input type="link" value="{{ $event->url }}" name="url" id="url"
                                            placeholder="{{ __('Event url') }}"
                                            class="form-control @error('url')? is-invalid @enderror">
                                        @error('url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                    </div>
                                </div>

                                <div class="videos-container">
                                    <h6 class="text-muted mt-4 mb-4">{{ __('Video Links') }}</h6>
                                    @php
                                        $existingVideos = $event->videos->first() ? $event->videos->first()->links : [];
                                    @endphp

                                    @if(!empty($existingVideos) && count($existingVideos) > 0)
                                        @foreach($existingVideos as $index => $link)
                                            <div class="video-entry mb-3">
                                                <div class="row">
                                                    <div class="col-lg-10">
                                                        <div class="form-group">
                                                            <label>{{ __('Video Link') }} {{ $index + 1 }}</label>
                                                            <input type="url" name="video_links[]" class="form-control"
                                                                value="{{ $link }}"
                                                                placeholder="{{ __('Enter video link (YouTube, Vimeo etc.)') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        @if($index == 0)
                                                            <button type="button" class="btn btn-primary add-more-videos"
                                                                style="margin-top: 30px;">
                                                                <i class="fas fa-plus"></i> {{ __('Add More') }}
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn btn-danger remove-video" style="margin-top: 30px;">
                                                                <i class="fas fa-trash"></i> {{ __('Remove') }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="video-entry mb-3">
                                            <div class="row">
                                                <div class="col-lg-10">
                                                    <div class="form-group">
                                                        <label>{{ __('Video Link') }}</label>
                                                        <input type="url" name="video_links[]" class="form-control"
                                                            placeholder="{{ __('Enter video link (YouTube, Vimeo etc.)') }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2">
                                                    <button type="button" class="btn btn-primary add-more-videos"
                                                        style="margin-top: 30px;">
                                                        <i class="fas fa-plus"></i> {{ __('Add More') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit"
                                        class="btn btn-primary demo-button">{{ __('Submit') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @php
        $gmapkey = App\Models\Setting::find(1)->map_key;
    @endphp
    <script type="text/javascript" src="https://maps.google.com/maps/api/js?key={{ $gmapkey }}&libraries=places">
    </script>

    <script>
        // Initialize organizer and scanner multi-select with Select2
        $(document).ready(function() {
            $('#org-for-event').select2({
                placeholder: '{{ __('Choose Organizer') }}',
                allowClear: true,
                multiple: true,
                width: '100%'
            });

            $('.scanner_id').select2({
                placeholder: '{{ __('Choose Scanner') }}',
                allowClear: true,
                multiple: true,
                width: '100%'
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Setup AJAX with CSRF token
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Function to update scanner dropdown with new data
            function updateScannerDropdown(showButton) {
                showButton = typeof showButton !== 'undefined' ? showButton : true;
                const refreshBtn = $('.refresh-scanners-btn');
                const originalHtml = refreshBtn.html();

                if (showButton) {
                    refreshBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                }

                const scannerSelect = $('select[name="scanner_id[]"]');
                // Get currently selected scanners to preserve selection
                const selectedScanners = scannerSelect.val() || [];

                const orgSelect = $('#org-for-event');
                const ajaxData = {};

                if (orgSelect.length > 0) {
                    const selectedOrganizers = orgSelect.val() || [];
                    ajaxData['organizer_ids'] = selectedOrganizers;
                    ajaxData['has_organizer_filter'] = 1;
                }

                $.ajax({
                    url: '{{ url("get-scanners") }}',
                    type: 'GET',
                    data: ajaxData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const prevSelected = Array.isArray(selectedScanners) ? selectedScanners.map(String) : (selectedScanners ? [String(selectedScanners)] : []);

                            // Clear existing options
                            scannerSelect.empty();

                            const validSelected = [];

                            // Add new scanner options
                            if (response.scanners && response.scanners.length > 0) {
                                response.scanners.forEach(function(scanner) {
                                    const isSelected = prevSelected.includes(scanner.id.toString());
                                    if (isSelected) {
                                        validSelected.push(scanner.id.toString());
                                    }
                                    const scannerName = scanner.name || (((scanner.first_name || '') + ' ' + (scanner.last_name || '')).trim() || scanner.email || ('Scanner #' + scanner.id));
                                    const option = new Option(scannerName, scanner.id, isSelected, isSelected);
                                    scannerSelect.append(option);
                                });
                            }

                            // Update Select2 cleanly with validated selected values
                            scannerSelect.val(validSelected).trigger('change');

                            if (showButton) {
                                refreshBtn.html('<i class="fas fa-check text-success"></i>');
                                setTimeout(function() {
                                    refreshBtn.prop('disabled', false).html(originalHtml);
                                }, 1000);
                            }
                        } else {
                            if (showButton) {
                                refreshBtn.html('<i class="fas fa-exclamation-triangle text-danger"></i>');
                                setTimeout(function() {
                                    refreshBtn.prop('disabled', false).html(originalHtml);
                                }, 2000);
                                alert('Failed to refresh scanners. Please try again.');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        if (showButton) {
                            refreshBtn.html('<i class="fas fa-exclamation-triangle text-danger"></i>');
                            setTimeout(function() {
                                refreshBtn.prop('disabled', false).html(originalHtml);
                            }, 2000);
                            alert('Error refreshing scanners: ' + (xhr.responseText || error));
                        }
                    }
                });
            }

            // Manual refresh button click handler
            $(document).on('click', '.refresh-scanners-btn', function(e) {
                e.preventDefault();
                updateScannerDropdown(true);
            });

            // Auto-update scanners when organizer selection changes
            $('#org-for-event').on('change', function() {
                updateScannerDropdown(false);
            });

            // Auto-refresh when tab/window gets focus (when user returns from adding scanner)
            $(window).on('focus', function() {
                updateScannerDropdown(false);
            });

            // Also detect when user clicks back on the page
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    updateScannerDropdown(false);
                }
            });
        });
    </script>

    <script>
        if (window.google && google.maps && google.maps.event) {
            google.maps.event.addDomListener(window, 'load', initialize);
        } else {
            document.addEventListener('DOMContentLoaded', initialize);
        }

        function initialize() {
            var input = document.getElementById('address');

            if (!input) {
                return;
            }

            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            if (!window.google || !google.maps || !google.maps.places) {
                return;
            }

            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setFields(['formatted_address', 'geometry', 'name']);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();

                if (place.formatted_address) {
                    input.value = place.formatted_address;
                } else if (place.name && !input.value) {
                    input.value = place.name;
                }

                if (place.geometry && place.geometry.location) {
                    $('#lat').val(place.geometry.location.lat());
                    $('#lang').val(place.geometry.location.lng());
                }
            });
        }

        // Multiple logos preview + dynamic add/remove
        var logoCount = 1;

        function previewLogo(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById(previewId);
                    preview.style.backgroundImage = 'url(' + e.target.result + ')';
                    preview.querySelector('label i').style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        $(document).on('click', '.add-logo-btn', function() {
            logoCount++;
            var html = '<div class="logo-entry d-flex align-items-center mb-2" style="gap:10px;">' +
                '<div class="image-preview" style="width:80px;height:80px;background-size:cover;background-position:center;" id="logo-preview-' + logoCount + '">' +
                    '<label for="logo-upload-' + logoCount + '" style="cursor:pointer;width:100%;height:100%;display:flex;align-items:center;justify-content:center;">' +
                        '<i class="fas fa-plus"></i>' +
                    '</label>' +
                    '<input type="file" name="event_logos[]" id="logo-upload-' + logoCount + '" accept="image/*" style="display:none" onchange="previewLogo(this,\'logo-preview-' + logoCount + '\')" />' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-danger remove-logo-btn"><i class="fas fa-trash"></i> {{ __("Remove") }}</button>' +
            '</div>';
            $('#logos-upload-container').append(html);
        });

        $(document).on('click', '.remove-logo-btn', function() {
            $(this).closest('.logo-entry').remove();
        });

        // Video links functionality
        $(document).ready(function() {
            var videoCount = {{ !empty($existingVideos) ? count($existingVideos) : 1 }};

            $(document).on('click', '.add-more-videos', function() {
                videoCount++;
                var newVideoEntry = `
                    <div class="video-entry mb-3">
                        <div class="row">
                            <div class="col-lg-10">
                                <div class="form-group">
                                    <label>{{ __('Video Link') }} ${videoCount}</label>
                                    <input type="url" name="video_links[]" class="form-control"
                                        placeholder="{{ __('Enter video link (YouTube, Vimeo etc.)') }}">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-danger remove-video" style="margin-top: 30px;">
                                    <i class="fas fa-trash"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('.videos-container').append(newVideoEntry);
            });

            $(document).on('click', '.remove-video', function() {
                $(this).closest('.video-entry').remove();
                videoCount--;
            });
        });
    </script>
    <style>
        .modal-backdrop {
            display: none;
        }

        .input-group > .select2-container--default {
            width: auto !important;
            flex: 1 1 auto;
        }

        .input-group > .select2-container--default .select2-selection--multiple {
            min-height: 42px;
        }
    </style>
@endsection
