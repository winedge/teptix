<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            width: 320px;
        }
        .ticket-container {
            width: 320px;
            border: 2px solid #d32f2f;
            border-radius: 10px;
            background-color: white;
            overflow: hidden;
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
        .event-section {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .event-image {
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
        .event-info {
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
        .ticket-detail {
            font-size: 14px;
            margin: 5px 0;
        }
        .ticket-detail strong {
            font-weight: bold;
        }
        .ticket-detail span {
            font-weight: normal;
        }
        .qr-section {
            text-align: center;
            margin-top: 15px;
        }
        .qr-section img {
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
        <div class="ticket-header">
            <span>The Event Palette</span>
        </div>
        <div class="ticket-body">
            <div class="event-section">
                @if($event->image)
                    <img src="{{ public_path('images/upload/' . $event->image) }}" alt="Event Poster" class="event-image">
                @endif
                <div class="event-details">
                    <div class="event-name">{{ $event->name }}</div>
                    <div class="event-info">{{ $organization->organization_name }}</div>
                    <div class="event-info">{{ $event->type == 'online' ? 'Online Event' : $event->address }}</div>
                    <div class="event-info">{{ $event->start_time->format('F d Y') }} | {{ $event->start_time->format('h:i a') }}</div>
                </div>
            </div>
            <div class="ticket-divider">
                <div class="ticket-type">{{ $ticket->ticket?->name ?? 'Ticket' }}</div>
                <div class="ticket-detail"><strong>Ticket:</strong> <span>{{ $ticket->ticket?->type ?? 'N/A' }}</span></div>
                @if(!empty($ticket->Book_Seat_Id))
                    <div class="ticket-detail"><strong>Seat Number:</strong> <span>{{ $ticket->Book_Seat_Id }}</span></div>
                @endif
            </div>
            <div class="qr-section">
                @php
                    $qrCode = QrCode::format('png')->size(200)->generate($ticket->ticket_number);
                    $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                @endphp
                <img src="{{ $base64QrCode }}" alt="QR Code"/>
            </div>
            <div class="ticket-number">#{{ $ticket->ticket_number }}</div>
        </div>
        <div class="ticket-footer">All Sales Are Final! No Refunds!</div>
    </div>
</body>
</html>
