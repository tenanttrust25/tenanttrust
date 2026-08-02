<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rent Receipt {{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #0F172A;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #DCA54A;
            padding-bottom: 20px;
        }
        .header h1 {
            text-transform: uppercase;
            color: #DCA54A;
            font-size: 22px;
            margin: 0;
            letter-spacing: 2px;
        }
        .header .subtitle {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .header .contact-info {
            font-size: 8px;
            color: #888;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #E5E7EB;
        }
        .header .contact-info span {
            margin: 0 3px;
        }
        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            color: #0F172A;
        }
        .tenant-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #F8F6F0;
            border-radius: 5px;
        }
        .tenant-info .label {
            font-weight: bold;
            color: #DCA54A;
            width: 100px;
        }
        .year-summary {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #FFF8E7;
            border: 1px solid #DCA54A;
            border-radius: 5px;
        }
        .year-summary .amount {
            font-size: 24px;
            font-weight: bold;
            color: #0F172A;
        }
        .year-summary .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #DCA54A;
            color: #0F172A;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 7px 10px;
            border-bottom: 1px solid #E5E7EB;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #DCA54A;
            border-bottom: none;
            padding-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #E5E7EB;
            padding-top: 15px;
        }
        .footer p {
            margin: 2px 0;
        }
        .page-break {
            page-break-after: always;
        }
        .disclaimer {
            margin-top: 20px;
            font-size: 8px;
            color: #999;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tenant Trust</h1>
        <div class="subtitle">Property Management</div>
        <div class="contact-info">
            <span>{{ $businessAddress }}</span> &bull;
            <span>{{ $businessPhone }}</span> &bull;
            <span>{{ $businessEmail }}</span> &bull;
            <span>{{ $businessWebsite }}</span>
        </div>
    </div>

    <div class="receipt-title">ANNUAL RENT RECEIPT — {{ $year }}</div>

    <div class="tenant-info">
        <table style="margin:0; border:none;">
            <tr>
                <td class="label">Tenant:</td>
                <td>{{ $user->name }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td>{{ $unitAddress ?? "N/A" }}</td>
            </tr>
            <tr>
                <td class="label">Year:</td>
                <td>January 1, {{ $year }} — December 31, {{ $year }}</td>
            </tr>
        </table>
    </div>

    <div class="year-summary">
        <div class="label">Total Rent Paid for {{ $year }}</div>
        <div class="amount">${{ number_format($totalPaid, 2) }}</div>
        <div style="margin-top:5px; font-size:10px; color:#666;">
            {{ count($paidInvoices) }} payment(s) made
        </div>
    </div>

    @if(count($paidInvoices) > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                    <th>Description</th>
                    <th>Property</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paidInvoices as $inv)
                    <tr>
                        <td>{{ $inv->due_date->format('M d, Y') }}</td>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ $inv->description ?? 'Rent' }}</td>
                        <td>{{ $inv->property?->title ?? '—' }}</td>
                        <td class="text-right">${{ number_format($inv->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right">Total for {{ $year }}</td>
                    <td class="text-right">${{ number_format($totalPaid, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p style="text-align:center; color:#999; margin-top:30px;">No paid invoices recorded for {{ $year }}.</p>
    @endif

    <div class="disclaimer">
        This is a computer-generated receipt and does not require a signature.<br>
        Tenant Trust &bull; info@tenanttrust.ca &bull; tenanttrust.ca
    </div>
</body>
</html>
