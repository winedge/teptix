<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f0f7;
        }
        .ticket {
            display: flex;
            flex-direction: row;
            background: white;
            border: 2px solid #f55a8c;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            width: 100%;
            margin: 20px auto;
        }
        .left-section {
            background: linear-gradient(135deg, #4a154b, #ec407a);
            color: white;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .left-section img {
            border-radius: 8px;
            width: 100%;
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            height: auto;
            margin-bottom: 20px;
        }
        .center-section {
            flex: 3;
            text-align: center;
            padding: 20px;
        }
        .center-section h2 {
            margin: 5px 0;
            color: #333;
        }
        .center-section .event-location {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
            font-size: 14px;
        }
        .center-section .event-location span {
            margin: 0 10px;
        }
        .right-section {
            flex: 1;
            background: #f7e8ee;
            text-align: center;
            border-left: 2px dashed #f55a8c;
            padding: 20px;
        }
        .right-section h4 {
            color: #333;
            margin-bottom: 15px;
        }
        .right-section img {
            width: 150px;
            height: 150px;
            margin: 10px 0;
        }
        .date-section {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .ticket-number {
            font-weight: bold;
            color: #f55a8c;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="left-section">
            @php
                $base64Profile = '';
                try {
                    $imagePath = $setting->imagePath . $order->event->image;
                    if (file_exists($imagePath)) {
                        $base64Profile = base64_encode(file_get_contents($imagePath));
                        $base64Profile = 'data:image/png;base64,' . $base64Profile;
                    } else {
                        $base64Profile = 'data:image/svg+xml;base64,' . base64_encode('<svg width="120" height="80" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" fill="#ddd"/><text x="60" y="40" font-family="Arial" font-size="12" text-anchor="middle" fill="#666">Event Image</text></svg>');
                    }
                } catch (Exception $e) {
                    $base64Profile = 'data:image/svg+xml;base64,' . base64_encode('<svg width="120" height="80" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" fill="#ddd"/><text x="60" y="40" font-family="Arial" font-size="12" text-anchor="middle" fill="#666">Event Image</text></svg>');
                }
            @endphp
            <img src="{{ $base64Profile }}" alt="Event Image">
            <div style="margin-bottom: 10px;"><strong>Organizer:</strong><br>{{ $order->organization->first_name . ' ' . $order->organization->last_name }}</div>
            @php
                $displayPaymentType = $order->payment_type;
                if ($order->payment_status == 1 && (empty($displayPaymentType) || $displayPaymentType === 'FREE')) {
                    $displayPaymentType = 'Offline';
                }
            @endphp
            <div><strong>Payment:</strong><br>{{ $displayPaymentType }}</div>
        </div>

        <div class="center-section">
            <div class="date-section">
                <span>{{ $order->event->start_time->format('l') }}</span>
                <span>{{ $order->event->start_time->format('F jS') }}</span>
                <span>{{ $order->event->start_time->format('Y') }}</span>
            </div>
            <h2>{{ $order->event->name }}</h2>
            <p>{{ $order->event->start_time->format('d F Y, h:i a') }} to {{ $order->event->end_time->format('d F Y, h:i a') }}</p>
            <p>Doors @ 7:00 PM</p>
            <div class="event-location">
                @php
                    $logoBase64 = '';
                    try {
                        $logoPath = $setting->imagePath . $setting->logo;
                        if (file_exists($logoPath)) {
                            $logoBase64 = base64_encode(file_get_contents($logoPath));
                            $logoBase64 = 'data:image/png;base64,' . $logoBase64;
                        } else {
                            $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg width="30" height="30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="15" fill="#f55a8c"/></svg>');
                        }
                    } catch (Exception $e) {
                        $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode('<svg width="30" height="30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="15" fill="#f55a8c"/></svg>');
                    }
                @endphp
                <span>
                    <img src="{{ $logoBase64 }}" alt="Logo" style="width: 30px; height: auto;">
                </span>
                <span>{{ $order->event->type == 'online' ? 'Online Event' : $order->event->address }}</span>
            </div>
        </div>

        <div class="right-section">
            <h4>{{ $order->event->name }}</h4>
            <div>
                @php
                    $qrCode = QrCode::format('png')->size(150)->generate($orderChild->ticket_number);
                    $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
                @endphp
                <img src="{{ $base64QrCode }}" alt="QR Code">
            </div>
            <div class="ticket-number">#{{ $orderChild->ticket_number }}</div>
        </div>
    </div>
</body>
</html>
