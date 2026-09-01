<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
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
            background-color: #f0f0ff;
            border-left: 4px solid #fe0000;
            padding: 15px 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 5px 0;
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
            <h1>Welcome to {{ config('app.name') }}!</h1>
        </div>
        <div class="body">
            <h2>Hello, {{ $organizer->first_name }} {{ $organizer->last_name }}!</h2>
            <p>
                Thank you for registering as an organizer on <strong>{{ config('app.name') }}</strong>.
                Your account has been created successfully and is now active.
            </p>
            <div class="info-box">
                <p><strong>Organization Name:</strong> {{ $organizer->name }}</p>
                <p><strong>Email:</strong> {{ $organizer->email }}</p>
            </div>
            <p>
                You can now log in to your dashboard to start creating and managing events.
            </p>
            <a href="{{ url('/user/login') }}" class="btn">Go to Login</a>
            <p style="margin-top: 25px;">
                If you have any questions or need assistance, feel free to contact our support team.
            </p>
            <p>Best regards,<br><strong>{{ config('app.name') }} Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
