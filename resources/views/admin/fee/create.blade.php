@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Add Fee Type'),
            'headerData' => __('Fee Type'),
            'url' => 'fee-type',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Add Fee Type') }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="{{ route('fee-type.store') }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                                <div class="form-group">
                                    <label>{{ __('Name') }}</label>
                                    <input type="text" name="name" placeholder="{{ __('Name') }}"
                                         value="{{ old('name') }}"
                                         class="form-control @error('name') is-invalid @enderror">
                                    @error('name')
                                         <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Amount Type') }}</label><br>
                                    <input class="ml-0" name="amount_type" type="radio" value="price"
                                         {{ old('amount_type') == 'price' ? 'checked' : '' }}
                                         class="form-control" checked>{{__("Price")}}
                                    <input class="ml-5" name="amount_type" type="radio" value="percentage"
                                         {{ old('amount_type') == 'percentage' ? 'checked' : '' }}
                                         class="form-control">{{__("Percentage")}}
                                    @error('amount_type')
                                         <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Charges') }}</label>
                                    <input type="number" min="0" step="any" name="price" placeholder="{{ __('Charges') }}"
                                         value="{{ old('price') }}"
                                         class="form-control @error('price') is-invalid @enderror">
                                    @error('price')
                                         <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control select2">
                                        <option value="1">{{ __('Active') }}</option>
                                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>
                                             {{ __('Inactive') }}
                                        </option>
                                    </select>
                                    @error('status')
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
