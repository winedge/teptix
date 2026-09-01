

@php
$setting=App\Models\Setting::find(1);
$orderchild = \App\Models\OrderChild::where('order_id', $orderId)->get();
$order = \App\Models\Order::with(['tickets'])->find($orderId);
// Check if all tickets are free type
$allTicketsFree = true;
if ($order && $order->tickets()) {
    foreach ($order->tickets() as $t) {
        if ($t->type != 'free') {
            $allTicketsFree = false;
            break;
        }
    }
}
@endphp
@foreach ($orderchild as $item)
    @php $ticket=\App\Models\Order::with(['event:id,start_time,end_time,name,type,address,image', 'organization:id,image,first_name,last_name','appUser:id,name,last_name', 'guestUser:id,name,last_name'])->find($orderId);
    @endphp
    <div class="ticket emailticket{{$orderId}}" id="emailticket" style="width: fit-content">
        <div class="left-section">
            <img src="{{$setting->imagePath . $ticket->event->image}}" alt="Event Image" crossOrigin="anonymous">
            <span style="font-weight:900">{{ __('Organizer:') }}</span><span>{{ $ticket->organization->first_name . ' ' . $ticket->organization->last_name }}</span>
            <span style="font-weight:900">{{ __('Payment method:') }}</span><span>
                @if ($allTicketsFree || $ticket->payment_type == 'FREE' || $ticket->tax_option == 'complimentary')
                    FREE
                @elseif ($ticket->payment_type == 'LOCAL' || $ticket->payment_type == 'Paid')
                    Offline
                @else
                    {{ $ticket->payment_type }}
                @endif
            </span>
        </div>
        <div class="center-section">
            <div class="date-section">
                <span>{{ $ticket->event->start_time->format('l') }}</span>  &nbsp;
                <span>{{ $ticket->event->start_time->format('F jS') }}</span> &nbsp; <span>{{ $ticket->event->start_time->format('Y') }}</span>
            </div>
            <h3 style="color: black">{{ $ticket->event->name }}</h3>
            <p class="event-info">{{ $ticket->event->start_time->format('d F Y') . ', ' . $ticket->event->start_time->format('h:i a') }} to {{ $ticket->event->end_time->format('d F Y') . ', ' . $ticket->event->end_time->format('h:i a') }}</p>
            <div class="event-location">
                <span>
                    <img src="{{$setting->imagePath . $setting->logo}}" alt="Smiley" style="width: 30px; height: auto;">
                </span>
                <span>{{ $ticket->event->type == 'online' ? 'Online Event' : $ticket->event->address }}</span>
            </div>
        </div>
        <div class="right-section">
            <h5 style="color: black">{{ $ticket->event->name }}</h5>
            <div class="">
                @php
                    $qrCode = QrCode::format('png')->size(150)->generate($item->ticket_number);
                    $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                @endphp
                <img src="{{ $base64QrCode }}" alt="QR Code" class="mx-auto mt-2"/>
            </div>
            <div class="ticket-number">#{{ $item->ticket_number }}</div>
        </div>
    </div>
@endforeach
