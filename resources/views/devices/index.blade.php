@extends('layouts.app')

@section('title', 'IoT Devices — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">IoT Devices Management</h1>
            <p class="page-subtitle">Monitor and manage all connected sensors, locks, readers and smart devices.</p>
        </div>
        @can('create', App\Models\Device::class)
            <a href="{{ route('devices.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Device
            </a>
        @endcan
    </div>

    {{-- Summary stat cards --}}
    <section class="stats-grid stats-grid-5">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </span>
                <span class="stat-value">{{ $stats['total'] }}</span>
            </div>
            <div class="stat-label">Total Devices</div>
            <div class="stat-meta">Registered in the system</div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
                </span>
                <span class="stat-value">{{ $stats['online'] }}</span>
            </div>
            <div class="stat-label">Online</div>
            <div class="stat-meta">Communicating normally</div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
                </span>
                <span class="stat-value">{{ $stats['offline'] }}</span>
            </div>
            <div class="stat-label">Offline</div>
            <div class="stat-meta">No communication</div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="18" height="12" rx="2"/><line x1="23" y1="10" x2="23" y2="14"/><line x1="5" y1="10" x2="5" y2="14"/></svg>
                </span>
                <span class="stat-value">{{ $stats['low_battery'] }}</span>
            </div>
            <div class="stat-label">Low Battery</div>
            <div class="stat-meta">≤ {{ App\Models\Device::LOW_BATTERY }}% remaining</div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="stat-value">{{ $stats['alerts'] }}</span>
            </div>
            <div class="stat-label">Active Alerts</div>
            <div class="stat-meta">Offline, low battery, weak signal</div>
        </div>
    </section>

    {{-- Search & filters --}}
    <form method="GET" action="{{ route('devices.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name, device ID or IP address…">
        </div>

        <div class="filter-field">
            <label for="type">Device Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="building">Building</label>
            <select id="building" name="building">
                <option value="">All</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building }}" @selected(request('building') === $building)>{{ $building }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="floor">Floor</label>
            <select id="floor" name="floor">
                <option value="">All</option>
                @foreach ($floors as $floor)
                    <option value="{{ $floor }}" @selected(request('floor') === $floor)>{{ $floor }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="zone">Zone</label>
            <select id="zone" name="zone">
                <option value="">All</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone }}" @selected(request('zone') === $zone)>{{ $zone }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="battery">Battery</label>
            <select id="battery" name="battery">
                <option value="">All</option>
                <option value="low" @selected(request('battery') === 'low')>Low (≤ 20%)</option>
                <option value="medium" @selected(request('battery') === 'medium')>Medium (21–60%)</option>
                <option value="high" @selected(request('battery') === 'high')>High (> 60%)</option>
                <option value="mains" @selected(request('battery') === 'mains')>Mains-powered</option>
            </select>
        </div>

        <div class="filter-field">
            <label for="signal">Signal</label>
            <select id="signal" name="signal">
                <option value="">All</option>
                @foreach ($signals as $signal)
                    <option value="{{ $signal->value }}" @selected(request('signal') === $signal->value)>{{ $signal->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'type', 'status', 'building', 'floor', 'zone', 'battery', 'signal']))
                <a href="{{ route('devices.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Devices table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Device ID</th>
                        <th>Type</th>
                        <th>Building</th>
                        <th>Location</th>
                        <th>Battery</th>
                        <th>Signal</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <x-device-icon :type="$device->type" />
                                    <span class="user-cell-name">{{ $device->name }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $device->device_id }}</td>
                            <td>{{ $device->type->label() }}</td>
                            <td>{{ $device->building }}</td>
                            <td>
                                {{ $device->floor }} · {{ $device->zone }}
                                @if ($device->room)
                                    <div class="cell-sub">{{ $device->room }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($device->battery_level !== null)
                                    <div class="battery battery-{{ $device->batteryTone() }}" title="{{ $device->battery_level }}%">
                                        <span class="battery-bar"><span class="battery-fill" style="width: {{ $device->battery_level }}%"></span></span>
                                        <span class="battery-value">{{ $device->battery_level }}%</span>
                                    </div>
                                @else
                                    <span class="muted" title="Mains-powered">⚡ Mains</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $device->signal_strength->badge() }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $device->signal_strength->label() }}</span></td>
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
                                    <a href="{{ route('devices.show', $device) }}" class="action-btn" title="View" aria-label="View {{ $device->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @can('update', $device)
                                        <a href="{{ route('devices.edit', $device) }}" class="action-btn" title="Edit" aria-label="Edit {{ $device->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                        </a>
                                    @endcan
                                    @can('delete', $device)
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                aria-label="Delete {{ $device->name }}"
                                                data-action="{{ route('devices.destroy', $device) }}"
                                                data-name="{{ $device->name }} ({{ $device->device_id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="table-empty">No devices match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($devices->hasPages())
            <div class="table-footer">
                {{ $devices->links('pagination.app') }}
            </div>
        @endif
    </section>

    <x-delete-modal title="Delete Device" message="Are you sure you want to delete this device?" />

@endsection
