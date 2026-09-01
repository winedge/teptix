<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Event Tickets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
        }
        .header h1 {
            color: #e74c3c;
            margin: 0;
        }
        .ticket {
            width: 320px;
            border: 2px solid #d32f2f;
            border-radius: 10px;
            background-color: white;
            overflow: hidden;
            margin: 20px auto;
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
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .event-info {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .event-poster {
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
        }
        .organizer-name {
            font-size: 12px;
            color: gray;
        }
        .event-location {
            font-size: 12px;
            color: gray;
        }
        .event-date {
            font-size: 12px;
            color: gray;
        }
        .ticket-separator {
            border-top: 1px dashed #d32f2f;
            padding-top: 10px;
        }
        .ticket-name {
            font-size: 14px;
            color: #d32f2f;
            font-weight: bold;
        }
        .ticket-info-row {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        .ticket-info-value {
            font-size: 16px;
            font-weight: 400;
            margin: 5px 0;
        }
        .qr-section {
            text-align: center;
            margin-top: 10px;
        }
        .qr-code {
            width: 200px;
            height: 200px;
        }
        .ticket-number {
            text-align: center;
            margin-top: 5px;
            font-size: 12px;
            color: gray;
        }
        .ticket-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            font-size: 10px;
            color: gray;
        }
        .email-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Event Tickets</h1>
            <p>Thank you for your booking! Here are your tickets with QR codes.</p>
        </div>

        @foreach($tickets as $ticket)
            <div class="ticket">
                <div class="ticket-header">
                    <span>The Event Palette</span>
                </div>

                <div class="ticket-body">
                    <div class="event-info">
                        <img src="{{ asset('images/upload/' . $order->event->image) }}" alt="Event Poster" class="event-poster">
                        <div class="event-details">
                            <div class="event-name">{{ $order->event->name }}</div>
                            <div class="organizer-name">{{ $order->organization->organization_name }}</div>
                            <div class="event-location">{{ $order->event->type == 'online' ? 'Online Event' : $order->event->address }}</div>
                            <div class="event-date">{{ \Carbon\Carbon::parse($order->event->start_time)->format('F d Y') }} | {{ \Carbon\Carbon::parse($order->event->start_time)->format('h:i a') }}</div>
                        </div>
                    </div>

                    <div class="ticket-separator">
                        <div class="ticket-name">{{ $ticket['ticket']->name }}</div>
                        <div class="ticket-info-row">Ticket :<span class="ticket-info-value"> {{ $ticket['ticket']->type }}</span></div>
                        <!--<div class="ticket-info-row">Seat Table :<span class="ticket-info-value">  {{ $ticket['seatTable']->name_of_table ?? 'Not assigned' }}</span></div>-->
                        @if(!empty($ticket['Book_Seat_Id']))
                            <div class="ticket-info-row">Seat Number :<span class="ticket-info-value"> {{ $ticket['Book_Seat_Id'] }}</span></div>
                        @endif
                    </div>

                    <div class="qr-section">
                        <img src="{{ $ticket['qr_code_base64'] }}" alt="QR Code" class="qr-code"/>
                    </div>

                    <div class="ticket-number">
                        #{{ $ticket['ticket_number'] }}
                    </div>
                </div>

                <div class="ticket-footer">
                    All Sales Are Final! No Refunds!
                </div>
            </div>
        @endforeach

        <div class="email-footer">
            <p>Please present these QR codes at the event for entry.</p>
            <p>For any questions, please contact the event organizer.</p>
            <p>Order ID: {{ $order->order_id }}</p>
        </div>
    </div>
</body>
</html>
