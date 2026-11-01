<x-filament-panels::page>
    <style>
        .tt-report { margin-bottom: 28px; }
        .tt-report h3 { font-size: 1rem; font-weight: 700; margin: 0 0 10px; }
        .tt-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .tt-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .dark .tt-card { background: #1f2937; border-color: #374151; }
        .tt-card .v { font-size: 1.4rem; font-weight: 800; }
        .tt-card .l { font-size: .72rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .tt-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .tt-table th, .tt-table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eef2f7; }
        .dark .tt-table th, .dark .tt-table td { border-color: #374151; }
        .tt-table th { font-size: .72rem; text-transform: uppercase; color: #6b7280; }
        .tt-right { text-align: right; }
    </style>

    <div class="tt-cards">
        <div class="tt-card"><div class="l">Active Leases</div><div class="v">{{ $rentRoll->count() }}</div></div>
        <div class="tt-card"><div class="l">Monthly Rent Roll</div><div class="v">${{ number_format($rentRollTotal, 2) }}</div></div>
        <div class="tt-card"><div class="l">Arrears Outstanding</div><div class="v">${{ number_format($arrearsTotal, 2) }}</div></div>
        <div class="tt-card"><div class="l">Vacant Units</div><div class="v">{{ $vacantUnits->count() }}</div></div>
        <div class="tt-card"><div class="l">Maintenance Spend</div><div class="v">${{ number_format($maintTotalCost, 2) }}</div></div>
    </div>

    <div class="tt-report">
        <h3>Rent Roll (active leases)</h3>
        <table class="tt-table">
            <thead><tr><th>Tenant</th><th>Property</th><th>Unit</th><th class="tt-right">Rent</th><th>Lease End</th></tr></thead>
            <tbody>
            @forelse ($rentRoll as $tenancy)
                <tr>
                    <td>{{ $tenancy->tenant?->name ?? '—' }}</td>
                    <td>{{ $tenancy->property?->title ?? '—' }}</td>
                    <td>{{ $tenancy->unit ?? '—' }}</td>
                    <td class="tt-right">${{ number_format((float) $tenancy->rent_amount, 2) }}</td>
                    <td>{{ $tenancy->lease_end?->format('M j, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No active leases.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tt-report">
        <h3>Arrears (outstanding balances)</h3>
        <table class="tt-table">
            <thead><tr><th>Tenant</th><th>Invoices</th><th>Oldest Due</th><th class="tt-right">Total Outstanding</th></tr></thead>
            <tbody>
            @forelse ($arrears as $row)
                <tr>
                    <td>{{ $row['tenant'] }}</td>
                    <td>{{ $row['invoices'] }}</td>
                    <td>{{ $row['oldest_due']?->format('M j, Y') ?? '—' }}</td>
                    <td class="tt-right">${{ number_format($row['outstanding'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No outstanding balances.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tt-report">
        <h3>Vacant Units</h3>
        <table class="tt-table">
            <thead><tr><th>Property</th><th>Unit</th><th class="tt-right">Rent</th></tr></thead>
            <tbody>
            @forelse ($vacantUnits as $unit)
                <tr>
                    <td>{{ $propertyTitles[$unit->property_id] ?? '—' }}</td>
                    <td>{{ $unit->unit ?? '—' }}</td>
                    <td class="tt-right">${{ number_format((float) $unit->rent_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No vacant units.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tt-report">
        <h3>Maintenance by Status</h3>
        <table class="tt-table">
            <thead><tr><th>Status</th><th class="tt-right">Tickets</th></tr></thead>
            <tbody>
            @forelse ($maintByStatus as $status => $count)
                <tr><td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td><td class="tt-right">{{ $count }}</td></tr>
            @empty
                <tr><td colspan="2">No tickets.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
