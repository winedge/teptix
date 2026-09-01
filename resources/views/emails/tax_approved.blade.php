<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Tax Approved</title></head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:30px;">
    <div style="max-width:580px;margin:auto;background:#fff;border-radius:10px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
        <h2 style="color:#28a745;">✅ Tax Approved</h2>
        <p>Hello,</p>
        <p>Your tax <strong>{{ $tax->name }}</strong> has been <strong style="color:#28a745;">approved</strong> by the admin.</p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;">
            <tr><td style="padding:8px;background:#f9f9f9;font-weight:bold;">Tax Name</td><td style="padding:8px;">{{ $tax->name }}</td></tr>
            <tr><td style="padding:8px;background:#f9f9f9;font-weight:bold;">Amount</td><td style="padding:8px;">{{ $tax->price }}{{ $tax->amount_type === 'percentage' ? '%' : '' }}</td></tr>
            <tr><td style="padding:8px;background:#f9f9f9;font-weight:bold;">Status</td><td style="padding:8px;color:#28a745;font-weight:bold;">Approved</td></tr>
        </table>
        <p>This tax is now active and will be linked to your tickets automatically.</p>
        <p style="color:#888;font-size:12px;">This is an automated email. Please do not reply.</p>
    </div>
</body>
</html>
