<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thank You for Your Donation</title>
    <style>
        body { margin:0; padding:0; font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; padding: 30px; border-radius: 8px; }
        .header { background: #276EF1; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 20px 0; color: #333333; line-height: 1.6; }
        .content p { margin: 12px 0; }
        .footer { color: #777777; font-size: 14px; padding-top: 18px; border-top: 1px solid #e8e8e8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Thank You for Your Donation</h1>
        </div>
        <div class="content">
            <p>Dear {{ $donorName ?: 'Donor' }},</p>
            <p>Thank you for your generous donation to Swaminaratan Gurkul Arizona.</p>
            <p>Your support makes a meaningful difference, and we are grateful for your trust and contribution.</p>
            <p>Invoice and receipt details were sent to you in a separate email.</p>
            <p><strong>ALL PROCEEDS GO TO Swaminaratan Gurkul Arizona.</strong></p>
            <p>If you have any questions, please reply to this email.</p>
        </div>
        <div class="footer">
            <p>{{ $sender->app_name ?? 'Teptix' }}</p>
            <p>{{ $sender->sender_email ?? '' }}</p>
        </div>
    </div>
</body>
</html>
