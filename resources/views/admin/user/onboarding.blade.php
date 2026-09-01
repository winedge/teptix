@extends('master')

@section('content')
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Organizer Onboarding'),
    ])

    <div class="section-body">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="section-title">{{ __('Personal Details') }}</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="{{ route('users.onboarding.save') }}">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Organization Name') }}</label>
                                <input type="text" name="organization_name" value="{{ old('organization_name', $user->organization_name) }}"
                                    class="form-control @error('organization_name') is-invalid @enderror">
                                @error('organization_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('First Name') }}</label>
                                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                                            class="form-control @error('first_name') is-invalid @enderror">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('Last Name') }}</label>
                                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                                            class="form-control @error('last_name') is-invalid @enderror">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('Phone') }}</label>
                                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                            class="form-control @error('phone') is-invalid @enderror">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>{{ __('Country') }}</label>
                                        <input type="text" name="country" value="{{ old('country', $user->country) }}"
                                            class="form-control @error('country') is-invalid @enderror">
                                        @error('country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Bio') }}</label>
                                <textarea name="bio" rows="5" class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
