@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Edit Manager'),
            'headerData' => __('Managers'),
            'url' => 'managers',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Edit Manager') }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="{{ url('managers/' . $manager->id . '/update') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('First Name') }}</label>
                                            <input type="text" name="first_name" placeholder="{{ __('First Name') }}"
                                                value="{{ old('first_name', $manager->first_name) }}"
                                                class="form-control @error('first_name') is-invalid @enderror" required>
                                            @error('first_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Last Name') }}</label>
                                            <input type="text" name="last_name" placeholder="{{ __('Last Name') }}"
                                                value="{{ old('last_name', $manager->last_name) }}"
                                                class="form-control @error('last_name') is-invalid @enderror" required>
                                            @error('last_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Email') }}</label>
                                            <input type="email" name="email" placeholder="{{ __('Email') }}"
                                                value="{{ old('email', $manager->email) }}"
                                                class="form-control @error('email') is-invalid @enderror" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Phone') }}</label>
                                            <input type="text" name="phone" placeholder="{{ __('Phone') }}"
                                                value="{{ old('phone', $manager->phone) }}"
                                                class="form-control @error('phone') is-invalid @enderror" required>
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Password') }} <small class="text-muted">({{ __('Leave blank to keep existing password') }})</small></label>
                                    <input type="password" name="password" placeholder="{{ __('Password') }}"
                                        class="form-control @error('password') is-invalid @enderror">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control select2">
                                        <option value="1" {{ $manager->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="0" {{ $manager->status == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="d-block font-weight-bold">{{ __('Assign Access / Permissions') }}</label>
                                    <div class="row mt-2">
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="dashboard_access" class="custom-control-input" id="perm_dashboard"
                                                    {{ in_array('dashboard_access', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_dashboard">{{ __('Dashboard Access') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="order_view" class="custom-control-input" id="perm_order_view"
                                                    {{ in_array('order_view', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_order_view">{{ __('Orders (View Only)') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="order_create" class="custom-control-input" id="perm_order_create"
                                                    {{ in_array('order_create', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_order_create">{{ __('Orders (Create)') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="scanner_create" class="custom-control-input" id="perm_scanner_create"
                                                    {{ in_array('scanner_create', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_scanner_create">{{ __('Scanner (Create)') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="ticket_verify" class="custom-control-input" id="perm_ticket_verify"
                                                    {{ in_array('ticket_verify', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_ticket_verify">{{ __('Ticket Verification (Access)') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]" value="revenue_view" class="custom-control-input" id="perm_revenue_view"
                                                    {{ in_array('revenue_view', $assignedPermissions) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="perm_revenue_view">{{ __('Revenue (View)') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary demo-button">{{ __('Update') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
