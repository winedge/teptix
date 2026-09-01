@extends('master')

@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
            'title' => __('Edit Ticket'),
            'headerData' => __('Ticket'),
            'url' => $event->id . '/' . preg_replace('/\s+/', '-', $event->name) . '/tickets',
        ])

        <div class="section-body">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="section-title"> {{ __('Edit Ticket') }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" class="ticket-form" action="{{ url('ticket/update/' . $ticket->id) }}"
                                enctype="multipart/form-data">
                                @csrf

                                <input type="hidden" name="event_id" value="{{ $event->id }}">
                                <div class="form-group">
                                    <div class="selectgroup">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="type"
                                                {{ $ticket->type == 'free' ? '' : 'checked' }} value="paid"
                                                class="selectgroup-input">
                                            <span class="selectgroup-button">{{ __('Paid') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" {{ $ticket->type == 'free' ? 'checked' : '' }}
                                                name="type" value="free" class="selectgroup-input">
                                            <span class="selectgroup-button">{{ __('Free') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Name') }}</label>
                                            <input type="text" name="name" placeholder="{{ __('Name') }}"
                                                value="{{ $ticket->name }}"
                                                class="form-control @error('name')? is-invalid @enderror">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Quantity') }}</label>
                                            <input type="number" name="quantity" min="1"
                                                placeholder="{{ __('Quantity') }}" value="{{ $ticket->quantity }}"
                                                class="form-control @error('quantity')? is-invalid @enderror">
                                            @error('quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Price') }} ({{ $currency }})</label>
                                            <input type="number" name="price" min="1"
                                                {{ $ticket->type == 'free' ? 'disabled' : '' }}
                                                placeholder="{{ __('Price') }}" id="price"
                                                value="{{ $ticket->price }}" step="any"
                                                class="form-control @error('price')? is-invalid @enderror">
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Maximum ticket per order') }}</label>
                                            <input type="number" name="ticket_per_order" min="1"
                                                placeholder="{{ __('Maximum ticket per order') }}" id="ticket_per_order"
                                                value="{{ $ticket->ticket_per_order }}"
                                                class="form-control @error('ticket_per_order')? is-invalid @enderror">
                                            @error('ticket_per_order')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @if(isset($venueMapRows) && $venueMapRows->isNotEmpty())
                                    @php
                                        $selectedVenueMapRows = array_map('intval', old('venue_map_row_ids', $selectedVenueMapRowIds ?? []));
                                    @endphp
                                    <div class="row mb-3">
                                        <div class="col-lg-12">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <label class="font-weight-bold mb-0">{{ __('Venue Map Rows for this Ticket') }}</label>
                                                    <label class="custom-control custom-checkbox mb-0 cursor-pointer" style="cursor: pointer;">
                                                        <input type="checkbox" id="select-all-venue-map-rows" class="custom-control-input">
                                                        <span class="custom-control-label font-weight-bold text-primary">{{ __('Select All Seats / Rows') }}</span>
                                                    </label>
                                                </div>
                                                <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;">
                                                    @foreach($venueMapRows->groupBy('event_venue_section_id') as $sectionRows)
                                                        @php $sectionName = optional($sectionRows->first()->section)->name ?: __('Section'); @endphp
                                                        <div class="section-row-group mb-3 pb-2 border-bottom">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <strong class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">{{ $sectionName }}</strong>
                                                                <label class="custom-control custom-checkbox mb-0 cursor-pointer" style="cursor: pointer;">
                                                                    <input type="checkbox" class="custom-control-input select-section-rows-checkbox">
                                                                    <span class="custom-control-label text-muted" style="font-size: 11px;">{{ __('Select Section') }}</span>
                                                                </label>
                                                            </div>
                                                            <div class="row">
                                                                @foreach($sectionRows as $venueMapRow)
                                                                    @php $isChecked = in_array((int) $venueMapRow->id, $selectedVenueMapRows, true); @endphp
                                                                    <div class="col-md-4 col-sm-6 mb-2">
                                                                        <div class="custom-control custom-checkbox bg-white p-2 rounded border">
                                                                            <input type="checkbox" name="venue_map_row_ids[]" value="{{ $venueMapRow->id }}" data-seat-count="{{ $venueMapRow->seat_count }}" class="custom-control-input venue-map-row-checkbox" id="vrow_{{ $venueMapRow->id }}" {{ $isChecked ? 'checked' : '' }}>
                                                                            <label class="custom-control-label d-flex justify-content-between align-items-center w-100 pr-2" for="vrow_{{ $venueMapRow->id }}" style="cursor: pointer; font-size: 12px;">
                                                                                <span>{{ $sectionName }} - {{ $venueMapRow->name }}</span>
                                                                                <small class="text-muted ml-1">({{ $venueMapRow->seat_count }} {{ __('seats') }})</small>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <small class="form-text text-muted">
                                                    {{ __('Select rows or check Select All to assign seats to this ticket.') }}
                                                </small>
                                                @error('venue_map_row_ids')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                @error('venue_map_row_ids.*')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Sale Start Time') }}</label>
                                            <input type="text" name="start_time" id="start_time"
                                                value="{{ $ticket->start_time }}"
                                                placeholder="{{ __('Choose Start time') }}"
                                                class="form-control date @error('start_time')? is-invalid @enderror">
                                            @error('start_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Sale End Time') }}</label>
                                            <input type="text" name="end_time" id="end_time"
                                                value="{{ $ticket->end_time }}" placeholder="{{ __('Choose End time') }}"
                                                class="form-control date @error('end_time')? is-invalid @enderror">
                                            @error('end_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Valid on') }}</label>
                                            <select name="allday" id=""
                                                class="form-control @error('allday')? is-invalid @enderror  w-100">
                                                <option value="1" {{ $ticket->allday == 1 ? 'selected' : '' }}>
                                                    {{ __('Anyday of the event') }}
                                                </option>
                                                <option value="0" {{ $ticket->allday == 0 ? 'selected' : '' }}>
                                                    {{ __('Only on date chosen by the customer during booking') }}</option>
                                            </select>
                                            @error('allday')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Maximum Check-ins') }}</label>
                                            <input type="number" name="maximum_checkins"
                                                placeholder="{{ __('Maximum Check-ins') }}" id="maximum_checkins"
                                                value="{{ $ticket->maximum_checkins ?? 1 }}"
                                                class="form-control @error('maximum_checkins')? is-invalid @enderror">
                                            @error('maximum_checkins')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Description') }}</label>
                                            <textarea name="description" placeholder="{{ __('Description') }}"
                                                class="form-control @error('description')? is-invalid @enderror">{{ $ticket->description }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('status') }}</label>
                                            <select name="status" class="form-control select2">
                                                <option value="1" {{ $ticket->status == '1' ? 'selected' : '' }}>
                                                    {{ __('Active') }}</option>
                                                <option value="0" {{ $ticket->status == '0' ? 'selected' : '' }}>
                                                    {{ __('Inactive') }}</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Multiple Tax') }}</label>
                                            <select name="tax_ids[]" class="form-control tax_ids select2" multiple>
                                            @if(isset($tax) && !empty($tax))
                                                <?php
                                                    $taxarray=explode(',',$ticket->tax_id);
                                                ?>
                                                @foreach ($tax as $key => $item)
                                                    <option value="{{$item->id}}" {{(in_array($item->id, $taxarray)) ? 'selected' : ''}}>{{$item->name}}</option>
                                                @endforeach
                                            @else
                                                <option value="">{{__('No Data')}}</option>
                                            @endif
                                            </select>
                                            @error('tax_ids')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-group form-check">
                                            <input type="checkbox" name="is_add_on" id="is_add_on"
                                                value="1" style="width: 20px; height: 20px;" {{$ticket->is_add_on == '1' ? 'checked' : ''}}
                                                class="form-check-input @error('is_add_on') is-invalid @enderror">
                                            <label class="form-check-label" for="is_add_on" style="font-size: 1.2rem; margin-left:4px;">{{ __('Is Add Ons') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group form-check">
                                            <input type="checkbox" name="connect_with_seat" id="connect_with_seat"
                                                value="1" style="width: 20px; height: 20px;"
                                                {{ (!empty($ticket->SeatTable_id) || (isset($ticket->connect_with_seat) && $ticket->connect_with_seat == '1')) ? 'checked' : '' }}
                                                class="form-check-input" onchange="toggleSeatTable()">
                                            <label class="form-check-label" for="connect_with_seat" style="font-size: 1.2rem; margin-left:4px;">{{ __('Connect with Seat Table') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group form-check">
                                            <input type="checkbox" name="allow_to_user" id="allow_to_user"
                                                value="1" style="width: 20px; height: 20px;" {{ ($ticket->allow_to_user ?? 1) == 1 ? 'checked' : '' }}
                                                class="form-check-input @error('allow_to_user') is-invalid @enderror">
                                            <label class="form-check-label" for="allow_to_user" style="font-size: 1.2rem; margin-left:4px;">{{ __('Allow to User') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="seat-table-row" style="display: {{ (!empty($ticket->SeatTable_id) || (isset($ticket->connect_with_seat) && $ticket->connect_with_seat == '1')) ? 'block' : 'none' }};">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>{{ __('Seat Table') }}</label>

                                            

                                            <select name="SeatTable_id[]" id="SeatTable_id" class="form-control select2" multiple>
                                                @if(isset($seatTables) && !empty($seatTables))
                                                    @php
                                                        $selectedSeatTables = isset($currentSeatTableIds) ? $currentSeatTableIds : [];
                                                        // Also check if SeatTable_id is passed from controller
                                                        if (empty($selectedSeatTables) && isset($SeatTable_id)) {
                                                            $selectedSeatTables = $SeatTable_id;
                                                        }
                                                        // Convert to array if string
                                                        if (is_string($selectedSeatTables)) {
                                                            $selectedSeatTables = explode(',', $selectedSeatTables);
                                                        }
                                                        $selectedSeatTables = array_filter($selectedSeatTables ?? []);
                                                    @endphp
                                                    @foreach ($seatTables as $seatTable)
                                                        <option value="{{ $seatTable->id }}"
                                                            @if(in_array($seatTable->id, $selectedSeatTables)) selected @endif>
                                                            {{ $seatTable->name_of_table }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="">{{ __('No Seat Tables Available') }}</option>
                                                @endif
                                            </select>
                                            @error('SeatTable_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    $(document).ready(function() {
                                        $('#SeatTable_id').select2({
                                            placeholder: "{{ __('Select Seat Tables') }}",
                                            width: '100%'
                                        });

                                        $('.venue-map-row-select').select2({
                                            placeholder: "{{ __('Select venue map rows') }}",
                                            allowClear: true,
                                            width: '100%'
                                        });

                                        function syncQuantityFromVenueRows() {
                                            var totalSeats = 0;

                                            $('.venue-map-row-checkbox:checked').each(function() {
                                                totalSeats += parseInt($(this).data('seat-count'), 10) || 0;
                                            });

                                            if (totalSeats > 0) {
                                                $('input[name="quantity"]').val(totalSeats);
                                            }
                                        }

                                        var selectAllCheckbox = document.getElementById('select-all-venue-map-rows');
                                        var rowCheckboxes = document.querySelectorAll('.venue-map-row-checkbox');
                                        var sectionCheckboxes = document.querySelectorAll('.select-section-rows-checkbox');

                                        function updateSelectAllState() {
                                            if (!selectAllCheckbox || rowCheckboxes.length === 0) return;
                                            var allChecked = true;
                                            rowCheckboxes.forEach(function (cb) {
                                                if (!cb.checked) allChecked = false;
                                            });
                                            selectAllCheckbox.checked = allChecked;

                                            sectionCheckboxes.forEach(function (secCb) {
                                                var group = secCb.closest('.section-row-group');
                                                if (group) {
                                                    var groupCbs = group.querySelectorAll('.venue-map-row-checkbox');
                                                    var groupAllChecked = groupCbs.length > 0;
                                                    groupCbs.forEach(function (cb) {
                                                        if (!cb.checked) groupAllChecked = false;
                                                    });
                                                    secCb.checked = groupAllChecked;
                                                }
                                            });

                                            syncQuantityFromVenueRows();
                                        }

                                        if (selectAllCheckbox) {
                                            selectAllCheckbox.addEventListener('change', function () {
                                                var isChecked = this.checked;
                                                rowCheckboxes.forEach(function (cb) {
                                                    cb.checked = isChecked;
                                                });
                                                sectionCheckboxes.forEach(function (secCb) {
                                                    secCb.checked = isChecked;
                                                });
                                                syncQuantityFromVenueRows();
                                            });
                                        }

                                        sectionCheckboxes.forEach(function (secCb) {
                                            secCb.addEventListener('change', function () {
                                                var isChecked = this.checked;
                                                var group = this.closest('.section-row-group');
                                                if (group) {
                                                    group.querySelectorAll('.venue-map-row-checkbox').forEach(function (cb) {
                                                        cb.checked = isChecked;
                                                    });
                                                }
                                                updateSelectAllState();
                                            });
                                        });

                                        rowCheckboxes.forEach(function (cb) {
                                            cb.addEventListener('change', updateSelectAllState);
                                        });

                                        updateSelectAllState();
                                    });
                                </script>
                                <div class="form-group">
                                    <button type="submit"
                                        class="btn btn-primary demo-button">{{ __('Submit') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var checkbox = document.getElementById('is_add_on');
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.value = 1; // Set value to 1
                this.setAttribute('checked', 'checked'); // Add the checked attribute
            } else {
                this.value = 0; // Set value to 0
                this.removeAttribute('checked'); // Remove the checked attribute
            }
        });
    });

    function toggleSeatTable() {
        var checkbox = document.getElementById('connect_with_seat');
        var seatTableRow = document.getElementById('seat-table-row');

        if (checkbox.checked) {
            seatTableRow.style.display = 'block';
            checkbox.value = 1;
        } else {
            seatTableRow.style.display = 'none';
            checkbox.value = 0;
            // Clear selected options when hiding
            $('#SeatTable_id').val(null).trigger('change');
        }
    }

    // Initialize the display state on page load
    document.addEventListener('DOMContentLoaded', function() {
        var checkbox = document.getElementById('connect_with_seat');
        var seatTableRow = document.getElementById('seat-table-row');

        // Show seat table row if checkbox is checked OR if there's existing seat table data
        var hasExistingSeatData = '{{ $ticket->SeatTable_id ?? "" }}' !== '';
        if (checkbox.checked || hasExistingSeatData) {
            seatTableRow.style.display = 'block';
            if (hasExistingSeatData && !checkbox.checked) {
                checkbox.checked = true;
                checkbox.value = 1;
            }
        }
    });
</script>
