@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Add Event'),
            'headerData' => __('Event'),
            'url' => 'events',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Add Event') }}</h2>
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
                            <form method="post" class="event-form" id="event-create-form" action="{{ url('events') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">

                                    <div class="col-lg-4">
                                        <label>{{ __('Image') }}</label>
                                        <div class="row form-group center">
                                            <div id="image-preview" class="image-preview">
                                                <label for="image-upload" id="image-label">
                                                    <i class="fas fa-plus"></i>
                                                </label>
                                                <input type="file" name="image" id="image-upload" />
                                            </div>
                                            @error('image')
                                                <div class="invalid-feedback block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Required dimension: 1099x550px</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <label>{{ __('Sponsor / Partner Logos') }} <small class="text-muted">({{ __('Upload multiple logos, 200x200px each') }})</small></label>
                                        @error('event_logos.*')
                                            <div class="invalid-feedback block d-block">{{ $message }}</div>
                                        @enderror
                                        <div id="logos-upload-container">
                                            <div class="logo-entry d-flex align-items-center mb-2" style="gap:10px;">
                                                <div class="image-preview" style="width:80px;height:80px;background-size:cover;background-position:center;" id="logo-preview-1">
                                                    <label for="logo-upload-1" style="cursor:pointer;width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fas fa-plus"></i>
                                                    </label>
                                                    <input type="file" name="event_logos[]" id="logo-upload-1" accept="image/*" style="display:none" onchange="previewLogo(this,'logo-preview-1')" />
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success add-logo-btn"><i class="fas fa-plus"></i> {{ __('Add Logo') }}</button>
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ __('Required dimension: 200x200px') }}</small>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Name') }}</label>
                                            <input type="text" name="name" value="{{ old('name') }}"
                                                placeholder="{{ __('Enter Event Name') }}"
                                                class="form-control @error('Enter Event Name')? is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ __('Category') }}</label>
                                            <select name="category_id" class="form-control select2">
                                                <option value="">{{ __('Select Event Category') }}</option>
                                                @foreach ($category as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ $item->id == old('category') ? 'Selected' : '' }}>
                                                        {{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('category')
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
                                                value="{{ old('start_time') }}"
                                                placeholder="{{ __('Choose Event Start time') }}"
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
                                                value="{{ old('end_time') }}" placeholder="{{ __('Choose Event End time') }}"
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
                                            @foreach ($users as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ in_array($item->id, (array) old('organizer_ids', [])) ? 'selected' : '' }}>
                                                    {{ trim($item->first_name . ' ' . $item->last_name) ?: ($item->email ?: 'Organizer #' . $item->id) }}</option>
                                            @endforeach
                                        </select>
                                        @error('organizer_ids')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                <div class="scanner">
                                    <div class="form-group">
                                        <label>{{ __('Scanner') }} {{ __('(Required)') }} <small class="text-muted">({{ __('Choose Multiple if required.') }})</small></label>
                                        <div class="input-group">
                                            <select name="scanner_id[]" class="form-control scanner_id select2" multiple id="scanner_id">
                                                @foreach ($scanner as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ (is_array(old('scanner_id')) && in_array($item->id, old('scanner_id'))) || $item->id == old('scanner_id') ? 'selected' : '' }}>
                                                        {{ trim($item->first_name . ' ' . $item->last_name) ?: ($item->email ?: 'Scanner #' . $item->id) }}</option>
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
                                            <input type="number" min='1' name="people" id="people"
                                                value="{{ old('people') }}"
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
                                            <select name="venue_map_template_id" class="form-control select2">
                                                <option value="">{{ __('No venue seat map') }}</option>
                                                @foreach ($venueMapTemplates as $template)
                                                    <option value="{{ $template->id }}"
                                                        {{ (int) old('venue_map_template_id') === (int) $template->id ? 'selected' : '' }}>
                                                        {{ $template->name }} v{{ $template->version }}
                                                        @if($template->venue)
                                                            - {{ $template->venue->name }}
                                                        @endif
                                                        ({{ $template->expected_seat_count }} {{ __('seats') }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('venue_map_template_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">{{ __('Select a published master map to enable manual seat selection for this event.') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('status') }}</label>
                                            <select name="status" class="form-control select2">
                                                <option value="1">{{ __('Active') }}</option>
                                                <option value="0">{{ __('Inactive') }}</option>
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
                                                <option value="0">{{ __('No') }}</option>
                                                <option value="1">{{ __('Yes') }}</option>
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
                                            <input type="text" name="tags" value="{{ old('tags') }}"
                                                class="form-control inputtags @error('tags')? is-invalid @enderror"
                                                placeholder="{{ __('Enter event tags (Music, Sports, Art, etc.)') }}">
                                            @error('tags')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Meta Pixel ID') }}</label>
                                            <input type="text" name="meta_pixel_id" value="{{ old('meta_pixel_id') }}"
                                            placeholder="{{ __('Enter Meta Pixel ID for tracking') }}"
                                                class="form-control @error('meta_pixel_id')? is-invalid @enderror">
                                            @error('meta_pixel_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Description (Enter description of event)') }}</label>
                                    <textarea name="description" placeholder="{{ __('Enter description of event') }}"
                                        class="textarea_editor @error('description')? is-invalid @enderror">
                                {{ old('description') }}
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
                                                {{ old('type') == 'online' ? '' : 'checked' }} checked value="offline"
                                                class="selectgroup-input" checked="">
                                            <span class="selectgroup-button">{{ __('Venue') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" {{ old('type') == 'online' ? 'checked' : '' }}
                                                name="type" value="online" class="selectgroup-input">
                                            <span class="selectgroup-button">{{ __('Online Event') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="location-detail {{ old('type') == 'online' ? 'hide' : '' }}">
                                    <div class="form-group">
                                        <label>{{ __('Event Address') }}</label>
                                        <input type="text" name="address" id="address"
                                            value="{{ old('address') }}"
                                            placeholder="{{ __('Enter Event Address') }}"
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
                                                    value="{{ old('lat') }}" placeholder="{{ __('Enter Event Location Latitude') }}"
                                                    class="form-control @error('lat')? is-invalid @enderror">
                                                @error('lat')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="ml-3">
                                                    <a href="#" data-toggle="modal" data-target="#latLongHelpModal">
                                                        {{ __('How to collect latitude and longitude?') }}
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>{{ __('Longitude') }}</label>
                                                <input type="text" name="lang" id="lang"
                                                    value="{{ old('lang') }}" placeholder="{{ __('Enter Event Location Longitude') }}"
                                                    class="form-control @error('lang')? is-invalid @enderror">
                                                @error('lang')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="url hide  {{ old('type') == 'online' ? 'block' : '' }}">
                                    <div class="form-group">
                                        <label>{{ __('Event url') }}</label>
                                        <input type="link" name="url" id="url"
                                            placeholder="{{ __('Event url') }}"
                                            class="form-control @error('url')? is-invalid @enderror">
                                        @error('url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                    </div>
                                </div>
                                <div class="videos-container">
                                    <h6 class="text-muted mt-4 mb-4">{{ __('Video Links') }}</h6>
                                    <div class="video-entry mb-3">
                                        <div class="row">
                                            <div class="col-lg-10">
                                                <div class="form-group">
                                                    <label>{{ __('Video Link') }}</label>
                                                    <input type="url" name="video_links[]" class="form-control"
                                                        placeholder="{{ __('Enter video link Related Event (YouTube, Vimeo etc.)') }}">
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
                                </div>

                                <div class="form-group mt-4">
                                    <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#eventTermsModal">
                                        <i class="fas fa-file-contract"></i> {{ __('Terms & Conditions') }}
                                    </button>
                                    <span id="event-terms-status" class="text-muted small">
                                        {{ old('event_terms_accepted') ? __('Accepted') : __('Please review and accept terms before submit.') }}
                                    </span>
                                    @error('event_terms_accepted')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary demo-button">
                                            {{ __('Submit') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('js')
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

            $(document).ready(function() {
                console.log('Event create page scripts loaded');

                // Video links functionality
                var videoCount = 1;

                $('.add-more-videos').click(function() {
                    videoCount++;
                    var newVideoEntry = '<div class="video-entry mb-3">' +
                        '<div class="row">' +
                            '<div class="col-lg-10">' +
                                '<div class="form-group">' +
                                    '<label>{{ __("Video Link") }} ' + videoCount + '</label>' +
                                    '<input type="url" name="video_links[]" class="form-control" placeholder="{{ __("Enter video link (YouTube, Vimeo etc.)") }}">' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-lg-2">' +
                                '<button type="button" class="btn btn-danger remove-video" style="margin-top: 30px;">' +
                                    '<i class="fas fa-trash"></i> {{ __("Remove") }}' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                    $('.videos-container').append(newVideoEntry);
                });

                $(document).on('click', '.remove-video', function() {
                    $(this).closest('.video-entry').remove();
                    videoCount--;
                });

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
                        // Show loading state on button
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

                    console.log('Fetching scanners from:', '{{ url("get-scanners") }}');
                    console.log('Selected organizers:', ajaxData['organizer_ids']);
                    console.log('Selected scanners:', selectedScanners);

                    $.ajax({
                        url: '{{ url("get-scanners") }}',
                        type: 'GET',
                        data: ajaxData,
                        dataType: 'json',
                        success: function(response) {
                            console.log('Scanner response:', response);

                            if (response.success) {
                                const prevSelected = Array.isArray(selectedScanners) ? selectedScanners.map(String) : [String(selectedScanners)];

                                // Clear existing options
                                scannerSelect.empty();

                                // Add new scanner options
                                if (response.scanners && response.scanners.length > 0) {
                                    response.scanners.forEach(function(scanner) {
                                        const isSelected = prevSelected.includes(scanner.id.toString());
                                        const scannerName = scanner.name || (((scanner.first_name || '') + ' ' + (scanner.last_name || '')).trim() || scanner.email || ('Scanner #' + scanner.id));
                                        const option = new Option(scannerName, scanner.id, isSelected, isSelected);
                                        scannerSelect.append(option);
                                    });
                                }

                                // Update Select2 cleanly
                                scannerSelect.trigger('change');

                                if (showButton) {
                                    // Show success state briefly
                                    refreshBtn.html('<i class="fas fa-check text-success"></i>');
                                    setTimeout(function() {
                                        refreshBtn.prop('disabled', false).html(originalHtml);
                                    }, 1000);
                                }

                                console.log('Scanner dropdown updated successfully');
                            } else {
                                console.error('Response success is false');
                                if (showButton) {
                                    // Show error state
                                    refreshBtn.html('<i class="fas fa-exclamation-triangle text-danger"></i>');
                                    setTimeout(function() {
                                        refreshBtn.prop('disabled', false).html(originalHtml);
                                    }, 2000);
                                    alert('Failed to refresh scanners. Please try again.');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error refreshing scanners:');
                            console.error('Status:', status);
                            console.error('Error:', error);
                            console.error('Response:', xhr.responseText);

                            if (showButton) {
                                // Show error state briefly
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
                    console.log('Refresh button clicked');
                    updateScannerDropdown(true);
                });

                // When organizer selection changes, refresh scanner dropdown to show only their scanners
                $('#org-for-event').on('change', function() {
                    console.log('Organizer selection changed, refreshing scanners');
                    updateScannerDropdown(false);
                });

                // Auto-refresh when tab/window gets focus (when user returns from adding scanner)
                $(window).on('focus', function() {
                    console.log('Window focused - refreshing scanners');
                    // Silently refresh in background without showing button animation
                    updateScannerDropdown(false);
                });

                // Also detect when user clicks back on the page
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        console.log('Page became visible - refreshing scanners');
                        // Page is now visible, refresh scanners
                        updateScannerDropdown(false);
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                var termsCheckbox = document.getElementById('event_terms_accepted');
                var termsStatus = document.getElementById('event-terms-status');

                if (!termsCheckbox || !termsStatus) {
                    return;
                }

                function updateTermsStatus() {
                    termsStatus.textContent = termsCheckbox.checked
                        ? '{{ __('Accepted') }}'
                        : '{{ __('Please review and accept terms before submit.') }}';
                    termsStatus.classList.toggle('text-success', termsCheckbox.checked);
                    termsStatus.classList.toggle('text-muted', !termsCheckbox.checked);
                }

                termsCheckbox.addEventListener('change', updateTermsStatus);
                updateTermsStatus();
            });
        </script>
        @endpush

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

            .event-terms-modal-body {
                max-height: 60vh;
                overflow-y: auto;
            }
        </style>

        <!-- Event Terms & Conditions Modal -->
        <div class="modal fade" id="eventTermsModal" tabindex="-1" role="dialog" aria-labelledby="eventTermsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventTermsModalLabel">{{ __('Event Terms & Conditions') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body event-terms-modal-body">
                        <p>{{ __('By creating an event, you confirm that all event details, dates, venue information, ticketing information, pricing, tax information, media, and promotional content are accurate and authorized for publication.') }}</p>
                        <p>{{ __('You are responsible for complying with applicable laws, venue rules, refund policies, attendee communications, scanner access, and any permissions required for the event.') }}</p>
                        <p>{{ __('The platform may review, suspend, hide, or remove events that contain misleading information, prohibited content, unauthorized media, invalid venue details, or activity that violates platform policy.') }}</p>
                        <p class="mb-0">{{ __('Your acceptance will be recorded with your account, IP address, browser details, event information, and acceptance date for audit purposes.') }}</p>
                    </div>
                    <div class="modal-footer d-block">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" name="event_terms_accepted" value="1" id="event_terms_accepted"
                                form="event-create-form"
                                class="custom-control-input @error('event_terms_accepted') is-invalid @enderror"
                                {{ old('event_terms_accepted') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="event_terms_accepted">
                                {{ __('I accept the event terms and conditions for creating this event.') }}
                            </label>
                            @error('event_terms_accepted')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('Done') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latitude & Longitude Help Modal -->
        <div class="modal fade" id="latLongHelpModal" tabindex="-1" role="dialog" aria-labelledby="latLongHelpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('How to collect Latitude & Longitude?') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <ol>
                            <li>Open <a href="https://www.google.com/maps" target="_blank">Google Maps</a>.</li>
                            <li>Right-click on the location on the map.</li>
                            <li>Select "What's here?"</li>
                            <li>A card at the bottom will show the coordinates (e.g., 19.0760, 72.8777).</li>
                            <li>Copy the values:
                                <ul>
                                    <li>First value = <strong>Latitude</strong></li>
                                    <li>Second value = <strong>Longitude</strong></li>
                                </ul>
                            </li>
                        </ol>
                        <p class="mt-2"><strong>Tip:</strong> You can also use your current location via mobile GPS apps or developer tools.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('address');

            if (!input) {
                return;
            }

            input.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });
        });
    </script>
@endsection
