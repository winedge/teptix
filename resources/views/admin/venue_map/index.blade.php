@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Venue Seat Maps'),
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if (session('error'))
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
                                    <h2 class="section-title mt-0">{{ __('Master Venue Templates') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <a href="{{ route('admin.venue-maps.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> {{ __('Create Template') }}
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="venue_map_table">
                                    <thead>
                                        <tr>
                                            <th class="no-export"></th>
                                            <th>{{ __('ID') }}</th>
                                            <th>{{ __('Venue') }}</th>
                                            <th>{{ __('Location') }}</th>
                                            <th>{{ __('Version') }}</th>
                                            <th>{{ __('Seats') }}</th>
                                            <th>{{ __('Plotted') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Updated') }}</th>
                                            <th class="no-export">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($templates as $template)
                                            <tr>
                                                <td></td>
                                                <td>{{ $template->id }}</td>
                                                <td>{{ $template->venue->name ?? '-' }}</td>
                                                <td>{{ $template->venue->location ?? '-' }}</td>
                                                <td>{{ $template->version }}</td>
                                                <td>{{ $template->seats_count }} / {{ $template->expected_seat_count }}</td>
                                                <td>{{ $template->plotted_seats_count }} / {{ $template->expected_seat_count }}</td>
                                                <td>
                                                    <span class="badge {{ $template->status === 'published' ? 'badge-success' : 'badge-warning' }}">
                                                        {{ ucfirst($template->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $template->updated_at ? $template->updated_at->format('Y-m-d H:i') : '-' }}</td>
                                                <td>
                                                    <a href="{{ route('admin.venue-maps.edit', $template) }}" class="btn btn-primary btn-sm mr-1" title="{{ __('Open') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if ($template->status === \App\Models\VenueMapTemplate::STATUS_DRAFT && $template->event_venue_maps_count == 0)
                                                        <form action="{{ route('admin.venue-maps.destroy', $template) }}" method="POST" class="d-inline-block" onsubmit="return confirm('{{ __('Are you sure you want to delete this draft venue seat map?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('Delete') }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" class="btn btn-secondary btn-sm" disabled title="{{ $template->status !== \App\Models\VenueMapTemplate::STATUS_DRAFT ? __('Only draft templates can be deleted') : __('Connected to an event') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
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

@push('js')
    <script>
        $(document).ready(function () {
            var table = $('#venue_map_table').DataTable({
                dom: `<'row mb-2'<'col-sm-6 text-left'f><'col-sm-6 text-right'B>>
                <'row'<'col-sm-12'tr>>
                <'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 'lp>>`,
                language: {
                    paginate: {
                        previous: "<i class='fas fa-angle-left'></i>",
                        next: "<i class='fas fa-angle-right'></i>"
                    }
                },
                buttons: [
                    {
                        text: '<i class="fas fa-trash-alt"></i> Delete',
                        className: 'btn btn-danger',
                        action: function (e, dt) {
                            var selectedRows = dt.rows({ selected: true }).data();

                            if (selectedRows.length === 0) {
                                Swal.fire('Warning', 'Please select at least one venue map to delete.', 'warning');
                                return;
                            }

                            Swal.fire({
                                title: 'Are you sure?',
                                text: 'Selected draft venue map templates will be deleted.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, delete it!'
                            }).then(function (result) {
                                if (!result.isConfirmed) {
                                    return;
                                }

                                var ids = [];
                                selectedRows.each(function (rowData) {
                                    ids.push(rowData[1]);
                                });

                                $.ajax({
                                    url: @json(route('admin.venue-maps.destroy-selected')),
                                    type: 'DELETE',
                                    data: JSON.stringify({ ids: ids }),
                                    contentType: 'application/json',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function (response) {
                                        var message = response.message || 'Venue map templates deleted.';

                                        if (response.skipped > 0) {
                                            message += ' ' + response.skipped + ' template(s) were skipped because they are not in draft mode or are connected to events.';
                                        }

                                        Swal.fire('Deleted!', message, 'success');

                                        if (response.deleted_ids && response.deleted_ids.length) {
                                            dt.rows(function (idx, data) {
                                                return response.deleted_ids.indexOf(parseInt(data[1], 10)) !== -1;
                                            }).remove().draw();
                                        }
                                    },
                                    error: function (xhr) {
                                        var message = xhr.responseJSON && xhr.responseJSON.message
                                            ? xhr.responseJSON.message
                                            : 'Unable to delete selected venue maps.';

                                        Swal.fire('Error', message, 'error');
                                    }
                                });
                            });
                        }
                    },
                    {
                        text: '<i class="fas fa-print"></i> Print',
                        extend: 'print',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                return !$(node).hasClass('no-export');
                            }
                        }
                    },
                    {
                        text: '<i class="far fa-file-excel"></i> Excel',
                        extend: 'excelHtml5',
                        title: new Date().toLocaleString('en-ca'),
                        exportOptions: {
                            columns: function (idx, data, node) {
                                return !$(node).hasClass('no-export');
                            }
                        }
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csvHtml5',
                        title: new Date().toLocaleString('en-ca'),
                        exportOptions: {
                            columns: function (idx, data, node) {
                                return !$(node).hasClass('no-export');
                            }
                        }
                    },
                    {
                        text: '<i class="far fa-file-pdf"></i> PDF',
                        extend: 'pdfHtml5',
                        title: new Date().toLocaleString('en-ca'),
                        orientation: 'landscape',
                        pageSize: 'A3',
                        exportOptions: {
                            columns: function (idx, data, node) {
                                return !$(node).hasClass('no-export');
                            }
                        }
                    }
                ],
                columnDefs: [{
                    orderable: false,
                    className: 'select-checkbox no-export',
                    targets: 0
                }],
                select: {
                    style: 'multi',
                    selector: 'td:first-child'
                }
            });
        });
    </script>
@endpush
