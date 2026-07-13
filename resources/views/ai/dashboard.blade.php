@extends('layouts.app')

@section('title', 'AI Security Bot — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">AI Security Bot</h1>
            <p class="page-subtitle">Intelligent monitoring of every security event — automatic risk analysis, alerts and recommendations.</p>
        </div>
        <div class="row-actions">
            @if (auth()->user()->role->canUseAiAssistant())
                <a href="{{ route('ai.chat') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Chat Assistant
                </a>
            @endif
            @if (auth()->user()->role->canManageAlerts())
            <form method="POST" action="{{ route('ai.scan') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Run AI Scan
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Summary stat cards --}}
    <section class="stats-grid stats-grid-5">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <span class="stat-value">{{ $stats['total'] }}</span>
            </div>
            <div class="stat-label">Total AI Alerts</div>
            <div class="stat-meta">{{ $stats['open'] }} still open</div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="stat-value">{{ $stats['critical'] }}</span>
            </div>
            <div class="stat-label">Critical Alerts</div>
            <div class="stat-meta">Immediate action required</div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span class="stat-value">{{ $stats['high'] }}</span>
            </div>
            <div class="stat-label">High Risk</div>
            <div class="stat-meta">Escalated findings</div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <span class="stat-value">{{ $stats['medium'] }}</span>
            </div>
            <div class="stat-label">Medium Risk</div>
            <div class="stat-meta">Worth reviewing</div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span class="stat-value">{{ $stats['low'] }}</span>
            </div>
            <div class="stat-label">Low Risk</div>
            <div class="stat-meta">Informational</div>
        </div>
    </section>

    {{-- AI system status --}}
    <section class="stats-grid stats-grid-4">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span class="stat-value" data-ai="today">{{ $stats['today'] }}</span>
            </div>
            <div class="stat-label">Today's Alerts</div>
            <div class="stat-meta">{{ now()->format('l, M j') }}</div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 1 7 7c0 3-2 5-2 5H7s-2-2-2-5a7 7 0 0 1 7-7z"/><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/></svg>
                </span>
                <span class="stat-value">Online</span>
            </div>
            <div class="stat-label">AI System Status</div>
            <div class="stat-meta" data-ai="last-sweep">Last sweep: {{ $lastSweep ? \Illuminate\Support\Carbon::parse($lastSweep)->diffForHumans() : 'never' }}</div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                <span class="stat-value">{{ $stats['accuracy'] }}%</span>
            </div>
            <div class="stat-label">AI Accuracy</div>
            <div class="stat-meta">Reviewed alerts confirmed genuine</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <span class="stat-value"><span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Live</span></span>
            </div>
            <div class="stat-label">Live Monitoring</div>
            <div class="stat-meta">Feed refreshes every 15 seconds</div>
        </div>
    </section>

    {{-- Live feed + charts --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Live Event Monitoring</h2>
            <div data-ai="feed">
                <p class="muted">Connecting to the AI monitoring feed…</p>
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">AI Alerts by Day — Last 7 Days</h2>
            @include('reports._bars', ['data' => $daily])
        </div>
    </section>

    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Risk Level Distribution (7 days)</h2>
            @foreach ($riskDist as $row)
                <div class="top-row">
                    <span class="top-label"><span class="badge {{ $row['badge'] }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $row['label'] }}</span></span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="panel">
            <h2 class="panel-title">Top Detected Events (7 days)</h2>
            @forelse ($topEvents as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No AI alerts this week — run a scan to analyze recent events.</p>
            @endforelse
        </div>
    </section>

    {{-- Latest AI alerts --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alert ID</th>
                        <th>Event</th>
                        <th>Risk</th>
                        <th>Recommendation</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $alert)
                        <tr>
                            <td class="mono">{{ $alert->ai_code }}</td>
                            <td>
                                {{ $alert->event_type }}
                                <div class="cell-sub">{{ \Illuminate\Support\Str::limit($alert->description, 60) }}</div>
                            </td>
                            <td><span class="badge {{ $alert->risk_level->badge() }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $alert->risk_level->label() }} ({{ $alert->risk_score }})</span></td>
                            <td>{{ $alert->recommendation->label() }}</td>
                            <td>{{ $alert->locationLabel() }}</td>
                            <td><span class="badge {{ $alert->status->badge() }}">{{ $alert->status->label() }}</span></td>
                            <td><span title="{{ $alert->happened_at->format('Y-m-d H:i') }}">{{ $alert->happened_at->diffForHumans() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No AI alerts yet — run a scan to analyze recent events.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <a href="{{ route('ai.alerts') }}" class="btn btn-secondary">Manage all AI alerts</a>
            <a href="{{ route('ai.history') }}" class="btn btn-ghost">Alert history</a>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    (function () {
        const feed = document.querySelector('[data-ai="feed"]');
        const today = document.querySelector('[data-ai="today"]');
        const sweep = document.querySelector('[data-ai="last-sweep"]');
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        let lastTopId = null;

        const refresh = async () => {
            try {
                const res = await fetch('{{ route('ai.feed') }}', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                if (today) today.textContent = data.todayCount;
                if (sweep && data.lastSweep) sweep.textContent = 'Last sweep: ' + esc(data.lastSweep);

                const isNew = data.items.length && lastTopId !== null && data.items[0].id !== lastTopId;
                if (data.items.length) lastTopId = data.items[0].id;

                feed.innerHTML = data.items.map((n, i) => `
                    <div class="feed-item ${i === 0 && isNew ? 'feed-item-new' : ''}">
                        <span class="avatar avatar-md" aria-hidden="true">${esc(n.icon)}</span>
                        <div class="feed-body">
                            <span class="feed-name">${esc(n.text)}</span>
                            <span class="feed-detail">${esc(n.time)}</span>
                        </div>
                        <span class="badge ${esc(n.badge)}"><span class="badge-indicator" aria-hidden="true"></span>${esc(n.label)}</span>
                    </div>`).join('') || '<p class="muted">No recent events.</p>';
            } catch (_) { /* keep current list */ }
        };

        refresh();
        // Poll only while the tab is visible.
        setInterval(() => { if (!document.hidden) refresh(); }, 15000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    })();
</script>
@endpush
