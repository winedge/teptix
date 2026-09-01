<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Tickets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .ticket-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }
        .ticket {
            width: 320px;
            border: 2px solid #d32f2f;
            border-radius: 10px;
            background-color: white;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .ticket-header {
            background-color: #f90b0b;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        .ticket-body {
            padding: 15px;
        }
        .event-info {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .event-info img {
            width: 60px;
            height: 40px;
            border-radius: 5px;
            object-fit: cover;
        }
        .event-details {
            flex-grow: 1;
        }
        .event-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .event-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        .ticket-divider {
            border-top: 1px dashed #d32f2f;
            padding-top: 10px;
            margin-top: 10px;
        }
        .ticket-type {
            font-size: 14px;
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .ticket-info {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        .ticket-info span {
            font-weight: 400;
        }
        .qr-code-section {
            text-align: center;
            margin-top: 10px;
        }
        .qr-code-section img {
            width: 200px;
            height: 200px;
        }
        .ticket-number {
            text-align: center;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        .ticket-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        @foreach ($order->tickets() as $ticketdata)
            @php
                $orderchildren = \App\Models\OrderChild::where('ticket_id', $ticketdata->id)
                    ->where('order_id', $order->id)
                    ->get();
            @endphp
            @foreach ($orderchildren as $item)
                <div class="ticket">
                    <div class="ticket-header">
                        <span>The Event Palette</span>
                    </div>
                    <div class="ticket-body">
                        <div class="event-info">
                            @php
                                $imagePath = public_path('images/upload/' . $order->event->image);
                                $imageData = '';
                                if (file_exists($imagePath)) {
                                    $imageContent = file_get_contents($imagePath);
                                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $imageData = 'data:image/' . $imageType . ';base64,' . base64_encode($imageContent);
                                }
                            @endphp
                            @if($imageData)
                                <img src="{{ $imageData }}" alt="Event Poster">
                            @endif
                            <div class="event-details">
                                <div class="event-name">{{ $order->event->name }}</div>
                                <div class="event-meta">{{ $order->organization->organization_name }}</div>
                                <div class="event-meta">{{ $order->event->type == 'online' ? 'Online Event' : $order->event->address }}</div>
                                <div class="event-meta">{{ $order->event->start_time->format('F d Y') }} | {{ $order->event->start_time->format('h:i a') }}</div>
                            </div>
                        </div>
                        <div class="ticket-divider">
                            <div class="ticket-type">{{ $ticketdata->name }}</div>
                            <div class="ticket-info">Ticket: <span>{{ $ticketdata->type }}</span></div>
                        </div>
                        <div class="qr-code-section">
                            @php
                                $qrCode = QrCode::format('png')->size(200)->generate($item->ticket_number);
                                $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                            @endphp
                            <img src="{{ $base64QrCode }}" alt="QR Code">
                        </div>
                        <div class="ticket-number">#{{ $item->ticket_number }}</div>
                    </div>
                    <div class="ticket-footer">All Sales Are Final! No Refunds!</div>
                </div>
            @endforeach
        @endforeach
    </div>
</body>
</html>
