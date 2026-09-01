@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Add Banner'),
            'headerData' => __('Banner'),
            'url' => 'banner',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Add banner') }}</h2>
                </div>
            </div>
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ $error }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endforeach
            @endif
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="{{ url('banner') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="form-group center">
                                            <label>{{ __('Desktop Image') }} <small class="text-muted">({{ __('Required: 1905x600px') }})</small></label>
                                            <div id="image-preview" class="image-preview">
                                                <label for="image-upload" id="image-label"> <i
                                                        class="fas fa-plus"></i></label>
                                                <input type="file" name="image" id="image-upload" />
                                            </div>
                                            @error('image')
                                                <div class="invalid-feedback-block text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group center">
                                            <label>{{ __('Mobile Image') }} <small class="text-muted">({{ __('Optional: 450x500px') }})</small></label>
                                            <div id="mobile-image-preview" class="image-preview">
                                                <label for="mobile-image-upload" id="mobile-image-label"> <i
                                                        class="fas fa-plus"></i></label>
                                                <input type="file" name="image_for_mobile" id="mobile-image-upload" />
                                            </div>
                                            @error('image_for_mobile')
                                                <div class="invalid-feedback-block text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group center">
                                            <label>{{ __('Android Image') }} <small class="text-muted">({{ __('Optional: 1905x893px') }})</small></label>
                                            <div id="android-image-preview" class="image-preview">
                                                <label for="android-image-upload" id="android-image-label"> <i
                                                        class="fas fa-plus"></i></label>
                                                <input type="file" name="image_for_android" id="android-image-upload" />
                                            </div>
                                            @error('image_for_android')
                                                <div class="invalid-feedback-block text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-6">

                                        <div class="form-group">
                                            <label>{{ __('Title') }}</label>
                                            <input type="text" name="title" placeholder="{{ __('Title') }}"
                                                value="{{ old('title') }}"
                                                class="form-control @error('title')? is-invalid @enderror">
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label>{{ __('Description') }}</label>
                                            <textarea name="description" placeholder="{{ __('Description') }}"
                                                class="form-control @error('description')? is-invalid @enderror">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ __('Banner Type') }}</label>
                                            <select name="banner_type" id="banner_type" class="form-control select2">
                                                <option value="event" {{ old('banner_type') == 'general' ? '' : 'selected' }}>{{ __('Event') }}</option>
                                                <option value="general" {{ old('banner_type') == 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
                                            </select>
                                            @error('banner_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group" id="event-field-group">
                                            <label>{{ __('Events') }}</label>
                                            <select name="event_id" class="form-control select2">
                                                <option value="" disabled selected>{{ __('Select Event') }}</option>
                                                @foreach ($events as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('event_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group" id="redirect-url-field-group">
                                            <label>{{ __('Redirect URL') }}</label>
                                            <input type="url" name="redirect_url" placeholder="{{ __('https://example.com') }}"
                                                value="{{ old('redirect_url') }}"
                                                class="form-control @error('redirect_url')? is-invalid @enderror">
                                            @error('redirect_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ __('Display Order') }}</label>
                                            <input type="number" name="display_order" placeholder="{{ __('Display Order') }}"
                                                value="{{ old('display_order') }}"
                                                class="form-control @error('display_order')? is-invalid @enderror">
                                            @error('display_order')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
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
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary demo-button">{{ __('Submit') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    // Toggle Event / Redirect URL fields based on Banner Type
    function toggleBannerTypeFields() {
        var type = $('#banner_type').val();
        if (type === 'general') {
            $('#event-field-group').hide().find('select').prop('required', false);
            $('#redirect-url-field-group').show().find('input').prop('required', true);
        } else {
            $('#event-field-group').show().find('select').prop('required', true);
            $('#redirect-url-field-group').hide().find('input').prop('required', false);
        }
    }

    $(document).ready(function() {
        toggleBannerTypeFields();
        $('#banner_type').on('change', toggleBannerTypeFields);
    });

    // Handle mobile image preview
    $(document).ready(function() {
        $('#mobile-image-upload').on('change', function() {

            var input = this;
            var url = $(this).val();
            var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
            if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#mobile-image-preview').css('background-image', 'url(' + e.target.result + ')');
                    $('#mobile-image-preview').hide();
                    $('#mobile-image-preview').fadeIn(650);
                }
                reader.readAsDataURL(input.files[0]);
            }
        });

        // Handle android image preview
        $('#android-image-upload').on('change', function() {
            var input = this;
            var url = $(this).val();
            var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
            if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#android-image-preview').css('background-image', 'url(' + e.target.result + ')');
                    $('#android-image-preview').hide();
                    $('#android-image-preview').fadeIn(650);
                }
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
</script>
@endsection
