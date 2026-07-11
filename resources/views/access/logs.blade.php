@extends('layouts.app')

@section('title', 'Access Logs — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Access Logs</h1>
            <p class="page-subtitle">Every badge and biometric access attempt recorded at the doors.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('access.index') }}" class="btn btn-secondary">← Access Control</a>
            @if (auth()->user()->role->canManageAccess())
                <a href="{{ route('access.logs.export', request()->query()) }}" class="btn btn-secondary" title="Export the filtered logs (opens in Excel)">Export CSV</a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print (or save as PDF)">Print</button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('access.logs') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Employee, visitor, badge ID or detail…">
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
            <label for="result">Result</label>
            <select id="result" name="result">
                <option value="">All results</option>
                @foreach ($results as $result)
                    <option value="{{ $result->value }}" @selected(request('result') === $result->value)>{{ $result->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="direction">Direction</label>
            <select id="direction" name="direction">
                <option value="">Entries & exits</option>
                <option value="entry" @selected(request('direction') === 'entry')>Entries</option>
                <option value="exit" @selected(request('direction') === 'exit')>Exits</option>
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
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'door', 'result', 'direction', 'device', 'date']))
                <a href="{{ route('access.logs') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Logs table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Person</th>
                        <th>Badge ID</th>
                        <th>Door</th>
                        <th>Building</th>
                        <th>Action</th>
                        <th>Result</th>
                        <th>Device</th>
                        <th>IP Address</th>
                        <th class="th-actions">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->happened_at->format('M j, Y') }}</td>
                            <td>{{ $log->happened_at->format('H:i:s') }}</td>
                            <td>
                                <div class="user-cell">
                                    @if ($log->user)
                                        <x-user-avatar :user="$log->user" />
                                    @else
                                        <span class="avatar avatar-md" aria-hidden="true">{{ strtoupper(mb_substr($log->person_name, 0, 1)) }}</span>
                                    @endif
                                    <span class="user-cell-name">{{ $log->person_name }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $log->badge_id ?? '—' }}</td>
                            <td>{{ $log->door?->name ?? '—' }}</td>
                            <td>{{ $log->door?->building ?? '—' }}</td>
                            <td>{{ ucfirst($log->direction ?? '—') }} · {{ ucfirst($log->method ?? '—') }}</td>
                            <td><x-status-badge :status="$log->result" /></td>
                            <td>{{ $log->device?->name ?? '—' }}</td>
                            <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="action-btn js-details" title="View Details"
                                            data-name="{{ $log->person_name }}"
                                            data-badge="{{ $log->badge_id ?? '—' }}"
                                            data-door="{{ $log->door?->name ?? '—' }} ({{ $log->door?->building ?? '—' }})"
                                            data-when="{{ $log->happened_at->format('M j, Y — H:i:s') }}"
                                            data-result="{{ $log->result?->label() }}"
                                            data-result-badge="{{ $log->result?->badge() }}"
                                            data-method="{{ ucfirst($log->method ?? '—') }} ({{ ucfirst($log->direction ?? '—') }})"
                                            data-device="{{ $log->device?->name ?? '—' }}"
                                            data-camera="{{ $log->camera?->name ?? ($log->door?->camera?->name ?? 'No camera assigned') }}"
                                            data-confidence="{{ $log->face_confidence !== null ? $log->face_confidence.'%' : '—' }}"
                                            data-notes="{{ $log->detail ?? '—' }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="table-empty">No access events match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="table-footer">{{ $logs->links('pagination.app') }}</div>
        @endif
    </section>

    {{-- Log details modal --}}
    <div class="modal-backdrop" id="details-modal" hidden>
        <div class="modal modal-wide" role="dialog" aria-modal="true" aria-labelledby="details-modal-title">
            <h3 class="modal-title" id="details-modal-title">Access Event Details</h3>
            <div class="cam-box cam-box-snapshot">
                <span class="cam-status">📷 Camera snapshot placeholder — <span id="d-camera"></span></span>
            </div>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Person</dt><dd id="d-name"></dd></div>
                <div class="profile-item"><dt>Badge</dt><dd class="mono" id="d-badge"></dd></div>
                <div class="profile-item"><dt>Door</dt><dd id="d-door"></dd></div>
                <div class="profile-item"><dt>Date & Time</dt><dd id="d-when"></dd></div>
                <div class="profile-item"><dt>Result</dt><dd><span class="badge" id="d-result"><span class="badge-indicator" aria-hidden="true"></span><span id="d-result-text"></span></span></dd></div>
                <div class="profile-item"><dt>Method</dt><dd id="d-method"></dd></div>
                <div class="profile-item"><dt>Device Used</dt><dd id="d-device"></dd></div>
                <div class="profile-item"><dt>AI Face Confidence</dt><dd class="mono" id="d-confidence"></dd></div>
                <div class="profile-item profile-item-wide"><dt>Notes</dt><dd id="d-notes"></dd></div>
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
        const modal = document.getElementById('details-modal');
        const set = (id, text) => { document.getElementById(id).textContent = text; };

        document.querySelectorAll('.js-details').forEach((btn) => {
            btn.addEventListener('click', () => {
                set('d-name', btn.dataset.name);
                set('d-badge', btn.dataset.badge);
                set('d-door', btn.dataset.door);
                set('d-when', btn.dataset.when);
                set('d-method', btn.dataset.method);
                set('d-device', btn.dataset.device);
                set('d-camera', btn.dataset.camera);
                set('d-confidence', btn.dataset.confidence);
                set('d-notes', btn.dataset.notes);
                set('d-result-text', btn.dataset.result || '—');
                document.getElementById('d-result').className = 'badge ' + (btn.dataset.resultBadge || 'badge-muted');
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
