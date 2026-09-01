<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Refund Notification</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eeeeee; }
        .header h2 { color: #007bff; margin: 0; }
        .content { padding: 20px 0; line-height: 1.6; }
        .details-box { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2>{{ $appName }}</h2>
            <p style="margin-top: 5px; color: #555;">{{ __('Order Refund Notification') }}</p>
        </div>
        <div class="content">
            <p>{{ __('Hello') }} <strong>{{ $detail['customer_name'] ?? 'Customer' }}</strong>,</p>
            <p>{{ __('Your order has been successfully refunded by the organizer.') }}</p>
            
            <div class="details-box">
                <p style="margin: 4px 0;"><strong>{{ __('Order ID') }}:</strong> {{ $order->order_id }}</p>
                <p style="margin: 4px 0;"><strong>{{ __('Event') }}:</strong> {{ $order->event?->name ?? 'N/A' }}</p>
                <p style="margin: 4px 0;"><strong>{{ __('Refunded Amount') }}:</strong> {{ $detail['currency'] ?? '$' }}{{ number_format((float)$order->payment, 2) }}</p>
                <p style="margin: 4px 0;"><strong>{{ __('Status') }}:</strong> <span style="color: #007bff; font-weight: bold;">{{ __('Refunded') }}</span></p>
            </div>

            <p>{{ __('All reserved seats associated with this order have been released.') }}</p>
            <p>{{ __('If you have any questions, please feel free to contact event support.') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>
</body>
</html>
