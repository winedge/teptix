@extends('master')

@section('content')
<section class="section">
    <div class="section-header ms-3 mt-3">
        <h1 class="h3 text-gray-800 fw-bold">{{ __('Ticket Verification') }}</h1>
    </div>
</section>

<div class="container-fluid px-4 py-3">
    @if(session('error'))
        <div class="alert alert-danger mb-4 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Main Workspace Split Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="row g-0 align-items-stretch">

            <!-- Left Side: Visual Guide Panel -->
            <div class="col-lg-5 d-flex flex-column align-items-center justify-content-center p-5 text-center border-end">
                <div class="illustration-wrapper position-relative mb-4">
                    <img src="{{ asset('images/ticketverify.png') }}"
                         alt="Scan Ticket Verification" class="img-fluid mix-blend-multiply" style="max-height: 220px;">
                </div>
                <h3 class="h4 fw-bold text-dark mb-2">Verify a Ticket</h3>
                <p class="text-muted small px-md-4">Scan the ticket QR code or enter the ticket details manually to verify attendee entry.</p>
            </div>

            <!-- Right Side: Interaction Form Panel -->
            <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center mb-4 text-danger">
                    <div class="icon-box bg-danger-subtle rounded-3 p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-ticket-alt fa-lg text-danger"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-0 text-danger p-1">Scan Ticket</h2>
                </div>

                <form id="ticket-scan-form">
                    @csrf

                    <!-- Ticket Number Field -->
                    <div class="mb-4">
                        <label for="ticket_number" class="form-label fw-semibold text-dark small mb-2">Ticket Number</label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                            <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-ticket-alt"></i></span>
                            <input type="text" class="form-control border-0 ps-2 fs-6 py-2.5" id="ticket_number" name="ticket_number"
                                   list="recent-ticket-numbers" placeholder="Enter Ticket Number" autocomplete="on" required autofocus maxlength="13">
                        </div>
                        <datalist id="recent-ticket-numbers">
                            <!-- Filled by JavaScript -->
                        </datalist>
                    </div>

                    <!-- Event Dropdown Field -->
                    <div class="mb-4">
                        <label for="event_id" class="form-label fw-semibold text-dark small mb-2">Event</label>
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                            <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-calendar-days"></i></span>
                            <select class="form-select border-0 ps-2 fs-6 py-2.5 text-muted" id="event_id" name="event_id" required>
                                <option value="" selected disabled>Select Event</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Scan Trigger Action -->
                    <div class="mt-4 pt-2">
                        <button type="submit" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm py-2.5 rounded-3 tracking-wide" id="submitBtn">
                            Scan Ticket
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Bottom Feature Value Props -->
    <div class="verification-feature-card mt-2">
        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="verification-info-card">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                        <i class="fas fa-shield-alt fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Secure Verification</h6>
                        <small class="text-muted">All ticket scans are secure and logged in real-time</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="verification-info-card">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                        <i class="fas fa-clock fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Real-time Updates</h6>
                        <small class="text-muted">Instantly verify and update entry status</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="verification-info-card">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                        <i class="fas fa-chart-bar fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Detailed Logs</h6>
                        <small class="text-muted">View scan history and attendee details</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="verification-info-card">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-check fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Easy to Use</h6>
                        <small class="text-muted">Fast and simple ticket verification process</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="ticketResponseModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden" id="ticketModalContent">
            <div class="modal-body p-4 text-white fs-5" id="ticketModalBody"></div>
            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-secondary px-4 py-2 rounded-3" id="closeModalBtn">Close</button>
                <button type="button" class="btn btn-dark px-4 py-2 rounded-3" id="stopTimerBtn">
                    <i class="fas fa-stopwatch me-1"></i> Stop Timer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audio Assets -->
