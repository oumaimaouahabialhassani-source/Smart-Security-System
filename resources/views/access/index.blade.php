@extends('layouts.app')

@section('title', 'Access Control — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Access Control</h1>
            <p class="page-subtitle">Permissions, doors, live activity and security incidents across the facility.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('access.logs') }}" class="btn btn-secondary">Access Logs</a>
            @if ($role->canManageAccess())
                <a href="{{ route('access.permissions.export') }}" class="btn btn-secondary" title="Export permissions (opens in Excel)">Export CSV</a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print (or save as PDF)">Print</button>
            <a href="{{ route('access.index') }}" class="btn btn-secondary" title="Refresh data">⟳ Refresh</a>
            @if ($role->canManageAccess())
                <a href="{{ route('access.permissions.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Permission
                </a>
            @endif
            @if ($role->canAdministerAccess())
                <button type="button" class="btn btn-danger js-confirm"
                        data-action="{{ route('access.lock-all') }}"
                        data-title="Emergency Lockdown"
                        data-text="Lock ALL doors immediately? This is logged as a critical security event."
                        data-name="Every door in the facility" data-btn="Lock All Doors">
                    Lock All Doors
                </button>
            @endif
        </div>
    </div>

    {{-- Stat cards with delta vs yesterday --}}
    <section class="stats-grid stats-grid-4">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value" data-count="{{ $stat['value'] }}">{{ $stat['value'] }}</div>
                <div class="stat-meta">
                    {{ $stat['meta'] }}
                    @if ($stat['delta'] !== null)
                        <span class="stat-delta {{ $stat['delta'] >= 0 ? 'delta-up' : 'delta-down' }}">
                            {{ $stat['delta'] >= 0 ? '▲' : '▼' }} {{ abs($stat['delta']) }}% vs yesterday
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    {{-- Charts row --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Access Attempts by Hour — Today</h2>
            <div class="bar-chart" role="img" aria-label="Bar chart of access attempts per two-hour slot">
                @foreach ($hourly as $slot)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ round($slot['count'] / $maxHourly * 100) }}%">
                            <span class="bar-value">{{ $slot['count'] }}</span>
                        </div>
                        <span class="bar-label">{{ $slot['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Granted vs Denied (7 days)</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $grantRate['percent'] }}%" role="img"
                     aria-label="{{ $grantRate['granted'] }} of {{ $grantRate['total'] }} attempts granted">
                    <span class="donut-center">{{ $grantRate['percent'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Granted — {{ $grantRate['granted'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Denied — {{ $grantRate['denied'] }}</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Trend + top lists --}}
    <section class="panels-grid panels-grid-3">
        <div class="panel">
            <h2 class="panel-title">Weekly Access Trend</h2>
            <div class="bar-chart bar-chart-short" role="img" aria-label="Access events per day, last 7 days">
                @foreach ($weekly as $day)
                    <div class="bar-col">
                        <div class="bar" style="height: {{ round($day['count'] / $maxWeekly * 100) }}%">
                            <span class="bar-value">{{ $day['count'] }}</span>
                        </div>
                        <span class="bar-label">{{ $day['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Most Used Doors (7 days)</h2>
            @forelse ($topDoors as $row)
                <div class="top-row">
                    <span class="top-label">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No door activity yet.</p>
            @endforelse
        </div>

        <div class="panel">
            <h2 class="panel-title">Access by Department (7 days)</h2>
            @forelse ($topDepartments as $row)
                <div class="top-row">
                    <span class="top-label">{{ $row['label'] }}</span>
                    <div class="progress"><div class="progress-fill" style="width: {{ $row['percent'] }}%"></div></div>
                    <span class="top-count mono">{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="muted">No badge activity linked to departments yet.</p>
            @endforelse
        </div>
    </section>

    {{-- Security notifications --}}
    @if ($alerts->isNotEmpty())
        <section class="panel panel-flush">
            <h2 class="panel-title panel-title-pad">Security Notifications</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Alert</th><th>Detail</th><th>Severity</th></tr></thead>
                    <tbody>
                        @foreach ($alerts as $alert)
                            <tr>
                                <td>{{ $alert['label'] }}</td>
                                <td>{{ $alert['detail'] }}</td>
                                <td>
                                    <span class="badge {{ $alert['severity'] === 'danger' ? 'badge-danger' : 'badge-warning' }}">
                                        <span class="badge-indicator" aria-hidden="true"></span>{{ $alert['severity'] === 'danger' ? 'Critical' : 'Warning' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('access.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Employee, badge ID or company…">
        </div>
        <div class="filter-field">
            <label for="department">Department</label>
            <select id="department" name="department">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="level">Access Level</label>
            <select id="level" name="level">
                <option value="">All levels</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(request('level') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="building">Building</label>
            <select id="building" name="building">
                <option value="">All buildings</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building }}" @selected(request('building') === $building)>{{ $building }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="door">Door</label>
            <select id="door" name="door">
                <option value="">All doors</option>
                @foreach ($doors as $door)
                    <option value="{{ $door->id }}" @selected(request('door') == $door->id)>{{ $door->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'department', 'level', 'building', 'door', 'status']))
                <a href="{{ route('access.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Access permissions table --}}
    <section class="panel panel-flush">
        <h2 class="panel-title panel-title-pad">Access Permissions</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Badge ID</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Access Level</th>
                        <th>Allowed Doors</th>
                        <th>Building</th>
                        <th>Schedule</th>
                        <th>Valid From</th>
                        <th>Valid Until</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    @if ($permission->user)
                                        <x-user-avatar :user="$permission->user" />
                                    @else
                                        <span class="avatar avatar-md" aria-hidden="true">{{ strtoupper(mb_substr($permission->holderName(), 0, 1)) }}</span>
                                    @endif
                                    <span class="user-cell-name">{{ $permission->holderName() }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $permission->badge_id }}</td>
                            <td>{{ $permission->department ?? '—' }}</td>
                            <td>{{ $permission->position ?? '—' }}</td>
                            <td>{{ $permission->access_level->label() }}</td>
                            <td title="{{ $permission->doors->pluck('name')->implode(', ') }}">
                                {{ $permission->doors->count() }} {{ Str::plural('door', $permission->doors->count()) }}
                            </td>
                            <td>{{ $permission->building ?? '—' }}</td>
                            <td>{{ $permission->scheduleLabel() }}</td>
                            <td>{{ $permission->valid_from->format('M j, Y') }}</td>
                            <td>{{ $permission->valid_until?->format('M j, Y') ?? 'No expiry' }}</td>
                            <td>
                                <span class="badge {{ $permission->isCurrentlyValid() ? 'badge-success' : 'badge-danger' }}">
                                    <span class="badge-indicator" aria-hidden="true"></span>{{ $permission->active ? ($permission->isCurrentlyValid() ? 'Active' : 'Expired') : 'Disabled' }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    @if ($role->canManageAccess())
                                        <a href="{{ route('access.permissions.edit', $permission) }}" class="action-btn" title="View / Edit">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                        </a>
                                    @endif
                                    @if ($role->canAdministerAccess())
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                data-action="{{ route('access.permissions.destroy', $permission) }}"
                                                data-name="{{ $permission->holderName() }} ({{ $permission->badge_id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="table-empty">No access permissions match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($permissions->hasPages())
            <div class="table-footer">{{ $permissions->links('pagination.app') }}</div>
        @endif
    </section>

    {{-- Temporary access + doors --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Temporary Visitor Access</h2>
            @if ($role->canGrantTemporaryAccess())
                <form method="POST" action="{{ route('access.temporary') }}" data-loading>
                    @csrf
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="visitor_name">Visitor Name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="visitor_name" name="visitor_name" value="{{ old('visitor_name') }}" required maxlength="150" placeholder="Visitor full name" @class(['is-invalid' => $errors->has('visitor_name')])>
                            @error('visitor_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label for="company">Company</label>
                            <input type="text" id="company" name="company" value="{{ old('company') }}" maxlength="150" placeholder="Company">
                        </div>
                        <div class="form-field">
                            <label for="host_user_id">Host Employee <span class="req" aria-hidden="true">*</span></label>
                            <select id="host_user_id" name="host_user_id" required>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((int) old('host_user_id') === $employee->id)>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="door_id">Door <span class="req" aria-hidden="true">*</span></label>
                            <select id="door_id" name="door_id" required>
                                @foreach ($doors as $door)
                                    <option value="{{ $door->id }}">{{ $door->name }} — {{ $door->building }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="start_time">Start Time <span class="req" aria-hidden="true">*</span></label>
                            <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '09:00') }}" required>
                        </div>
                        <div class="form-field">
                            <label for="end_time">End Time <span class="req" aria-hidden="true">*</span></label>
                            <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '17:00') }}" required @class(['is-invalid' => $errors->has('end_time')])>
                            @error('end_time') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label for="valid_until">Expiration Date <span class="req" aria-hidden="true">*</span></label>
                            <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', today()->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" data-loading-text="Granting…">Grant Temporary Access</button>
                    </div>
                </form>
            @endif

            @if ($temporary->isNotEmpty())
                <h3 class="form-section-title">Recent Temporary Passes</h3>
                @foreach ($temporary as $pass)
                    <div class="feed-item">
                        <span class="avatar avatar-md" aria-hidden="true">{{ strtoupper(mb_substr($pass->visitor_name, 0, 1)) }}</span>
                        <div class="feed-body">
                            <span class="feed-name">{{ $pass->visitor_name }} <span class="mono muted">({{ $pass->badge_id }})</span></span>
                            <span class="feed-detail">{{ $pass->doors->first()?->name ?? '—' }} · host {{ $pass->host?->name ?? '—' }} · expires {{ $pass->valid_until?->format('M j') }}</span>
                        </div>
                        <span class="badge {{ $pass->isCurrentlyValid() ? 'badge-success' : 'badge-muted' }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $pass->isCurrentlyValid() ? 'Active' : 'Expired' }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="panel panel-flush">
            <h2 class="panel-title panel-title-pad">Door Management</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Door</th>
                            <th>Building / Floor</th>
                            <th>Device</th>
                            <th>Required Level</th>
                            <th>Status</th>
                            <th>Last Activity</th>
                            <th class="th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($doors as $door)
                            <tr>
                                <td>{{ $door->name }}</td>
                                <td>{{ $door->building }} — {{ $door->floor }}</td>
                                <td>{{ $door->device?->name ?? '—' }}</td>
                                <td>{{ $door->required_access_level->label() }}</td>
                                <td><x-status-badge :status="$door->status" /></td>
                                <td>
                                    @if ($door->last_activity_at)
                                        <span title="{{ $door->last_activity_at->format('Y-m-d H:i') }}">{{ $door->last_activity_at->diffForHumans() }}</span>
                                    @else
                                        <span class="muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        @if ($role->canManageAccess() && $door->status !== App\Enums\DoorStatus::Offline)
                                            @if ($door->status === App\Enums\DoorStatus::Locked)
                                                <button type="button" class="action-btn js-confirm" title="Unlock"
                                                        data-action="{{ route('access.door-unlock', $door) }}"
                                                        data-title="Unlock Door" data-text="Unlock this door?"
                                                        data-name="{{ $door->name }}" data-btn="Unlock">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                                </button>
                                            @else
                                                <button type="button" class="action-btn js-confirm" title="Lock"
                                                        data-action="{{ route('access.door-lock', $door) }}"
                                                        data-title="Lock Door" data-text="Lock this door?"
                                                        data-name="{{ $door->name }}" data-btn="Lock">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Security events timeline + live feed --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Recent Security Events</h2>
            <ol class="timeline">
                @forelse ($incidents as $incident)
                    <li class="timeline-item">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-body">
                            <span class="timeline-event">
                                {{ $incident->detail }}
                                <span class="badge {{ $incident->severity?->badge() ?? 'badge-muted' }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $incident->severity?->label() ?? '—' }}</span>
                            </span>
                            <span class="timeline-detail">{{ $incident->door?->name ?? 'Facility' }} — {{ $incident->person_name }}</span>
                            <span class="timeline-time" title="{{ $incident->happened_at->format('Y-m-d H:i:s') }}">{{ $incident->happened_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <p class="muted">No security incidents recorded.</p>
                @endforelse
            </ol>
        </div>

        <div class="panel">
            <h2 class="panel-title">Live Activity Feed <span class="live-dot" aria-hidden="true"></span></h2>
            <div id="live-feed">
                @foreach ($feed as $event)
                    <div class="feed-item">
                        @if ($event->user)
                            <x-user-avatar :user="$event->user" />
                        @else
                            <span class="avatar avatar-md" aria-hidden="true">{{ strtoupper(mb_substr($event->person_name, 0, 1)) }}</span>
                        @endif
                        <div class="feed-body">
                            <span class="feed-name">{{ $event->person_name }}</span>
                            <span class="feed-detail">{{ $event->door?->name ?? '—' }} · {{ $event->device?->name ?? 'manual' }} · {{ $event->happened_at->format('H:i:s') }}</span>
                        </div>
                        <span class="badge {{ $event->result?->badge() ?? 'badge-muted' }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $event->result?->label() ?? '—' }}</span>
                    </div>
                @endforeach
            </div>
            <p class="muted tab-note">Auto-refreshes every 15 seconds.</p>
        </div>
    </section>

    @if ($role->canAdministerAccess())
        <x-delete-modal title="Delete Permission" message="Are you sure you want to delete this access permission?" />
    @endif

    {{-- Confirm modal (lock / unlock / lockdown) --}}
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
        const modal = document.getElementById('confirm-modal');
        const form = document.getElementById('confirm-modal-form');
        document.querySelectorAll('.js-confirm').forEach((btn) => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.action;
                document.getElementById('confirm-modal-title').textContent = btn.dataset.title;
                document.getElementById('confirm-modal-text').textContent = btn.dataset.text;
                document.getElementById('confirm-modal-name').textContent = btn.dataset.name;
                document.getElementById('confirm-modal-btn').textContent = btn.dataset.btn;
                modal.hidden = false;
            });
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-confirm]')) modal.hidden = true;
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.hidden = true; });

        // Live activity feed — polls the JSON endpoint every 15 s
        const feedEl = document.getElementById('live-feed');
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        let lastTopId = null;

        const refreshFeed = async () => {
            try {
                const res = await fetch('{{ route('access.feed') }}', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const events = await res.json();
                if (!events.length) return;
                const isNew = lastTopId !== null && events[0].id !== lastTopId;
                lastTopId = events[0].id;
                feedEl.innerHTML = events.map((e, i) => `
                    <div class="feed-item ${i === 0 && isNew ? 'feed-item-new' : ''}">
                        <span class="avatar avatar-md" aria-hidden="true">${esc(e.initials)}</span>
                        <div class="feed-body">
                            <span class="feed-name">${esc(e.name)}</span>
                            <span class="feed-detail">${esc(e.door)} · ${esc(e.device)} · ${esc(e.time)}</span>
                        </div>
                        <span class="badge ${esc(e.badge ?? 'badge-muted')}"><span class="badge-indicator" aria-hidden="true"></span>${esc(e.result ?? '—')}</span>
                    </div>`).join('');
            } catch (_) { /* offline — keep the current list */ }
        };
        setInterval(refreshFeed, 15000);
    })();
</script>
@endpush
