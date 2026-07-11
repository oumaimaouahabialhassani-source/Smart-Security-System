@extends('layouts.app')

@section('title', $camera->name . ' — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $camera->name }} <span class="mono page-title-sub">{{ $camera->camera_id }}</span></h1>
            <p class="page-subtitle">{{ $camera->placement() }} — {{ $camera->location }}</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('cameras.index') }}" class="btn btn-secondary">← Back to Cameras</a>
            @can('update', $camera)
                <a href="{{ route('cameras.edit', $camera) }}" class="btn btn-primary">Edit Camera</a>
            @endcan
        </div>
    </div>

    {{-- Live stream + snapshot --}}
    <section class="stream-grid">
        <div class="panel panel-flush stream-panel">
            {{-- Placeholder container: mount an RTSP/WebRTC player here later
                 (stream source available as $camera->rtsp_url). --}}
            <div class="live-stream" data-rtsp="{{ $camera->rtsp_url }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                <span class="live-label"><span class="live-dot" aria-hidden="true"></span> LIVE STREAM</span>
                <span class="live-hint">{{ $camera->status === App\Enums\CameraStatus::Online ? 'Player integration pending — RTSP source configured' : 'Camera is '.strtolower($camera->status->label()) }}</span>
            </div>
            <div class="stream-bar">
                <x-status-badge :status="$camera->status" />
                <span class="muted">{{ $camera->resolution }} @ {{ $camera->fps }} fps</span>
                @if ($camera->recording_enabled)
                    <span class="badge badge-rec"><span class="badge-indicator" aria-hidden="true"></span>Recording</span>
                @endif
            </div>
        </div>

        <div class="panel snapshot-panel">
            <h2 class="panel-title">Latest Snapshot</h2>
            <div class="snapshot-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                <span class="muted">Snapshot capture pending integration</span>
            </div>
            <p class="muted tab-note">Last seen: {{ $camera->last_seen?->diffForHumans() ?? 'never' }}</p>
        </div>
    </section>

    {{-- Camera information --}}
    <section class="panel">
        <h2 class="panel-title">Camera Information</h2>
        <dl class="profile-grid">
            <div class="profile-item"><dt>Camera Name</dt><dd>{{ $camera->name }}</dd></div>
            <div class="profile-item"><dt>Camera ID</dt><dd class="mono">{{ $camera->camera_id }}</dd></div>
            <div class="profile-item"><dt>Brand</dt><dd>{{ $camera->brand->label() }}</dd></div>
            <div class="profile-item"><dt>Model</dt><dd>{{ $camera->model }}</dd></div>
            <div class="profile-item"><dt>Type</dt><dd>{{ $camera->type->label() }}</dd></div>
            <div class="profile-item"><dt>IP Address</dt><dd class="mono">{{ $camera->ip_address }}</dd></div>
            <div class="profile-item"><dt>MAC Address</dt><dd class="mono">{{ $camera->mac_address }}</dd></div>
            <div class="profile-item"><dt>Username</dt><dd>{{ $camera->username }}</dd></div>
            <div class="profile-item profile-item-wide"><dt>RTSP URL</dt><dd class="mono">{{ $camera->rtsp_url }}</dd></div>
            <div class="profile-item"><dt>Building</dt><dd>{{ $camera->building }}</dd></div>
            <div class="profile-item"><dt>Floor</dt><dd>{{ $camera->floor }}</dd></div>
            <div class="profile-item"><dt>Zone</dt><dd>{{ $camera->zone }}</dd></div>
            <div class="profile-item"><dt>Location</dt><dd>{{ $camera->location }}</dd></div>
            <div class="profile-item"><dt>Resolution</dt><dd>{{ $camera->resolution }}</dd></div>
            <div class="profile-item"><dt>FPS</dt><dd>{{ $camera->fps }}</dd></div>
            <div class="profile-item"><dt>Status</dt><dd>{{ $camera->status->label() }}</dd></div>
            <div class="profile-item"><dt>Recording</dt><dd>{{ $camera->recording_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
            <div class="profile-item"><dt>Last Seen</dt><dd>{{ $camera->last_seen?->format('M j, Y — H:i') ?? 'Never' }}</dd></div>
            <div class="profile-item"><dt>Created At</dt><dd>{{ $camera->created_at->format('M j, Y — H:i') }}</dd></div>
            <div class="profile-item"><dt>Updated At</dt><dd>{{ $camera->updated_at->format('M j, Y — H:i') }}</dd></div>
            @if ($camera->description)
                <div class="profile-item profile-item-wide"><dt>Description</dt><dd>{{ $camera->description }}</dd></div>
            @endif
        </dl>
    </section>

    {{-- Health + recording --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Camera Health <span class="label-hint">(placeholder metrics)</span></h2>
            <div class="health-grid">
                @foreach ([
                    ['label' => 'CPU Usage', 'value' => 34, 'display' => '34%', 'tone' => 'ok'],
                    ['label' => 'Temperature', 'value' => 52, 'display' => '47 °C', 'tone' => 'ok'],
                    ['label' => 'Storage Usage', 'value' => 71, 'display' => '71%', 'tone' => 'warn'],
                    ['label' => 'Bitrate', 'value' => 62, 'display' => '4.2 Mbps', 'tone' => 'ok'],
                    ['label' => 'Network Speed', 'value' => 83, 'display' => '96 Mbps', 'tone' => 'ok'],
                    ['label' => 'Signal Quality', 'value' => 91, 'display' => 'Excellent', 'tone' => 'ok'],
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

        <div class="panel">
            <h2 class="panel-title">Recording Information</h2>
            <dl class="profile-grid profile-grid-1col">
                <div class="profile-item">
                    <dt>Recording Enabled</dt>
                    <dd>
                        @if ($camera->recording_enabled)
                            <span class="badge badge-rec"><span class="badge-indicator" aria-hidden="true"></span>Recording</span>
                        @else
                            <span class="badge badge-muted">Not Recording</span>
                        @endif
                    </dd>
                </div>
                <div class="profile-item">
                    <dt>Storage Used <span class="label-hint">(placeholder)</span></dt>
                    <dd>
                        <div class="progress"><div class="progress-fill progress-warn" style="width: 71%"></div></div>
                        <span class="muted">356 GB of 500 GB</span>
                    </dd>
                </div>
                <div class="profile-item"><dt>Storage Available</dt><dd>144 GB</dd></div>
                <div class="profile-item"><dt>Recording Schedule</dt><dd>24 / 7 — Continuous</dd></div>
            </dl>
        </div>
    </section>

    {{-- Tabs --}}
    <section class="panel">
        <div class="tabs" role="tablist" aria-label="Camera details tabs">
            <button type="button" class="tab active" role="tab" aria-selected="true" data-tab="events">Events</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="ai">AI Detection</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="settings">Settings</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="permissions">Permissions</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="maintenance">Maintenance</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="logs">Logs</button>
        </div>

        {{-- Events tab --}}
        <div class="tab-panel" id="tab-events" role="tabpanel">
            <ol class="timeline">
                @foreach ([
                    ['event' => 'Motion Detected', 'detail' => 'Movement detected in the monitored area', 'time' => 'Today 09:41'],
                    ['event' => 'Recording Started', 'detail' => 'Continuous recording resumed', 'time' => 'Today 06:00'],
                    ['event' => 'Camera Online', 'detail' => 'Connection re-established', 'time' => 'Today 05:59'],
                    ['event' => 'Camera Offline', 'detail' => 'Network connection lost', 'time' => 'Today 05:47'],
                    ['event' => 'Recording Stopped', 'detail' => 'Recording paused by schedule', 'time' => 'Yesterday 23:00'],
                    ['event' => 'Camera Restarted', 'detail' => 'Scheduled nightly restart', 'time' => 'Yesterday 03:00'],
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
            <p class="muted tab-note">Example events — real data will stream in once the Event Logs module is connected.</p>
        </div>

        {{-- AI Detection tab --}}
        <div class="tab-panel" id="tab-ai" role="tabpanel" hidden>
            <div class="ai-grid">
                @foreach ([
                    'Faces Detected' => 'M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 0 0 5 0M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
                    'Unknown Persons' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 11h-6',
                    'Vehicles' => 'M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2m14 0a2 2 0 1 1-4 0m4 0a2 2 0 1 0-4 0M8 16a2 2 0 1 1-4 0m4 0a2 2 0 1 0-4 0',
                    'Motion Detection' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
                    'People Count' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
                    'Intrusion Detection' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM12 8v4M12 16h.01',
                    'Fire Detection' => 'M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z',
                    'Smoke Detection' => 'M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z',
                    'Loitering Detection' => 'M12 6v6l4 2M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
                    'Abandoned Object Detection' => 'M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
                ] as $label => $path)
                    <div class="ai-card">
                        <span class="ai-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                        </span>
                        <span class="ai-label">{{ $label }}</span>
                        <span class="ai-value">—</span>
                        <span class="badge badge-muted">Coming Soon</span>
                    </div>
                @endforeach
            </div>
            <p class="muted tab-note">AI analytics will populate these cards when detection models are integrated.</p>
        </div>

        {{-- Settings tab --}}
        <div class="tab-panel" id="tab-settings" role="tabpanel" hidden>
            <div class="settings-grid">
                @foreach ([
                    ['label' => 'Brightness', 'type' => 'range', 'value' => 55],
                    ['label' => 'Contrast', 'type' => 'range', 'value' => 60],
                    ['label' => 'Exposure', 'type' => 'range', 'value' => 45],
                    ['label' => 'Motion Sensitivity', 'type' => 'range', 'value' => 70],
                ] as $setting)
                    <div class="setting-item">
                        <div class="setting-head">
                            <span>{{ $setting['label'] }}</span>
                            <span class="health-value">{{ $setting['value'] }}%</span>
                        </div>
                        <input type="range" min="0" max="100" value="{{ $setting['value'] }}" disabled aria-label="{{ $setting['label'] }}">
                    </div>
                @endforeach

                @foreach ([
                    ['label' => 'Night Vision', 'on' => true],
                    ['label' => 'IR Mode', 'on' => true],
                    ['label' => 'Motion Detection', 'on' => true],
                    ['label' => 'Rotation (180°)', 'on' => false],
                ] as $toggle)
                    <div class="setting-item setting-toggle">
                        <span>{{ $toggle['label'] }}</span>
                        <span class="toggle {{ $toggle['on'] ? 'toggle-on' : '' }}" role="switch" aria-checked="{{ $toggle['on'] ? 'true' : 'false' }}" aria-label="{{ $toggle['label'] }}"><span class="toggle-knob"></span></span>
                    </div>
                @endforeach

                <div class="setting-item">
                    <div class="setting-head"><span>Zoom</span></div>
                    <select disabled aria-label="Zoom"><option>1× (Optical)</option></select>
                </div>
                <div class="setting-item">
                    <div class="setting-head"><span>Recording Quality</span></div>
                    <select disabled aria-label="Recording Quality"><option>High — {{ $camera->resolution }} @ {{ $camera->fps }} fps</option></select>
                </div>
            </div>
            <p class="muted tab-note">Settings are read-only placeholders — they will control the device once the camera API integration lands.</p>
        </div>

        {{-- Permissions tab --}}
        <div class="tab-panel" id="tab-permissions" role="tabpanel" hidden>
            <div class="permissions-grid">
                @foreach ([
                    'Administrator' => ['desc' => 'Full control — view, configure and delete', 'granted' => true],
                    'Security Officer' => ['desc' => 'View live stream and acknowledge events', 'granted' => true],
                    'Manager' => ['desc' => 'View live stream and reports', 'granted' => false],
                ] as $role => $info)
                    <label class="permission-card">
                        <input type="checkbox" disabled @checked($info['granted'])>
                        <span class="permission-info">
                            <span class="permission-name">{{ $role }}</span>
                            <span class="permission-desc">{{ $info['desc'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="muted tab-note">Camera access control will connect to the role &amp; permission system when it lands.</p>
        </div>

        {{-- Maintenance tab --}}
        <div class="tab-panel" id="tab-maintenance" role="tabpanel" hidden>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Last Maintenance Date</dt><dd>{{ now()->subDays(34)->format('M j, Y') }}</dd></div>
                <div class="profile-item"><dt>Assigned Technician</dt><dd>K. Bennani — FieldOps</dd></div>
                <div class="profile-item"><dt>Maintenance Status</dt><dd><span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Up to date</span></dd></div>
                <div class="profile-item profile-item-wide"><dt>Notes</dt><dd>Lens cleaned, firmware 5.7.3 applied, mounting bracket tightened. Next scheduled check in 60 days. <span class="label-hint">(placeholder)</span></dd></div>
            </dl>
        </div>

        {{-- Logs tab --}}
        <div class="tab-panel" id="tab-logs" role="tabpanel" hidden>
            <ol class="timeline">
                @foreach ([
                    ['event' => 'Camera Updated', 'detail' => 'Configuration changed by System Administrator', 'time' => $camera->updated_at->format('M j, Y — H:i')],
                    ['event' => 'Recording Started', 'detail' => 'Continuous schedule active', 'time' => 'Today 06:00'],
                    ['event' => 'Camera Configuration Changed', 'detail' => 'Motion sensitivity adjusted to 70%', 'time' => now()->subDays(2)->format('M j, Y').' — 14:12'],
                    ['event' => 'Camera Restarted', 'detail' => 'Scheduled nightly restart', 'time' => now()->subDays(3)->format('M j, Y').' — 03:00'],
                    ['event' => 'Camera Added', 'detail' => 'Registered in the system', 'time' => $camera->created_at->format('M j, Y — H:i')],
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
            <p class="muted tab-note">Sample log entries — a full audit trail arrives with the Event Logs module.</p>
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
</script>
@endpush
