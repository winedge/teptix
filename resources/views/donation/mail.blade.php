<!DOCTYPE html>
<html>
<head>
    <title>Donation Receipt</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Lato', Helvetica, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        .email-container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #002147; padding: 40px 20px; color: #ffffff; text-align: center; }
        .content { background-color: #ffffff; padding: 30px; color: #333333; }
        .footer { background-color: #f4f4f4; padding: 20px 30px; color: #777777; font-size: 14px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #276EF1; color: #ffffff; text-decoration: none; border-radius: 4px; }
        .small-note { color: #555555; font-size: 14px; line-height: 24px; }
    </style>
</head>
<body>
    <table class="email-container" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header">
                <h1>Donation Receipt</h1>
            </td>
        </tr>
        <tr>
            <td class="content">
                <p>Dear {{ $donorName ?: 'Donor' }},</p>
                <p>Thank you for your generous donation. Your receipt is attached to this email.</p>
                <p><strong>ALL PROCEEDS GO TO Swaminaratan Gurkul Arizona.</strong></p>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Donation ID:</td>
                        <td style="padding: 8px 0;">{{ $donation->donation_id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Amount:</td>
                        <td style="padding: 8px 0;">{{ $currency }}{{ number_format($donation->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold;">Date:</td>
                        <td style="padding: 8px 0;">{{ $donation->created_at->format('F j, Y') }}</td>
                    </tr>
                </table>
                <p class="small-note">If you have any questions, reply to this email or contact {{ $sender->app_name ?? 'our team' }}.</p>
            </td>
        </tr>
        <tr>
            <td class="footer">
                <p>Thank you again for supporting Swaminaratan Gurkul Arizona.</p>
                <p>{{ $sender->app_name ?? 'Teptix' }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
