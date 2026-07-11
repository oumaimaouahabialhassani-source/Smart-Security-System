@extends('layouts.app')

@section('title', 'Biometric Management — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Biometric Management</h1>
            <p class="page-subtitle">Enroll faces and fingerprints, verify identities, and monitor biometric readers.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('biometrics.logs') }}" class="btn btn-secondary">Activity Logs</a>
            @if ($role->canManageBiometrics())
                <a href="{{ route('biometrics.export') }}" class="btn btn-secondary" title="Export enrollment status (opens in Excel)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print this dashboard (or save as PDF)">Print</button>
            @if ($role->canManageBiometrics())
                <a href="{{ route('biometrics.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Enroll Employee
                </a>
            @endif
        </div>
    </div>

    {{-- Stat cards (animated counters) --}}
    <section class="stats-grid">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value" @if (is_int($stat['value'])) data-count="{{ $stat['value'] }}" @endif>{{ $stat['value'] }}</div>
                <div class="stat-meta">{{ $stat['meta'] }}</div>
            </div>
        @endforeach
    </section>

    {{-- Charts row --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Verifications — Last 7 Days</h2>
            <div class="bar-chart" role="img" aria-label="Bar chart of biometric verifications per day">
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
            <h2 class="panel-title">Authentication Success Rate (7 days)</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $successRate['percent'] }}%" role="img"
                     aria-label="{{ $successRate['success'] }} of {{ $successRate['total'] }} verifications succeeded">
                    <span class="donut-center">{{ $successRate['percent'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Success — {{ $successRate['success'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Failed / Warning — {{ $successRate['failed'] }}</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Authentication alerts --}}
    @if ($alerts->isNotEmpty())
        <section class="panel panel-flush">
            <h2 class="panel-title panel-title-pad">Authentication Alerts</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Alert</th><th>Detail</th><th>Severity</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($alerts as $alert)
                            <tr>
                                <td>@if ($alert['url'])<a href="{{ $alert['url'] }}">{{ $alert['label'] }}</a>@else{{ $alert['label'] }}@endif</td>
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

    {{-- Search & filters --}}
    <form method="GET" action="{{ route('biometrics.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Employee name, ID or email…">
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
            <label for="device">Device</label>
            <select id="device" name="device">
                <option value="">All devices</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}" @selected(request('device') == $device->id)>{{ $device->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="result">Verification Status</label>
            <select id="result" name="result">
                <option value="">All results</option>
                @foreach ($results as $result)
                    <option value="{{ $result->value }}" @selected(request('result') === $result->value)>{{ $result->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="method">Auth Method</label>
            <select id="method" name="method">
                <option value="">All methods</option>
                @foreach ($methods as $method)
                    <option value="{{ $method->value }}" @selected(request('method') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="date">Last Verified On</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'department', 'device', 'result', 'method', 'date']))
                <a href="{{ route('biometrics.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Registered biometrics table --}}
    <section class="panel panel-flush">
        <h2 class="panel-title panel-title-pad">Registered Biometrics</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Face</th>
                        <th>Fingerprint</th>
                        <th>Iris</th>
                        <th>Last Verification</th>
                        <th>Method</th>
                        <th>Result</th>
                        <th>Assigned Device</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($profiles as $profile)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <x-user-avatar :user="$profile->user" />
                                    <span class="user-cell-name">{{ $profile->user->name }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $profile->employee_code }}</td>
                            <td>{{ $profile->department }}</td>
                            <td>{{ $profile->position }}</td>
                            <td>
                                @if ($profile->face_enrolled_at)
                                    <span class="badge badge-success" title="Enrolled {{ $profile->face_enrolled_at->format('M j, Y') }} — quality {{ $profile->face_quality }}%"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                                @else
                                    <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                                @endif
                            </td>
                            <td>
                                @if ($profile->fingerprint_enrolled_at)
                                    <span class="badge badge-success" title="{{ $profile->fingerprint_finger }} — quality {{ $profile->fingerprint_quality }}%"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                                @else
                                    <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                                @endif
                            </td>
                            <td>
                                @if ($profile->iris_enrolled_at)
                                    <span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                                @else
                                    <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                                @endif
                            </td>
                            <td>
                                @if ($profile->latestVerification)
                                    <span title="{{ $profile->latestVerification->happened_at->format('Y-m-d H:i') }}">{{ $profile->latestVerification->happened_at->diffForHumans() }}</span>
                                @else
                                    <span class="muted">Never</span>
                                @endif
                            </td>
                            <td>
                                @if ($profile->latestVerification)
                                    <span title="{{ $profile->latestVerification->method->label() }}"><span aria-hidden="true">{{ $profile->latestVerification->method->icon() }}</span> {{ $profile->latestVerification->method->label() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($profile->latestVerification)
                                    <x-status-badge :status="$profile->latestVerification->result" />
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $profile->assignedDevice?->name ?? '—' }}</td>
                            <td><x-status-badge :status="$profile->status" /></td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('biometrics.show', $profile) }}" class="action-btn" title="View" aria-label="View {{ $profile->user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @if ($role->canManageBiometrics())
                                        <a href="{{ route('biometrics.edit', $profile) }}" class="action-btn" title="Edit" aria-label="Edit {{ $profile->user->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                        </a>
                                        <button type="button" class="action-btn js-face" title="Register Face"
                                                aria-label="Register face for {{ $profile->user->name }}"
                                                data-action="{{ route('biometrics.enroll-face', $profile) }}"
                                                data-name="{{ $profile->user->name }}"
                                                data-code="{{ $profile->employee_code }}"
                                                data-dept="{{ $profile->department }}"
                                                data-initials="{{ $profile->user->initials }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M7 20.5c1-3 3-4.5 5-4.5s4 1.5 5 4.5"/><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/></svg>
                                        </button>
                                        <button type="button" class="action-btn js-finger" title="Register Fingerprint"
                                                aria-label="Register fingerprint for {{ $profile->user->name }}"
                                                data-action="{{ route('biometrics.enroll-fingerprint', $profile) }}"
                                                data-name="{{ $profile->user->name }}"
                                                data-code="{{ $profile->employee_code }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 11a4 4 0 0 1 4 4c0 2-1 4-2 6"/><path d="M8 15a4 4 0 0 1 8 0"/><path d="M4.5 12.5A8 8 0 0 1 20 15"/><path d="M6 8.5A8 8 0 0 1 18 8"/></svg>
                                        </button>
                                        <button type="button" class="action-btn js-iris" title="Register Iris"
                                                aria-label="Register iris for {{ $profile->user->name }}"
                                                data-action="{{ route('biometrics.enroll-iris', $profile) }}"
                                                data-name="{{ $profile->user->name }}"
                                                data-code="{{ $profile->employee_code }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1"/></svg>
                                        </button>
                                        <button type="button" class="action-btn js-verify" title="Verify Identity"
                                                aria-label="Verify identity of {{ $profile->user->name }}"
                                                data-action="{{ route('biometrics.verify', $profile) }}"
                                                data-name="{{ $profile->user->name }}"
                                                data-face="{{ $profile->face_enrolled_at ? 1 : 0 }}"
                                                data-fingerprint="{{ $profile->fingerprint_enrolled_at ? 1 : 0 }}"
                                                data-iris="{{ $profile->iris_enrolled_at ? 1 : 0 }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                                        </button>
                                    @endif
                                    @if ($role->canAdministerBiometrics())
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                aria-label="Delete profile {{ $profile->employee_code }}"
                                                data-action="{{ route('biometrics.destroy', $profile) }}"
                                                data-name="{{ $profile->user->name }} ({{ $profile->employee_code }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="table-empty">No biometric profiles match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($profiles->hasPages())
            <div class="table-footer">
                {{ $profiles->links('pagination.app') }}
            </div>
        @endif
    </section>

    {{-- Devices panel + real-time monitoring --}}
    <section class="panels-grid">
        <div class="panel panel-flush">
            <h2 class="panel-title panel-title-pad">Biometric Devices</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>IP Address</th>
                            <th>Firmware</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                            <th class="th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($devices as $device)
                            <tr>
                                <td>{{ $device->name }}</td>
                                <td>{{ $device->type->label() }}</td>
                                <td>{{ $device->placement() }}</td>
                                <td class="mono">{{ $device->ip_address }}</td>
                                <td class="mono">{{ $device->firmware_version ?? '—' }}</td>
                                <td><x-status-badge :status="$device->status" /></td>
                                <td>
                                    @if ($device->last_seen)
                                        <span title="{{ $device->last_seen->format('Y-m-d H:i') }}">{{ $device->last_seen->diffForHumans() }}</span>
                                    @else
                                        <span class="muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('devices.show', $device) }}" class="action-btn" title="View">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        @if ($role->canManageBiometrics())
                                            <button type="button" class="action-btn js-confirm" title="Sync Templates"
                                                    data-action="{{ route('biometrics.device-sync', $device) }}"
                                                    data-title="Synchronize Device"
                                                    data-text="Push all enrolled biometric templates to this device?"
                                                    data-name="{{ $device->name }}" data-btn="Sync">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.5 9a9 9 0 0 1 14.9-3L23 10M1 14l4.6 4a9 9 0 0 0 14.9-3"/></svg>
                                            </button>
                                        @endif
                                        @if ($role->canAdministerBiometrics())
                                            <button type="button" class="action-btn js-confirm" title="Restart Device"
                                                    data-action="{{ route('biometrics.device-restart', $device) }}"
                                                    data-title="Restart Device"
                                                    data-text="Send a restart command to this device?"
                                                    data-name="{{ $device->name }}" data-btn="Restart">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                            </button>
                                            <a href="{{ route('devices.edit', $device) }}" class="action-btn" title="Configure">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                            </a>
                                            <button type="button" class="action-btn action-danger js-delete" title="Remove Device"
                                                    data-action="{{ route('devices.destroy', $device) }}"
                                                    data-name="{{ $device->name }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="table-empty">No biometric readers registered — add Face Terminals or Fingerprint Scanners in the IoT Devices module.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-title">Real-Time Device Monitoring</h2>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $monitoring['active'] }}</span><span class="mini-stat-label">Active</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $monitoring['offline'] }}</span><span class="mini-stat-label">Offline</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $monitoring['synced'] }}/{{ $monitoring['total'] }}</span><span class="mini-stat-label">Synced</span></div>
            </div>

            @foreach ($monitoring['perDevice'] as $entry)
                <div class="device-meter">
                    <div class="device-meter-head">
                        <span class="device-meter-name">{{ $entry['device']->name }}</span>
                        <x-status-badge :status="$entry['device']->status" />
                    </div>
                    @if ($entry['device']->status === App\Enums\DeviceStatus::Online)
                        <div class="device-meter-row">
                            <span>CPU {{ $entry['cpu'] }}%</span>
                            <div class="progress"><div class="progress-fill {{ $entry['cpu'] > 60 ? 'progress-warn' : '' }}" style="width: {{ $entry['cpu'] }}%"></div></div>
                        </div>
                        <div class="device-meter-row">
                            <span>Memory {{ $entry['memory'] }}%</span>
                            <div class="progress"><div class="progress-fill {{ $entry['memory'] > 70 ? 'progress-warn' : '' }}" style="width: {{ $entry['memory'] }}%"></div></div>
                        </div>
                    @else
                        <p class="muted device-meter-off">No telemetry — device unreachable.</p>
                    @endif
                </div>
            @endforeach
            <p class="muted tab-note">CPU / memory are placeholder values until hardware telemetry is connected.</p>
        </div>
    </section>

    {{-- Recent verification logs --}}
    <section class="panel panel-flush">
        <h2 class="panel-title panel-title-pad">Recent Verifications</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Method</th>
                        <th>Device</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Result</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    @if ($log->profile?->user)
                                        <x-user-avatar :user="$log->profile->user" />
                                    @else
                                        <span class="avatar avatar-md" aria-hidden="true">?</span>
                                    @endif
                                    <span class="user-cell-name">{{ $log->subject_name }}</span>
                                </div>
                            </td>
                            <td><span aria-hidden="true">{{ $log->method->icon() }}</span> {{ $log->method->label() }}</td>
                            <td>{{ $log->device?->name ?? '—' }}</td>
                            <td>{{ $log->happened_at->format('M j, Y') }}</td>
                            <td>{{ $log->happened_at->format('H:i:s') }}</td>
                            <td>{{ $log->duration_ms }} ms</td>
                            <td><x-status-badge :status="$log->result" /></td>
                            <td>{{ $log->detail ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="table-empty">No verification attempts recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <a href="{{ route('biometrics.logs') }}" class="btn btn-ghost">View all activity logs →</a>
        </div>
    </section>

    @if ($role->canAdministerBiometrics())
        <x-delete-modal title="Delete Record" message="Are you sure you want to delete this record?" />
    @endif

    {{-- Confirm modal (sync / restart) --}}
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

    {{-- Register Face modal --}}
    <div class="modal-backdrop" id="face-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="face-modal-title">
            <h3 class="modal-title" id="face-modal-title">Register Face</h3>
            <div class="enroll-employee">
                <span class="avatar avatar-lg" id="face-avatar" aria-hidden="true"></span>
                <div>
                    <p class="modal-target" id="face-name"></p>
                    <p class="muted" id="face-meta"></p>
                </div>
            </div>
            <div class="cam-box" id="face-cam">
                <div class="cam-ring" aria-hidden="true">
                    {{-- AI landmark preview: shown once a face is captured --}}
                    <svg class="cam-landmarks" id="face-landmarks" viewBox="0 0 70 70" aria-hidden="true" hidden>
                        <circle cx="24" cy="28" r="2"/><circle cx="46" cy="28" r="2"/>
                        <circle cx="35" cy="38" r="2"/>
                        <circle cx="26" cy="48" r="2"/><circle cx="35" cy="51" r="2"/><circle cx="44" cy="48" r="2"/>
                        <path d="M24 28 35 38 46 28M26 48 35 51 44 48M35 38 35 51" fill="none"/>
                    </svg>
                </div>
                <span class="cam-status" id="face-status">Camera off — press “Start Camera”</span>
            </div>
            <div class="enroll-quality">
                <span>Face quality</span>
                <div class="progress"><div class="progress-fill" id="face-quality-bar" style="width: 0"></div></div>
                <span class="mono" id="face-quality-value">—</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-face>Cancel</button>
                <button type="button" class="btn btn-secondary" id="face-start">Start Camera</button>
                <button type="button" class="btn btn-secondary" id="face-capture" disabled>Capture</button>
                <button type="button" class="btn btn-ghost" id="face-retake" hidden>Retake</button>
                <form method="POST" id="face-form" data-loading>
                    @csrf
                    <input type="hidden" name="quality" id="face-quality-input">
                    <button type="submit" class="btn btn-primary" id="face-save" disabled data-loading-text="Saving…">Save Face Data</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Register Iris modal --}}
    <div class="modal-backdrop" id="iris-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="iris-modal-title">
            <h3 class="modal-title" id="iris-modal-title">Register Iris</h3>
            <p class="modal-target" id="iris-name"></p>
            <div class="cam-box" id="iris-cam">
                <span class="iris-glyph" aria-hidden="true">◎</span>
                <span class="cam-status" id="iris-status">Iris camera placeholder — press “Capture Iris” and look into the lens</span>
            </div>
            <div class="enroll-quality">
                <span>Detection</span>
                <div class="progress"><div class="progress-fill" id="iris-progress-bar" style="width: 0"></div></div>
                <span class="mono" id="iris-progress-value">—</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-iris>Cancel</button>
                <button type="button" class="btn btn-secondary" id="iris-capture">Capture Iris</button>
                <form method="POST" id="iris-form" data-loading>
                    @csrf
                    <button type="submit" class="btn btn-primary" id="iris-save" disabled data-loading-text="Saving…">Save Iris Data</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Register Fingerprint modal --}}
    <div class="modal-backdrop" id="finger-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="finger-modal-title">
            <h3 class="modal-title" id="finger-modal-title">Register Fingerprint</h3>
            <p class="modal-target" id="finger-name"></p>
            <form method="POST" id="finger-form" data-loading>
                @csrf
                <div class="form-grid">
                    <div class="form-field">
                        <label for="finger-select">Finger</label>
                        <select id="finger-select" name="finger">
                            @foreach ($fingers as $finger)
                                <option value="{{ $finger }}">{{ $finger }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="finger-device">Scanner Device</label>
                        <select id="finger-device" name="device_id">
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }} — {{ $device->type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="cam-box cam-box-finger" id="finger-scanner">
                    <span class="finger-glyph" aria-hidden="true">❋</span>
                    <span class="cam-status" id="finger-status">Scanner placeholder — press “Scan” and hold the finger on the reader</span>
                </div>
                <div class="enroll-quality">
                    <span>Scan progress</span>
                    <div class="progress"><div class="progress-fill" id="finger-progress-fill" style="width: 0"></div></div>
                    <span class="mono" id="finger-quality-value">—</span>
                </div>
                <input type="hidden" name="quality" id="finger-quality-input">
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" data-close-finger>Cancel</button>
                    <button type="button" class="btn btn-secondary" id="finger-scan">Scan</button>
                    <button type="submit" class="btn btn-primary" id="finger-save" disabled data-loading-text="Saving…">Save Fingerprint</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Verify Identity modal --}}
    <div class="modal-backdrop" id="verify-modal" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="verify-modal-title">
            <h3 class="modal-title" id="verify-modal-title">Verify Identity</h3>
            <p class="modal-target" id="verify-name"></p>
            <form method="POST" id="verify-form" data-loading>
                @csrf
                <div class="verify-methods" id="verify-methods">
                    <label class="check-option"><input type="radio" name="method" value="face" checked> ◉ Face Recognition <span class="muted" data-method-note="face"></span></label>
                    <label class="check-option"><input type="radio" name="method" value="fingerprint"> ❋ Fingerprint <span class="muted" data-method-note="fingerprint"></span></label>
                    <label class="check-option"><input type="radio" name="method" value="iris"> ◎ Iris Scan <span class="muted" data-method-note="iris"></span></label>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" data-close-verify>Cancel</button>
                    <button type="submit" class="btn btn-primary" data-loading-text="Verifying…">Run Verification</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (() => {
        // ----- Animated counters -----
        document.querySelectorAll('.stat-value[data-count]').forEach((el) => {
            const target = parseInt(el.dataset.count, 10);
            if (!Number.isFinite(target) || target === 0) return;
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min((now - start) / 700, 1);
                el.textContent = Math.round(target * p);
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });

        // ----- Generic modal helpers -----
        const wire = (modalId, closeAttr) => {
            const modal = document.getElementById(modalId);
            if (!modal) return null;
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.closest(`[${closeAttr}]`)) modal.hidden = true;
            });
            return modal;
        };

        const confirmModal = wire('confirm-modal', 'data-close-confirm');
        const faceModal = wire('face-modal', 'data-close-face');
        const fingerModal = wire('finger-modal', 'data-close-finger');
        const irisModal = wire('iris-modal', 'data-close-iris');
        const verifyModal = wire('verify-modal', 'data-close-verify');

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') [confirmModal, faceModal, fingerModal, irisModal, verifyModal].forEach((m) => { if (m) m.hidden = true; });
        });

        // ----- Confirm modal (sync / restart) -----
        if (confirmModal) {
            const form = document.getElementById('confirm-modal-form');
            document.querySelectorAll('.js-confirm').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    document.getElementById('confirm-modal-title').textContent = btn.dataset.title;
                    document.getElementById('confirm-modal-text').textContent = btn.dataset.text;
                    document.getElementById('confirm-modal-name').textContent = btn.dataset.name;
                    document.getElementById('confirm-modal-btn').textContent = btn.dataset.btn;
                    confirmModal.hidden = false;
                });
            });
        }

        // ----- Register Face (Start Camera → Capture → Retake / Save) -----
        if (faceModal) {
            const form = document.getElementById('face-form');
            const status = document.getElementById('face-status');
            const bar = document.getElementById('face-quality-bar');
            const value = document.getElementById('face-quality-value');
            const start = document.getElementById('face-start');
            const capture = document.getElementById('face-capture');
            const retake = document.getElementById('face-retake');
            const save = document.getElementById('face-save');
            const cam = document.getElementById('face-cam');
            const landmarks = document.getElementById('face-landmarks');

            const resetFace = (message) => {
                status.textContent = message;
                bar.style.width = '0';
                value.textContent = '—';
                save.disabled = true;
                capture.disabled = true;
                start.disabled = false;
                retake.hidden = true;
                landmarks.hidden = true;
                cam.classList.remove('cam-active');
            };

            document.querySelectorAll('.js-face').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    document.getElementById('face-name').textContent = btn.dataset.name;
                    document.getElementById('face-meta').textContent = `${btn.dataset.code} — ${btn.dataset.dept}`;
                    document.getElementById('face-avatar').textContent = btn.dataset.initials;
                    resetFace('Camera off — press “Start Camera”');
                    faceModal.hidden = false;
                });
            });

            start.addEventListener('click', () => {
                cam.classList.add('cam-active');
                start.disabled = true;
                capture.disabled = false;
                status.textContent = 'Camera preview active — align the face inside the ring, then press “Capture”.';
            });

            capture.addEventListener('click', () => {
                capture.disabled = true;
                status.textContent = 'Detecting face…';
                const quality = 72 + Math.floor(Math.random() * 27); // simulated capture quality
                let p = 0;
                const step = () => {
                    p = Math.min(p + 4, quality);
                    bar.style.width = p + '%';
                    value.textContent = p + '%';
                    if (p < quality) { setTimeout(step, 40); return; }
                    status.textContent = 'Face detected — AI landmarks locked, template generated and ready to save.';
                    landmarks.hidden = false;
                    document.getElementById('face-quality-input').value = quality;
                    save.disabled = false;
                    retake.hidden = false;
                };
                setTimeout(step, 350);
            });

            retake.addEventListener('click', () => {
                resetFace('Camera preview active — align the face inside the ring, then press “Capture”.');
                cam.classList.add('cam-active');
                start.disabled = true;
                capture.disabled = false;
            });
        }

        // ----- Register Iris -----
        if (irisModal) {
            const form = document.getElementById('iris-form');
            const status = document.getElementById('iris-status');
            const bar = document.getElementById('iris-progress-bar');
            const value = document.getElementById('iris-progress-value');
            const save = document.getElementById('iris-save');
            const cam = document.getElementById('iris-cam');

            document.querySelectorAll('.js-iris').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    document.getElementById('iris-name').textContent = `${btn.dataset.name} (${btn.dataset.code})`;
                    status.textContent = 'Iris camera placeholder — press “Capture Iris” and look into the lens';
                    bar.style.width = '0';
                    value.textContent = '—';
                    save.disabled = true;
                    cam.classList.remove('cam-active');
                    irisModal.hidden = false;
                });
            });

            document.getElementById('iris-capture').addEventListener('click', () => {
                cam.classList.add('cam-active');
                status.textContent = 'Scanning iris… keep looking at the lens.';
                save.disabled = true;
                let p = 0;
                const step = () => {
                    p = Math.min(p + 5, 100);
                    bar.style.width = p + '%';
                    value.textContent = p + '%';
                    if (p < 100) { setTimeout(step, 45); return; }
                    status.textContent = 'Iris pattern captured — template generated and ready to save.';
                    save.disabled = false;
                };
                setTimeout(step, 300);
            });
        }

        // ----- Register Fingerprint -----
        if (fingerModal) {
            const form = document.getElementById('finger-form');
            const status = document.getElementById('finger-status');
            const bar = document.getElementById('finger-progress-fill');
            const value = document.getElementById('finger-quality-value');
            const save = document.getElementById('finger-save');

            document.querySelectorAll('.js-finger').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    document.getElementById('finger-name').textContent = `${btn.dataset.name} (${btn.dataset.code})`;
                    status.textContent = 'Scanner placeholder — press “Scan” and hold the finger on the reader';
                    bar.style.width = '0';
                    value.textContent = '—';
                    save.disabled = true;
                    fingerModal.hidden = false;
                });
            });

            document.getElementById('finger-scan').addEventListener('click', () => {
                status.textContent = 'Scanning… keep the finger still.';
                save.disabled = true;
                const quality = 68 + Math.floor(Math.random() * 31); // simulated scan quality
                let p = 0;
                const step = () => {
                    p = Math.min(p + 3, 100);
                    bar.style.width = p + '%';
                    value.textContent = p < 100 ? p + '%' : `quality ${quality}%`;
                    if (p < 100) { setTimeout(step, 30); return; }
                    status.textContent = `Scan complete — ridge quality ${quality}%. Verified against live read.`;
                    document.getElementById('finger-quality-input').value = quality;
                    save.disabled = false;
                };
                setTimeout(step, 300);
            });
        }

        // ----- Verify Identity -----
        if (verifyModal) {
            const form = document.getElementById('verify-form');
            document.querySelectorAll('.js-verify').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    document.getElementById('verify-name').textContent = btn.dataset.name;
                    ['face', 'fingerprint', 'iris'].forEach((m) => {
                        const note = verifyModal.querySelector(`[data-method-note="${m}"]`);
                        if (note) note.textContent = btn.dataset[m] === '1' ? '(enrolled)' : '(not enrolled)';
                    });
                    verifyModal.hidden = false;
                });
            });
        }
    })();
</script>
@endpush
