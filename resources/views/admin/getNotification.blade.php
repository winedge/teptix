@extends('master')

@section('content')
<style>
    div#cke_notifications_area_editor1 {
        display: none;
    }
</style>

<section class="section">
    @include('admin.layout.breadcrumbs', ['title' => __('Send Notification')])

    <div>
        <h2 class="section-title">{{ __('Send Notification') }}</h2>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="notification-form" action="javascript:void(0);" enctype="multipart/form-data">
                        @csrf

                        {{-- Title --}}
                        <div class="form-group">
                            <label>{{ __('Title') }}</label>
                            <input type="text" name="title" placeholder="New notification" class="form-control" required>
                        </div>

                        {{-- Description --}}
                        <div class="form-group">
                            <label>{{ __('Description') }}</label>
                            <textarea name="description" class="form-control ckeditor" id="editor1"></textarea>
                        </div>

                        {{-- Image (Hidden) --}}
                        <div class="form-group d-none">
                            <label>{{ __('Image') }}</label>
                            <input type="file" name="image" class="form-control" onchange="readURL(this)">
                            <img class="mt-3" src="" id="img" style="height:100px; display:none;">
                        </div>

                        {{-- Event Dropdown --}}
                        <div class="form-group">
                            <label>{{ __('Select Event') }}</label>
                            <select class="form-control" name="event_id">
                                <option value="">{{ __('-- Select Event --') }}</option>
                                @foreach ($events as $_events)
                                    <option value="{{ $_events->id }}">{{ $_events->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">{{ __('Optional: Select event to send to all event attendees, or leave blank and enter specific emails below') }}</small>
                        </div>

                        {{-- Email Input for Specific Recipients --}}
                        <div class="form-group">
                            <label>{{ __('Send to Specific Email(s)') }}</label>
                            <input type="text" name="specific_emails" id="specific_emails" placeholder="user2@example.com" class="form-control">
                            <small class="form-text text-muted">{{ __('Enter one or more email addresses separated by commas. If user has mobile app installed, push notification will be sent too.') }}</small>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-success" type="submit"> {{ __('Send Notification') }}</button>
                            <button class="btn btn-info ml-2" type="button" id="send-to-email-btn"> {{ __('Send to Specific Email(s)') }}</button>
                            <button class="btn btn-primary ml-2" type="button" id="send-to-all-btn"> {{ __('Send to All Clients') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal --}}
<div class="modal fade" id="mailStatusModal" tabindex="-1" role="dialog" aria-labelledby="mailStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="mailStatusModalLabel">📧 Notification Status</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="mailStatusContent" style="max-height: 500px; overflow-y: auto;">
        Sending...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push("js")
<!-- CSRF Token for JS -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/4.11.2/full/ckeditor.js"></script>


<script>
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Preview image
    function readURL(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#img').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).ready(function () {
        // Initialize CKEditor
        if (CKEDITOR.instances.editor1) {
            CKEDITOR.instances.editor1.destroy(true);
        }
        CKEDITOR.replace('editor1');

        // Handle form submission
        $('#notification-form').on('submit', function (e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            formData.set('description', CKEDITOR.instances.editor1.getData());

            // Show modal before sending
            $('#mailStatusContent').html('📤 Sending emails in batches...');
            $('#mailStatusModal').modal('show');

            $.ajax({
                url: "{{ route('send.notification') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    let msg = `<div class="text-success">✅ ${response.status}</div>`;

                    // Display detailed statistics
                    if (response.summary) {
                        msg += `<div class="mt-3"><strong>📊 Notification Summary:</strong>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📧 Email Statistics</h6>
                                        <small>Total Emails Found: <strong>${response.email_stats.total_emails_found}</strong></small><br>
                                        <small>Emails Sent: <strong class="text-success">${response.email_stats.emails_sent}</strong></small><br>
                                        <small>Email Failures: <strong class="text-danger">${response.email_stats.email_failures}</strong></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📱 Mobile Statistics</h6>
                                        <small>Total Tokens Found: <strong>${response.fcm_stats.total_tokens_found}</strong></small><br>
                                        <small>Mobile Success: <strong class="text-success">${response.fcm_stats.fcm_success}</strong></small><br>
                                        <small>Mobile Failures: <strong class="text-danger">${response.fcm_stats.fcm_failures}</strong></small>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-body bg-info text-white p-2">
                                <small><strong>Total Recipients: ${response.summary.total_recipients}</strong></small><br>
                                <small>Total Users Notified: <strong>${response.summary.app_users_notified + response.summary.guest_users_notified}</strong></small>
                            </div>
                        </div>`;
                    }

                    if (response.failed && response.failed.length > 0) {
                        msg += `<br><div class="text-danger"><b>❌ Failed Emails:</b><ul>`;
                        response.failed.forEach(f => {
                            msg += `<li>${f.email} - ${f.error}</li>`;
                        });
                        msg += `</ul></div>`;
                    }

                    $('#mailStatusContent').html(msg);
                    form.reset();
                    $('#img').attr('src', '').hide();
                    CKEDITOR.instances.editor1.setData('');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.error || 'Something went wrong while sending emails.';
                    $('#mailStatusContent').html(`<div class="text-danger">❌ ${message}</div>`);
                }
            });
        });

        // Handle "Send to Specific Email(s)" button
        $('#send-to-email-btn').on('click', function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('title', $('input[name="title"]').val());
            formData.append('description', CKEDITOR.instances.editor1.getData());
            formData.append('specific_emails', $('#specific_emails').val());

            const imageFile = $('input[name="image"]')[0].files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }

            // Validate required fields
            if (!formData.get('title') || !formData.get('description')) {
                alert('Please fill in Title and Description fields.');
                return;
            }

            if (!formData.get('specific_emails')) {
                alert('Please enter at least one email address.');
                return;
            }

            // Show modal before sending
            $('#mailStatusContent').html('📤 Sending notifications to specific email(s)...');
            $('#mailStatusModal').modal('show');

            $.ajax({
                url: "{{ route('send.specific.emails') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    let msg = `<div class="text-success">✅ ${response.status}</div>`;

                    // Display detailed statistics
                    if (response.email_stats) {
                        msg += `<div class="mt-3"><strong>📊 Notification Summary:</strong>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📧 Email Statistics</h6>
                                        <small>Total Emails: <strong>${response.email_stats.total_emails}</strong></small><br>
                                        <small>Emails Sent: <strong class="text-success">${response.email_stats.emails_sent}</strong></small><br>
                                        <small>Email Failures: <strong class="text-danger">${response.email_stats.email_failures}</strong></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📱 Mobile Push Notifications</h6>
                                        <small>App Users Found: <strong>${response.mobile_stats.users_found}</strong></small><br>
                                        <small>FCM Tokens Found: <strong class="text-info">${response.mobile_stats.tokens_found}</strong></small><br>
                                        <small>Push Sent: <strong class="text-success">${response.mobile_stats.push_sent}</strong></small><br>
                                        <small>Push Failures: <strong class="text-danger">${response.mobile_stats.push_failures}</strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }

                    // Display debug information if available
                    // if (response.debug) {
                    //     msg += `<div class="mt-2"><small class="text-muted">
                    //         <strong>Debug Info:</strong>
                    //         App Users: ${response.debug.app_users_found},
                    //         Guest Users: ${response.debug.guest_users_found}`;

                    //     if (response.debug.fcm_errors && response.debug.fcm_errors.length > 0) {
                    //         msg += `<br><span class="text-warning">FCM Errors:</span><ul>`;
                    //         response.debug.fcm_errors.slice(0, 3).forEach(err => {
                    //             msg += `<li>${err}</li>`;
                    //         });
                    //         if (response.debug.fcm_errors.length > 3) {
                    //             msg += `<li>... and ${response.debug.fcm_errors.length - 3} more</li>`;
                    //         }
                    //         msg += `</ul>`;
                    //     }
                    //     msg += `</small></div>`;
                    // }

                    if (response.failed && response.failed.length > 0) {
                        msg += `<br><div class="text-danger"><b>❌ Failed Emails:</b><ul>`;
                        response.failed.forEach(f => {
                            msg += `<li>${f.email} - ${f.error}</li>`;
                        });
                        msg += `</ul></div>`;
                    }

                    $('#mailStatusContent').html(msg);
                    $('#notification-form')[0].reset();
                    $('#img').attr('src', '').hide();
                    CKEDITOR.instances.editor1.setData('');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.error || 'Something went wrong while sending notifications.';
                    $('#mailStatusContent').html(`<div class="text-danger">❌ ${message}</div>`);
                }
            });
        });

        // Handle "Send to All Clients" button
        $('#send-to-all-btn').on('click', function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('title', $('input[name="title"]').val());
            formData.append('description', CKEDITOR.instances.editor1.getData());

            const imageFile = $('input[name="image"]')[0].files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }

            // Validate required fields
            if (!formData.get('title') || !formData.get('description')) {
                alert('Please fill in Title and Description fields.');
                return;
            }

            // Show modal before sending
            $('#mailStatusContent').html('📤 Sending emails to all clients across all events...');
            $('#mailStatusModal').modal('show');

            $.ajax({
                url: "{{ route('send.all.clients') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    let msg = `<div class="text-success">✅ ${response.status}</div>`;

                    if (response.total_events) {
                        msg += `<br><div class="text-info"><b>📅 Events covered:</b> ${response.total_events}</div>`;
                    }

                    // Display detailed statistics for "Send to All Clients"
                    if (response.summary) {
                        msg += `<div class="mt-3"><strong>📊 All Clients Summary:</strong>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📧 Email Statistics</h6>
                                        <small>Total Emails Found: <strong>${response.email_stats.total_emails_found}</strong></small><br>
                                        <small>Emails Sent: <strong class="text-success">${response.email_stats.emails_sent}</strong></small><br>
                                        <small>Email Failures: <strong class="text-danger">${response.email_stats.email_failures}</strong></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-body bg-light p-2 mb-2">
                                        <h6 class="mb-1">📱 Mobile Statistics</h6>
                                        <small>Total Found: <strong>${response.fcm_stats.total_tokens_found}</strong></small><br>
                                        <small>Mobile Success: <strong class="text-success">${response.fcm_stats.fcm_success}</strong></small><br>
                                        <small>Mobile Failures: <strong class="text-danger">${response.fcm_stats.fcm_failures}</strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }

                    if (response.total_emails_sent) {
                        msg += `<br><div class="text-info"><b>📧 Total emails sent:</b> ${response.total_emails_sent}</div>`;
                    }

                    if (response.failed && response.failed.length > 0) {
                        msg += `<br><div class="text-danger"><b>❌ Failed Emails:</b><ul>`;
                        response.failed.forEach(f => {
                            msg += `<li>${f.email} - ${f.error}</li>`;
                        });
                        msg += `</ul></div>`;
                    }

                    $('#mailStatusContent').html(msg);
                    $('#notification-form')[0].reset();
                    $('#img').attr('src', '').hide();
                    CKEDITOR.instances.editor1.setData('');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.error || 'Something went wrong while sending emails to all clients.';
                    $('#mailStatusContent').html(`<div class="text-danger">❌ ${message}</div>`);
                }
            });
        });
    });
</script>
@endpush
