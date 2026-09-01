@extends('frontend.master', ['activePage' => 'My-Order-Tickets'])
@section('title', __('My Order Tickets'))
@section('content')
    <style>
        body.modal-open {
                overflow: hidden;
            }

            .modal {
                display: none;
                position: fixed;
                z-index: 1050;
                left: 0;
                top: 0;
                right:0;
                bottom:0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                background-color: rgba(0, 0, 0, 0.5);
                outline: 0;
            }

            .modal-dialog {
                position: relative;
                margin: auto;
                top: 15%;
                max-width: 725px;
                background-color: #fff;
                border-radius: 5px;
                box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
            }

            .modal-content {
                position: relative;
                display: flex;
                flex-direction: column;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid rgba(0, 0, 0, 0.2);
                border-radius: 0.3rem;
                outline: 0;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #dee2e6;
            }

            .modal-header .close {
                padding: 0;
                background-color: transparent;
                border: 0;
                font-size: 1.5rem;
                line-height: 1;
                color: #000;
            }

            .modal-title {
                margin: 0;
                line-height: 1.5;
            }

            .modal-body {
                position: relative;
                overflow-y:auto;
                flex: 1 1 auto;
                padding: 1rem;
                height:300px;
            }

            .modal-footer {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                border-top: 1px solid #dee2e6;
            }

            .modal-footer > * {
                margin: 0.25rem;
            }

            .btn {
                display: inline-block;
                font-weight: 400;
                color: #212529;
                text-align: center;
                vertical-align: middle;
                cursor: pointer;
                background-color: transparent;
                border: 1px solid transparent;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: 0.25rem;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .btn-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }

            .btn-primary:hover {
                color: #fff;
                background-color: #0056b3;
                border-color: #004085;
            }

            .btn-secondary {
                color: #fff;
                background-color: #6c757d;
                border-color: #6c757d;
            }

            .btn-secondary:hover {
                color: #fff;
                background-color: #5a6268;
                border-color: #545b62;
            }
    </style>
    {{-- content --}}
    <div class=" bg-scroll min-h-screen" style="background-image: url('images/events.png')">
        {{-- scroll --}}
        <div class="mr-4 flex justify-end z-30">
            <a type="button" href="{{ url('#') }}"
                class="scroll-up-button bg-primary rounded-full p-4 fixed z-20  2xl:mt-[49%] xl:mt-[59%] xlg:mt-[68%] lg:mt-[75%] xxmd:mt-[83%] md:mt-[90%]
                xmd:mt-[90%] sm:mt-[117%] msm:mt-[125%] xsm:mt-[160%]">
                <img src="{{ asset('images/downarrow.png') }}" alt="" class="w-3 h-3 z-20">
            </a>
        </div>
        <div
            class="mt-5 3xl:mx-52 2xl:mx-28 1xl:mx-28 xl:mx-36 xlg:mx-32 lg:mx-36 xxmd:mx-24 xmd:mx-32 md:mx-28 sm:mx-20 msm:mx-16 xsm:mx-10 xxsm:mx-5 z-10 relative">
            <div
                class="flex sm:space-x-0 sm:space-y-5 msm:space-x-0 xxsm:space-x-0 xxmd:flex-col xxmd:space-x-0 xxmd:space-y-5 xmd:flex-col xxsm:flex-col lg:flex-col xlg:flex-row xlg:space-x-6">
                <div class="xlg:w-2/3 xmd:w-full xxsm:w-full lg:w-full">
                    <div>

                        <img src="{{ asset('images/upload/' . $order->event->image) }}" class=" w-full h-200 object-cover"
                            alt="">
                    </div>
                    <div class="mt-8 pb-5 bg-white shadow-lg rounded-md">
                        <div
                            class="flex justify-between p-4 lg:flex-wrap sm:flex-wrap msm:flex-wrap xxsm:flex-wrap xlg:flex-nowrap">
                            <div class="">
                                <p class="font-poppins font-semibold text-4xl leading-10 text-gray">
                                    {{ $order->event->name }}</p>
                            </div>
                            <a
                                href="{{ url('/organization/' . $order->organization->id . '/' . $order->organization->name) }}">
                                <div class="flex msm:flex-wrap xxsm:flex-wrap">
                                    <div class="">
                                        <img src="{{ asset('images/upload/' . $order->organization->image) }}"
                                            class="w-10 h-10 bg-cover object-cover" alt="">
                                    </div>
                                    <div class="ml-3">
                                        <p class="font-poppins font-normal text-base leading-6 text-gray">
                                            {{ $order->organization->name }}
                                        </p>
                                        <p class="font-poppins font-normal text-xs leading-4 text-gray-100">
                                            {{ __('Organize by') }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="flex flex-col items-center justify-center h-screen">
                            <p class="font-poppins font-semibold text-2xl leading-8 text-black pb-3 text-center">{{ __('Ticket Details') }}</p>
                            <span class="text-center">&#x2193;</span> <!-- Unicode character for down arrow -->
                            <p
                                class="font-poppins font-medium text-lg leading-6 text-primary bg-primary-light py-2 px-4 rounded-md">
                                {{ $order->order_id }}</p>
                        </div>
                    </div>
                    <?php
                        $addonscount=0;
                    ?>
                    @foreach ($order->tickets() as $ticket)
                        @if($ticket->is_add_on < 1)

                            <div class="w-full mt-1 shadow-lg  rounded-lg bg-white">
                                <div class="w-full flex flex-wrap items-center ">
                                
                                    <div class="flex-1 p-2">
                                        <button onclick="popupqr({{ $ticket->id }},'{{$ticket->name}}')" class="px-5 py-3 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md">Show QR</button>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                            <strong class="text-danger">{{ __('Name') }}:</strong> {{$ticket->name}}
                                        </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                            <strong class="text-danger">{{ __('Type') }}:</strong> {{$ticket->type}}
                                        </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        
                                            <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                                <strong class="text-danger">{{ __('Price') }}:</strong> {{$ticket->type == 'paid'? (\App\Models\Setting::first()->currency_sybmol ?? '$') . number_format($ticket->price, 2) : 'N/A'}}
                                            </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <?php
                                        $count = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                        ->where('order_id', $order->id)
                                        ->count();
                                        ?>
                                            <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                                <strong class="text-danger">{{ __('Quantity') }}:</strong> {{ $count }}
                                            </p>
                                    </div>
                                
                                </div>
                            </div>
                            <div id="modal_{{ $ticket->id }}" class="modal">
                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <!-- Modal Header -->
                                        <div class="modal-header">
                                            <h5 class="modal-title">QR Code for : <span id="title_{{ $ticket->id }}">Test</span></h5>
                                            <button type="button" class="close" onclick="popupqrclose({{ $ticket->id }})" id="closeModalBtn_{{ $ticket->id }}">&times;</button>
                                        </div>

                                        <!-- Modal Body -->
                                        <div class="modal-body">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <?php
                                                $orderchild = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                                    ->where('order_id', $order->id)->get();
                                                ?>
                                                @foreach ($orderchild as $item)
                                                    <div class="flex flex-col items-center py-5">
                                                        <p class="font-semibold text-2xl leading-8 text-black">
                                                            {{ __('Ticket Number') }}
                                                        </p>
                                                        <p class="font-normal leading-7 text-gray-500 ml-5">
                                                            {{ $item->ticket_number }}
                                                        </p>
                                                        {!! QrCode::size(180)->generate($item->ticket_number) !!}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                        
                                            <button type="button" class="btn btn-secondary" onclick="popupqrclose({{ $ticket->id }})" >Close</button>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($ticket->is_add_on > 0)
                            <?php
                                $addonscount++;
                            ?>
                            @if($addonscount == 1)
                                <div class="flex flex-col items-center justify-center h-screen mt-3">
                                    <p class="font-poppins font-semibold text-2xl leading-8 text-black p-3 text-center"><u>{{ __('Add Ons') }}</u></p>
                                </div>
                            @endif 
                            <div class="w-full mb-1 shadow-lg  rounded-lg bg-white">
                                <div class="w-full flex flex-wrap items-center ">
                                
                                    <div class="flex-1 p-2">
                                        <button onclick="popupqr({{ $ticket->id }},'{{$ticket->name}}')" class="px-5 py-3 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md">Show QR</button>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                            <strong class="text-danger">{{ __('Name') }}:</strong> {{$ticket->name}}
                                        </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                            <strong class="text-danger">{{ __('Type') }}:</strong> {{$ticket->type}}
                                        </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        
                                            <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                                <strong class="text-danger">{{ __('Price') }}:</strong> {{$ticket->type == 'paid'? (\App\Models\Setting::first()->currency_sybmol ?? '$') . number_format($ticket->price, 2) : 'N/A'}}
                                            </p>
                                    </div>
                                    <div class="flex-1 p-2">
                                        <?php
                                        $count = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                        ->where('order_id', $order->id)
                                        ->count();
                                        ?>
                                            <p class="font-poppins font-medium text-sm leading-7 text-black text-left">
                                                <strong class="text-danger">{{ __('Quantity') }}:</strong> {{ $count }}
                                            </p>
                                    </div>
                                
                                </div>
                            </div>
                            <div id="modal_{{ $ticket->id }}" class="modal">
                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <!-- Modal Header -->
                                        <div class="modal-header">
                                            <h5 class="modal-title">QR Code for : <span id="title_{{ $ticket->id }}">Test</span></h5>
                                            <button type="button" class="close" onclick="popupqrclose({{ $ticket->id }})" id="closeModalBtn_{{ $ticket->id }}">&times;</button>
                                        </div>

                                        <!-- Modal Body -->
                                        <div class="modal-body">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <?php
                                                $orderchild = \App\Models\OrderChild::where('ticket_id', $ticket->id)
                                                    ->where('order_id', $order->id)->get();
                                                ?>
                                                @foreach ($orderchild as $item)
                                                    <div class="flex flex-col items-center py-5">
                                                        <p class="font-semibold text-2xl leading-8 text-black">
                                                            {{ __('Ticket Number') }}
                                                        </p>
                                                        <p class="font-normal leading-7 text-gray-500 ml-5">
                                                            {{ $item->ticket_number }}
                                                        </p>
                                                        {!! QrCode::size(180)->generate($item->ticket_number) !!}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Modal Footer -->
                                        <div class="modal-footer">
                                        
                                            <button type="button" class="btn btn-secondary" onclick="popupqrclose({{ $ticket->id }})" >Close</button>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                </div>
                <div class="xlg:w-1/3 xmd:w-full xxsm:w-full lg:w-full xxmd:pb-10 xxsm:pb-10">
                    <div class="p-4 bg-white shadow-lg rounded-md space-y-5">
                        <p class="font-poppins font-semibold text-2xl leading-8 text-black pb-3">Order Summary : {{ $order->order_id }}
                        </p>
                        <div class="flex justify-between">
                            <p class="font-poppins font-normal text-lg leading-7 text-gray-200">{{ __('No. of Tickets') }}
                            </p>
                            <p class="font-poppins font-medium text-lg leading-7 text-gray-300">
                                {{ $order->quantity }}</p>
                        </div>
                        <div class="flex justify-between">
                                <p class="font-poppins font-normal text-lg leading-7 text-gray-200">
                                    {{ __('Coupon discount') }}</p>
                                <p class="font-poppins font-medium text-lg leading-7 text-gray-300">
                                    {{ $order->coupon_discount }}</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="font-poppins font-normal text-lg leading-7 text-gray-200">
                                {{ __('Fees and charges') }}</p>
                            <p class="font-poppins font-medium text-lg leading-7 text-gray-300">
                                {{ $order->tax }}</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="font-poppins font-bold text-lg leading-7 text-gray-200"> {{ __('Total amount') }}
                            </p>
                            <p class="font-poppins font-bold text-lg leading-7 text-gray-300">{{ $order->payment }}</p>
                        </div>
                        <div class="flex justify-between">
                            @if ($order->event->type == 'online')
                                <p class="font-poppins font-normal text-lg leading-7 text-gray-200">
                                    {{ __('Event Url') }}
                                </p>
                                <p class="font-poppins font-medium text-lg leading-7 text-gray-300">
                                    <a href="{{ $order->event->url }}    " target="_blank" rel="noopener noreferrer">{{ $order->event->url }}</a>
                                </p>
                            @endif
                        </div>
                        <div class="flex justify-between xxsm:flex-wrap sm:flex-nowrap">
                            <a href="{{ url('order-invoice-print/' . $order->id) }}" target="_blank"
                                class="font-poppins font-normal text-sm leading-5 text-success rounded-md px-4 py-2 bg-success-light flex"><img
                                    src="{{ asset('image/printer.png') }}" alt=""
                                    class="">{{ __('Print') }}</a>
                        </div>
                        @if ($order->order_status == 'Complete')
                            @if ($review == null)
                                <div class="ml-5 ">
                                    <p class="font-poppins font-semibold text-lg leading-10 text-gray">
                                        {{ __('Drop a review') }}</p>
                                    <form method="post" action="{{ url('add-review') }}">
                                        @csrf
                                        <div class="">
                                            <div class="form-group">
                                                <label
                                                    class="font-poppins font-semibold text-lg leading-10 text-gray">{{ __('Rate') }}</label>
                                                <input type="hidden" name="order_id" id="order_id"
                                                    value="{{ $order->id }}">
                                                <input type="hidden" id="rate" name="rate" value="0" required>
                                                <div class="rating">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="fa fa-star fa-2x" style="color:#d2d2d2"
                                                            onclick="addRate('{{ $i }}');"
                                                            id="rate-{{ $i }}"></i>
                                                    @endfor
                                                </div>
                                                @error('rate')
                                                    <p class="error">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label
                                                    class="font-poppins font-semibold text-lg leading-10 text-gray">{{ __('Message') }}</label>

                                            </div>
                                            <div>
                                                <textarea name="message" required
                                                    class="shadow appearance-none border rounded w-[70%] py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                                    placeholder="{{ __('Write a review') }}"></textarea>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="submit" id="add"
                                                class="px-10 py-3 text-white bg-primary text-center font-poppins font-normal text-base leading-6 rounded-md">{{ __('Submit') }}</button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="ml-5">
                                    <p class="font-poppins font-semibold text-lg leading-10 text-gray">
                                        {{ __('Your review') }}</p>
                                    <div class="flex card">
                                        <p class="font-poppins font-medium text-base leading-4 text-gray-200 pt-1 mr-3">
                                            {{ __('Rating : ' . $review->rate) }}</p>
                                        <div class="flex space-x-1">
                                            @for ($i = 1; $i <= $review->rate; $i++)
                                                <img src="{{ asset('images/star-fill.png') }}"
                                                    class="h-5 w-5 bg-cover object-cover" alt="">
                                            @endfor
                                        </div>
                                    </div>
                                    <div class=" mt-4">
                                        <p class="font-poppins font-medium text-base leading-6 text-gray-200">
                                            {{ __('Message : ' . $review->message) }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endif
                       
                        
                        <div class="px-4">
                            <div class="pt-4 flex space-x-6 md:flex-nowrap sm:flex-wrap xxsm:flex-wrap">
                                <img src="{{ asset('images/calender-icon.png') }}" alt=""
                                    class="bg-success-light rounded-md p-2 w-9 h-10">
                                <div class="flex space-x-2 ">
                                    <p class="font-poppins font-bold text-4xl leading-7 text-black">
                                        {{ Carbon\Carbon::parse($order->event->start_time)->format('d') }}
                                    </p>
                                    <p class="font-poppins font-semibold text-2xl leading-7 text-gray-200 pt-2">
                                        {{ Carbon\Carbon::parse($order->event->start_time)->format(' M Y') }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <p class="font-poppins font-bold text-4xl leading-7 text-black">
                                        {{ Carbon\Carbon::parse($order->event->end_time)->format('d') }}
                                    </p>
                                    <p class="font-poppins font-semibold text-2xl leading-7 text-gray-200 pt-2">
                                        {{ Carbon\Carbon::parse($order->event->end_time)->format('M Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 flex space-x-6 md:flex-nowrap sm:flex-wrap xxsm:flex-wrap">
                                <img src="{{ asset('images/location-icon.png') }}" alt=""
                                    class="p-2 w-9 h-10 rounded-md bg-blue-light">
                                <div class="">
                                    <p class="font-poppins font-normal text-lg leading-7 text-gray">
                                        {{ $order->event->address }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

<script>
    function popupqr(tid,name){
        // alert(tid);
        var modal = document.getElementById('modal_' + tid);
        modal.style.display = "block";
        $("#title_"+tid).html(name);
        document.body.classList.add("modal-open");
    }

    function popupqrclose(tid){
        var modal = document.getElementById('modal_' + tid);
        modal.style.display = "none";
        document.body.classList.remove('modal-open');

    }
    // document.addEventListener('DOMContentLoaded', function() {
    //     var openModalBtns = document.querySelectorAll('[id^="openModalBtn_"]');

    //     openModalBtns.forEach(function(openBtn) {
    //         var ticketId = openBtn.id.split('_')[1];
    //         var modal = document.getElementById('modal_' + ticketId);

    //         openBtn.onclick = function() {
    //             if (modal) {
    //                 modal.classList.remove('hidden');
    //                 document.body.classList.add('modal-open');
    //             }
    //         };

    //         var closeModalBtns = document.querySelectorAll('#closeModalBtn_' + ticketId + ', #closeModalBtnFooter_' + ticketId);

    //         closeModalBtns.forEach(function(closeBtn) {
    //             closeBtn.onclick = function() {
    //                 if (modal) {
    //                     modal.classList.add('hidden');
    //                     document.body.classList.remove('modal-open');
    //                 }
    //             };
    //         });
    //     });
    // });
</script>

