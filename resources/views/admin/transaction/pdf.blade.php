<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stripe Transactions Report</title>
    <style>
        @page {
            margin: 8mm;
            size: A4 landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
        }
        h2 {
            text-align: center;
            margin-bottom: 8px;
            font-size: 12px;
        }
        p {
            font-size: 8px;
            margin-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px 4px;
            text-align: left;
            word-wrap: break-word;
            overflow: hidden;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 7px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        /* column widths */
        col.c-num     { width: 3%; }
        col.c-pid     { width: 12%; }
        col.c-oid     { width: 6%; }
        col.c-event   { width: 11%; }
        col.c-cust    { width: 10%; }
        col.c-amt     { width: 6%; }
        col.c-cur     { width: 5%; }
        col.c-charge  { width: 12%; }
        col.c-txn     { width: 12%; }
        col.c-fee     { width: 6%; }
        col.c-status  { width: 7%; }
        col.c-method  { width: 6%; }
        col.c-date    { width: 8%; }
    </style>
</head>
<body>
    <h2>Stripe Transactions Report</h2>
    <p><strong>Generated:</strong> {{ date('d M Y, H:i') }}</p>

    <table>
        <colgroup>
            <col class="c-num">
            <col class="c-pid">
            <col class="c-oid">
            <col class="c-event">
            <col class="c-cust">
            <col class="c-amt">
            <col class="c-cur">
            <col class="c-charge">
            <col class="c-txn">
            <col class="c-fee">
            <col class="c-status">
            <col class="c-method">
            <col class="c-date">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment ID</th>
                <th>Order ID</th>
                <th>Event</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Latest Charge</th>
                <th>Transaction ID</th>
                <th>Stripe Fee</th>
                <th>Status</th>
                <th>Pay Method</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ Str::limit($item->payment_id, 22) }}</td>
                <td>{{ $item->order ? $item->order->order_id : 'N/A' }}</td>
                <td>
                    @if($item->order && $item->order->event)
                        {{ Str::limit($item->order->event->name, 20) }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($item->order)
                        @if($item->order->appUser)
                            {{ Str::limit($item->order->appUser->name . ' ' . $item->order->appUser->last_name, 18) }}
                        @elseif($item->order->guestUser)
                            {{ Str::limit($item->order->guestUser->name . ' ' . $item->order->guestUser->last_name, 18) }}
                        @else
                            N/A
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-right">{{ $currency }}{{ number_format($item->amount, 2) }}</td>
                <td>{{ strtoupper($item->currency) }}</td>
                <td>{{ $item->latest_charge ? Str::limit($item->latest_charge, 22) : 'N/A' }}</td>
                <td>{{ $item->txn_id ? Str::limit($item->txn_id, 22) : 'N/A' }}</td>
                <td class="text-right">{{ $item->tax_amount ? $currency . number_format($item->tax_amount, 2) : 'N/A' }}</td>
                <td>{{ $item->status ? ucfirst(str_replace('_', ' ', $item->status)) : 'N/A' }}</td>
                <td>{{ $item->payment_method_types ? implode(', ', array_map('ucfirst', $item->payment_method_types)) : 'N/A' }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d M Y, H:i') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9" class="text-right">Total Stripe Fee:</th>
                <th class="text-right">{{ $currency }}{{ number_format($transactions->sum('tax_amount'), 2) }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<body>
    <h2>Stripe Transactions Report</h2>
    <p><strong>Generated:</strong> {{ date('d M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment ID</th>
                <th>Order ID</th>
                <th>Event</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Latest Charge</th>
                <th>Transaction ID</th>
                <th>Stripe Fee</th>
                <th>Status</th>
                <th>Payment Methods</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ Str::limit($item->payment_id, 20) }}</td>
                <td>{{ $item->order ? $item->order->order_id : 'N/A' }}</td>
                <td>
                    @if($item->order && $item->order->event)
                        {{ Str::limit($item->order->event->name, 25) }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($item->order)
                        @if($item->order->appUser)
                            {{ $item->order->appUser->name }} {{ $item->order->appUser->last_name }}
                        @elseif($item->order->guestUser)
                            {{ $item->order->guestUser->name }} {{ $item->order->guestUser->last_name }}
                        @else
                            N/A
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-right">{{ $currency }}{{ number_format($item->amount, 2) }}</td>
                <td>{{ strtoupper($item->currency) }}</td>
                <td>{{ $item->latest_charge ? Str::limit($item->latest_charge, 20) : 'N/A' }}</td>
                <td>{{ $item->txn_id ? Str::limit($item->txn_id, 20) : 'N/A' }}</td>
                <td class="text-right">{{ $item->tax_amount ? $currency . number_format($item->tax_amount, 2) : 'N/A' }}</td>
                <td>{{ $item->status ? ucfirst(str_replace('_', ' ', $item->status)) : 'N/A' }}</td>
                <td>{{ $item->payment_method_types ? implode(', ', array_map('ucfirst', $item->payment_method_types)) : 'N/A' }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d M Y, H:i') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9" class="text-right">Total Stripe Fee:</th>
                <th class="text-right">{{ $currency }}{{ number_format($transactions->sum('tax_amount'), 2) }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
