@extends('layouts.app')

@section('title', $device->name . ' — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $device->name }} <span class="mono page-title-sub">{{ $device->device_id }}</span></h1>
            <p class="page-subtitle">{{ $device->type->label() }} — {{ $device->placement() }}</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('devices.index') }}" class="btn btn-secondary">← Back to Devices</a>
            @can('update', $device)
                <a href="{{ route('devices.edit', $device) }}" class="btn btn-primary">Edit Device</a>
            @endcan
        </div>
    </div>

    {{-- Active alerts --}}
    @if (count($device->activeAlerts()))
        <section class="alerts-row">
            @foreach ($device->activeAlerts() as $alert)
                <div class="alert-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    {{ $alert }}
                </div>
            @endforeach
        </section>
    @endif

    {{-- Live status cards --}}
    <section class="stats-grid stats-grid-5">
        <div class="stat-card {{ $device->status === App\Enums\DeviceStatus::Online ? 'stat-success' : 'stat-danger' }}">
            <div class="stat-label">Online Status</div>
            <div class="stat-value-sm"><x-status-badge :status="$device->status" /></div>
            <div class="stat-meta">Last seen {{ $device->last_seen?->diffForHumans() ?? 'never' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Temperature</div>
            <div class="stat-value">23.1 °C</div>
            <div class="stat-meta">Placeholder telemetry</div>
        </div>
        <div class="stat-card {{ $device->batteryTone() === 'danger' ? 'stat-danger' : ($device->batteryTone() === 'warn' ? 'stat-warning' : '') }}">
            <div class="stat-label">Battery</div>
            <div class="stat-value">{{ $device->battery_level !== null ? $device->battery_level.'%' : '⚡' }}</div>
            <div class="stat-meta">{{ $device->battery_level !== null ? 'Battery-powered' : 'Mains-powered' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Signal Quality</div>
            <div class="stat-value-sm"><span class="badge {{ $device->signal_strength->badge() }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $device->signal_strength->label() }}</span></div>
            <div class="stat-meta">{{ $device->protocol->label() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Network Status</div>
            <div class="stat-value-sm">{{ $device->ip_address ?? 'No IP (mesh)' }}</div>
            <div class="stat-meta mono">{{ $device->mac_address }}</div>
        </div>
    </section>

    {{-- Sensor data + commands --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Sensor Data <span class="label-hint">(placeholder)</span></h2>
            @php($reading = $device->type->sampleReading())
            <div class="sensor-reading">
                <x-device-icon :type="$device->type" class="sensor-icon" />
                <div>
                    <div class="sensor-label">{{ $reading['label'] }}</div>
                    <div class="sensor-value">{{ $reading['value'] }}</div>
                </div>
            </div>
            <p class="muted tab-note">Live readings will stream here once hardware telemetry (MQTT/webhooks) is integrated.</p>
        </div>

        <div class="panel">
            <h2 class="panel-title">Device Commands</h2>
            <div class="commands-grid">
                @foreach ([
                    ['label' => 'Restart Device', 'icon' => 'M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15'],
                    ['label' => 'Shutdown Device', 'icon' => 'M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10'],
                    ['label' => 'Update Firmware', 'icon' => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5-5 5 5M12 5v12'],
                    ['label' => 'Factory Reset', 'icon' => 'M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6'],
                    ['label' => 'Synchronize Time', 'icon' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 6v6l4 2'],
                    ['label' => 'Reconnect Device', 'icon' => 'M5 12.55a11 11 0 0 1 14.08 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01'],
                ] as $cmd)
                    <button type="button" class="command-btn js-command" data-command="{{ $cmd['label'] }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $cmd['icon'] }}"/></svg>
                        {{ $cmd['label'] }}
                    </button>
                @endforeach
            </div>
            <p class="muted tab-note" id="command-feedback">Commands are queued locally — hardware integration pending.</p>
        </div>
    </section>

    {{-- Health --}}
    <section class="panel">
        <h2 class="panel-title">Device Health <span class="label-hint">(placeholder metrics)</span></h2>
        <div class="health-flex">
            <div class="gauge-row">
                @foreach ([
                    ['label' => 'CPU', 'value' => 22],
                    ['label' => 'Memory', 'value' => 41],
                    ['label' => 'Storage', 'value' => 12],
                ] as $gauge)
                    <div class="gauge" style="--gauge: {{ $gauge['value'] }}">
                        <div class="gauge-ring"><span>{{ $gauge['value'] }}%</span></div>
                        <span class="gauge-label">{{ $gauge['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="health-grid health-grid-flex">
                @foreach ([
                    ['label' => 'Network Quality', 'value' => 88, 'display' => 'Stable', 'tone' => 'ok'],
                    ['label' => 'Signal Strength', 'value' => $device->signal_strength === App\Enums\SignalStrength::Excellent ? 92 : ($device->signal_strength === App\Enums\SignalStrength::Good ? 65 : 28), 'display' => $device->signal_strength->label(), 'tone' => $device->signal_strength === App\Enums\SignalStrength::Weak ? 'warn' : 'ok'],
                    ['label' => 'Device Temperature', 'value' => 38, 'display' => '23.1 °C', 'tone' => 'ok'],
                    ['label' => 'Battery Health', 'value' => $device->battery_level ?? 100, 'display' => $device->battery_level !== null ? $device->battery_level.'%' : 'Mains', 'tone' => $device->batteryTone() === 'ok' ? 'ok' : 'warn'],
                ] as $metric)
                    <div class="health-item">
                        <div class="health-head">
                            <span>{{ $metric['label'] }}</span>
                            <span class="health-value">{{ $metric['display'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-fill {{ $metric['tone'] === 'warn' ? 'progress-warn' : '' }}" style="width: {{ $metric['value'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Device information --}}
    <section class="panel">
        <h2 class="panel-title">Device Information</h2>
        <dl class="profile-grid">
            <div class="profile-item"><dt>Device Name</dt><dd>{{ $device->name }}</dd></div>
            <div class="profile-item"><dt>Device ID</dt><dd class="mono">{{ $device->device_id }}</dd></div>
            <div class="profile-item"><dt>Type</dt><dd>{{ $device->type->label() }}</dd></div>
            <div class="profile-item"><dt>Brand</dt><dd>{{ $device->brand }}</dd></div>
            <div class="profile-item"><dt>Model</dt><dd>{{ $device->model }}</dd></div>
            <div class="profile-item"><dt>Protocol</dt><dd>{{ $device->protocol->label() }}</dd></div>
            <div class="profile-item"><dt>IP Address</dt><dd class="mono">{{ $device->ip_address ?? '—' }}</dd></div>
            <div class="profile-item"><dt>MAC Address</dt><dd class="mono">{{ $device->mac_address }}</dd></div>
            <div class="profile-item"><dt>Serial Number</dt><dd class="mono">{{ $device->serial_number }}</dd></div>
            <div class="profile-item"><dt>Firmware Version</dt><dd class="mono">{{ $device->firmware_version }}</dd></div>
            <div class="profile-item"><dt>Username</dt><dd>{{ $device->username }}</dd></div>
            <div class="profile-item"><dt>Building</dt><dd>{{ $device->building }}</dd></div>
            <div class="profile-item"><dt>Floor</dt><dd>{{ $device->floor }}</dd></div>
            <div class="profile-item"><dt>Zone</dt><dd>{{ $device->zone }}</dd></div>
            <div class="profile-item"><dt>Room</dt><dd>{{ $device->room ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Status</dt><dd>{{ $device->status->label() }}</dd></div>
            <div class="profile-item"><dt>Battery Level</dt><dd>{{ $device->battery_level !== null ? $device->battery_level.'%' : 'Mains-powered' }}</dd></div>
            <div class="profile-item"><dt>Signal Strength</dt><dd>{{ $device->signal_strength->label() }}</dd></div>
            <div class="profile-item"><dt>Last Seen</dt><dd>{{ $device->last_seen?->format('M j, Y — H:i') ?? 'Never' }}</dd></div>
            <div class="profile-item"><dt>Created At</dt><dd>{{ $device->created_at->format('M j, Y — H:i') }}</dd></div>
            <div class="profile-item"><dt>Updated At</dt><dd>{{ $device->updated_at->format('M j, Y — H:i') }}</dd></div>
            @if ($device->description)
                <div class="profile-item profile-item-wide"><dt>Description</dt><dd>{{ $device->description }}</dd></div>
            @endif
        </dl>
    </section>

    {{-- Tabs --}}
    <section class="panel">
        <div class="tabs" role="tablist" aria-label="Device details tabs">
            <button type="button" class="tab active" role="tab" aria-selected="true" data-tab="events">Event History</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="automation">Automation Rules</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="maintenance">Maintenance</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="logs">Device Logs</button>
        </div>

        {{-- Events --}}
        <div class="tab-panel" id="tab-events" role="tabpanel">
            <ol class="timeline">
                @foreach ([
                    ['event' => 'Sensor Triggered', 'detail' => $device->type->sampleReading()['label'].' event registered', 'time' => 'Today 09:41'],
                    ['event' => 'Device Connected', 'detail' => 'Communication re-established via '.$device->protocol->label(), 'time' => 'Today 06:12'],
                    ['event' => 'Device Disconnected', 'detail' => 'Heartbeat missed (3 attempts)', 'time' => 'Today 06:09'],
                    ['event' => 'Battery Low', 'detail' => 'Battery dropped below 20%', 'time' => now()->subDays(1)->format('M j').' — 18:44'],
                    ['event' => 'Firmware Updated', 'detail' => 'Updated to v'.$device->firmware_version, 'time' => now()->subDays(6)->format('M j').' — 02:30'],
                    ['event' => 'Device Added', 'detail' => 'Registered in the system', 'time' => $device->created_at->format('M j, Y — H:i')],
                ] as $entry)
                    <li class="timeline-item">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-body">
                            <span class="timeline-event">{{ $entry['event'] }}</span>
                            <span class="timeline-detail">{{ $entry['detail'] }}</span>
                            <span class="timeline-time">{{ $entry['time'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="muted tab-note">Example events — real telemetry arrives with the Event Logs module.</p>
        </div>

        {{-- Automation rules --}}
        <div class="tab-panel" id="tab-automation" role="tabpanel" hidden>
            <div class="automation-grid">
                <div class="automation-card">
                    <div class="automation-title">Rule Example — Intrusion Response</div>
                    <div class="flow">
                        <div class="flow-step flow-if">IF Motion Detected</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step">Turn On Camera</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step">Send Notification</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step flow-action">Activate Alarm</div>
                    </div>
                </div>

                <div class="automation-card">
                    <div class="automation-title">Rule Example — Fire Response</div>
                    <div class="flow">
                        <div class="flow-step flow-if">IF Smoke Detected</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step">Activate Siren</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step">Notify Administrator</div>
                        <div class="flow-arrow" aria-hidden="true">↓</div>
                        <div class="flow-step flow-action">Start Camera Recording</div>
                    </div>
                </div>
            </div>
            <p class="muted tab-note">Preview of the future Automation Engine — rules are illustrative and not executed.</p>
        </div>

        {{-- Maintenance --}}
        <div class="tab-panel" id="tab-maintenance" role="tabpanel" hidden>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Last Maintenance Date</dt><dd>{{ now()->subDays(52)->format('M j, Y') }}</dd></div>
                <div class="profile-item"><dt>Assigned Technician</dt><dd>K. Bennani — FieldOps</dd></div>
                <div class="profile-item"><dt>Maintenance Status</dt><dd><span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Up to date</span></dd></div>
                <div class="profile-item profile-item-wide"><dt>Notes</dt><dd>Battery contacts cleaned, firmware {{ $device->firmware_version }} verified, mounting checked. Next scheduled inspection in 90 days. <span class="label-hint">(placeholder)</span></dd></div>
            </dl>
        </div>

        {{-- Logs --}}
        <div class="tab-panel" id="tab-logs" role="tabpanel" hidden>
            <ol class="timeline">
                @foreach ([
                    ['event' => 'Configuration Updated', 'detail' => 'Settings changed by System Administrator', 'time' => $device->updated_at->format('M j, Y — H:i')],
                    ['event' => 'Online', 'detail' => 'Device reported healthy heartbeat', 'time' => 'Today 06:12'],
                    ['event' => 'Offline', 'detail' => 'Connection lost', 'time' => 'Today 06:09'],
                    ['event' => 'Firmware Updated', 'detail' => 'v'.$device->firmware_version.' installed', 'time' => now()->subDays(6)->format('M j, Y').' — 02:30'],
                    ['event' => 'Battery Changed', 'detail' => 'CR123A replaced by technician', 'time' => now()->subDays(52)->format('M j, Y').' — 11:05'],
                    ['event' => 'Maintenance Completed', 'detail' => 'Routine inspection passed', 'time' => now()->subDays(52)->format('M j, Y').' — 11:20'],
                    ['event' => 'Device Registered', 'detail' => 'Added to the system', 'time' => $device->created_at->format('M j, Y — H:i')],
                ] as $entry)
                    <li class="timeline-item">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-body">
                            <span class="timeline-event">{{ $entry['event'] }}</span>
                            <span class="timeline-detail">{{ $entry['detail'] }}</span>
                            <span class="timeline-time">{{ $entry['time'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="muted tab-note">Sample log entries — the full audit trail arrives with the Event Logs module.</p>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach((t) => {
                t.classList.toggle('active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            document.querySelectorAll('.tab-panel').forEach((panel) => {
                panel.hidden = panel.id !== 'tab-' + tab.dataset.tab;
            });
        });
    });

    // Placeholder command feedback until hardware integration lands.
    const feedback = document.getElementById('command-feedback');
    document.querySelectorAll('.js-command').forEach((btn) => {
        btn.addEventListener('click', () => {
            feedback.textContent = '“' + btn.dataset.command + '” queued — will execute when hardware integration is connected.';
        });
    });
</script>
@endpush
