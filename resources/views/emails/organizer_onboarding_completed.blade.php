<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Submitted - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #fe0000;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .body {
            padding: 30px;
            color: #333333;
        }
        .body h2 {
            color: #fe0000;
        }
        .info-box {
            background-color: #ebf8ff;
            border-left: 4px solid #3182ce;
            padding: 15px 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 5px 0;
            color: #2b6cb0;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #888888;
        }
        .btn {
            display: inline-block;
            background-color: #fe0000;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Onboarding Completed!</h1>
        </div>
        <div class="body">
            <h2>Hello, {{ $organizer->first_name }} {{ $organizer->last_name }}!</h2>
            <p>
                Thank you for completing your onboarding process for <strong>{{ $organizer->organization_name ?? config('app.name') }}</strong>.
            </p>
            <div class="info-box">
                <p><strong>Organization:</strong> {{ $organizer->organization_name }}</p>
                <p><strong>Contact Phone:</strong> {{ $organizer->phone }}</p>
                <p><strong>Status:</strong> Pending Admin Verification</p>
            </div>
            <p>
                Our team is reviewing your onboarding details. Once verification is complete, you will receive a confirmation email and full access to publish events on <strong>{{ config('app.name') }}</strong>.
            </p>
            <a href="{{ url('/organization-home') }}" class="btn">View Dashboard</a>
            <p style="margin-top: 25px;">
                If you have any questions, feel free to contact our support team.
            </p>
            <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
