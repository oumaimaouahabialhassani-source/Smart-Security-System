@extends('layouts.app')

@section('title', 'AI Analytics — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">AI Analytics Dashboard</h1>
            <p class="page-subtitle">Historical security analysis, health statistics and predictive insights — last 30 days unless noted.</p>
        </div>
        <div class="row-actions">
            <a href="{{ route('ai.dashboard') }}" class="btn btn-secondary">AI Security Bot</a>
            <a href="{{ route('ai.report') }}" class="btn btn-ghost" target="_blank" rel="noopener">Print Report</a>
        </div>
    </div>

    {{-- Security overview --}}
    <section class="stats-grid stats-grid-4">
        <div class="stat-card {{ $overview['scoreTone'] }}">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </span>
                <span class="stat-value">{{ $overview['score'] }}<span class="muted" style="font-size:15px">/100</span></span>
            </div>
            <div class="stat-label">Security Score</div>
            <div class="stat-meta">Live composite of open threats &amp; coverage</div>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                <span class="stat-value">{{ $overview['alerts30d'] }}</span>
            </div>
            <div class="stat-label">AI Alerts (30 days)</div>
            <div class="stat-meta">{{ $overview['openCritical'] }} critical + {{ $overview['openHigh'] }} high still open</div>
        </div>
        <div class="stat-card {{ $overview['unauthorized24h'] > 0 ? 'stat-warning' : 'stat-success' }}">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <span class="stat-value">{{ $overview['unauthorized24h'] }}</span>
            </div>
            <div class="stat-label">Unauthorized Attempts</div>
            <div class="stat-meta">Last 24 hours</div>
        </div>
        <div class="stat-card {{ $forecast['trend'] === 'rising' ? 'stat-danger' : ($forecast['trend'] === 'falling' ? 'stat-success' : '') }}">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span class="stat-value" style="text-transform:capitalize">{{ $forecast['trend'] }}</span>
            </div>
            <div class="stat-label">Threat Prediction</div>
            <div class="stat-meta">Next 7 days — forecast risk: <span class="badge {{ $forecast['risk']->badge() }}">{{ $forecast['risk']->label() }}</span></div>
        </div>
    </section>

    {{-- AI Insights --}}
    <section class="panel">
        <h2 class="panel-title">AI Insights &amp; Recommendations</h2>
        @forelse ($insights as $insight)
            <div class="feed-item">
                <span class="avatar avatar-md" aria-hidden="true">✦</span>
                <div class="feed-body">
                    <span class="feed-name">{{ $insight['title'] }} <span class="badge {{ $insight['tone'] }}">{{ $insight['confidence'] }}% confidence</span></span>
                    <span class="feed-detail">{{ $insight['detail'] }}</span>
                    <span class="feed-detail"><strong>Recommendation:</strong> {{ $insight['recommendation'] }}</span>
                </div>
            </div>
        @empty
            <p class="muted">Not enough historical data yet — insights appear as the AI accumulates events.</p>
        @endforelse
    </section>

    {{-- Trends --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Daily Security Events (14 days)</h2>
            @include('reports._bars', ['data' => $daily])
        </div>
        <div class="panel">
            <h2 class="panel-title">Weekly Trend (8 weeks)</h2>
            @include('reports._bars', ['data' => $weekly])
        </div>
        <div class="panel">
            <h2 class="panel-title">Monthly Trend (6 months)</h2>
            @include('reports._bars', ['data' => $monthly])
        </div>
    </section>

    {{-- Peak hours + forecast --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Peak Activity Hours</h2>
            @include('reports._bars', ['data' => $peakActivity])
        </div>
        <div class="panel">
            <h2 class="panel-title">Peak Alert Hours</h2>
            @include('reports._bars', ['data' => $peakAlerts])
        </div>
        <div class="panel">
            <h2 class="panel-title">Risk Forecast — Next 7 Days</h2>
            @include('reports._bars', ['data' => $forecast['days']])
            <p class="muted" style="font-size:12.5px">Least-squares projection of the last 14 days of AI alerts.</p>
        </div>
    </section>

    {{-- Zones & cameras --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Top Risk Zones</h2>
            @forelse ($riskZones['riskiest'] as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono" title="{{ $row['meta'] }}">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No zone data yet.</p>
            @endforelse
            <p class="muted" style="font-size:12.5px">Average AI risk score per zone (/100).</p>
        </div>
        <div class="panel">
            <h2 class="panel-title">Most Dangerous Areas</h2>
            @forelse ($riskZones['dangerous'] as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No critical or high alerts this month.</p>
            @endforelse
            <p class="muted" style="font-size:12.5px">Critical + high alerts per area.</p>
        </div>
        <div class="panel">
            <h2 class="panel-title">Most Active Cameras</h2>
            @forelse ($activeCameras as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No camera-linked alerts this month.</p>
            @endforelse
        </div>
    </section>

    {{-- People + alert types --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Most Frequent Alert Types</h2>
            @foreach ($alertTypes as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="panel">
            <h2 class="panel-title">Top Employees Triggering Alerts</h2>
            @forelse ($topEmployees as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No employee-linked alerts this month.</p>
            @endforelse
        </div>
        <div class="panel">
            <h2 class="panel-title">Top Visitors Triggering Alerts</h2>
            @forelse ($topVisitors as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No visitor-linked alerts this month.</p>
            @endforelse
        </div>
    </section>

    {{-- Health + access + model quality --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Camera &amp; Device Health</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $cameraHealth['uptime'] }}%" role="img" aria-label="{{ $cameraHealth['online'] }} of {{ $cameraHealth['total'] }} cameras online">
                    <span class="donut-center">{{ $cameraHealth['uptime'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Cameras online — {{ $cameraHealth['online'] }} / {{ $cameraHealth['total'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Offline — {{ $cameraHealth['offline'] }} · Maintenance — {{ $cameraHealth['maintenance'] }}</li>
                    <li><span class="dot dot-online" aria-hidden="true"></span> Devices online — {{ $deviceHealth['online'] }} / {{ $deviceHealth['total'] }} ({{ $deviceHealth['uptime'] }}%)</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Devices offline — {{ $deviceHealth['offline'] }} · Low battery — {{ $deviceHealth['lowBattery'] }}</li>
                </ul>
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Access Attempts (30 days)</h2>
            <div class="top-row">
                <span class="top-label">Granted</span>
                <div class="progress"><div class="progress-fill" style="width: {{ $access['grantRate'] }}%"></div></div>
                <span class="top-count mono">{{ $access['granted'] }}</span>
            </div>
            <div class="top-row">
                <span class="top-label"><span class="badge badge-danger">Unauthorized</span></span>
                <div class="progress"><div class="progress-fill" style="width: {{ 100 - $access['grantRate'] }}%"></div></div>
                <span class="top-count mono">{{ $access['unauthorized'] }}</span>
            </div>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Total attempts</dt><dd>{{ $access['total'] }}</dd></div>
                <div class="profile-item"><dt>Grant rate</dt><dd>{{ $access['grantRate'] }}%</dd></div>
                <div class="profile-item"><dt>Unknown faces</dt><dd>{{ $access['unknownFaces'] }}</dd></div>
                <div class="profile-item"><dt>Motion detections</dt><dd>{{ $access['motion'] }}</dd></div>
            </dl>
        </div>
        <div class="panel">
            <h2 class="panel-title">AI Model Quality</h2>
            <div class="top-row">
                <span class="top-label"><span class="badge badge-success">Accuracy</span></span>
                <div class="progress"><div class="progress-fill" style="width: {{ $quality['accuracy'] }}%"></div></div>
                <span class="top-count mono">{{ $quality['accuracy'] }}%</span>
            </div>
            <div class="top-row">
                <span class="top-label"><span class="badge badge-success">True Positives</span></span>
                <div class="progress"><div class="progress-fill" style="width: {{ $quality['truePositiveRate'] }}%"></div></div>
                <span class="top-count mono">{{ $quality['truePositiveRate'] }}%</span>
            </div>
            <div class="top-row">
                <span class="top-label"><span class="badge badge-warning">False Positives</span></span>
                <div class="progress"><div class="progress-fill" style="width: {{ $quality['falsePositiveRate'] }}%"></div></div>
                <span class="top-count mono">{{ $quality['falsePositiveRate'] }}%</span>
            </div>
            <p class="muted" style="font-size:12.5px">Based on {{ $quality['reviewed'] }} human-reviewed alerts.</p>

            <h3 class="form-section-title">Door Activity (30 days)</h3>
            @foreach ($doorActivity as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Incident timeline --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Incident</th>
                        <th>Risk</th>
                        <th>Location</th>
                        <th>Person</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeline as $alert)
                        <tr>
                            <td>
                                <span class="mono">{{ $alert->ai_code }}</span> — {{ $alert->event_type }}
                                <div class="cell-sub">{{ \Illuminate\Support\Str::limit($alert->description, 70) }}</div>
                            </td>
                            <td><span class="badge {{ $alert->risk_level->badge() }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $alert->risk_level->label() }}</span></td>
                            <td>{{ $alert->locationLabel() }}</td>
                            <td>{{ $alert->personLabel() ?? '—' }}</td>
                            <td><span title="{{ $alert->happened_at->format('Y-m-d H:i:s') }}">{{ $alert->happened_at->diffForHumans() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table-empty">No critical or high incidents recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span class="muted">Incident timeline — latest critical &amp; high risk events.</span>
        </div>
    </section>

@endsection
