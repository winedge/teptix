<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Teptix</title>
</head>
<body>
    @php
    $ticket = DB::table('order_child')
        ->select([
            'events.image',
            'events.name',
            'users.organization_name',
            'events.type',
            'events.address',
            'events.start_time',
            'tickets.name as ticket_name',
            'tickets.type as ticket_type',
            'order_child.ticket_number',
            'order_child.Book_Seat_Id',
            'app_user.email as app_email',
            'guest_user.email as guest_email'
        ])
        ->join('orders', 'order_child.order_id', '=', 'orders.id')
        ->join('events', 'orders.event_id', '=', 'events.id')
        ->join('users', 'orders.organization_id', '=', 'users.id')
        ->leftJoin('app_user', function ($join) {
            $join->on('orders.customer_id', '=', 'app_user.id')
            ->whereNotNull('orders.customer_id'); 
        })
        ->leftJoin('guest_user', function ($join) {
            $join->on('orders.guestuser_id', '=', 'guest_user.id')
            ->whereNotNull('orders.guestuser_id'); 
        })
        ->join('tickets', 'order_child.ticket_id', '=', 'tickets.id')
        ->where('order_child.id', $qrId)
        ->first();
    @endphp
    <div class="ticket qrimageData" id="ticket">
        <div style="width: 100%; max-width: 400px; border: 2px solid #d32f2f; border-radius: 10px; background-color: white; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); margin: auto;">
            <div style="background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold;">
                <span>The Event Palette</span>
            </div>
            <div style="padding: 15px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div style="flex-grow: 1; min-width: 150px;">
                        <div style="font-size: 16px; font-weight: bold;">{{ $ticket->name }}</div>
                        <div style="font-size: 12px; color: gray;">{{ $ticket->organization_name }}</div>
                        <div style="font-size: 12px; color: gray;">{{ $ticket->type == 'online' ? 'Online Event' : $ticket->address }}</div>
                        <div style="font-size: 12px; color: gray;">{{\Carbon\Carbon::parse($ticket->start_time)->format('l') }}, {{ \Carbon\Carbon::parse($ticket->start_time)->format('d F') }} | {{ \Carbon\Carbon::parse($ticket->start_time)->format('h:i a') }}</div>
                    </div>
                </div>
                <div style="border-top: 1px dashed #d32f2f; padding-top: 10px; text-align: center;">
                    <div style="font-size: 20px; color: #d32f2f; font-weight: bold;">{{ $ticket->ticket_name }}</div>
                    <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Ticket {{ $ticket->ticket_type }}</div>
                    @if(!empty($ticket->Book_Seat_Id))
                        <div style="font-size: 14px; font-weight: bold; color: #1a202c; margin-top: 6px; background-color: #f7fafc; padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e0; display: inline-block;">
                            Seat: <span style="color: #d32f2f;">{{ $ticket->Book_Seat_Id }}</span>
                        </div>
                    @endif
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    @php
                        $qrCode=QrCode::format('png')->size(150)->generate($ticket->ticket_number);
                        $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                    @endphp
                    <img src="{{ $base64QrCode }}" alt="QR Code" class="mx-auto mt-2" style="width: 100%; max-width: 200px; height: auto;"/>
                </div>
                <div style="text-align: center; margin-top: 5px; font-size: 12px; color: gray;">#{{ $ticket->ticket_number }}</div>
            </div>
            <div style="background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: gray;">
                All Sales Are Final! No Refunds!
            </div>
        </div>
    </div>
</body>
</html>