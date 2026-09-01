<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 10px;
            color: #333;
        }

        /* Main container */
        .email-wrapper {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        /* Header with primary border */
        .email-header {
            background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, {{ $primaryColor }}, #28a745, #ffc107, #dc3545);
        }

        .title-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px 20px;
            border-radius: 8px;
            border: 3px solid {{ $primaryColor }};
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px {{ $primaryColor }}55;
        }

        .email-title {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            word-wrap: break-word;
        }

        .title-accent {
            display: block;
            width: 60px;
            height: 4px;
            background: {{ $primaryColor }};
            margin: 15px auto 0;
            border-radius: 2px;
        }

        /* Body content */
        .email-body {
            padding: 40px 30px;
            background-color: #ffffff;
        }

        .content-section {
            margin-bottom: 30px;
        }

        .email-content {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 25px;
        }

        .email-content p {
            margin-bottom: 15px;
        }

        .email-content strong {
            color: {{ $primaryColor }};
        }

        /* Image styling */
        .email-image {
            text-align: center;
            margin: 30px 0;
        }

        .email-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #e0e0e0;
        }

        /* Info box */
        .info-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 5px solid {{ $primaryColor }};
            padding: 20px;
            border-radius: 0 8px 8px 0;
            margin: 25px 0;
            position: relative;
        }

        .info-box::before {
            content: '📧';
            position: absolute;
            top: 15px;
            left: -15px;
            background: {{ $primaryColor }};
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .info-text {
            font-size: 14px;
            color: #666;
            font-style: italic;
            margin: 0;
        }

        /* Footer */
        .email-footer {
            background: linear-gradient(135deg, #343a40, #495057);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .footer-text {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .footer-link {
            color: {{ $primaryColor }};
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: {{ $primaryColor }}dd;
            text-decoration: underline;
        }

        .footer-divider {
            width: 50px;
            height: 2px;
            background: {{ $primaryColor }};
            margin: 15px auto;
        }

        /* Responsive design */
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px 5px;
            }

            .email-wrapper {
                margin: 0;
                border-radius: 8px;
            }

            .email-header {
                padding: 30px 20px;
            }

            .title-container {
                padding: 20px 15px;
            }

            .email-title {
                font-size: 22px;
                line-height: 1.3;
            }

            .email-body {
                padding: 30px 20px;
            }

            .email-content {
                font-size: 15px;
            }

            .email-footer {
                padding: 25px 20px;
            }
        }

        @media only screen and (max-width: 480px) {
            .email-title {
                font-size: 20px;
            }

            .email-body {
                padding: 25px 15px;
            }

            .email-header {
                padding: 25px 15px;
            }

            .title-container {
                padding: 18px 12px;
                border-width: 2px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-wrapper {
                background-color: #1a1a1a;
                border-color: #333;
            }

            .email-body {
                background-color: #1a1a1a;
            }

            .email-content {
                color: #e0e0e0;
            }

            .info-box {
                background: linear-gradient(135deg, #2a2a2a, #333);
                color: #e0e0e0;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header with bordered title -->
        <div class="email-header">
            <div class="title-container">
                <h1 class="email-title">{{ $title }}</h1>
                <div class="title-accent"></div>
            </div>
        </div>

        <!-- Body content -->
        <div class="email-body">
            <div class="content-section">
                <div class="email-content">
                    {!! $description !!}
                </div>

                @if($image)
                    <div class="email-image">
                        <img src="{{ asset($image) }}" alt="Event Image" />
                    </div>
                @endif

                <div class="info-box">
                    <p class="info-text">
                        This is an automated notification from our event management system.
                        Please do not reply to this email.
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text">
                Thank you for using our service!
            </p>
            <div class="footer-divider"></div>
            <p class="footer-text">
                <a href="#" class="footer-link">Visit our website</a> |
                <a href="#" class="footer-link">Contact support</a>
            </p>
        </div>
    </div>
</body>
</html>
