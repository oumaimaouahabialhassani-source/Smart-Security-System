@extends('layouts.app')

@section('title', 'Alerts & Notifications — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Alerts & Notifications</h1>
            <p class="page-subtitle">Security Operations Center — monitor, triage and resolve every incident.</p>
        </div>
        <div class="page-head-actions">
            @if ($role->canManageAlerts())
                <button type="button" class="btn btn-secondary js-confirm"
                        data-action="{{ route('alerts.acknowledge-all') }}"
                        data-title="Acknowledge All" data-text="Mark every NEW alert as pending?"
                        data-name="All new alerts" data-btn="Acknowledge">
                    Acknowledge All
                </button>
            @endif
            @if ($role->canManageAlerts())
                <a href="{{ route('alerts.export', request()->query()) }}" class="btn btn-secondary" title="Export the filtered alerts (opens in Excel)">Export CSV</a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print report (or save as PDF)">Print</button>
            <a href="{{ route('alerts.index') }}" class="btn btn-secondary" title="Refresh data">⟳ Refresh</a>
        </div>
    </div>

    {{-- Stat cards --}}
    <section class="stats-grid">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value" data-count="{{ $stat['value'] }}">{{ $stat['value'] }}</div>
                <div class="stat-meta">
                    {{ $stat['meta'] }}
                    @if ($stat['delta'] !== null)
                        <span class="stat-delta {{ $stat['delta'] >= 0 ? 'delta-down' : 'delta-up' }}">
                            {{ $stat['delta'] >= 0 ? '▲' : '▼' }} {{ abs($stat['delta']) }}% vs yesterday
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    {{-- Charts row 1 --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Alerts by Hour — Today</h2>
            <div class="bar-chart" role="img" aria-label="Alerts per two-hour slot today">
                @foreach ($hourly as $slot)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ round($slot['count'] / $maxHourly * 100) }}%"><span class="bar-value">{{ $slot['count'] }}</span></div>
                        <span class="bar-label">{{ $slot['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Alerts by Day — Last 7 Days</h2>
            <div class="bar-chart" role="img" aria-label="Alerts per day, last seven days">
                @foreach ($daily as $day)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ round($day['count'] / $maxDaily * 100) }}%"><span class="bar-value">{{ $day['count'] }}</span></div>
                        <span class="bar-label">{{ $day['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Charts row 2 --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Severity Distribution (7 days)</h2>
            @foreach ($severityDist as $row)
                <div class="top-row">
                    <span class="top-label"><span class="badge {{ $row['badge'] }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $row['label'] }}</span></span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="panel">
            <h2 class="panel-title">Resolved vs Pending</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $resolvedRate['percent'] }}%" role="img"
                     aria-label="{{ $resolvedRate['resolved'] }} of {{ $resolvedRate['total'] }} alerts resolved">
                    <span class="donut-center">{{ $resolvedRate['percent'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Resolved — {{ $resolvedRate['resolved'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Open — {{ $resolvedRate['open'] }}</li>
                </ul>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Top Alert Types (7 days)</h2>
            @forelse ($topTypes as $row)
                <div class="top-row">
                    <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No alerts this week.</p>
            @endforelse
            @if ($topDevices->isNotEmpty())
                <h3 class="form-section-title">Top Devices Triggering Alerts</h3>
                @foreach ($topDevices as $row)
                    <div class="top-row">
                        <span class="top-label" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                        <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                        <span class="top-count mono">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- Interactive facility map + AI insights --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Facility Map — Live Status</h2>
            <div class="map-wrap">
                <svg viewBox="0 0 640 310" class="facility-map" role="img" aria-label="Facility map with door and camera status">
                    <rect x="2" y="2" width="636" height="306" rx="12" class="map-outline"/>
                    @foreach ($map['floors'] as $floorName => $y)
                        <rect x="14" y="{{ $y }}" width="612" height="90" rx="8" class="map-floor"/>
                        <text x="26" y="{{ $y + 22 }}" class="map-floor-label">{{ $floorName }}</text>
                    @endforeach
                    @foreach ($map['doors'] as $door)
                        <g>
                            <title>{{ $door['name'] }} — {{ $door['status'] }}</title>
                            <rect x="{{ $door['x'] }}" y="{{ $door['y'] + 34 }}" width="26" height="34" rx="4" fill="{{ $door['tone'] }}" opacity="0.85"/>
                            <text x="{{ $door['x'] + 13 }}" y="{{ $door['y'] + 82 }}" class="map-marker-label">{{ \Illuminate\Support\Str::limit($door['name'], 12, '') }}</text>
                            @if ($door['offline'])
                                <circle cx="{{ $door['x'] + 13 }}" cy="{{ $door['y'] + 51 }}" r="20" class="map-alert-ring"/>
                            @endif
                        </g>
                    @endforeach
                    @foreach ($map['cameras'] as $camera)
                        <g>
                            <title>{{ $camera['name'] }} — {{ $camera['status'] }}</title>
                            <circle cx="{{ $camera['cx'] }}" cy="{{ $camera['cy'] }}" r="7" fill="{{ $camera['tone'] }}" opacity="0.9"/>
                            <circle cx="{{ $camera['cx'] }}" cy="{{ $camera['cy'] }}" r="3" class="map-cam-lens"/>
                            @if ($camera['offline'])
                                <circle cx="{{ $camera['cx'] }}" cy="{{ $camera['cy'] }}" r="14" class="map-alert-ring"/>
                            @endif
                        </g>
                    @endforeach
                </svg>
                <ul class="map-legend">
                    <li><span class="dot" style="background: var(--green)"></span> Normal</li>
                    <li><span class="dot" style="background: var(--orange)"></span> Attention</li>
                    <li><span class="dot" style="background: var(--red)"></span> Alert / Offline</li>
                    <li><span class="map-key-door"></span> Door</li>
                    <li><span class="dot map-key-cam"></span> Camera</li>
                </ul>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">AI Security Insights</h2>
            @forelse ($insights as $insight)
                <div class="insight-card">
                    <div class="insight-head">
                        <span class="insight-title">{{ $insight['title'] }}</span>
                        <span class="badge badge-warning"><span class="badge-indicator" aria-hidden="true"></span>{{ $insight['confidence'] }}% confidence</span>
                    </div>
                    <p class="insight-detail">{{ $insight['detail'] }}</p>
                    <p class="insight-action">→ {{ $insight['action'] }}</p>
                </div>
            @empty
                <p class="muted">No anomalies detected in the last 24 hours.</p>
            @endforelse
            <p class="muted tab-note">Insights are derived from live data; confidence scores are simulated until the AI engine is connected.</p>
        </div>
    </section>

    {{-- Filters --}}
    <form method="GET" action="{{ route('alerts.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Alert ID, type or description…">
        </div>
        <div class="filter-field">
            <label for="type">Alert Type</label>
            <select id="type" name="type">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="severity">Severity</label>
            <select id="severity" name="severity">
                <option value="">All severities</option>
                @foreach ($severities as $severity)
                    <option value="{{ $severity->value }}" @selected(request('severity') === $severity->value)>{{ $severity->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="{{ request('from') }}">
        </div>
        <div class="filter-field">
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="{{ request('to') }}">
        </div>
        <div class="filter-field">
            <label for="assigned">Assigned To</label>
            <select id="assigned" name="assigned">
                <option value="">Anyone</option>
                @foreach ($officers as $officer)
                    <option value="{{ $officer->id }}" @selected(request('assigned') == $officer->id)>{{ $officer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Search</button>
            @if (request()->hasAny(['search', 'type', 'severity', 'status', 'from', 'to', 'building', 'assigned']))
                <a href="{{ route('alerts.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Alerts table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alert ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Location</th>
                        <th>Person</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td class="mono">{{ $alert->alert_code }}</td>
                            <td>{{ $alert->happened_at->format('M j, Y') }}</td>
                            <td>{{ $alert->happened_at->format('H:i:s') }}</td>
                            <td>{{ $alert->type }}</td>
                            <td><x-status-badge :status="$alert->severity" /></td>
                            <td>{{ $alert->locationLabel() }}</td>
                            <td>{{ $alert->user?->name ?? $alert->visit?->full_name ?? '—' }}</td>
                            <td title="{{ $alert->description }}">{{ \Illuminate\Support\Str::limit($alert->description, 34) }}</td>
                            <td><x-status-badge :status="$alert->status" /></td>
                            <td>{{ $alert->assignee?->name ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="action-btn js-view" title="View & Manage"
                                            data-code="{{ $alert->alert_code }}"
                                            data-when="{{ $alert->happened_at->format('M j, Y — H:i:s') }}"
                                            data-type="{{ $alert->type }}"
                                            data-severity="{{ $alert->severity->label() }}"
                                            data-severity-badge="{{ $alert->severity->badge() }}"
                                            data-location="{{ $alert->locationLabel() }} ({{ $alert->building ?? '—' }}, {{ $alert->floor ?? '—' }})"
                                            data-device="{{ $alert->device?->name ?? '—' }}"
                                            data-camera="{{ $alert->camera?->name ?? '—' }}"
                                            data-person="{{ $alert->user?->name ?? $alert->visit?->full_name ?? '—' }}"
                                            data-confidence="{{ $alert->ai_confidence !== null ? $alert->ai_confidence.'%' : '—' }}"
                                            data-description="{{ $alert->description }}"
                                            data-status="{{ $alert->status->value }}"
                                            data-assigned="{{ $alert->assigned_to }}"
                                            data-notes="{{ $alert->notes }}"
                                            data-action="{{ route('alerts.update', $alert) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    @if ($role->canManageAlerts() && $alert->status->isOpen())
                                        <button type="button" class="action-btn js-confirm" title="Resolve"
                                                data-action="{{ route('alerts.resolve', $alert) }}"
                                                data-title="Resolve Alert" data-text="Mark this alert as resolved?"
                                                data-name="{{ $alert->alert_code }} — {{ $alert->type }}" data-btn="Resolve">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                                        </button>
                                    @endif
                                    @if ($role === App\Enums\UserRole::Administrator)
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                data-action="{{ route('alerts.destroy', $alert) }}"
                                                data-name="{{ $alert->alert_code }} — {{ $alert->type }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="table-empty">No alerts match your filters — all clear. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($alerts->hasPages())
            <div class="table-footer">{{ $alerts->links('pagination.app') }}</div>
        @endif
    </section>

    {{-- Notification center + timeline --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Real-Time Notification Center <span class="live-dot" aria-hidden="true"></span></h2>
            <div id="notification-feed"><p class="muted">Loading notifications…</p></div>
            <p class="muted tab-note">Alerts, access attempts and device events — refreshes every 15 seconds.</p>
        </div>

        <div class="panel">
            <h2 class="panel-title">Recent Activity Timeline</h2>
            <ol class="timeline">
                @forelse ($timeline as $incident)
                    <li class="timeline-item">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-body">
                            <span class="timeline-event">{{ $incident->detail }}</span>
                            <span class="timeline-detail">{{ $incident->door?->name ?? 'Facility' }} — {{ $incident->person_name }}</span>
                            <span class="timeline-time" title="{{ $incident->happened_at->format('Y-m-d H:i:s') }}">{{ $incident->happened_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <p class="muted">No recent security activity.</p>
                @endforelse
            </ol>
        </div>
    </section>

    {{-- System health + notification settings --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">System Health</h2>
            <div class="health-grid">
                @foreach ($health as $label => $item)
                    <div class="health-item">
                        <span class="health-dot {{ $item['ok'] ? 'health-ok' : 'health-bad' }}" aria-hidden="true"></span>
                        <div>
                            <span class="health-label">{{ $label }}</span>
                            <span class="health-value">{{ $item['value'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Notification Settings</h2>
            <form method="POST" action="{{ route('alerts.preferences') }}" data-loading>
                @csrf
                <div class="check-grid">
                    @foreach ([
                        'email' => 'Email Notifications',
                        'sms' => 'SMS Notifications',
                        'push' => 'Push Notifications',
                        'desktop' => 'Desktop Notifications',
                        'sound' => 'Sound Alerts',
                        'critical_only' => 'Critical Alerts Only',
                        'real_time' => 'Real-Time Alerts',
                        'daily_summary' => 'Daily Summary',
                        'weekly_report' => 'Weekly Report',
                    ] as $key => $label)
                        <label class="check-option">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked($preferences[$key] ?? in_array($key, ['email', 'real_time']))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Preferences</button>
                </div>
            </form>
            <p class="muted tab-note">Delivery channels (email/SMS/push) are stored per user and will be honored once the Notifications dispatcher is connected.</p>
        </div>
    </section>

    @if ($role === App\Enums\UserRole::Administrator)
        <x-delete-modal title="Delete Alert" message="Are you sure you want to delete this alert?" />
    @endif

    {{-- Confirm modal --}}
    <div class="modal-backdrop" id="confirm-modal" hidden>
        <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title">
            <h3 class="modal-title" id="confirm-modal-title">Confirm</h3>
            <p class="modal-text" id="confirm-modal-text"></p>
            <p class="modal-target" id="confirm-modal-name"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-confirm>Cancel</button>
                <form method="POST" id="confirm-modal-form" data-loading>
                    @csrf
                    <button type="submit" class="btn btn-primary" id="confirm-modal-btn" data-loading-text="Processing…">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Alert details & management modal --}}
    <div class="modal-backdrop" id="view-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="view-modal-title">
            <h3 class="modal-title" id="view-modal-title">Alert Details — <span class="mono" id="v-code"></span></h3>

            <div class="cam-box cam-box-snapshot">
                <span class="cam-status">📷 Camera snapshot / recorded video placeholder — <span id="v-camera"></span></span>
            </div>

            <dl class="profile-grid">
                <div class="profile-item"><dt>Date & Time</dt><dd id="v-when"></dd></div>
                <div class="profile-item"><dt>Type</dt><dd id="v-type"></dd></div>
                <div class="profile-item"><dt>Severity</dt><dd><span class="badge" id="v-severity"><span class="badge-indicator" aria-hidden="true"></span><span id="v-severity-text"></span></span></dd></div>
                <div class="profile-item"><dt>Location</dt><dd id="v-location"></dd></div>
                <div class="profile-item"><dt>Device</dt><dd id="v-device"></dd></div>
                <div class="profile-item"><dt>Related Person</dt><dd id="v-person"></dd></div>
                <div class="profile-item"><dt>AI Confidence</dt><dd class="mono" id="v-confidence"></dd></div>
                <div class="profile-item profile-item-wide"><dt>Description</dt><dd id="v-description"></dd></div>
            </dl>

            @if ($role->canManageAlerts())
                <form method="POST" id="view-form" data-loading>
                    @csrf
                    @method('PATCH')
                    <h3 class="form-section-title">Manage Alert</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="v-status-input">Status</label>
                            <select id="v-status-input" name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="v-assign-input">Assign to Security Officer</label>
                            <select id="v-assign-input" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($officers as $officer)
                                    <option value="{{ $officer->id }}">{{ $officer->name }} — {{ $officer->role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field form-field-full">
                            <label for="v-notes-input">Notes</label>
                            <textarea id="v-notes-input" name="notes" rows="3" maxlength="1000" placeholder="Investigation notes…"></textarea>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" data-close-view>Close</button>
                        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                    </div>
                </form>
            @else
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" data-close-view>Close</button>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (() => {
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

        // Confirm modal
        const confirmModal = document.getElementById('confirm-modal');
        const confirmForm = document.getElementById('confirm-modal-form');
        document.querySelectorAll('.js-confirm').forEach((btn) => {
            btn.addEventListener('click', () => {
                confirmForm.action = btn.dataset.action;
                document.getElementById('confirm-modal-title').textContent = btn.dataset.title;
                document.getElementById('confirm-modal-text').textContent = btn.dataset.text;
                document.getElementById('confirm-modal-name').textContent = btn.dataset.name;
                document.getElementById('confirm-modal-btn').textContent = btn.dataset.btn;
                confirmModal.hidden = false;
            });
        });
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal || e.target.closest('[data-close-confirm]')) confirmModal.hidden = true;
        });

        // Details & manage modal
        const viewModal = document.getElementById('view-modal');
        const viewForm = document.getElementById('view-form');
        const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        document.querySelectorAll('.js-view').forEach((btn) => {
            btn.addEventListener('click', () => {
                set('v-code', btn.dataset.code);
                set('v-when', btn.dataset.when);
                set('v-type', btn.dataset.type);
                set('v-severity-text', btn.dataset.severity);
                document.getElementById('v-severity').className = 'badge ' + btn.dataset.severityBadge;
                set('v-location', btn.dataset.location);
                set('v-device', btn.dataset.device);
                set('v-camera', btn.dataset.camera);
                set('v-person', btn.dataset.person);
                set('v-confidence', btn.dataset.confidence);
                set('v-description', btn.dataset.description);
                if (viewForm) {
                    viewForm.action = btn.dataset.action;
                    document.getElementById('v-status-input').value = btn.dataset.status;
                    document.getElementById('v-assign-input').value = btn.dataset.assigned || '';
                    document.getElementById('v-notes-input').value = btn.dataset.notes || '';
                }
                viewModal.hidden = false;
            });
        });
        viewModal.addEventListener('click', (e) => {
            if (e.target === viewModal || e.target.closest('[data-close-view]')) viewModal.hidden = true;
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { confirmModal.hidden = true; viewModal.hidden = true; }
        });

        // Real-time notification center + top-bar bell
        const feedEl = document.getElementById('notification-feed');
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        const refresh = async () => {
            try {
                const res = await fetch('{{ route('alerts.feed') }}', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                feedEl.innerHTML = data.items.map((n) => `
                    <div class="feed-item">
                        <span class="avatar avatar-md" aria-hidden="true">${esc(n.icon)}</span>
                        <div class="feed-body">
                            <span class="feed-name">${esc(n.text)}</span>
                            <span class="feed-detail">${esc(n.time)}</span>
                        </div>
                        <span class="badge ${esc(n.badge)}"><span class="badge-indicator" aria-hidden="true"></span>${esc(n.label)}</span>
                    </div>`).join('') || '<p class="muted">No notifications.</p>';
            } catch (_) { /* keep current list */ }
        };
        refresh();
        setInterval(refresh, 15000);
    })();
</script>
@endpush
