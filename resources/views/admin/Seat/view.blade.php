@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Manage Seats & Sponsorships'),
            'headerData' => __('Seats'),
            'url' => route('admin.Seat.view'),
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
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
                                <div class="col-lg-8">
                                    <h2 class="section-title mt-0">{{ __('Seat Tables') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <a href="{{ route('admin.Seat.createSeat') }}" class="btn btn-primary me-2">
                                        <i class="fas fa-plus"></i> {{ __('Create Seat Table') }}
                                    </a>
                                    <a href="{{ route('admin.Seat.createSponser') }}" class="btn btn-success">
                                        <i class="fas fa-plus"></i> {{ __('Create Sponsorship') }}
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="seat_table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('ID') }}</th>
                                            <th>{{ __('Table Name') }}</th>
                                            <th>{{ __('Seats') }}</th>
                                            <th>{{ __('Prefix') }}</th>
                                            <th>{{ __('Sponsorship') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($seats as $seat)
                                            <tr>
                                                <td>{{ $seat->id }}</td>
                                                <td>{{ $seat->name_of_table }}</td>
                                                <td>{{ $seat->number_seat }}</td>
                                                <td>{{ $seat->prefixname }}</td>
                                                <td>{{ $seat->sponsership->name ?? 'None' }}</td>
                                                <td>
                                                    <form action="{{ route('admin.Seat.deleteSeat', $seat->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this seat table?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h4 class="section-title mt-5">{{ __('Sponsorships') }}</h4>
                            <div class="table-responsive">
                                <table class="table" id="sponsership_table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('ID') }}</th>
                                            <th>{{ __('Sponsorship Name') }}</th>
                                            <th>{{ __('Details') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sponsers as $sponser)
                                            <tr>
                                                <td>{{ $sponser->id }}</td>
                                                <td>{{ $sponser->name }}</td>
                                                <td>{{ $sponser->details ?? 'N/A' }}</td>
                                                <td>
                                                    <form action="{{ route('admin.Seat.deleteSponser', $sponser->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this sponsorship? Note: You cannot delete sponsorships that have associated seat tables.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                                    </form>
                                                </td>
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