<audio id="successSound" src="{{ asset('frontend/sound/success.mpeg') }}" preload="auto"></audio>
<audio id="errorSound" src="{{ asset('frontend/sound/beep-warning.mp3') }}" preload="auto"></audio>
@endsection

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Design alignment tweaks */
.bg-danger-subtle { background-color: #ffe5e5 !important; }
.btn-danger { background-color: #ff0000 !important; border-color: #ff0000 !important; }
.btn-danger:hover { background-color: #d60000 !important; border-color: #d60000 !important; }
.text-danger { color: #ff0000 !important; }
.input-group:focus-within { border-color: #ff0000 !important; box-shadow: 0 0 0 0.25rem rgba(255, 0, 0, 0.15) !important; }
.input-group .form-control:focus, .input-group .form-select:focus { box-shadow: none !important; outline: none !important; }

.ticket-card {
    background: #ffffff;
    border: 1px dashed #dee2e6;
    border-radius: 12px;
    padding: 16px;
    margin-top: 15px;
    color: #333333;
}
.ticket-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #eaeaea; font-size: 0.9rem; }
.ticket-row:last-child { border-bottom: none; }
.ticket-row .label { color: #6c757d; font-weight: 500; }
.ticket-row .value { font-weight: 600; color: #212529; }
.modal-content.success { background-color: #198754 !important; }
.modal-content.error { background-color: #dc3545 !important; }
#stopTimerBtn { display: none; }

.verification-info-card {
    display: flex;
    align-items: center;
    gap: 14px;
    height: 100%;
    min-height: 112px;
    padding: 18px;
    background: #ffffff;
    border: 1px solid #f0f0f0;
    border-radius: 16px;
    box-shadow: 0 0.125rem 0.75rem rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.verification-info-card:hover {
    border-color: #ffd0d0;
    box-shadow: 0 0.5rem 1.25rem rgba(255, 0, 0, 0.08);
    transform: translateY(-2px);
}

.verification-info-card small {
    display: block;
    margin-top: 4px;
    line-height: 1.4;
}

@media (max-width: 767.98px) {
    .verification-feature-card {
        display: none !important;
    }
}
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Keep existing Javascript configurations completely untouched here to guarantee logic runs exactly as before.
function saveRecentTicketNumber(ticketNumber) {
    if (!ticketNumber) return;
    let stored = JSON.parse(localStorage.getItem('recent_ticket_numbers') || '[]');
    stored = stored.filter(item => item !== ticketNumber);
    stored.unshift(ticketNumber);
    if (stored.length > 5) stored = stored.slice(0, 5);
    localStorage.setItem('recent_ticket_numbers', JSON.stringify(stored));
}

function populateRecentTicketNumbers() {
    const dataList = document.getElementById('recent-ticket-numbers');
    const stored = JSON.parse(localStorage.getItem('recent_ticket_numbers') || '[]');
    dataList.innerHTML = stored.map(num => `<option value="${num}">`).join('');
}

function forceFocus(element) {
    element.focus();
    element.blur();
    setTimeout(() => element.focus(), 100);
}

document.addEventListener('DOMContentLoaded', function () {
    const ticketInput = document.getElementById('ticket_number');
    const eventSelect = document.getElementById('event_id');
    const form = document.getElementById('ticket-scan-form');
    const submitBtn = document.getElementById('submitBtn');

    if (ticketInput) {
        forceFocus(ticketInput);
        ticketInput.addEventListener('focus', function() {
            this.select();
        });
    }

    populateRecentTicketNumbers();

    const modalElement = document.getElementById('ticketResponseModal');
    const modalBody = document.getElementById('ticketModalBody');
    const modalContent = document.getElementById('ticketModalContent');
    const successSound = document.getElementById('successSound');
    const errorSound = document.getElementById('errorSound');
    const modal = new bootstrap.Modal(modalElement);
    const stopTimerBtn = document.getElementById('stopTimerBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');

    let autoCloseTimeout = null;
    let isTimerStopped = false;

    ticketInput.addEventListener('input', function(e) {
        if (this.value.length === 13) {
            checkAndSubmit();
        }
    });

    function checkAndSubmit() {
        if (ticketInput.value.trim() !== '' && eventSelect.value !== '') {
            setTimeout(() => {
                form.dispatchEvent(new Event('submit'));
            }, 200);
        }
    }

    eventSelect.addEventListener('change', checkAndSubmit);

    modalElement.addEventListener('hidden.bs.modal', function () {
        isTimerStopped = false;
        stopTimerBtn.style.display = 'none';
        if (modalContent.classList.contains('success')) {
            ticketInput.value = '';
        }
        forceFocus(ticketInput);
    });

    stopTimerBtn.addEventListener('click', function () {
        if (autoCloseTimeout) {
            clearTimeout(autoCloseTimeout);
            autoCloseTimeout = null;
        }
        isTimerStopped = true;
        stopTimerBtn.style.display = 'none';
    });

    closeModalBtn.addEventListener('click', function () {
        modal.hide();
        if (modalContent.classList.contains('success')) {
            ticketInput.value = '';
        }
        forceFocus(ticketInput);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        const formData = new FormData(form);
        const ticketNumber = ticketInput.value;

        fetch('{{ route("scan.ticket") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            modalContent.classList.remove('success', 'error');
            modalContent.classList.add(data.success ? 'success' : 'error');

            let alertClass = data.success ? 'alert-success' : 'alert-danger';
            let content = `<div class="alert ${alertClass}">${data.msg}</div>`;

            saveRecentTicketNumber(ticketNumber);
            populateRecentTicketNumbers();

            if (data.success && data.data) {
                successSound.play().catch(err => console.warn('Sound error:', err));
                content += `
                    <div class="ticket-card shadow-sm text-dark">
                        <div class="ticket-body">
                            <div class="ticket-row"><span class="label"><i class="fas fa-heading me-1 text-muted"></i> Event Name:</span><span class="value">${data.data.event_name ?? '-'}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-tag me-1 text-muted"></i> Ticket Title:</span><span class="value">${data.data.ticket_title ?? '-'}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-hashtag me-1 text-muted"></i> Ticket Number:</span><span class="value">${data.data.ticket_number ?? '-'}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-money-bill me-1 text-muted"></i> Price:</span><span class="value">${data.data.currency} ${data.data.amount}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-credit-card me-1 text-muted"></i> Payment:</span><span class="value">${data.data.payment_type}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-users me-1 text-muted"></i> Remaining Details:</span><span class="value">${data.data.remaining_check_ins ?? 'N/A'}</span></div>
                            <div class="ticket-row"><span class="label"><i class="fas fa-id-card me-1 text-muted"></i> Book Seat ID:</span><span class="value">${data.data.Book_Seat_Id ?? '-'}</span></div>
                            ${data.data.seat_details && data.data.seat_details.length
                                ? data.data.seat_details.map(seat =>
                                    `<div class="ticket-row"><span class="label"><i class="fas fa-chair me-1 text-muted"></i> Seat:</span><span class="value">${seat.seat_number ?? '-'}</span></div>`
                                ).join('')
                                : ''
                            }
                        </div>
                    </div>`;
            } else {
                errorSound.play().catch(err => console.warn('Sound error:', err));
            }

            modalBody.innerHTML = content;
            modal.show();
            stopTimerBtn.style.display = 'inline-block';
            isTimerStopped = false;

            autoCloseTimeout = setTimeout(() => {
                if (!isTimerStopped) {
                    modal.hide();
                    if (data.success) {
                        ticketInput.value = '';
                    }
                    forceFocus(ticketInput);
                }
            }, 3000);
        })
        .catch(error => {
            modalContent.classList.remove('success');
            modalContent.classList.add('error');
            modalBody.innerHTML = `<div class="alert alert-danger">Something went wrong. Please try again.</div>`;
            modal.show();
            errorSound.play().catch(err => console.warn('Sound error:', err));
            stopTimerBtn.style.display = 'inline-block';
            isTimerStopped = false;

            autoCloseTimeout = setTimeout(() => {
                if (!isTimerStopped) {
                    modal.hide();
                    forceFocus(ticketInput);
                }
            }, 3000);
        })
        .finally(() => {
            submitBtn.disabled = false;
        });
    });
});
</script>
@endpush
