<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 10pt; }
        /* Top ~2.75in is left BLANK: PostGrid prints the return + destination
           addresses there (1-sheet layout). Branding lives in the footer band. */
        .page { padding: 3.3in 0.9in 0.78in; }
        .date { margin: 0 0 11px; }
        .re { font-weight: bold; margin: 0 0 11px; }
        p.body { line-height: 1.3; margin: 0 0 8px; text-align: left; }
        .sig { margin-top: 16px; line-height: 1.3; }
        .foot { position: fixed; bottom: 0.42in; left: 0.9in; right: 0.9in;
                border-top: 1px solid #cbd5e1; padding-top: 6px; text-align: center;
                font-size: 7.5pt; color: #64748b; }
        .foot img { width: 54px; display: block; margin: 0 auto 3px; }
    </style>
</head>
<body>
<div class="page">
    <div class="date">{{ $date }}</div>

    @if ($subject)
        <div class="re">RE: {{ $subject }}</div>
    @endif

    @if ($salutation)
        <div style="margin-bottom:12px;">{{ $salutation }}</div>
    @endif

    @foreach ($paragraphs as $para)
        <p class="body">{{ $para }}</p>
    @endforeach

    <div class="sig">
        Sincerely,<br><br>
        {{ $signerName }}<br>
        Tenant Trust<br>
        3-474 McKim St, Sudbury, ON&nbsp; P3C 2L6<br>
        249-523-3331 &nbsp;|&nbsp; info@tenanttrust.ca
    </div>
</div>

<div class="foot">
    @if ($logo)<img src="{{ $logo }}" alt="Tenant Trust">@endif
    Tenant Trust &nbsp;&middot;&nbsp; 3-474 McKim St, Sudbury ON P3C 2L6 &nbsp;&middot;&nbsp; 249-523-3331 &nbsp;&middot;&nbsp; info@tenanttrust.ca
</div>
</body>
</html>
