@extends('layouts.app')

@section('title', 'Cameras Management — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Cameras Management</h1>
            <p class="page-subtitle">Monitor and manage all security cameras across your facilities.</p>
        </div>
        <a href="{{ route('cameras.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Camera
        </a>
    </div>

    {{-- Summary stat cards --}}
    <section class="stats-grid stats-grid-5">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                </span>
                <span class="stat-value">{{ $stats['total'] }}</span>
            </div>
            <div class="stat-label">Total Cameras</div>
            <div class="stat-meta">Registered in the system</div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span class="stat-value">{{ $stats['online'] }}</span>
            </div>
            <div class="stat-label">Online</div>
            <div class="stat-meta">Streaming normally</div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </span>
                <span class="stat-value">{{ $stats['offline'] }}</span>
            </div>
            <div class="stat-label">Offline</div>
            <div class="stat-meta">No connection</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon stat-icon-rec" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                </span>
                <span class="stat-value">{{ $stats['recording'] }}</span>
            </div>
            <div class="stat-label">Recording</div>
            <div class="stat-meta">Recording enabled</div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="stat-value">{{ $stats['errors'] }}</span>
            </div>
            <div class="stat-label">Needs Attention</div>
            <div class="stat-meta">In maintenance / errors</div>
        </div>
    </section>

    {{-- Search & filters --}}
    <form method="GET" action="{{ route('cameras.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name, camera ID, IP address or location…">
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
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="brand">Brand</label>
            <select id="brand" name="brand">
                <option value="">All</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->value }}" @selected(request('brand') === $brand->value)>{{ $brand->label() }}</option>
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

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'status', 'type', 'brand', 'building', 'floor', 'zone']))
                <a href="{{ route('cameras.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Cameras table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Camera</th>
                        <th>Camera ID</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Brand</th>
                        <th>Status</th>
                        <th>Recording</th>
                        <th>Last Seen</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cameras as $camera)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span class="cam-thumb" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                                    </span>
                                    <span class="user-cell-name">{{ $camera->name }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $camera->camera_id }}</td>
                            <td>
                                <span title="{{ $camera->placement() }}">{{ $camera->location }}</span>
                                <div class="cell-sub">{{ $camera->building }} · {{ $camera->floor }} · {{ $camera->zone }}</div>
                            </td>
                            <td>{{ $camera->type->label() }}</td>
                            <td>{{ $camera->brand->label() }}</td>
                            <td><x-status-badge :status="$camera->status" /></td>
                            <td>
                                @if ($camera->recording_enabled)
                                    <span class="badge badge-rec"><span class="badge-indicator" aria-hidden="true"></span>Recording</span>
                                @else
                                    <span class="badge badge-muted">Not Recording</span>
                                @endif
                            </td>
                            <td>
                                @if ($camera->last_seen)
                                    <span title="{{ $camera->last_seen->format('Y-m-d H:i') }}">{{ $camera->last_seen->diffForHumans() }}</span>
                                @else
                                    <span class="muted">Never</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('cameras.show', $camera) }}" class="action-btn" title="View" aria-label="View {{ $camera->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <a href="{{ route('cameras.edit', $camera) }}" class="action-btn" title="Edit" aria-label="Edit {{ $camera->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                    </a>
                                    <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                            aria-label="Delete {{ $camera->name }}"
                                            data-action="{{ route('cameras.destroy', $camera) }}"
                                            data-name="{{ $camera->name }} ({{ $camera->camera_id }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No cameras match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cameras->hasPages())
            <div class="table-footer">
                {{ $cameras->links('pagination.app') }}
            </div>
        @endif
    </section>

    <x-delete-modal title="Delete Camera" message="Are you sure you want to delete this camera?" />

@endsection
