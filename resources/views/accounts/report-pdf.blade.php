<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 24px 34px; }
        body { color: #17283e; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { margin: 0 0 5px; color: #142b4a; font-size: 18px; }
        .subtitle { margin-bottom: 15px; color: #657386; font-size: 9px; }
        .summary { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .summary td { width: 33%; padding: 8px; border: 1px solid #d7e0e8; background: #f3f7fa; }
        .summary span { display: block; margin-bottom: 4px; color: #6a7889; font-size: 7px; font-weight: bold; }
        .summary strong { color: #17283e; font-size: 10px; }
        table.records { width: 100%; border-collapse: collapse; }
        .records thead { display: table-header-group; }
        .records th { padding: 6px 4px; background: #142b4a; color: #fff; font-size: 7px; text-align: left; }
        .records td { padding: 5px 4px; border-bottom: 1px solid #e3e8ed; vertical-align: top; }
        .records tr { page-break-inside: avoid; }
        .number { text-align: right; white-space: nowrap; }
        .footer { position: fixed; right: 0; bottom: -18px; left: 0; color: #738194; font-size: 7px; text-align: right; }
        .empty { padding: 20px; color: #68788c; text-align: center; }
    </style>
</head>
<body>
    <h1>Account Payment Report</h1>
    <div class="subtitle">
        Generated {{ $generatedAt->format('d M Y, h:i A') }}
        @if (($filters['start_date'] ?? null) || ($filters['end_date'] ?? null))
            · Payment date: {{ $filters['start_date'] ?? 'Any' }} to {{ $filters['end_date'] ?? 'Any' }}
        @endif
        @if (($filters['min_paid'] ?? null) !== null || ($filters['max_paid'] ?? null) !== null)
            · Amount paid: ₹{{ $filters['min_paid'] ?? '0' }} to ₹{{ $filters['max_paid'] ?? 'Any' }}
        @endif
        @if ($filters['status'] ?? null)
            · Status: {{ \App\Models\DefaultingAccount::STATUSES[$filters['status']] }}
        @endif
        @if ($filters['category'] ?? null)
            · Category: {{ $filters['category'] }}
        @endif
        @if ($filters['search'] ?? null)
            · Search: {{ $filters['search'] }}
        @endif
    </div>

    <table class="summary">
        <tr>
            <td><span>ACCOUNTS</span><strong>{{ number_format($accounts->count()) }}</strong></td>
            <td><span>TOTAL PAID</span><strong>₹{{ number_format((float) $accounts->sum('paid_amount'), 2) }}</strong></td>
            <td><span>TOTAL CLOSING BALANCE</span><strong>₹{{ number_format((float) $accounts->sum('closing_balance'), 2) }}</strong></td>
        </tr>
    </table>

    <table class="records">
        <thead>
            <tr>
                <th>ACCOUNT / ID</th>
                <th>PHONE</th>
                <th>PAYMENT DATE</th>
                <th>STATUS</th>
                <th>CATEGORY</th>
                <th class="number">CLOSING BALANCE</th>
                <th class="number">PAID</th>
                <th class="number">PENDING</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $account)
                <tr>
                    <td>{{ $account->name }}<br>{{ $account->account_id }}</td>
                    <td>{{ $account->phone_number ?: '—' }}</td>
                    <td>{{ $account->payment_date?->format('d M Y') ?: '—' }}</td>
                    <td>{{ $account->status_label }}</td>
                    <td>{{ $account->category ?: '—' }}</td>
                    <td class="number">₹{{ number_format((float) $account->closing_balance, 2) }}</td>
                    <td class="number">{{ $account->paid_amount === null ? '—' : '₹'.number_format((float) $account->paid_amount, 2) }}</td>
                    <td class="number">₹{{ number_format($account->pending_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="8">No accounts match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Ledgerly · Account Payment Report · {{ $generatedAt->format('d M Y') }}</div>
</body>
</html>
