<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #1a1a1a; padding: 30px; text-align: center; }
        .header h1 { color: #DCA54A; margin: 0; font-size: 24px; font-family: Georgia, serif; letter-spacing: 2px; }
        .body { padding: 30px; }
        .body h2 { color: #1f2937; font-size: 18px; margin: 0 0 16px; }
        .body p { color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0 0 10px; }
        .details { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; font-size: 14px; }
        .details td:last-child { text-align: right; font-weight: 600; }
        .total-row td { border-top: 2px solid #DCA54A; padding-top: 12px; font-size: 16px; font-weight: 700; }
        .button { display: inline-block; background: #DCA54A; color: #1a1a1a; text-decoration: none; padding: 12px 32px; border-radius: 6px; font-weight: 700; font-size: 14px; margin-top: 20px; }
        .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TENANT TRUST</h1>
        </div>
        <div class="body">
            <h2>Invoice #{{ $invoice->invoice_number }}</h2>

            <p>Dear <strong>{{ $invoice->tenant?->name }}</strong>,</p>

            <p>Please find your invoice attached below. Your payment is due by <strong>{{ $invoice->due_date?->format('F j, Y') }}</strong>.</p>

            <div class="details">
                <table>
                    <tr><td>Invoice #</td><td>{{ $invoice->invoice_number }}</td></tr>
                    <tr><td>Date</td><td>{{ $invoice->created_at?->format('F j, Y') }}</td></tr>
                    <tr><td>Due Date</td><td>{{ $invoice->due_date?->format('F j, Y') }}</td></tr>
                    <tr><td>Status</td><td>{{ $invoice->status === 'pending' ? 'Unpaid' : ucfirst($invoice->status) }}</td></tr>
                    <tr><td colspan="2" style="padding:8px 0;border-bottom:1px solid #e5e7eb;"></td></tr>
                    <tr><td>{{ $invoice->description }}</td><td>${{ number_format($invoice->amount, 2) }}</td></tr>
                    <tr class="total-row"><td>Total Due</td><td>${{ number_format($invoice->amount, 2) }}</td></tr>
                </table>
            </div>

            <p style="text-align:center;">
                <a href="{{ url('/tenant/invoices') }}" class="button">Pay Online</a>
            </p>

            <p style="color:#6b7280;font-size:13px;">If you have any questions regarding this invoice, please contact us at <a href="mailto:info@tenanttrust.ca" style="color:#DCA54A;">info@tenanttrust.ca</a> or call (705) 555-0123.</p>
        </div>
        <div class="footer">
            Tenant Trust Inc. &bull; 123 Main Street West, Sudbury, ON P3E 4T2 &bull; (705) 555-0123
        </div>
    </div>
</body>
</html>
