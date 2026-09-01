@extends('master')

@push('css')
<style>
    .roles-page .roles-card {
        border: 0;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .roles-page .roles-toolbar,
    .roles-page .roles-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 24px;
    }

    .roles-page .roles-search-wrap {
        position: relative;
        width: min(100%, 280px);
    }

    .roles-page .roles-search-icon {
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .roles-page .roles-search {
        height: 38px;
        border-color: #dcdcdc;
        border-radius: 6px;
        padding-left: 36px;
    }

    .roles-page .roles-add-btn {
        height: 38px;
        border: 0;
        border-radius: 6px;
        background-color: #ff1e1e;
        white-space: nowrap;
    }

    .roles-page .roles-table {
        border-top: 1px solid #f0f0f0;
        min-width: 760px;
    }

    .roles-page .roles-table thead {
        background-color: #f8f9fa;
    }

    .roles-page .roles-table th {
        border: 0;
        color: #111827;
        font-weight: 700;
        vertical-align: middle;
        white-space: nowrap;
    }

    .roles-page .roles-table td {
        border-top: 0;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .roles-page .role-icon {
        width: 40px;
        height: 40px;
        border: 1px solid #ffd1d1;
        background-color: #fff3f3;
        color: #ff4d4d;
    }

    .roles-page .permission-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        max-width: 620px;
    }

    .roles-page .permission-badge {
        border-radius: 4px;
        background-color: #39b54a;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 400;
        line-height: 1.1;
        padding: 7px 9px;
        white-space: normal;
        word-break: break-word;
    }

    .roles-page .permission-badge-warning {
        background-color: #ffb800;
        color: #1f2937;
    }

    .roles-page .roles-action-btn {
        width: 34px;
        height: 34px;
        border-color: #ff4d4d !important;
        border-radius: 6px;
        background-color: #fff;
        color: #ff4d4d;
    }

    .roles-page .roles-footer {
        border-top: 1px solid #f0f0f0;
        color: #6b7280;
        font-size: 0.9rem;
        flex-wrap: wrap;
    }

    .roles-page .roles-footer-actions {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .roles-page .roles-length {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .roles-page .roles-length select {
        width: 74px;
        border-color: #ff4d4d;
        color: #ff4d4d;
        border-radius: 4px;
    }

    .roles-page .roles-pagination .pagination {
        margin-bottom: 0;
    }

    .roles-page .roles-pagination .page-link {
        color: #ff4d4d;
        border-color: #ffe0e0;
        min-width: 36px;
        text-align: center;
    }

    .roles-page .roles-pagination .page-item.active .page-link {
        background-color: #ff4d4d;
        border-color: #ff4d4d;
        color: #fff;
    }

    .roles-page .dataTables_wrapper .row {
        margin-left: 0;
        margin-right: 0;
    }

    .roles-page .dataTables_empty {
        padding: 36px 16px !important;
        color: #6b7280;
    }

    @media (max-width: 767.98px) {
        .roles-page .roles-toolbar,
        .roles-page .roles-footer {
            align-items: stretch;
            padding: 16px;
        }

        .roles-page .roles-toolbar,
        .roles-page .roles-footer,
        .roles-page .roles-footer-actions {
            flex-direction: column;
        }

        .roles-page .roles-search-wrap,
        .roles-page .roles-add-btn,
        .roles-page .roles-footer-actions,
        .roles-page .roles-pagination,
        .roles-page .roles-length {
            width: 100%;
        }

        .roles-page .roles-add-btn {
            justify-content: center;
        }

        .roles-page .roles-footer-actions {
            gap: 12px;
        }

        .roles-page .roles-length {
            justify-content: space-between;
        }

        .roles-page .roles-pagination .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }

        .roles-page .roles-table {
            min-width: 0;
            border-top: 0;
        }

        .roles-page .roles-table thead {
            display: none;
        }

        .roles-page .roles-table,
        .roles-page .roles-table tbody,
        .roles-page .roles-table tr,
        .roles-page .roles-table td {
            display: block;
            width: 100%;
        }

        .roles-page .roles-table tr {
            border-bottom: 1px solid #f0f0f0;
            padding: 16px;
        }

        .roles-page .roles-table td {
            border-bottom: 0;
            padding: 8px 0;
        }

        .roles-page .roles-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .roles-page .role-icon-cell,
        .roles-page .role-action-cell {
            text-align: left !important;
        }

        .roles-page .permission-list {
            max-width: none;
        }
    }
</style>
@endpush

@section('content')
<section class="section roles-page">
    @include('admin.layout.breadcrumbs', [
        'title' => __('Roles'),
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
                <div class="card roles-card">
                    <div class="card-body p-0">
                        <div class="roles-toolbar">
                            <div class="roles-search-wrap">
                                <i class="fas fa-search position-absolute text-muted roles-search-icon"></i>
                                <input type="text" id="customSearch" class="form-control roles-search" placeholder="{{ __('Search Roles ...') }}">
                            </div>

                            @can('role_create')
                                <a href="{{ url('roles/create') }}" class="btn text-white font-weight-bold d-flex align-items-center px-3 roles-add-btn">
                                    <i class="fas fa-plus mr-2"></i> {{ __('Add Role') }}
                                </a>
                            @endcan
                        </div>

                        <div class="table-responsive">
                            <table class="table mb-0 roles-table" id="roles_table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 80px;"></th>
                                        <th>{{ __('Name') }} <i class="fas fa-sort-alpha-down text-muted ml-1" style="font-size: 0.8rem;"></i></th>
                                        <th>{{ __('Permissions') }} <i class="fas fa-sort-alpha-down text-muted ml-1" style="font-size: 0.8rem;"></i></th>
                                        @if(Gate::check('role_edit') || Gate::check('role_delete'))
                                            <th class="text-center" style="width: 120px;">{{ __('Action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $item)
                                        <tr>
                                            <td class="text-center role-icon-cell" data-label="{{ __('Role') }}">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle role-icon">
                                                    @if($item->name == 'admin')
                                                        <i class="fas fa-shield-alt"></i>
                                                    @elseif($item->name == 'scanner')
                                                        <i class="fas fa-mobile-alt"></i>
                                                    @else
                                                        <i class="fas fa-user-tag"></i>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="font-weight-bold text-dark" data-label="{{ __('Name') }}">
                                                {{ Str::ucfirst($item->name) }}
                                            </td>

                                            <td data-label="{{ __('Permissions') }}">
                                                <div class="permission-list">
                                                    @forelse ($item->permissions as $permission)
                                                        <span class="permission-badge">
                                                            {{ Str::ucfirst($permission->name) }}
                                                        </span>
                                                    @empty
                                                        @if($item->name == 'admin')
                                                            <span class="permission-badge permission-badge-warning">{{ __('All') }}</span>
                                                        @else
                                                            <span class="permission-badge permission-badge-warning">{{ __('No Data') }}</span>
                                                        @endif
                                                    @endforelse
                                                </div>
                                            </td>

                                            @if(Gate::check('role_edit') || Gate::check('role_delete'))
                                                <td class="text-center role-action-cell" data-label="{{ __('Action') }}">
                                                    @if($item->name != 'admin')
                                                        @can('role_edit')
                                                            <a href="{{ route('roles.edit', $item->id) }}" class="btn btn-sm d-inline-flex align-items-center justify-content-center p-2 roles-action-btn" title="{{ __('Edit') }}">
                                                                <i class="fas fa-pen" style="font-size: 0.85rem;"></i>
                                                            </a>
                                                        @endcan
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="roles-footer">
                            <div id="tableInfo"></div>
                            <div class="roles-footer-actions">
                                <div id="customNav" class="roles-pagination"></div>

                                <label class="roles-length mb-0">
                                    <select id="rolesPageLength" class="form-control form-control-sm">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                    <span>{{ __('Items per page') }}</span>
                                </label>
                            </div>
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
$(function() {
    var labelOf = @json(__('of'));
    var labelItems = @json(__('items'));
    var labelPrevious = @json(__('Previous'));
    var labelNext = @json(__('Next'));

    var table = $('#roles_table').DataTable({
        dom: 'rt',
        pageLength: 10,
        order: [[1, 'asc']],
        language: {
            emptyTable: @json(__('No roles found')),
            zeroRecords: @json(__('No matching roles found')),
            paginate: {
                previous: "<i class='fas fa-angle-left'></i>",
                next: "<i class='fas fa-angle-right'></i>"
            }
        },
        columnDefs: [{
            orderable: false,
            targets: [
                0
                @if(Gate::check('role_edit') || Gate::check('role_delete'))
                    , 3
                @endif
            ]
        }]
    });

    function renderPagination() {
        var info = table.page.info();
        var start = info.recordsDisplay ? info.start + 1 : 0;
        var end = info.end;
        var total = info.recordsDisplay;
        var pagination = $('<ul class="pagination"></ul>');

        $('#tableInfo').text(start + '-' + end + ' ' + labelOf + ' ' + total + ' ' + labelItems);

        var previous = $('<li class="page-item"></li>').toggleClass('disabled', info.page === 0);
        previous.append(
            $('<a></a>', {
                class: 'page-link',
                href: '#',
                'data-page': 'previous',
                'aria-label': labelPrevious
            }).html('<i class="fas fa-angle-left"></i>')
        );
        pagination.append(previous);

        for (var page = 0; page < info.pages; page++) {
            var item = $('<li class="page-item"></li>').toggleClass('active', page === info.page);
            item.append(
                $('<a></a>', {
                    class: 'page-link',
                    href: '#',
                    'data-page': page
                }).text(page + 1)
            );
            pagination.append(item);
        }

        var next = $('<li class="page-item"></li>').toggleClass('disabled', info.page >= info.pages - 1);
        next.append(
            $('<a></a>', {
                class: 'page-link',
                href: '#',
                'data-page': 'next',
                'aria-label': labelNext
            }).html('<i class="fas fa-angle-right"></i>')
        );
        pagination.append(next);

        $('#customNav').html(pagination);
    }

    $('#customSearch').on('keyup change', function() {
        table.search(this.value).draw();
    });

    $('#rolesPageLength').on('change', function() {
        table.page.len(parseInt(this.value, 10)).draw();
    });

    $('#customNav').on('click', 'a.page-link', function(event) {
        event.preventDefault();

        if ($(this).closest('.page-item').hasClass('disabled')) {
            return;
        }

        var page = $(this).data('page');

        if (page === 'previous') {
            table.page('previous').draw('page');
            return;
        }

        if (page === 'next') {
            table.page('next').draw('page');
            return;
        }

        table.page(page).draw('page');
    });

    table.on('draw', renderPagination);
    renderPagination();
});
</script>
@endpush
