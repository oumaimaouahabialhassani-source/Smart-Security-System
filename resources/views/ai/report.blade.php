<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Security Report — {{ $date->format('Y-m-d') }} — {{ config('app.name') }}</title>
    {{-- Standalone printable page (like the visitor badge/pass): use the browser's Print → Save as PDF. --}}
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 32px; font-family: 'Segoe UI', Arial, sans-serif;
            background: #e8ebe9; color: #10201a; font-size: 13px;
        }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 15px; }
        .sub { color: #4b5b54; margin: 0 0 20px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 22px; }
        .card {
            background: #fff; border: 1px solid #d5dbd8; border-radius: 14px; padding: 14px;
            box-shadow: 0 8px 30px rgba(16, 32, 26, .08);
        }
        .card .v { font-size: 22px; font-weight: 700; color: #047857; }
        .card .l { color: #4b5b54; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; background: #fff; }
        th, td { border: 1px solid #d5dbd8; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f0f4f2; font-size: 12px; }
        .pill {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
        }
        .pill-critical, .pill-high { background: rgba(220, 38, 38, .10); color: #dc2626; }
        .pill-medium { background: rgba(217, 119, 6, .12); color: #d97706; }
        .pill-low { background: rgba(5, 150, 105, .12); color: #047857; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 18px; }
        .toolbar button {
            padding: 8px 18px; border-radius: 8px; border: 1px solid #059669; cursor: pointer;
            background: #059669; color: #fff; font-size: 14px;
        }
        footer { margin-top: 26px; color: #4b5b54; font-size: 11px; }
        @media print { .toolbar { display: none; } body { padding: 0; background: #fff; } }
    </style>
</head>
<body>

    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <h1>AI Security Bot — Daily Security Report</h1>
    <p class="sub">{{ config('app.name') }} · {{ $date->format('l, F j, Y') }} · Generated {{ now()->format('Y-m-d H:i') }} by {{ auth()->user()->name }}</p>

    <div class="grid">
        <div class="card"><div class="v">{{ $alerts->count() }}</div><div class="l">AI alerts on this day</div></div>
        <div class="card"><div class="v">{{ $byLevel->get('critical', 0) }} / {{ $byLevel->get('high', 0) }}</div><div class="l">Critical / High risk</div></div>
        <div class="card"><div class="v">{{ $camerasOffline }} + {{ $devicesOffline }}</div><div class="l">Cameras + IoT devices offline now</div></div>
        <div class="card"><div class="v">{{ $visitorsInside }}</div><div class="l">Visitors currently inside</div></div>
    </div>

    <h2 style="font-size:15px">Detected Events</h2>
    <table>
        <thead>
            <tr>
                <th>Alert ID</th>
                <th>Time</th>
                <th>Event Type</th>
                <th>Description</th>
                <th>Risk</th>
                <th>AI Analysis</th>
                <th>Recommendation</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($alerts as $alert)
                <tr>
                    <td>{{ $alert->ai_code }}</td>
                    <td>{{ $alert->happened_at->format('H:i:s') }}</td>
                    <td>{{ $alert->event_type }}</td>
                    <td>{{ $alert->description }}</td>
                    <td><span class="pill pill-{{ $alert->risk_level->value }}">{{ $alert->risk_level->label() }} ({{ $alert->risk_score }})</span></td>
                    <td>{{ $alert->analysis }}</td>
                    <td>{{ $alert->recommendation->label() }}</td>
                    <td>{{ $alert->status->label() }}{{ $alert->reviewer ? ' — '.$alert->reviewer->name : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No AI alerts were recorded on this day.</td></tr>
            @endforelse
        </tbody>
    </table>

    <footer>Generated automatically by the AI Security Bot module of {{ config('app.name') }}. Risk levels and recommendations are produced by a rule-based analysis engine.</footer>

</body>
</html>
