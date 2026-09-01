@extends('master')
@section('content')
    <section class="section">
        @include('admin.layout.breadcrumbs', [
        'title' => __('Ticket Detail'),
        'headerData' => __('Orders') ,
        'url' => 'orders' ,
        ])

        <div class="section-body">
            <div class="invoice">
                <div class="invoice-print" >
                    <div class="ticket-header mb-4 text-center text-primary">
                        <h2>{{ $setting->app_name }}</h2>
                    </div>
                    {{-- <div class="ticket" id="ticket">
                        <div style="width: 400px; border: 2px solid #d32f2f; border-radius: 10px; background-color: white; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);" class="qrimageData">
                            <div style="background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold;">
                                <span>The Event Palette</span>
                            </div>
                            <div style="padding: 15px; display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <img src="{{$setting->imagePath . $ticket->image}}" alt="Event Poster" style="width: 150px; height: 90px; border-radius: 5px; object-fit: cover;">
                                    <div style="flex-grow: 1;">
                                        <div style="font-size: 16px; font-weight: bold;">{{ $ticket->name }}</div>
                                        <div style="font-size: 12px; color: gray;">{{ $ticket->first_name . ' ' . $ticket->last_name }}</div>
                                        <div style="font-size: 12px; color: gray;">{{ $ticket->type == 'online' ? 'Online Event' : $ticket->address }}</div>
                                        <div style="font-size: 12px; color: gray;">{{\Carbon\Carbon::parse($ticket->start_time)->format('l') }}, {{ \Carbon\Carbon::parse($ticket->start_time)->format('d F') }} | {{ \Carbon\Carbon::parse($ticket->start_time)->format('h:i a') }}</div>
                                    </div>
                                </div>
                                <div style="border-top: 1px dashed #d32f2f; padding-top: 10px;">
                                    <div style="font-size: 14px; color: #d32f2f; font-weight: bold;">{{ $ticket->ticket_name }}</div>
                                    <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Ticket {{ $ticket->ticket_type }}</div>
                                </div>
                                <div style="text-align: center; margin-top: 10px;">
                                    @php
                                        $base64QrCode = 'data:image/png;base64,' . base64_encode($ticket->qrCode);
                                    @endphp
                                    <img src="{{ $base64QrCode }}" alt="QR Code" class="mx-auto mt-2" style="width: 200px; height: 200px;"/>
                                </div>
                                <div style="text-align: center; margin-top: 5px; font-size: 12px; color: gray;"> #{{ $ticket->ticket_number }} </div>
                            </div>
                            <div style="background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 10px; color: gray;">All Sales Are Final! No Refunds!</div>
                        </div>
                    </div> --}}
                    <div class="ticket" id="ticket" style="background-color: #fff !important;">
                        <div style="width: 100%; max-width: 400px; border: 2px solid #d32f2f; border-radius: 10px; background-color: #fff; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); margin: auto;">
                            <div style="background-color: #f90b0b; color: white; text-align: center; padding: 10px; font-size: 14px; font-weight: bold;">
                                <span>The Event Palette</span>
                            </div>
                            <div style="background-color: #fff; padding: 15px; display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                    {{-- <img src="{{$setting->imagePath . $ticket->image}}" alt="Event Poster" style="width: 100%; max-width: 150px; height: auto; border-radius: 5px; object-fit: cover;"> --}}
                                    <div style="flex-grow: 1; min-width: 150px;">
                                        <div style="font-size: 16px; font-weight: bold;">{{ $ticket->name }}</div>
                                        <div style="font-size: 12px; color: gray;">{{ $ticket->organization_name }} </div>
                                        <div style="font-size: 12px; color: gray;">{{ $ticket->type == 'online' ? 'Online Event' : $ticket->address }}</div>
                                        <div style="font-size: 12px; color: gray;">{{\Carbon\Carbon::parse($ticket->start_time)->format('l') }}, {{ \Carbon\Carbon::parse($ticket->start_time)->format('d F') }} | {{ \Carbon\Carbon::parse($ticket->start_time)->format('h:i a') }}</div>
                                    </div>
                                </div>
                                <div style="border-top: 1px dashed #d32f2f; padding-top: 10px; text-align: center;">
                                    <div style="font-size: 20px; color: #d32f2f; font-weight: bold;">{{ $ticket->ticket_name }}</div>
                                    <div style="font-size: 16px; font-weight: bold; margin: 5px 0;">Ticket {{ $ticket->ticket_type }}</div>
                                   @if($ticket->Book_Seat_Id)
                                        <div style="font-size: 14px; color: #666; margin: 5px 0;"><strong>Seat:</strong> {{ $ticket->Book_Seat_Id }}</div>
                                   @endif
                                </div>
                                <div style="text-align: center; margin-top: 10px;">
                                    @php
                                        $base64QrCode = 'data:image/png;base64,' . base64_encode($ticket->qrCode);
                                    @endphp
                                    <img src="{{ $base64QrCode }}" alt="QR Code" class="mx-auto mt-2" style="width: 100%; max-width: 200px; height: auto;"/>
                                </div>
                                <div style="text-align: center; margin-top: 5px; font-size: 12px; color: gray;">#{{ $ticket->ticket_number }}</div>
                            </div>
                            <div style="background-color: transparent; text-align: center; padding: 10px; font-size: 10px; color: gray;">
                                All Sales Are Final! No Refunds!
                            </div>
                        </div>
                    </div>
                    <div>
                        @if($ticket->email)
                            <button type="button" onclick="sendTicket()" class="btn btn-primary" id="sendEmail">Send in Gmail</button>
                        @else
                            <button type="button" class="btn btn-secondary" disabled title="Email address not available for this ticket">Send in Gmail (No Email)</button>
                        @endif
                        <button type="button" onclick="downloadTicket()" class="btn btn-primary">Download Ticket</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    function downloadTicket() {
        var ticket = document.getElementById("ticket");
        html2canvas(ticket, { scale: 2 }).then(canvas => {
            let link = document.createElement("a");
            link.href = canvas.toDataURL("image/png");
            link.download = "ticket.png";
            link.click();
        });
    }
</script>
<script>
    function sendTicket() {
        // Check if email exists
        var email = '{{ $ticket->email ?? "" }}';
        if (!email) {
            alert('✗ Cannot send email!\nNo email address is associated with this ticket.');
            return;
        }

        // Select all tickets by their class
        $('#sendEmail').prop('disabled', true);
        $('#sendEmail').text('Sending...');
        const tickets = document.querySelectorAll('.ticket');
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
            formData.append('email', email);

            // Send the images to the Laravel backend to store them
            fetch('{{ route("uploadTicketImage") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                $('#sendEmail').text('Send in Gmail');
                $('#sendEmail').prop('disabled', false);

                if (data.success) {
                    alert('✓ Ticket sent successfully via email!');
                    console.log('Images uploaded successfully!');
                } else {
                    alert('✗ Failed to send email!\n' + (data.message || 'Please check your email configuration in settings.'));
                    console.error('Failed to upload images:', data.message);
                }
            })
            .catch(error => {
                $('#sendEmail').text('Send in Gmail');
                $('#sendEmail').prop('disabled', false);
                alert('✗ Error occurred while sending email!\nPlease check your internet connection and email settings.');
                console.error('Error:', error);
            });
        });
    }
</script>
