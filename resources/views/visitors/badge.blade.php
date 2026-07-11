<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $format === 'pass' ? 'Visitor Pass' : 'Visitor Badge' }} — {{ $visit->visit_code }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px; font-family: 'Segoe UI', Arial, sans-serif;
            background: #e8ebe9; color: #10201a;
            display: flex; flex-direction: column; align-items: center; gap: 16px; min-height: 100vh;
        }
        .toolbar { display: flex; gap: 8px; }
        .toolbar button, .toolbar a {
            padding: 8px 18px; border-radius: 8px; border: 1px solid #059669; cursor: pointer;
            background: #059669; color: #fff; font-size: 14px; text-decoration: none;
        }
        .toolbar a { background: transparent; color: #059669; }
        .card {
            background: #fff; border-radius: 14px; overflow: hidden;
            box-shadow: 0 8px 30px rgba(16, 32, 26, .18); border: 1px solid #d5dbd8;
            width: {{ $format === 'pass' ? '560px' : '340px' }}; max-width: 100%;
        }
        .card-head {
            background: linear-gradient(135deg, #064e3b, #059669); color: #fff;
            padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
        .card-head .brand { font-weight: 700; letter-spacing: .4px; font-size: 15px; }
        .card-head .kind { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: .9; }
        .card-body { padding: 18px; display: flex; gap: 16px; }
        .photo {
            width: 84px; height: 84px; border-radius: 12px; flex: none; object-fit: cover;
            background: #059669; color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 30px; font-weight: 700;
        }
        .who { min-width: 0; }
        .name { font-size: 19px; font-weight: 700; margin: 0 0 2px; }
        .company { color: #4b5b54; margin: 0 0 8px; font-size: 13px; }
        .meta { display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; font-size: 12.5px; }
        .meta dt { color: #6b7a73; }
        .meta dd { margin: 0; font-weight: 600; }
        .card-foot {
            border-top: 1px dashed #c8d0cc; padding: 14px 18px;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        .code { font-family: Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: 1px; }
        .badge-no { font-size: 12px; color: #4b5b54; }
        .qr { image-rendering: pixelated; border: 4px solid #fff; outline: 1px solid #c8d0cc; }
        .access {
            display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px;
            background: rgba(5, 150, 105, .12); color: #047857; font-size: 11.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
        }
        .pass-extra { padding: 0 18px 16px; font-size: 12.5px; }
        .pass-extra h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #6b7a73; margin: 12px 0 6px; }
        .pass-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 18px; }
        .pass-grid div { display: flex; justify-content: space-between; gap: 10px; border-bottom: 1px dotted #dde3e0; padding: 3px 0; }
        .pass-grid span:first-child { color: #6b7a73; }
        .rules { color: #4b5b54; margin: 8px 0 0; padding-left: 18px; }
        .rules li { margin-bottom: 2px; }
        .disabled-stamp {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            font-size: 34px; font-weight: 800; color: rgba(220, 38, 38, .35);
            transform: rotate(-18deg); pointer-events: none; text-transform: uppercase; letter-spacing: 4px;
        }
        .card { position: relative; }
        .hint { font-size: 12px; color: #6b7a73; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar, .hint { display: none; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button type="button" onclick="window.print()">🖨 Print {{ $format === 'pass' ? 'Pass' : 'Badge' }}</button>
    <a href="{{ route('visitors.show', $visit) }}">Back to visit</a>
</div>

<div class="card">
    @if (in_array($visit->status, [App\Enums\VisitStatus::CheckedOut, App\Enums\VisitStatus::Completed, App\Enums\VisitStatus::Rejected], true))
        <div class="disabled-stamp">{{ $visit->status === App\Enums\VisitStatus::Rejected ? 'Rejected' : 'Expired' }}</div>
    @endif

    <div class="card-head">
        <span class="brand">🛡 {{ config('app.name') }}</span>
        <span class="kind">{{ $format === 'pass' ? 'Visitor Pass' : 'Visitor Badge' }}</span>
    </div>

    <div class="card-body">
        @if ($visit->photo_url)
            <img class="photo" src="{{ $visit->photo_url }}" alt="{{ $visit->full_name }}">
        @else
            <div class="photo" aria-hidden="true">{{ $visit->initials }}</div>
        @endif

        <div class="who">
            <p class="name">{{ $visit->full_name }}</p>
            <p class="company">{{ $visit->company ?? 'Individual visitor' }}</p>
            <dl class="meta">
                <dt>Visiting</dt><dd>{{ $visit->host?->name ?? '—' }}</dd>
                <dt>Department</dt><dd>{{ $visit->department }}</dd>
                <dt>Date</dt><dd>{{ $visit->visit_date->format('M j, Y') }}</dd>
                @if ($visit->checked_in_at)
                    <dt>Check-In</dt><dd>{{ $visit->checked_in_at->format('H:i') }}</dd>
                @endif
            </dl>
            <span class="access">{{ $visit->access_level->label() }}</span>
        </div>
    </div>

    @if ($format === 'pass')
        <div class="pass-extra">
            <h3>Visit Details</h3>
            <div class="pass-grid">
                <div><span>{{ $visit->document_type->label() }}</span><span>{{ $visit->national_id }}</span></div>
                <div><span>Phone</span><span>{{ $visit->phone }}</span></div>
                <div><span>Purpose</span><span>{{ \Illuminate\Support\Str::limit($visit->purpose, 30) }}</span></div>
                <div><span>Companions</span><span>{{ $visit->companions }}</span></div>
                <div><span>Expected Duration</span><span>{{ $visit->expected_duration_minutes }} min</span></div>
                <div><span>Vehicle</span><span>{{ $visit->vehicle_plate ?? '—' }}</span></div>
                <div><span>Bag Inspected</span><span>{{ $visit->bag_inspected ? 'Yes' : 'No' }}</span></div>
                <div><span>Registered By</span><span>{{ $visit->registrar?->name ?? '—' }}</span></div>
            </div>
            <h3>Building Rules</h3>
            <ul class="rules">
                <li>Wear this pass visibly at all times inside the facility.</li>
                <li>Stay within your granted access level: {{ $visit->access_level->label() }}.</li>
                <li>You must be accompanied by your host outside reception areas.</li>
                <li>Return the badge and check out at reception before leaving.</li>
            </ul>
        </div>
    @endif

    <div class="card-foot">
        <div>
            <div class="code">{{ $visit->visit_code }}</div>
            <div class="badge-no">Badge: {{ $visit->badge_number ?? 'issued at check-in' }}</div>
        </div>

        {{-- Deterministic QR-style placeholder rendered from the visit code.
             Swap for a real QR library when scanner hardware is integrated. --}}
        @php
            $size = 21;
            $bits = '';
            for ($i = 0; strlen($bits) < $size * $size; $i++) {
                foreach (str_split(md5($visit->visit_code.'|'.$i)) as $hex) {
                    $bits .= str_pad(base_convert($hex, 16, 2), 4, '0', STR_PAD_LEFT);
                }
            }
        @endphp
        <svg class="qr" width="84" height="84" viewBox="0 0 {{ $size }} {{ $size }}" role="img" aria-label="Machine-readable code for {{ $visit->visit_code }}">
            <rect width="{{ $size }}" height="{{ $size }}" fill="#fff"/>
            @for ($y = 0; $y < $size; $y++)
                @for ($x = 0; $x < $size; $x++)
                    @php
                        $inFinder = ($x < 7 && $y < 7) || ($x >= $size - 7 && $y < 7) || ($x < 7 && $y >= $size - 7);
                        $dark = $bits[$y * $size + $x] === '1';
                    @endphp
                    @if ($inFinder)
                        @continue
                    @endif
                    @if ($dark)<rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#10201a"/>@endif
                @endfor
            @endfor
            @foreach ([[0, 0], [$size - 7, 0], [0, $size - 7]] as [$fx, $fy])
                <rect x="{{ $fx }}" y="{{ $fy }}" width="7" height="7" fill="#10201a"/>
                <rect x="{{ $fx + 1 }}" y="{{ $fy + 1 }}" width="5" height="5" fill="#fff"/>
                <rect x="{{ $fx + 2 }}" y="{{ $fy + 2 }}" width="3" height="3" fill="#10201a"/>
            @endforeach
        </svg>
    </div>
</div>

<p class="hint">Use your browser's print dialog to save as PDF.</p>

</body>
</html>
