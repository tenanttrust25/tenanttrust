<x-filament-panels::page>
    <div style="max-width:800px;margin:0 auto;font-family:Arial,sans-serif;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:32px;">

        {{-- Header: Logo left, Invoice title right --}}
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:60%;vertical-align:top;">
                    <img src="{{ asset('logo-tt.svg') }}" alt="Tenant Trust" style="width:200px;height:auto;" />
                </td>
                <td style="width:40%;vertical-align:top;text-align:right;">
                    <div style="font-size:24px;font-weight:700;color:#1f2937;letter-spacing:1px;">INVOICE</div>
                    <div style="margin-top:8px;font-size:13px;color:#6b7280;">
                        <div><strong style="color:#374151;">Status:</strong>
                            <span style="display:inline-block;padding:2px 12px;border-radius:12px;font-size:12px;font-weight:600;
                                {{ $this->record->status === 'pending' ? 'background:#fef3c7;color:#d97706;' : '' }}
                                {{ $this->record->status === 'paid' ? 'background:#d1fae5;color:#059669;' : '' }}
                                {{ $this->record->status === 'overdue' ? 'background:#fee2e2;color:#dc2626;' : '' }}
                                {{ $this->record->status === 'cancelled' ? 'background:#f3f4f6;color:#6b7280;' : '' }}">
                                {{ $this->record->status === 'pending' ? 'Unpaid' : ucfirst($this->record->status) }}
                            </span>
                        </div>
                        <div style="margin-top:6px;"><strong style="color:#374151;">Invoice #:</strong> {{ $this->record->invoice_number }}</div>
                        <div style="margin-top:4px;"><strong style="color:#374151;">Date:</strong> {{ $this->record->created_at?->format('F j, Y') }}</div>
                        <div style="margin-top:4px;"><strong style="color:#374151;">Due Date:</strong> {{ $this->record->due_date?->format('F j, Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <hr style="border:none;border-top:2px solid #DCA54A;margin:24px 0;" />

        {{-- Bill To + Tenant Info --}}
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="width:50%;vertical-align:top;">
                    <div style="font-size:14px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Bill To</div>
                    <div style="font-size:15px;font-weight:600;color:#1f2937;">{{ $this->record->tenant?->name }}</div>
                    <div style="font-size:13px;color:#6b7280;margin-top:2px;">{{ $this->record->tenant?->email }}</div>
                </td>
                <td style="width:50%;vertical-align:top;">
                    <div style="font-size:14px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Property</div>
                    <div style="font-size:15px;font-weight:600;color:#1f2937;">{{ $this->record->property?->title }}</div>
                </td>
            </tr>
        </table>

        {{-- Items Table --}}
        <table style="width:100%;border-collapse:collapse;margin-top:24px;">
            <thead>
                <tr style="background:#1a1a1a;">
                    <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#DCA54A;text-transform:uppercase;letter-spacing:0.5px;">Description</th>
                    <th style="padding:12px 16px;text-align:right;font-size:13px;font-weight:600;color:#DCA54A;text-transform:uppercase;letter-spacing:0.5px;width:150px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:14px 16px;font-size:14px;color:#374151;">{{ $this->record->description }}</td>
                    <td style="padding:14px 16px;text-align:right;font-size:16px;font-weight:700;color:#1f2937;">${{ number_format($this->record->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Total --}}
        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <tr style="border-top:2px solid #DCA54A;">
                <td style="padding:12px 16px;text-align:right;font-size:16px;font-weight:700;color:#1f2937;">Total Due</td>
                <td style="padding:12px 16px;text-align:right;font-size:18px;font-weight:800;color:#1f2937;">${{ number_format($this->record->amount, 2) }}</td>
            </tr>
        </table>

        {{-- Footer --}}
        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0 16px;" />
        <div style="text-align:center;font-size:12px;color:#9ca3af;">
            Tenant Trust &bull; 474 McKim St Unit 3, Sudbury, ON P3C 2L6 &bull; info@tenanttrust.ca &bull; 249-523-3331
        </div>
    </div>
</x-filament-panels::page>
