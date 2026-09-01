@extends('master')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            important: '#ticket-create-page',
            corePlugins: {
                preflight: false
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .ticket-form input[type="text"],
        .ticket-form input[type="number"],
        .ticket-form select,
        .ticket-form textarea {
            height: 40px;
            min-height: 40px;
            line-height: 22px;
        }

        .ticket-form textarea {
            resize: none;
            overflow-y: auto;
        }

        .ticket-form .select2-container--default .select2-selection--multiple,
        .ticket-form .select2-container--default .select2-selection--single {
            height: 40px;
            min-height: 40px;
            padding: 4px 6px;
            border-color: transparent;
            font-size: 13px;
            overflow: hidden;
        }

        .ticket-form .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
        }

        .ticket-form .select2-container--default .select2-selection--multiple .select2-selection__choice {
            margin-top: 2px;
            padding: 2px 6px;
            font-size: 12px;
        }

        .ticket-form .select2-container--default .select2-search--inline .select2-search__field {
            margin-top: 4px;
            height: 28px;
            min-height: 28px;
            font-size: 13px;
        }

        .ticket-field:focus-within {
            border-color: var(--primary_color);
            box-shadow: 0 0 0 1px var(--primary_color);
        }

        .ticket-type-option:has(input:checked) {
            background: var(--primary_color);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.14);
        }

        .ticket-primary-icon {
            color: var(--primary_color);
        }

        .ticket-primary-checkbox {
            accent-color: var(--primary_color);
        }

        .ticket-submit-button {
            min-height: 40px;
            background: var(--primary_color);
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
        }

        .ticket-submit-button:hover {
            filter: brightness(0.93);
        }

        .ticket-submit-button:focus {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary_color) 24%, transparent);
        }

        .main-navbar .navbar-right,
        .main-navbar .navbar-right > .dropdown > .nav-link {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
        }

        .main-navbar .dropdown-menu .dropdown-item {
            display: block;
            width: 100%;
            clear: both;
            white-space: nowrap;
        }

        .main-navbar .dropdown-menu .dropdown-item.has-icon i {
            width: 22px;
            margin-right: 7px;
            text-align: center;
        }
    </style>
@endpush

