<x-filament-widgets::widget>
    <style>
        /* Self-contained dashboard styles — no Tailwind dependency (app ships no compiled CSS) */
        .ttdash { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin: 4px 0 18px; }
        .ttdash .c { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, .06); min-width: 0; }
        .ttdash .l { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ttdash .n { font-size: 22px; font-weight: 800; line-height: 1.2; margin-top: 2px; color: #0f172a; }
        .ttdash .s { font-size: 11.5px; color: #6b7280; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ttdash .c.green  { border-color: #d1fae5; } .ttdash .green .n  { color: #059669; }
        .ttdash .c.orange { border-color: #fed7aa; } .ttdash .orange .n { color: #ea580c; }
        .ttdash .c.red    { border-color: #fecaca; } .ttdash .red .n    { color: #dc2626; }
        .ttdash .c.blue   { border-color: #bfdbfe; } .ttdash .blue .n   { color: #2563eb; }
        .ttdash .c.purple { border-color: #ddd6fe; } .ttdash .purple .n { color: #7c3aed; }
        html.dark .ttdash .c { background: #1e293b; border-color: #334155; box-shadow: none; }
        html.dark .ttdash .l, html.dark .ttdash .s { color: #94a3b8; }
        html.dark .ttdash .n { color: #f1f5f9; }
        @media (max-width: 1100px) { .ttdash { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 560px)  { .ttdash { grid-template-columns: 1fr; } }
        .ttsec-title { font-size: 14px; font-weight: 600; color: #374151; margin: 4px 0 10px; }
        html.dark .ttsec-title { color: #e5e7eb; }
        .tttbl { width: 100%; border-collapse: collapse; font-size: 13.5px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        html.dark .tttbl { border-color: #334155; }
        .tttbl th { text-align: left; padding: 9px 12px; background: #f9fafb; color: #6b7280; font-weight: 600; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        html.dark .tttbl th { background: #0f172a; color: #94a3b8; border-bottom-color: #334155; }
        .tttbl td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; color: #111827; }
        html.dark .tttbl td { border-bottom-color: #1e293b; color: #e5e7eb; }
        .tttbl tr:last-child td { border-bottom: none; }
        .tttbl tr:hover td { background: #f9fafb; }
        html.dark .tttbl tr:hover td { background: #1e293b; }
        .tttbl .r { text-align: right; }
        .tttbl .ctr { text-align: center; }
        .ttpill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
        .ttpill.paid { background: #d1fae5; color: #047857; }
        .ttpill.pending { background: #fef3c7; color: #b45309; }
        .ttpill.overdue { background: #fee2e2; color: #b91c1c; }
        .ttpill.cancelled { background: #f3f4f6; color: #6b7280; }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span>🏢 Site Overview</span>
            </div>
        </x-slot>

        <div class="ttdash">
            <div class="c">
                <div class="l">Properties</div>
                <div class="n">{{ $totalProperties }}</div>
                <div class="s">{{ $occupiedUnits }} occupied</div>
            </div>
            <div class="c">
                <div class="l">Tenants</div>
                <div class="n">{{ $totalTenants }}</div>
                <div class="s">{{ $totalOwners }} owners · {{ $previousTenants }} previous</div>
            </div>
            <div class="c green">
                <div class="l">Monthly Revenue</div>
                <div class="n">${{ number_format($monthlyRevenue) }}</div>
            </div>
            <div class="c purple">
                <div class="l">Mgmt Fees</div>
                <div class="n">${{ number_format($monthlyManagementFees, 2) }}</div>
                <div class="s">${{ number_format($ytdManagementFees, 2) }} YTD</div>
            </div>
            <div class="c orange">
                <div class="l">Pending Invoices</div>
                <div class="n">{{ $pendingInvoices }}</div>
            </div>
            <div class="c red">
                <div class="l">Overdue</div>
                <div class="n">{{ $overdueInvoices }}</div>
                <div class="s">${{ number_format($overdueAmount) }} total</div>
            </div>
            <div class="c blue">
                <div class="l">Open Tickets</div>
                <div class="n">{{ $openTickets }}</div>
            </div>
            <div class="c {{ $dueReminders > 0 ? 'red' : '' }}">
                <div class="l">Reminders</div>
                <div class="n">{{ $totalReminders }}</div>
                <div class="s">{{ $dueReminders }} due now</div>
            </div>
        </div>

        <div>
            <h3 class="ttsec-title">Recent Invoices</h3>
            @if(count($recentInvoices) > 0)
                <table class="tttbl">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Tenant</th>
                            <th>Property</th>
                            <th class="r">Amount</th>
                            <th class="ctr">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentInvoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->tenant?->name }}</td>
                                <td>{{ $inv->property?->title }}</td>
                                <td class="r">${{ number_format($inv->amount, 2) }}</td>
                                <td class="ctr">
                                    <span class="ttpill {{ $inv->status === "paid" ? "paid" : "" }}{{ $inv->status === "pending" ? "pending" : "" }}{{ $inv->status === "overdue" ? "overdue" : "" }}{{ $inv->status === "cancelled" ? "cancelled" : "" }}">
                                        {{ $inv->status === "pending" ? "Unpaid" : ucfirst($inv->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="padding:16px;text-align:center;font-size:13px;color:#9ca3af;">No invoices yet.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
