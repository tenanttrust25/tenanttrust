<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Pay Invoice #{{ $invoice->invoice_number }} — Tenant Trust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Work Sans', sans-serif;
            background: #0F172A;
            color: #4A4A4A;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            max-width: 460px;
            width: 100%;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
        }
        .brand {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0F172A;
            text-align: center;
            margin-bottom: 24px;
        }
        .brand span { color: #DCA54A; }
        h1 {
            font-family: 'Manrope', sans-serif;
            font-size: 18px;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            text-align: center;
            margin-bottom: 4px;
        }
        .sub { text-align: center; font-size: 14px; color: #8A8A8A; margin-bottom: 24px; }
        .amount {
            text-align: center;
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 40px;
            color: #0F172A;
            margin-bottom: 4px;
        }
        .due { text-align: center; font-size: 14px; color: #8A8A8A; margin-bottom: 24px; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 16px;
        }
        .status-pending, .status-overdue { background: #FEF3C7; color: #92400E; }
        .status-paid { background: #D1FAE5; color: #065F46; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
        .center { text-align: center; }
        .desc {
            font-size: 14px;
            color: #4A4A4A;
            background: #F7F7F8;
            border-radius: 10px;
            padding: 12px 16px;
            margin: 16px 0 24px;
            line-height: 1.6;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: #DCA54A;
            color: #0F172A;
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn:hover { background: #c9933a; }
        .btn-green { background: #16A34A; color: #fff; }
        .btn-green:hover { background: #15803D; }
        .btn-gray { background: #E5E7EB; color: #374151; }
        .btn-gray:hover { background: #D1D5DB; }
        .secure {
            text-align: center;
            font-size: 12px;
            color: #B0B0B0;
            margin-top: 16px;
        }
        .success-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #D1FAE5;
            color: #065F46;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .message { text-align: center; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .foot {
            text-align: center;
            font-size: 12px;
            color: #B0B0B0;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">Tenant<span>Trust</span></div>

        @if ($paid)
            <div class="success-icon">✓</div>
            <h1>Payment Received</h1>
            <p class="sub">Invoice #{{ $invoice->invoice_number }}</p>
            <div class="amount">${{ number_format($invoice->amount, 2) }}</div>
            <p class="message" style="margin-top:16px;">
                Thank you — your payment has been processed successfully.<br>
                A receipt is on file with Tenant Trust.
            </p>
            <div class="center"><span class="status-badge status-paid">Paid</span></div>
        @elseif ($cancelled)
            <h1>Payment Not Completed</h1>
            <p class="sub">Invoice #{{ $invoice->invoice_number }}</p>
            <div class="amount">${{ number_format($invoice->amount, 2) }}</div>
            <p class="message" style="margin-top:16px;">
                Your payment was cancelled. No charge was made.<br>
                You can try again whenever you're ready.
            </p>
            <form method="POST" action="{{ route('invoice.pay.checkout', $invoice->payment_token) }}">
                @csrf
                <button type="submit" class="btn">Try Again — Pay Now</button>
            </form>
        @else
            <div class="center">
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ $invoice->status === 'overdue' ? 'Overdue' : ucfirst($invoice->status) }}
                </span>
            </div>
            <h1>Invoice #{{ $invoice->invoice_number }}</h1>
            <p class="sub">Tenant Trust — Property Management</p>
            <div class="amount">${{ number_format($invoice->amount, 2) }}</div>
            <p class="due">Due {{ $invoice->due_date?->format('F j, Y') }}</p>

            @if ($invoice->description)
                <div class="desc">{{ $invoice->description }}</div>
            @endif

            @if (in_array($invoice->status, ['pending', 'overdue']))
                <form method="POST" action="{{ route('invoice.pay.checkout', $invoice->payment_token) }}">
                    @csrf
                    <button type="submit" class="btn">Pay Now</button>
                </form>
                <p class="secure">🔒 Secured payment powered by Stripe</p>
            @else
                <div class="center"><span class="status-badge status-{{ $invoice->status }}">
                    {{ ucfirst($invoice->status) }}
                </span></div>
            @endif
        @endif

        <p class="foot">Tenant Trust · tenanttrust.ca</p>
    </div>
</body>
</html>
