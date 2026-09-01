<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generating Ticket Images</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .message {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .qrimageData {
            display: none; /* Hide from view but keep for image generation */
        }
    </style>
</head>
<body>
    <div class="message">
        <h2>Generating Ticket Images...</h2>
        <p>Please wait while we generate and send your ticket images via email.</p>
        <p>This page will process automatically.</p>
    </div>

    <!-- Hidden ticket elements for image generation (same as guestordersuccess) -->
    @foreach ($tickets as $keyData => $ticket)
        <div id="ticket" style="width: 320px; border: 2px solid #d32f2f; border-radius: 10px; background-color: white; overflow: hidden;" class="qrimageData">
            <div style="background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold;">
                <span>The Event Palette</span>
            </div>
            <div style="padding: 15px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <img src="{{ asset('images/upload/' . $order->event->image) }}" alt="Event Poster" style="width: 60px; height: 40px; border-radius: 5px; object-fit: cover;">
                    <div style="flex-grow: 1;">
                        <div style="font-size: 16px; font-weight: bold;">{{ $order->event->name }}</div>
                        <div style="font-size: 12px; color: gray;">{{ $order->organization->organization_name }}</div>
                        <div style="font-size: 12px; color: gray;">{{ $order->event->type == 'online' ? 'Online Event' : $order->event->address }}</div>
                        <div style="font-size: 12px; color: gray;">{{ \Carbon\Carbon::parse($order->event->start_time)->format('F d Y') }} | {{ \Carbon\Carbon::parse($order->event->start_time)->format('h:i a') }}</div>
                    </div>
                </div>
                <div style="border-top: 1px dashed #d32f2f; padding-top: 10px;">
                    <div style="font-size: 14px; color: #d32f2f; font-weight: bold;">{{ $ticket['ticket']->name }}</div>
                    <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Ticket :<span style="font-size: 16px; font-weight:400 ; margin: 5px 0;"> {{ $ticket['ticket']->type }}</span></div>
                    <!--<div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Seat Table :<span style="font-size: 16px; font-weight:400 ; margin: 5px 0;">{{ $ticket['seatTable']->name_of_table ?? 'Not assigned' }}</span></div>-->
                    @if(!empty($ticket['Book_Seat_Id']))
                        <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Seat Number :<span style="font-size: 16px; font-weight:400 ; margin: 5px 0;"> {{ $ticket['Book_Seat_Id'] }}</span></div>
                    @endif
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <img src="{{ $ticket['qr_code_base64'] }}" alt="QR Code" class="mx-auto mt-2" style="width: 200px; height: 200px;"/>
                </div>
                <div style="text-align: center; margin-top: 5px; font-size: 12px; color: gray;"> #{{ $ticket['ticket_number'] }} </div>
            </div>
            <div style="background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: gray;">All Sales Are Final! No Refunds!</div>
        </div>
    @endforeach

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    // Automatically generate and send PNG images when page loads
    setTimeout(function() {
        generateAndSendImages();
    }, 2000);

    function generateAndSendImages() {
        const tickets = document.querySelectorAll('.qrimageData');
        const images = []; // Array to store all images

        // Generate the canvas for each ticket
        const promises = Array.from(tickets).map(ticket => {
            return html2canvas(ticket).then(function (canvas) {
                // Convert the canvas to base64 PNG image
                const imageData = canvas.toDataURL('image/png');
                images.push(imageData); // Add image to the array
            });
        });

        // Once all canvases are generated
        Promise.all(promises).then(() => {
            // Prepare the data to be sent to the server
            const formData = new FormData();
            images.forEach((image, index) => {
                formData.append(`images[${index}]`, image); // Send array of images
            });
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('email', '{{ $userDetail->email }}');

            // Send the images to the Laravel backend to store them
            fetch('{{ route("uploadTicketImage") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.message').innerHTML =
                        '<h2>✅ Success!</h2><p>Ticket images have been generated and sent to your email: <strong>{{ $userDetail->email }}</strong></p><p>You can close this page now.</p>';
                    // Hide the ticket elements after successful generation
                    document.querySelectorAll('.qrimageData').forEach(el => el.style.display = 'none');
                } else {
                    document.querySelector('.message').innerHTML =
                        '<h2>❌ Error</h2><p>Failed to send ticket images. Please try again.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.querySelector('.message').innerHTML =
                    '<h2>❌ Error</h2><p>An error occurred while processing ticket images.</p>';
            });
        });
    }
    </script>
</body>
</html>
