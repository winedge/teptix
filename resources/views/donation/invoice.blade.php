<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Donation Invoice</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f2f2f2; }
        .invoice-wrapper { max-width: 800px; margin: 30px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 0 16px rgba(0,0,0,0.05); }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .invoice-title { font-size: 26px; color: #333; margin: 0; }
        .invoice-meta { text-align: right; font-size: 14px; color: #555; }
        .invoice-note { margin: 24px 0 18px; font-size: 15px; color: #333; background: #f9f9f9; border-left: 4px solid #276EF1; padding: 14px 16px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .table th, .table td { padding: 14px 12px; border: 1px solid #e8e8e8; text-align: left; }
        .table th { background: #f7f7f7; color: #333; }
        .total-row td { font-weight: bold; font-size: 16px; }
        .footer { margin-top: 28px; font-size: 13px; color: #666; }
    </style>
</head>
<body>
    @php $currency = $sender->currency_sybmol ?? '$'; @endphp
    <div class="invoice-wrapper">
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">Donation Invoice</h1>
                <p>{{ $sender->app_name ?? 'Teptix' }}</p>
            </div>
            <div class="invoice-meta">
                <div><strong>Donation ID:</strong> {{ $donation->donation_id }}</div>
                <div><strong>Date:</strong> {{ $donation->created_at->format('F j, Y') }}</div>
            </div>
        </div>

        <div class="invoice-note">
            <strong>ALL PROCEEDS GO TO Swaminaratan Gurkul Arizona.</strong>
        </div>

        <table class="table">
            <tbody>
                <tr>
                    <th>Donor</th>
                    <td>{{ $donorName ?: ($donation->guestUser->email ?? $donation->appUser->email ?? 'Guest Donor') }}</td>
                </tr>
                <tr>
                    <th>Donor Email</th>
                    <td>{{ $donation->appUser->email ?? $donation->guestUser->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Donation For</th>
                    <td>{{ $donation->event->name ?? 'General Donation' }}</td>
                </tr>
                <tr class="total-row">
                    <th>Amount</th>
                    <td>{{ $currency }}{{ number_format($donation->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Please keep this invoice as your donation record. No tax information is shown on this receipt.</p>
        </div>
    </div>
</body>
</html>
