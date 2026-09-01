@extends('master')

@section('content')
    @php
        $currency = \App\Models\Setting::first()->currency;
    @endphp
    <style>
        .disabledbtn {
            pointer-events: none;
            cursor: default;
        }
    </style>
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Fee Type'),
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
                                    <h2 class="section-title mt-0"> {{ __('View Fee Types') }}</h2>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <button class="btn btn-primary add-button">
                                        <a href="{{ route('fee-type.create') }}">
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
                                            <th>{{ __('Charges') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                            <th>{{ __('Default') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fees as $item)
                                            <tr>
                                                <td></td>
                                                <td>{{ $item->name }}</td>
                                                @if ($item->amount_type == 'percentage')
                                                    <td>{{ $item->price }}{{ '%' }}</td>
                                                @else
                                                    <td>{{ $currency . $item->price }}</td>
                                                @endif

                                                <td> 
                                                    <span class="badge {{ $item->status == 1 ? 'badge-success' : 'badge-danger' }} m-1">
                                                        {{ $item->status == 1 ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <a href="{{ route('fee-type.edit', $item->id) }}" class="btn-icon">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" onclick="deleteFee('{{ $item->id }}')" class="btn-icon text-danger">
                                                        <i class="fas fa-trash-alt text-danger"></i>
                                                    </a>
                                                </td>
                                                @php
                                                    $def = $item->is_default == 1 ? 'checked' : '';
                                                @endphp
                                                <td> 
                                                    <input type="radio" name="default" onclick="setDefaultFee('{{ $item->id }}')" {{ $def }} id="default">
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

    <script>
        function setDefaultFee(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to set this record as default fee",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, set default'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "get",
                        dataType: "JSON",
                        url: '{{ url("fee-type/setdefault") }}/' + id,
                        success: function (result) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                            Swal.fire({
                                icon: 'success',
                                title: 'Set to default!',
                                text: 'The record was set to default successfully.'
                            })
                        },
                        error: function (err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'The record was not set to default!'
                            })
                        }
                    });
                }
            });
        }

        function deleteFee(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this record?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {
                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = '{{ url("fee-type") }}/' + id;
                    
                    var csrfInput = document.createElement("input");
                    csrfInput.type = "hidden";
                    csrfInput.name = "_token";
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    var methodInput = document.createElement("input");
                    methodInput.type = "hidden";
                    methodInput.name = "_method";
                    methodInput.value = "DELETE";
                    form.appendChild(methodInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection
