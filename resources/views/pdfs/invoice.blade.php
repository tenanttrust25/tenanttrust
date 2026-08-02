<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Work Sans', sans-serif;
            font-size: 12px;
            color: #0F172A;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-family: 'Manrope', sans-serif;
            text-transform: uppercase;
            color: #DCA54A;
            font-size: 24px;
            margin: 0;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
        }
        .details {
            width: 100%;
            margin-bottom: 30px;
        }
        .details td {
            padding: 5px 10px;
            vertical-align: top;
        }
        .details .label {
            font-weight: bold;
            width: 120px;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .amount-table th, .amount-table td {
            border: 1px solid #DCA54A;
            padding: 10px;
            text-align: left;
        }
        .amount-table th {
            background-color: #F0E6C5;
            font-family: 'Manrope', sans-serif;
            text-transform: uppercase;
            font-size: 11px;
        }
        .total-row td {
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-overdue { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tenant Trust</h1>
        <div class="invoice-title">INVOICE</div>
    </div>

    <table class="details">
        <tr>
            <td class="label">Invoice #:</td>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Date:</td>
            <td>{{ $invoice->created_at->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Due Date:</td>
            <td>{{ $invoice->due_date->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td>
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </td>
        </tr>
    </table>

    <h3>Bill To:</h3>
    <p>
        {{ $tenant->name }}<br>
        {{ $tenant->address ?? 'N/A' }}<br>
        {{ $tenant->email }}
    </p>

    @if ($property)
        <h3>Property:</h3>
        <p>{{ $property->title }} - {{ $property->address }}, {{ $property->city }}, {{ $property->province }}</p>
    @endif

    <table class="amount-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->description ?? 'Rent' }}</td>
                <td style="text-align: right">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Due</td>
                <td style="text-align: right">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Tenant Trust &bull; 123 Business Ave &bull; Toronto, ON &bull; hello@tenanttrust.ca</p>
        <p>Thank you for your business!</p>
    </div>
</body>
</html>
