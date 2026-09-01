@extends('master')

@section('content')
<section class="section">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Coupon'),
    ])

    <div class="section-body">

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
            </div>
            <div class="col-12">
              <div class="card">
                <div class="card-body">
                    <div class="row mb-4 mt-2">
                        <div class="col-lg-8"><h2 class="section-title mt-0"> {{__('View Coupon')}}</h2></div>
                        <div class="col-lg-4 text-right">
                            @if(Auth::user()->hasRole('Organizer') || Gate::check('coupon_create'))
                            <button class="btn btn-primary add-button"><a href="{{url('coupon/create')}}"><i class="fas fa-plus"></i> {{__('Add New')}}</a></button>
                            @endif
                        </div>
                    </div>
                    @if(Auth::user()->hasRole('admin'))
                        <form method="GET" action="{{ route('coupon.index') }}" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-lg-4 col-md-6">
                                    <div class="form-group mb-md-0">
                                        <label>{{ __('Filter by Organizer') }}</label>
                                        <select name="organizer_id" class="form-control select2">
                                            <option value="">{{ __('All Organizers') }}</option>
                                            @foreach ($organizers as $organizer)
                                                @php
                                                    $organizerName = $organizer->organization_name ?: trim($organizer->first_name . ' ' . $organizer->last_name);
                                                @endphp
                                                <option value="{{ $organizer->id }}" @selected((string) $selectedOrganizer === (string) $organizer->id)>
                                                    {{ $organizerName ?: __('Organizer') }} (#{{ $organizer->id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <button type="submit" class="btn btn-primary mr-2">
                                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                                    </button>
                                    <a href="{{ route('coupon.index') }}" class="btn btn-light">
                                        {{ __('Reset') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    @endif
                  <div class="table-responsive">
                    <table class="table" id="report_table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{__('Coupon Code')}}</th>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Event')}}</th>
                                @if(Auth::user()->hasRole('admin'))
                                <th>{{__('Organizer')}}</th>
                                @endif
                                <th>{{__('Discount')}}</th>
                                <th>{{__('Duration')}}</th>
                                <th>{{__('Available')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Minimum Amount')}}</th>
                                <th>{{__('Maximum Discount')}}</th>
                                <th>{{__('Max Usage Per User')}}</th>
                                @if(Auth::user()->hasRole('Organizer') || Gate::check('coupon_edit') || Gate::check('coupon_delete'))
                                <th>{{__('Action')}}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coupon as $item)
                                @php
                                    $organizers = $item->event ? $item->event->organizations : collect();
                                    $organizerLabel = $organizers->map(function ($organizer) {
                                        $name = $organizer->organization_name ?: trim($organizer->first_name . ' ' . $organizer->last_name);
                                        return trim($name) . ' (#' . $organizer->id . ')';
                                    })->filter()->join(', ');
                                @endphp
                                <tr>
                                    <td></td>
                                    <td> {{$item->coupon_code}}</td>
                                    <td> {{$item->name}}</td>
                                    <td>
                                        @if($item->event)
                                            <div>{{ $item->event->name }}</div>
                                            <small class="text-muted">#{{ $item->event->id }}</small>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    @if(Auth::user()->hasRole('admin'))
                                    <td>{{ $organizerLabel ?: 'N/A' }}</td>
                                    @endif
                                    <td>{{ $item->discount_type == 0 ? $item->discount . '%' : $item->discount }}</td>
                                    <td>{{$item->start_date.' to '.$item->end_date}}</td>
                                    <td>{{$item->max_use-$item->use_count.' time'}}</td>
                                    <td>
                                        <h5><span class="badge {{$item->status=="1"?'badge-success': 'badge-warning'}}  m-1">{{$item->status=="1"?'Active': 'Inactive'}}</span></h5>
                                    </td>
                                    <td>{{$item->minimum_amount}}</td>
                                    <td>{{$item->maximum_discount}}</td>
                                    <td>{{$item->max_use_per_user}}</td>
                                    @if(Auth::user()->hasRole('Organizer') || Gate::check('coupon_edit') || Gate::check('coupon_delete'))
                                    <td>
                                        @if(Auth::user()->hasRole('Organizer') || Gate::check('coupon_edit'))
                                        <a href="{{ route('coupon.edit', $item->id) }}" class="btn-icon"><i class="fas fa-edit"></i></a>
                                        @endif
                                        @can('coupon_delete')
                                        <a href="#"  onclick="deleteData('coupon','{{$item->id}}');" class="btn-icon"><i class="fas fa-trash-alt text-danger"></i></a>
                                        @endcan
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
    </section>
@endsection
