@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Configure Event Fees'),
            'headerData' => __('Event Fee Mapping'),
            'url' => 'event-fee',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Configure Fees for Event') }}: {{ $event->name }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="{{ route('event-fee.update', $event->id) }}">
                                @csrf
                                @method('PUT')
                                
                                <div class="form-group">
                                    <label class="d-block">{{ __('Select Additional Fees to Apply') }}</label>
                                    @foreach ($fees as $fee)
                                        @php
                                            $checked = in_array($fee->id, $assignedFeeIds) ? 'checked' : '';
                                            $defaultBadge = $fee->is_default == 1 ? '<span class="badge badge-success badge-sm ml-2">Default (Always Applied for New Events)</span>' : '';
                                        @endphp
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" name="fee_ids[]" value="{{ $fee->id }}"
                                                class="custom-control-input" id="fee_{{ $fee->id }}" {{ $checked }}>
                                            <label class="custom-control-label" for="fee_{{ $fee->id }}">
                                                <strong>{{ $fee->name }}</strong> 
                                                ({{ $fee->amount_type == 'percentage' ? $fee->price . '%' : '$' . number_format($fee->price, 2) }})
                                                {!! $defaultBadge !!}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="form-group mt-4">
                                     <button type="submit" class="btn btn-primary demo-button">{{ __('Save Changes') }}</button>
                                     <a href="{{ route('event-fee.index') }}" class="btn btn-light ml-2">{{ __('Cancel') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
