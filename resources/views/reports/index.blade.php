@extends('layouts.app')

@section('title', 'Reports & Analytics — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Reports & Analytics</h1>
            <p class="page-subtitle">Cross-module insights — {{ $from->format('M j, Y') }} → {{ $to->format('M j, Y') }}</p>
        </div>
        <div class="page-head-actions">
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print this report (or save as PDF)">Print Report</button>
        </div>
    </div>

    {{-- Global date range --}}
    <form method="GET" action="{{ route('reports.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field">
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="{{ request('from', $from->format('Y-m-d')) }}">
        </div>
        <div class="filter-field">
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="{{ request('to', $to->format('Y-m-d')) }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Range</button>
            @if (request()->hasAny(['from', 'to']))
                <a href="{{ route('reports.index') }}" class="btn btn-ghost">Last 30 days</a>
            @endif
        </div>
        <p class="muted filters-note">The range drives every time-based chart below. Fine-grained filters live in each module's own page.</p>
    </form>

    <section class="panel">
        <div class="tabs tabs-wrap" role="tablist" aria-label="Report sections">
            @foreach (['executive' => 'Executive', 'employees' => 'Employees', 'visitors' => 'Visitors', 'cameras' => 'Cameras', 'devices' => 'IoT Devices', 'biometrics' => 'Biometrics', 'access' => 'Access Control', 'alerts' => 'Alerts', 'audit' => 'Audit'] as $key => $label)
                <button type="button" class="tab" role="tab" data-tab="{{ $key }}" aria-selected="false">{{ $label }}</button>
            @endforeach
        </div>

        {{-- ===== Executive ===== --}}
        <div class="tab-panel" id="tab-executive" role="tabpanel" hidden>
            <div class="report-export">
                <a href="{{ route('reports.export', ['section' => 'executive'] + request()->query()) }}" class="btn btn-ghost">Export CSV</a>
            </div>
            <div class="stats-grid">
                @foreach ($executive as $label => $kpi)
                    <div class="stat-card">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="stat-value" data-count="{{ is_int($kpi['value']) ? $kpi['value'] : '' }}">{{ $kpi['value'] }}</div>
                        <div class="stat-meta">{{ $kpi['meta'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== Employees ===== --}}
        <div class="tab-panel" id="tab-employees" role="tabpanel" hidden>
            <div class="panels-grid panels-grid-3">
                <div class="report-block">
                    <h3 class="panel-title">Employees by Department</h3>
                    @forelse ($employees['byDepartment'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @empty
                        <p class="muted">No enrolled employees yet.</p>
                    @endforelse
                    <h3 class="form-section-title">By Position</h3>
                    @foreach ($employees['byPosition'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Active vs Inactive</h3>
                    <div class="donut-wrap">
                        <div class="donut" style="--online-percent: {{ $employees['activeRate']['percent'] }}%"><span class="donut-center">{{ $employees['activeRate']['percent'] }}%</span></div>
                        <ul class="donut-legend">
                            <li><span class="dot dot-online" aria-hidden="true"></span> Active — {{ $employees['activeRate']['part'] }}</li>
                            <li><span class="dot dot-offline" aria-hidden="true"></span> Other — {{ $employees['activeRate']['total'] - $employees['activeRate']['part'] }}</li>
                        </ul>
                    </div>
                    <h3 class="form-section-title">Biometric Enrollment</h3>
                    @php($enrollMax = max(array_column($employees['enrollment'], 'count')) ?: 1)
                    @foreach ($employees['enrollment'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ round($row['count'] / $enrollMax * 100) }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
                <div class="report-block">
                    <h3 class="panel-title">New Users Over Time</h3>
                    @include('reports._bars', ['data' => $employees['newOverTime']])
                </div>
            </div>
        </div>

        {{-- ===== Visitors ===== --}}
        <div class="tab-panel" id="tab-visitors" role="tabpanel" hidden>
            <div class="report-export">
                <a href="{{ route('reports.export', ['section' => 'visitors'] + request()->query()) }}" class="btn btn-ghost">Export CSV</a>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $visitors['total'] }}</span><span class="mini-stat-label">Visits</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $visitors['checkedIn'] }}</span><span class="mini-stat-label">Check-ins</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $visitors['checkedOut'] }}</span><span class="mini-stat-label">Check-outs</span></div>
            </div>
            <div class="panels-grid panels-grid-3">
                <div class="report-block report-block-wide">
                    <h3 class="panel-title">Visits Over the Period</h3>
                    @include('reports._bars', ['data' => $visitors['perDay']])
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Peak Visiting Hours</h3>
                    @include('reports._bars', ['data' => $visitors['peakHours']])
                </div>
            </div>
            <div class="panels-grid">
                <div class="report-block">
                    <h3 class="panel-title">Visitors by Company</h3>
                    @forelse ($visitors['byCompany'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @empty
                        <p class="muted">No company data in this period.</p>
                    @endforelse
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Most Visited Employees</h3>
                    @forelse ($visitors['topHosts'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @empty
                        <p class="muted">No hosted visits in this period.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Cameras ===== --}}
        <div class="tab-panel" id="tab-cameras" role="tabpanel" hidden>
            <div class="panels-grid panels-grid-3">
                <div class="report-block">
                    <h3 class="panel-title">Online vs Offline</h3>
                    <div class="donut-wrap">
                        <div class="donut" style="--online-percent: {{ $cameras['onlineRate']['percent'] }}%"><span class="donut-center">{{ $cameras['onlineRate']['percent'] }}%</span></div>
                        <ul class="donut-legend">
                            @foreach ($cameras['byStatus'] as $row)
                                <li><span class="dot {{ $row['label'] === 'Online' ? 'dot-online' : 'dot-offline' }}" aria-hidden="true"></span> {{ $row['label'] }} — {{ $row['count'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Recording & Health</h3>
                    <div class="top-row"><span class="top-label">Recording enabled</span><div class="progress"><div class="progress-fill" style="width: {{ $cameras['recording']['percent'] }}%"></div></div><span class="top-count mono">{{ $cameras['recording']['part'] }}/{{ $cameras['recording']['total'] }}</span></div>
                    <div class="top-row"><span class="top-label">In maintenance</span><div class="progress"><div class="progress-fill progress-warn" style="width: {{ $cameras['recording']['total'] > 0 ? round($cameras['health'] / $cameras['recording']['total'] * 100) : 0 }}%"></div></div><span class="top-count mono">{{ $cameras['health'] }}</span></div>
                    <h3 class="form-section-title">Storage Usage (estimate)</h3>
                    @php($storagePct = min(100, (int) round($cameras['storage']['used'] / max($cameras['storage']['max'], 1) * 100)))
                    <div class="top-row"><span class="top-label">{{ $cameras['storage']['used'] }} GB / {{ $cameras['storage']['max'] }} GB</span><div class="progress"><div class="progress-fill {{ $storagePct > 80 ? 'progress-warn' : '' }}" style="width: {{ $storagePct }}%"></div></div><span class="top-count mono">{{ $storagePct }}%</span></div>
                    <p class="muted tab-note">Estimated until the recording hardware (NVR) is connected. Cap set in Settings → Cameras.</p>
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Most Active Cameras</h3>
                    @forelse ($cameras['mostActive'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @empty
                        <p class="muted">No door cameras captured access events yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== IoT Devices ===== --}}
        <div class="tab-panel" id="tab-devices" role="tabpanel" hidden>
            <div class="panels-grid panels-grid-3">
                <div class="report-block">
                    <h3 class="panel-title">Online vs Offline</h3>
                    <div class="donut-wrap">
                        <div class="donut" style="--online-percent: {{ $devices['onlineRate']['percent'] }}%"><span class="donut-center">{{ $devices['onlineRate']['percent'] }}%</span></div>
                        <ul class="donut-legend">
                            <li><span class="dot dot-online" aria-hidden="true"></span> Online — {{ $devices['onlineRate']['part'] }}</li>
                            <li><span class="dot dot-offline" aria-hidden="true"></span> Other — {{ $devices['onlineRate']['total'] - $devices['onlineRate']['part'] }}</li>
                        </ul>
                    </div>
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Battery Status</h3>
                    @php($batMax = max(array_column($devices['battery'], 'count')) ?: 1)
                    @foreach ($devices['battery'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ round($row['count'] / $batMax * 100) }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                    <h3 class="form-section-title">Signal Strength</h3>
                    @php($sigMax = max(array_column($devices['signal'], 'count')) ?: 1)
                    @foreach ($devices['signal'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ round($row['count'] / $sigMax * 100) }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Device Activity</h3>
                    @php($actMax = max(array_column($devices['activity'], 'count')) ?: 1)
                    @foreach ($devices['activity'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ round($row['count'] / $actMax * 100) }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Biometrics ===== --}}
        <div class="tab-panel" id="tab-biometrics" role="tabpanel" hidden>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $biometrics['success'] }}</span><span class="mini-stat-label">Successful</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $biometrics['failed'] }}</span><span class="mini-stat-label">Failed</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $biometrics['successRate']['percent'] }}%</span><span class="mini-stat-label">Success rate</span></div>
            </div>
            <div class="panels-grid">
                <div class="report-block report-block-wide">
                    <h3 class="panel-title">Verifications Over the Period</h3>
                    @include('reports._bars', ['data' => $biometrics['perDay']])
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Success Rate</h3>
                    <div class="donut-wrap">
                        <div class="donut" style="--online-percent: {{ $biometrics['successRate']['percent'] }}%"><span class="donut-center">{{ $biometrics['successRate']['percent'] }}%</span></div>
                        <ul class="donut-legend">
                            <li><span class="dot dot-online" aria-hidden="true"></span> Success — {{ $biometrics['success'] }}</li>
                            <li><span class="dot dot-offline" aria-hidden="true"></span> Failed — {{ $biometrics['failed'] }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Access Control ===== --}}
        <div class="tab-panel" id="tab-access" role="tabpanel" hidden>
            <div class="report-export">
                <a href="{{ route('reports.export', ['section' => 'access'] + request()->query()) }}" class="btn btn-ghost">Export CSV</a>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $access['granted'] }}</span><span class="mini-stat-label">Granted</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $access['denied'] }}</span><span class="mini-stat-label">Denied</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $access['temporary'] }}</span><span class="mini-stat-label">Temp permissions</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $access['expired'] }}</span><span class="mini-stat-label">Expired permissions</span></div>
            </div>
            <div class="panels-grid">
                <div class="report-block">
                    <h3 class="panel-title">Peak Access Hours — Day × Time Heatmap</h3>
                    <div class="heatmap" role="img" aria-label="Access volume by day of week and two-hour slot">
                        <span class="heatmap-corner"></span>
                        @foreach (range(0, 11) as $slot)
                            <span class="heatmap-axis">{{ $slot * 2 }}h</span>
                        @endforeach
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d => $dayName)
                            <span class="heatmap-axis">{{ $dayName }}</span>
                            @foreach ($access['heatmap'][$d] as $count)
                                <span class="heatmap-cell" style="--heat: {{ round($count / $access['heatMax'], 2) }}" title="{{ $dayName }} — {{ $count }} events"></span>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Granted vs Denied</h3>
                    <div class="donut-wrap">
                        <div class="donut" style="--online-percent: {{ $access['grantRate']['percent'] }}%"><span class="donut-center">{{ $access['grantRate']['percent'] }}%</span></div>
                        <ul class="donut-legend">
                            <li><span class="dot dot-online" aria-hidden="true"></span> Granted — {{ $access['granted'] }}</li>
                            <li><span class="dot dot-offline" aria-hidden="true"></span> Denied — {{ $access['denied'] }}</li>
                        </ul>
                    </div>
                    <h3 class="form-section-title">Most Used Doors</h3>
                    @foreach ($access['topDoors'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Alerts ===== --}}
        <div class="tab-panel" id="tab-alerts" role="tabpanel" hidden>
            <div class="report-export">
                <a href="{{ route('reports.export', ['section' => 'alerts'] + request()->query()) }}" class="btn btn-ghost">Export CSV</a>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $alerts['critical'] }}</span><span class="mini-stat-label">Critical</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $alerts['resolvedRate']['percent'] }}%</span><span class="mini-stat-label">Resolved</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $alerts['avgResolution'] }}</span><span class="mini-stat-label">Avg resolution</span></div>
            </div>
            <div class="panels-grid">
                <div class="report-block">
                    <h3 class="panel-title">Alerts by Severity</h3>
                    @php($sevMax = max(array_column($alerts['bySeverity'], 'count')) ?: 1)
                    @foreach ($alerts['bySeverity'] as $row)
                        <div class="top-row">
                            <span class="top-label"><span class="badge {{ $row['badge'] }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $row['label'] }}</span></span>
                            <div class="progress"><div class="progress-fill" style="width: {{ round($row['count'] / $sevMax * 100) }}%"></div></div>
                            <span class="top-count mono">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Top Alert Types</h3>
                    @forelse ($alerts['byType'] as $row)
                        <div class="top-row"><span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @empty
                        <p class="muted">No alerts in this period. 🎉</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Audit ===== --}}
        <div class="tab-panel" id="tab-audit" role="tabpanel" hidden>
            <div class="report-export">
                <a href="{{ route('reports.export', ['section' => 'audit'] + request()->query()) }}" class="btn btn-ghost">Export CSV</a>
                <a href="{{ route('audit.index') }}" class="btn btn-ghost">Open Audit Logs →</a>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $audit['logins'] }}</span><span class="mini-stat-label">Logins</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $audit['failedLogins'] }}</span><span class="mini-stat-label">Failed logins</span></div>
            </div>
            <div class="panels-grid panels-grid-3">
                <div class="report-block report-block-wide">
                    <h3 class="panel-title">Activities Over the Period</h3>
                    @include('reports._bars', ['data' => $audit['perDay']])
                </div>
                <div class="report-block">
                    <h3 class="panel-title">Activities by Module</h3>
                    @foreach ($audit['byModule'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                    <h3 class="form-section-title">Most Active Users</h3>
                    @foreach ($audit['topUsers'] as $row)
                        <div class="top-row"><span class="top-label">{{ $row['label'] }}</span><div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div><span class="top-count mono">{{ $row['count'] }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    (() => {
        // Tabs with ?tab= deep link (shared pattern with Settings)
        const tabs = document.querySelectorAll('.tab');
        const activate = (name) => {
            tabs.forEach((t) => {
                const on = t.dataset.tab === name;
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('.tab-panel').forEach((p) => { p.hidden = p.id !== 'tab-' + name; });
        };
        tabs.forEach((t) => t.addEventListener('click', () => {
            activate(t.dataset.tab);
            const url = new URL(location.href);
            url.searchParams.set('tab', t.dataset.tab);
            history.replaceState(null, '', url);
        }));
        activate(new URLSearchParams(location.search).get('tab') || 'executive');

        // Animated counters
        document.querySelectorAll('.stat-value[data-count]').forEach((el) => {
            const target = parseInt(el.dataset.count, 10);
            if (!Number.isFinite(target) || target === 0) return;
            const startAt = performance.now();
            const tick = (now) => {
                const p = Math.min((now - startAt) / 700, 1);
                el.textContent = Math.round(target * p);
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    })();
</script>
@endpush
