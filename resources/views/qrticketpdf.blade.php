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
				padding: 20px;
				background-color: #f5f5f5;
			}
			.ticket-container {
				max-width: 400px;
				margin: 0 auto 30px auto;
				background: white;
				border: 3px solid #d32f2f;
				border-radius: 10px;
				overflow: hidden;
				box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
				/* avoid splitting a ticket across pages */
				page-break-inside: avoid;
				break-inside: avoid;
				/* force a page break after each ticket except the last one */
				page-break-after: always;
			}
			.ticket-container:last-child {
				/* do not create an extra blank page after the last ticket */
				page-break-after: auto;
				margin-bottom: 0;
			}
			.ticket-header {
				background-color: #f90b0b;
				color: white;
				text-align: center;
				padding: 15px;
				font-size: 18px;
				font-weight: bold;
			}
			.ticket-body {
				padding: 20px;
			}
			.event-image {
				width: 80px;
				height: 80px;
				border-radius: 8px;
				object-fit: cover;
				margin-bottom: 15px;
			}
			.event-title {
				font-size: 18px;
				font-weight: bold;
				color: #333;
				margin: 5px 0;
			}
			.event-detail {
				font-size: 13px;
				color: #666;
				margin: 3px 0;
			}
			.divider {
				border-top: 2px dashed #d32f2f;
				margin: 15px 0;
			}
			.ticket-type {
				font-size: 18px;
				color: #d32f2f;
				font-weight: bold;
				text-align: center;
				margin: 10px 0;
			}
			.ticket-price {
				font-size: 15px;
				font-weight: bold;
				text-align: center;
				margin: 8px 0;
				color: #333;
			}
			.qr-section {
				text-align: center;
				margin: 20px 0;
			}
			.qr-code {
				width: 200px;
				height: 200px;
				margin: 0 auto;
			}
			.ticket-number {
				text-align: center;
				font-size: 12px;
				color: #666;
				margin-top: 10px;
			}
			.ticket-footer {
				background-color: white;
				text-align: center;
				padding: 12px;
				font-size: 11px;
				color: #666;
				border-top: 1px solid #eee;
			}
			.seat-info {
				font-size: 13px;
				color: #333;
				text-align: center;
				margin: 8px 0;
			}
		</style>
	</head>
	<body>
		@php
            $ticket = $order;
			$currency = \App\Models\Setting::first()->currency_sybmol ?? '$';
        @endphp

		@foreach ($order->ticket_data as $item)
		@php
			$ticketInfo = \App\Models\Ticket::find($item->ticket_id);
		@endphp
		<div class="ticket-container">
			<!-- Header -->
			<div class="ticket-header">
				The Event Palette
			</div>

			<!-- Body -->
			<div class="ticket-body">
				<!-- Event Image -->
				@php
					$base64Profile = base64_encode(file_get_contents($setting->imagePath . $ticket->event->image));
					$base64Profile = 'data:image/png;base64,' . $base64Profile;
				@endphp
				<img src="{{ $base64Profile }}" alt="Event Image" class="event-image">

				<!-- Event Details -->
				<div class="event-title">{{ $ticket->event->name }}</div>
				<div class="event-detail">{{ $ticket->organization->organization_name }}</div>
				<div class="event-detail">{{ $ticket->event->type == 'online' ? 'Online Event' : $ticket->event->address }}</div>
				<div class="event-detail">{{ $ticket->event->start_time->format('F d Y | h:i a') }}</div>

				<!-- Divider -->
				<div class="divider"></div>

				<!-- Ticket Type -->
				<div class="ticket-type">{{ $ticketInfo->name ?? 'Ticket' }}</div>

				<!-- Ticket Price -->
				@if($ticket->payment_type === 'FREE' || strtolower($ticketInfo->type ?? '') === 'complementary' || strtolower($ticketInfo->type ?? '') === 'complementry')
					<div class="ticket-price">Ticket: free</div>
				@else
					<div class="ticket-price">Ticket: {{ $ticketInfo->type ?? 'Paid' }}</div>
				@endif

				<!-- Seat Information -->
				@if($item->Book_Seat_Id)
					<div class="seat-info">
						<strong>Seat Number:</strong> {{ $item->Book_Seat_Id }}
					</div>
				@endif

				<!-- QR Code -->
				<div class="qr-section">
					@php
						$qrCode = QrCode::format('png')->size(200)->generate($item->ticket_number);
						$base64QrCode = 'data:image/png;base64,' . base64_encode($qrCode);
					@endphp
					<img src="{{ $base64QrCode }}" alt="QR Code" class="qr-code">
				</div>

				<!-- Ticket Number -->
				<div class="ticket-number">#{{ $item->ticket_number }}</div>
			</div>

			<!-- Footer -->
			<div class="ticket-footer">
				All Sales Are Final! No Refunds!
			</div>
		</div>
		@endforeach
	</body>
</html>