@section('content')
<section id="ticket-create-page" class="min-h-screen bg-gray-50 py-2 px-2 sm:px-2 lg:px-2 font-sans">
    <div class="mx-auto bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-2 py-2 border-b border-gray-100">
            <h1 class="text-lg font-bold text-gray-900">{{ __('Add Ticket') }}</h1>
        </div>

        <div class="p-2">
            <form method="post" class="ticket-form space-y-2" action="{{ url('ticket/create') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="event_id" value="{{ $event->id }}">

                <div class="flex items-center">
                    <div class="inline-grid grid-cols-2 rounded-lg border border-gray-300 bg-gray-100 p-1 shadow-sm">
                        <label class="ticket-type-option relative flex h-9 min-w-[86px] items-center justify-center rounded-md px-4 text-sm font-semibold text-gray-700 cursor-pointer transition-all">
                            <input type="radio" name="type" {{ old('type') == 'free' ? '' : 'checked' }} value="paid" class="sr-only selectgroup-input">
                            <span>{{ __('Paid') }}</span>
                        </label>
                        <label class="ticket-type-option relative flex h-9 min-w-[86px] items-center justify-center rounded-md px-4 text-sm font-semibold text-gray-700 cursor-pointer transition-all">
                            <input type="radio" {{ old('type') == 'free' ? 'checked' : '' }} name="type" value="free" class="sr-only selectgroup-input">
                            <span>{{ __('Free') }}</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Name') }}</label>
                        <div class="relative flex items-center border @error('name') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            </div>
                            <input type="text" name="name" placeholder="{{ __('Enter ticket name') }}" value="{{ old('name') }}" class="h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r">
                        </div>
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Quantity') }}</label>
                        <div class="relative flex items-center border @error('quantity') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l font-bold text-lg">#</div>
                            <input type="number" name="quantity" min="1" placeholder="{{ __('Quantity') }}" value="{{ old('quantity') }}" class="h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r">
                        </div>
                        @error('quantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Price') }} ({{ $currency }})</label>
                        <div class="relative flex items-center border @error('price') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            </div>
                            <input type="number" name="price" min="1" placeholder="{{ __('Price') }}" id="price" step="any" value="{{ old('price') }}" class="h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r">
                        </div>
                        @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Maximum ticket per order') }}</label>
                        <div class="relative flex items-center border @error('ticket_per_order') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1V7zM4 11a1 1 0 011-1h10a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1v-1zM4 15a1 1 0 011-1h10a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1v-1z"></path></svg>
                            </div>
                            <input type="number" name="ticket_per_order" min="1" required placeholder="{{ __('Maximum ticket per order') }}" id="ticket_per_order" value="{{ old('ticket_per_order') }}" class="h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r">
                        </div>
                        @error('ticket_per_order') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(isset($venueMapRows) && $venueMapRows->isNotEmpty())
                    @php $selectedVenueMapRows = array_map('intval', old('venue_map_row_ids', [])); @endphp
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-bold text-gray-900">{{ __('Venue Map Rows for this Ticket') }}</label>
                            <label class="inline-flex items-center cursor-pointer text-xs font-semibold text-primary hover:underline">
                                <input type="checkbox" id="select-all-venue-map-rows" class="rounded border-gray-300 text-primary focus:ring-primary mr-1.5 h-4 w-4">
                                <span>{{ __('Select All Seats / Rows') }}</span>
                            </label>
                        </div>

                        <div class="border @error('venue_map_row_ids') border-red-500 @else border-gray-300 @enderror rounded-lg p-3 bg-gray-50/50 max-h-60 overflow-y-auto space-y-3">
                            @foreach($venueMapRows->groupBy('event_venue_section_id') as $sectionRows)
                                @php $sectionName = optional($sectionRows->first()->section)->name ?: __('Section'); @endphp
                                <div class="section-row-group">
                                    <div class="flex items-center justify-between mb-1.5 pb-1 border-b border-gray-200">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $sectionName }}</span>
                                        <label class="inline-flex items-center cursor-pointer text-[11px] font-medium text-gray-500 hover:text-gray-800">
                                            <input type="checkbox" class="select-section-rows-checkbox rounded border-gray-300 mr-1 h-3.5 w-3.5">
                                            <span>{{ __('Select Section') }}</span>
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($sectionRows as $venueMapRow)
                                            @php $isChecked = in_array((int) $venueMapRow->id, $selectedVenueMapRows, true); @endphp
                                            <label class="inline-flex items-center p-2 rounded bg-white border border-gray-200 hover:border-primary/50 cursor-pointer transition text-xs">
                                                <input type="checkbox" name="venue_map_row_ids[]" value="{{ $venueMapRow->id }}" data-seat-count="{{ $venueMapRow->seat_count }}" class="venue-map-row-checkbox rounded border-gray-300 text-primary focus:ring-primary mr-2 h-4 w-4" {{ $isChecked ? 'checked' : '' }}>
                                                <span class="font-medium text-gray-800">{{ $sectionName }} - {{ $venueMapRow->name }}</span>
                                                <span class="ml-auto text-[11px] text-gray-400">({{ $venueMapRow->seat_count }} {{ __('seats') }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">{{ __('Select rows or check Select All to assign seats to this ticket.') }}</p>
                        @error('venue_map_row_ids') <p class="text-[11px] text-red-500 mt-1 d-block">{{ $message }}</p> @enderror
                        @error('venue_map_row_ids.*') <p class="text-[11px] text-red-500 mt-1 d-block">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Sale Start Time') }}</label>
                        <div class="relative flex items-center border @error('start_time') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field bg-white">
                            <input type="text" name="start_time" id="start_time" value="{{ old('start_time') }}" placeholder="{{ __('Choose Start Time') }}" class="date h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-l">
                            <div class="px-3 ticket-primary-icon flex items-center justify-center h-10 w-11">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        @error('start_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Sale End Time') }}</label>
                        <div class="relative flex items-center border @error('end_time') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field bg-white">
                            <input type="text" name="end_time" id="end_time" value="{{ old('end_time') }}" placeholder="{{ __('Choose End Time') }}" class="date h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-l">
                            <div class="px-3 ticket-primary-icon flex items-center justify-center h-10 w-11">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        @error('end_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Valid on') }}</label>
                        <div class="relative flex items-center border @error('allday') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field bg-white">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <select name="allday" class="h-10 w-full px-3 py-2 text-sm text-gray-900 focus:outline-none rounded-r appearance-none bg-transparent pr-8">
                                <option value="1" {{ old('allday', '1') == '1' ? 'selected' : '' }}>{{ __('Anyday of the event') }}</option>
                                <option value="0" {{ old('allday') == '0' ? 'selected' : '' }}>{{ __('Only on date chosen by the customer during booking') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                        @error('allday') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Maximum Check-ins') }}</label>
                        <div class="relative flex items-center border @error('maximum_checkins') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <input type="number" name="maximum_checkins" placeholder="{{ __('Maximum Check-ins') }}" id="maximum_checkins" value="{{ old('maximum_checkins', 1) }}" class="h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r">
                        </div>
                        @error('maximum_checkins') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Description') }}</label>
                        <div class="relative flex border @error('description') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field items-stretch">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <textarea name="description" placeholder="{{ __('Description') }}" class="h-10 min-h-10 w-full px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none rounded-r resize-none">{{ old('description') }}</textarea>
                        </div>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('status') }}</label>
                        <div class="relative flex items-center border @error('status') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field bg-white">
                            <select name="status" class="h-10 w-full px-3 py-2 text-sm text-green-600 font-medium focus:outline-none rounded appearance-none bg-transparent pr-8">
                                <option value="1" class="text-green-600 font-medium">{{ __('Active') }}</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }} class="text-red-600 font-medium">{{ __('Inactive') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                        @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-gray-900">{{ __('Multiple Tax') }}</label>
                        <div class="relative flex items-center border @error('tax_ids') border-red-500 @else border-gray-300 @enderror rounded shadow-sm ticket-field bg-white">
                            <div class="px-3 border-r border-gray-300 text-gray-500 bg-gray-50 flex items-center justify-center h-10 w-11 rounded-l font-medium text-lg">%</div>
                            <select name="tax_ids[]" class="tax_ids h-10 w-full px-3 py-2 text-sm text-gray-900 focus:outline-none rounded-r appearance-none bg-transparent pr-8" multiple>
                                @if(isset($tax) && !empty($tax))
                                    @foreach ($tax as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">{{ __('No Data') }}</option>
                                @endif
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                        @error('tax_ids') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center space-x-4 pt-1">
                    <div class="flex items-center space-x-1.5">
                        <input type="checkbox" name="is_add_on" id="is_add_on" value="1" class="ticket-primary-checkbox w-4 h-4 border-gray-300 rounded @error('is_add_on') is-invalid @enderror">
                        <label class="text-xs font-bold text-gray-900 select-none" for="is_add_on">{{ __('Is Add Ons') }}</label>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <input type="checkbox" name="allow_to_user" id="allow_to_user" value="1" {{ old('allow_to_user', '1') == '1' ? 'checked' : '' }} class="ticket-primary-checkbox w-4 h-4 border-gray-300 rounded @error('allow_to_user') is-invalid @enderror">
                        <label class="text-xs font-bold text-gray-900 select-none" for="allow_to_user">{{ __('Allow to User') }}</label>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="ticket-submit-button inline-flex items-center justify-center gap-2 rounded-lg px-6 py-2 text-sm font-semibold transition-all">
                        <i class="fas fa-save text-xs"></i>
                        {{ __('Submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        $('.tax_ids').select2({
            placeholder: "{{ __('Choose tax method') }}",
            allowClear: true,
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
@endpush
