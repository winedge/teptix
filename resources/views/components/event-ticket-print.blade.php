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

    .left-section,
    .center-section,
    .right-section {
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

    .center-section h2,
    .center-section h3 {
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

    button {
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #ec407a;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #d03468;
    }

    /* Responsive Styling */
    @media (max-width: 768px) {
        .ticket {
            flex-direction: column;
            max-width: 100%;
        }

        .left-section,
        .center-section,
        .right-section {
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

        .center-section p,
        .right-section .ticket-number {
            font-size: 14px;
        }
    }
</style>
<div class="ticket" id="ticket">
    <div class="left-section">
        <img src="{{$setting->imagePath . $ticket->event->image}}" alt="Event Image" crossOrigin="anonymous">
        {{-- <span>Admit One</span> --}}
    </div>
    <div class="center-section">
        <div class="date-section">
            <span>{{ $ticket->start_time->format('l') }}</span>
            <span>{{ $ticket->start_time->format('F jS') }}</span> &nbsp; <span>{{ $ticket->start_time->format('Y') }}</span>
        </div>
        <h3 style="color: black">{{ $ticket->event->name }}</h3>
        <p class="event-info">{{ $ticket->start_time->format('d F Y') . ', ' . $ticket->start_time->format('h:i a') }} to {{ $ticket->end_time->format('d F Y') . ', ' . $ticket->end_time->format('h:i a') }}</p>
        <div class="event-location">
            <span>
                <img src="{{$setting->imagePath . $setting->logo}}" alt="Smiley" style="width: 30px; height: auto;">
            </span>
            <span>{{ $ticket->event->type == 'online' ? 'Online Event' : $ticket->event->address }}</span>
        </div>
    </div>
    <div class="right-section">
        <h4 style="color: black">{{ $ticket->name }}</h4>
        <div class="">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"  viewBox="0 0 200 200" style="display: none">
                <defs>
                    <rect id="r0" width="6" height="6" fill="#000000"></rect>
                </defs>
            </svg>
            {!! $qrCode !!}
        </div>
        <div class="ticket-number">#{{ $ticket->ticket_number }}</div>
    </div>
</div>
<button id="downloadBtn" style="margin-top: 20px;">Download as Image</button>

<!-- Include html2canvas -->
