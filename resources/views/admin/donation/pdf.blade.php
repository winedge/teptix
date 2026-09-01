<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Donations Export</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h2 { text-align: center; margin-bottom: 4px; font-size: 12px; }
        p.sub { text-align: center; color: #666; margin-top: 0; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th { background: #1f2937; color: #fff; padding: 4px 4px; text-align: left; font-size: 8px; word-wrap: break-word; }
        td { padding: 3px 4px; border-bottom: 1px solid #e5e7eb; word-wrap: break-word; overflow: hidden; font-size: 8px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge-success { background: #16a34a; color: #fff; padding: 1px 4px; border-radius: 6px; font-size: 7px; }
        .badge-warning { background: #d97706; color: #fff; padding: 1px 4px; border-radius: 6px; font-size: 7px; }
        .badge-danger { background: #dc2626; color: #fff; padding: 1px 4px; border-radius: 6px; font-size: 7px; }
        .total-row { font-weight: bold; background: #f0fdf4; }
        col.c-num    { width: 4%; }
        col.c-did    { width: 8%; }
        col.c-event  { width: 18%; }
        col.c-donor  { width: 14%; }
        col.c-amt    { width: 8%; }
        col.c-status { width: 9%; }
        col.c-pid    { width: 27%; }
        col.c-date   { width: 12%; }
    </style>
</head>
<body>
    <h2>Donations Report</h2>
    <p class="sub">Exported on {{ $date }}</p>
    <table>
        <colgroup>
            <col class="c-num">
            <col class="c-did">
            <col class="c-event">
            <col class="c-donor">
            <col class="c-amt">
            <col class="c-status">
            <col class="c-pid">
            <col class="c-date">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Donation ID</th>
                <th>Event</th>
                <th>Donor</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment Intent ID</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><span class="badge-success">don_{{ $item->id }}</span></td>
                <td>{{ $item->event ? \Illuminate\Support\Str::limit($item->event->name, 35) : 'N/A' }}</td>
                <td>
                    @if($item->appUser)
                        {{ $item->appUser->name }} {{ $item->appUser->last_name }}
                    @elseif($item->guestUser)
                        {{ $item->guestUser->name }} {{ $item->guestUser->last_name }}
                    @else
                        Guest
                    @endif
                </td>
                <td>{{ $currency }}{{ number_format($item->amount, 2) }}</td>
                <td>
                    @if($item->status === 'succeeded')
                        <span class="badge-success">Succeeded</span>
                    @elseif($item->status === 'pending')
                        <span class="badge-warning">Pending</span>
                    @else
                        <span class="badge-danger">{{ ucfirst($item->status) }}</span>
                    @endif
                </td>
                <td style="font-size:9px;">{{ $item->payment_intent_id }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d M Y H:i') : 'N/A' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total (Succeeded):</td>
                <td>{{ $currency }}{{ number_format($donations->whereIn('status', ['completed','succeeded'])->sum('amount'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
