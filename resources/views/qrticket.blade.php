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
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
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
        }
        .left-section, .center-section, .right-section {
            padding: 20px;
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
        }
        .left-section img {
            border-radius: 8px;
            width: 100%;
            max-width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        .center-section {
            flex: 3;
            text-align: center;
        }
        .center-section h2, .center-section h3 {
            margin: 5px 0;
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
        }
        .right-section img {
            width: 100px;
            height: 100px;
        }
        .date-section {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        /* Responsive Styling */
        @media (max-width: 768px) {
            .ticket {
                flex-direction: column;
                max-width: 100%;
            }
            .left-section, .center-section, .right-section {
                padding: 15px;
                text-align: center;
            }
            .date-section {
                flex-direction: column;
                align-items: center;
                gap: 5px;
            }
            .center-section .event-location {
                flex-direction: column;
            }
        }
        @media (max-width: 480px) {
            .center-section h2 {
                font-size: 24px;
            }
            .center-section h3 {
                font-size: 18px;
            }
            .center-section p, .right-section .ticket-number {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="ticket" id="ticket">
        <div class="left-section">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcROurZUDAen_z9kTMix4of2TfCrfyzHyVI-gw&s" alt="Event Image" crossOrigin="anonymous">
            <span>Admit One</span>
        </div>
        <div class="center-section">
            <div class="date-section">
                <span>TUESDAY</span>
                <span>JUNE 29TH</span>
                &nbsp;<span>2025</span>
            </div>
            <h2>Falguni Pathak</h2>
            <h3>Dandiya Dhoom 2025</h3>
            <p class="event-info">8:00 PM to 11:00 PM</p>
            <p>Doors @ 7:00 PM</p>
            <div class="event-location">
                <span>East High School</span>
                <span><img src="https://theeventpalette.com/uploads/file-8.png" alt="Smiley" style="width: 30px; height: auto;"></span>
                <span>Salt Lake City, Utah</span>
            </div>
        </div>
        <div class="right-section">
            <h4>Dandiya Dhoom 2025</h4>
            {!! $qrCode !!}
            <div class="ticket-number">#20030220</div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        window.addEventListener('load', function () {
            html2canvas(document.getElementById('ticket')).then(function (canvas) {
                // Convert the canvas to base64 PNG image
                var imageData = canvas.toDataURL('image/png');

                // Prepare the data to be sent to the server
                var formData = new FormData();
                formData.append('image', imageData);
                formData.append('_token', '{{ csrf_token() }}');

                // Send the image data to the Laravel backend to store it
                fetch('{{ route("uploadTicketImage") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Image uploaded successfully!');
                    } else {
                        console.log('Failed to upload image!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    </script>
</body>
</html>
