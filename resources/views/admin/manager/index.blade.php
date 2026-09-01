@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Managers'),
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
                                <div class="col-lg-8">
                                    <h2 class="section-title mt-0"> {{ __('View Managers') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <button class="btn btn-primary add-button">
                                        <a href="{{ url('managers/create') }}">
                                            <i class="fas fa-plus"></i> {{ __('Add New') }}
                                        </a>
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="report_table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Phone') }}</th>
                                            <th>{{ __('Assigned Permissions') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($managers as $item)
                                            <tr>
                                                <td></td>
                                                <td>
                                                    <div class="media">
                                                        <img alt="image" class="mr-3 avatar"
                                                            src="{{ url('images/upload/' . $item->image) }}">
                                                        <div class="media-body">
                                                            <div class="media-title mb-0">
                                                                {{ $item->first_name . ' ' . $item->last_name }}
                                                            </div>
                                                            <div class="media-description text-muted"> {{ $item->email }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $item->phone }}</td>
                                                <td>
                                                    @if(empty($item->permissions_list))
                                                        <span class="badge badge-light text-muted">{{ __('No permissions') }}</span>
                                                    @else
                                                        @foreach($item->permissions_list as $perm)
                                                            @php
                                                                $label = '';
                                                                $colorClass = 'badge-secondary';
                                                                if($perm == 'dashboard_access') { $label = __('Dashboard'); $colorClass = 'badge-primary'; }
                                                                elseif($perm == 'order_view') { $label = __('View Orders'); $colorClass = 'badge-info'; }
                                                                elseif($perm == 'order_create') { $label = __('Create Order'); $colorClass = 'badge-success'; }
                                                                elseif($perm == 'scanner_create') { $label = __('Create Scanner'); $colorClass = 'badge-warning'; }
                                                                elseif($perm == 'ticket_verify') { $label = __('Verification'); $colorClass = 'badge-danger'; }
                                                                elseif($perm == 'revenue_view') { $label = __('Revenue'); $colorClass = 'badge-dark'; }
                                                            @endphp
                                                            <span class="badge {{ $colorClass }} mb-1">{{ $label }}</span>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->status == 1 ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $item->status == 1 ? __('Active') : __('Inactive') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ url('managers/' . $item->id . '/edit') }}" class="btn-icon text-primary mr-2" title="{{ __('Edit') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    @if ($item->status == 0)
                                                        <a href="{{ url('managers/' . $item->id . '/status') }}" title="{{ __('Activate') }}" class="btn-icon text-success mr-2">
                                                            <i class="fas fa-unlock-alt"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ url('managers/' . $item->id . '/status') }}" title="{{ __('Deactivate') }}" class="btn-icon text-warning mr-2">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    @endif

                                                    <a href="{{ url('managers/' . $item->id . '/delete') }}" onclick="return confirm('{{ __('Are you sure you want to delete this manager?') }}')" class="btn-icon text-danger" title="{{ __('Delete') }}">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
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
