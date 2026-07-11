@extends('layouts.app')

@section('title', 'Audit Logs — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Audit Logs</h1>
            <p class="page-subtitle">Every important action performed in the system, recorded automatically.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('audit.export', request()->query()) }}" class="btn btn-secondary" title="Export the filtered logs (opens in Excel)">Export CSV</a>
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print (or save as PDF)">Print</button>
            <a href="{{ route('audit.index') }}" class="btn btn-secondary" title="Refresh">⟳ Refresh</a>
        </div>
    </div>

    {{-- Stat cards --}}
    <section class="stats-grid">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value" @if (is_int($stat['value'])) data-count="{{ $stat['value'] }}" @endif>{{ $stat['value'] }}</div>
                <div class="stat-meta">{{ $stat['meta'] }}</div>
            </div>
        @endforeach
    </section>

    {{-- Charts --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Activities per Day (7 days)</h2>
            <div class="bar-chart bar-chart-short" role="img" aria-label="Audit entries per day">
                @foreach ($daily as $day)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ round($day['count'] / $maxDaily * 100) }}%"><span class="bar-value">{{ $day['count'] }}</span></div>
                        <span class="bar-label">{{ $day['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Success vs Failed (7 days)</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $successRate['percent'] }}%" role="img"
                     aria-label="{{ $successRate['success'] }} of {{ $successRate['total'] }} activities succeeded">
                    <span class="donut-center">{{ $successRate['percent'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Success — {{ $successRate['success'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Failed — {{ $successRate['failed'] }}</li>
                </ul>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Top Modules (7 days)</h2>
            @forelse ($byModule as $row)
                <div class="top-row">
                    <span class="top-label">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No activity this week.</p>
            @endforelse
            @if ($topUsers->isNotEmpty())
                <h3 class="form-section-title">Most Active Users</h3>
                @foreach ($topUsers as $row)
                    <div class="top-row">
                        <span class="top-label">{{ $row['label'] }}</span>
                        <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                        <span class="top-count mono">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- Filters --}}
    <form method="GET" action="{{ route('audit.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="User, action, description or IP…">
        </div>
        <div class="filter-field">
            <label for="user">User</label>
            <select id="user" name="user">
                <option value="">Anyone</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="module">Module</label>
            <select id="module" name="module">
                <option value="">All modules</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="action">Action</label>
            <select id="action" name="action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="success" @selected(request('status') === 'success')>Success</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </div>
        <div class="filter-field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
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
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'user', 'module', 'action', 'status', 'role', 'from', 'to', 'ip']))
                <a href="{{ route('audit.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Logs table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th class="th-actions">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="mono">#{{ $log->id }}</td>
                            <td>
                                <div class="user-cell">
                                    @if ($log->user)
                                        <x-user-avatar :user="$log->user" />
                                    @else
                                        <span class="avatar avatar-md" aria-hidden="true">{{ $log->user_name ? strtoupper(mb_substr($log->user_name, 0, 1)) : '⚙' }}</span>
                                    @endif
                                    <span class="user-cell-name">{{ $log->user_name ?? 'System' }}</span>
                                </div>
                            </td>
                            <td>{{ $log->module }}</td>
                            <td>{{ $log->action }}</td>
                            <td>
                                <span class="badge {{ $log->status === 'success' ? 'badge-success' : 'badge-danger' }}">
                                    <span class="badge-indicator" aria-hidden="true"></span>{{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                            <td>{{ $log->happened_at->format('M j, Y') }}</td>
                            <td>{{ $log->happened_at->format('H:i:s') }}</td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="action-btn js-details" title="View Details"
                                            data-id="#{{ $log->id }}"
                                            data-user="{{ $log->user_name ?? 'System' }}{{ $log->user_role ? ' — '.$log->user_role : '' }}"
                                            data-module="{{ $log->module }} · {{ $log->action }}"
                                            data-status="{{ ucfirst($log->status) }}"
                                            data-status-badge="{{ $log->status === 'success' ? 'badge-success' : 'badge-danger' }}"
                                            data-when="{{ $log->happened_at->format('M j, Y — H:i:s') }}"
                                            data-ip="{{ $log->ip_address ?? '—' }}"
                                            data-browser="{{ $log->browser ?? '—' }} · {{ $log->operating_system ?? '—' }} · {{ $log->device_type ?? '—' }}"
                                            data-request="{{ $log->http_method ?? '—' }} {{ $log->url ?? '' }}"
                                            data-description="{{ $log->description }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-empty">No audit entries match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="table-footer">{{ $logs->links('pagination.app') }}</div>
        @endif
    </section>

    {{-- Details modal --}}
    <div class="modal-backdrop" id="details-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="details-modal-title">
            <h3 class="modal-title" id="details-modal-title">Audit Entry <span class="mono" id="d-id"></span></h3>
            <dl class="profile-grid">
                <div class="profile-item"><dt>User</dt><dd id="d-user"></dd></div>
                <div class="profile-item"><dt>Module · Action</dt><dd id="d-module"></dd></div>
                <div class="profile-item"><dt>Status</dt><dd><span class="badge" id="d-status"><span class="badge-indicator" aria-hidden="true"></span><span id="d-status-text"></span></span></dd></div>
                <div class="profile-item"><dt>Timestamp</dt><dd id="d-when"></dd></div>
                <div class="profile-item"><dt>IP Address</dt><dd class="mono" id="d-ip"></dd></div>
                <div class="profile-item"><dt>Browser · OS · Device</dt><dd id="d-browser"></dd></div>
                <div class="profile-item profile-item-wide"><dt>Request</dt><dd class="mono" id="d-request"></dd></div>
                <div class="profile-item profile-item-wide"><dt>Description</dt><dd id="d-description"></dd></div>
            </dl>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-details>Close</button>
            </div>
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

        // Details modal
        const modal = document.getElementById('details-modal');
        const set = (id, text) => { document.getElementById(id).textContent = text; };
        document.querySelectorAll('.js-details').forEach((btn) => {
            btn.addEventListener('click', () => {
                set('d-id', btn.dataset.id);
                set('d-user', btn.dataset.user);
                set('d-module', btn.dataset.module);
                set('d-status-text', btn.dataset.status);
                document.getElementById('d-status').className = 'badge ' + btn.dataset.statusBadge;
                set('d-when', btn.dataset.when);
                set('d-ip', btn.dataset.ip);
                set('d-browser', btn.dataset.browser);
                set('d-request', btn.dataset.request);
                set('d-description', btn.dataset.description);
                modal.hidden = false;
            });
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-details]')) modal.hidden = true;
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.hidden = true; });
    })();
</script>
@endpush
