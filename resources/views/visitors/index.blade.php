@extends('layouts.app')

@section('title', 'Visitors Management — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Visitors Management</h1>
            <p class="page-subtitle">Register visitors, issue badges, and monitor everyone inside the building.</p>
        </div>
        <div class="page-head-actions">
            @if ($role->canManageVisitors())
                <a href="{{ route('visitors.export', request()->query()) }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export CSV
                </a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print
            </button>
            @if ($role->canManageVisitors())
                <a href="{{ route('visitors.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Register Visitor
                </a>
            @endif
        </div>
    </div>

    {{-- Stat cards --}}
    <section class="stats-grid">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-meta">{{ $stat['meta'] }}</div>
            </div>
        @endforeach
    </section>

    {{-- Visitor alerts --}}
    @if ($alerts->isNotEmpty())
        <section class="panel panel-flush">
            <h2 class="panel-title panel-title-pad">Visitor Alerts</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Alert</th>
                            <th>Visitor</th>
                            <th>Detail</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alerts as $alert)
                            <tr>
                                <td>{{ $alert['label'] }}</td>
                                <td><a href="{{ route('visitors.show', $alert['visit']) }}">{{ $alert['visit']->full_name }} ({{ $alert['visit']->visit_code }})</a></td>
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
    <form method="GET" action="{{ route('visitors.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name, national ID, company or visit ID…">
        </div>

        <div class="filter-field">
            <label for="host">Person Visited</label>
            <select id="host" name="host">
                <option value="">Anyone</option>
                @foreach ($hosts as $host)
                    <option value="{{ $host->id }}" @selected(request('host') == $host->id)>{{ $host->name }}</option>
                @endforeach
            </select>
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
            <label for="company">Company</label>
            <input type="text" id="company" name="company" value="{{ request('company') }}" placeholder="Company…">
        </div>

        <div class="filter-field">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}">
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

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'host', 'department', 'company', 'date', 'status']))
                <a href="{{ route('visitors.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Visits table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Visitor</th>
                        <th>National ID</th>
                        <th>Company</th>
                        <th>Person Visited</th>
                        <th>Department</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td class="mono">{{ $visit->visit_code }}</td>
                            <td>
                                <div class="user-cell">
                                    <x-visitor-avatar :visit="$visit" />
                                    <span class="user-cell-name">{{ $visit->full_name }}</span>
                                </div>
                            </td>
                            <td class="mono">{{ $visit->national_id }}</td>
                            <td>{{ $visit->company ?? '—' }}</td>
                            <td>{{ $visit->host?->name ?? '—' }}</td>
                            <td>{{ $visit->department }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($visit->purpose, 28) }}</td>
                            <td>{{ $visit->visit_date->format('M j, Y') }}</td>
                            <td>{{ $visit->checked_in_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $visit->checked_out_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $visit->durationLabel() ?? '—' }}</td>
                            <td><x-status-badge :status="$visit->status" /></td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('visitors.show', $visit) }}" class="action-btn" title="View" aria-label="View {{ $visit->full_name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>

                                    @if ($role->canManageVisitors())
                                        <a href="{{ route('visitors.edit', $visit) }}" class="action-btn" title="Edit" aria-label="Edit {{ $visit->full_name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                        </a>
                                    @endif

                                    @if ($role->canProcessVisits() && $visit->status === App\Enums\VisitStatus::Expected)
                                        <button type="button" class="action-btn js-confirm" title="Check-In"
                                                aria-label="Check in {{ $visit->full_name }}"
                                                data-action="{{ route('visitors.check-in', $visit) }}"
                                                data-title="Check-In Visitor"
                                                data-text="Confirm check-in? The current time will be recorded and a badge will be issued for:"
                                                data-name="{{ $visit->full_name }} ({{ $visit->visit_code }})"
                                                data-btn="Check In">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                        </button>
                                    @endif

                                    @if ($role->canProcessVisits() && $visit->status === App\Enums\VisitStatus::Inside)
                                        <button type="button" class="action-btn js-confirm" title="Check-Out"
                                                aria-label="Check out {{ $visit->full_name }}"
                                                data-action="{{ route('visitors.check-out', $visit) }}"
                                                data-title="Check-Out Visitor"
                                                data-text="Confirm check-out? The visit duration will be calculated and the badge disabled for:"
                                                data-name="{{ $visit->full_name }} ({{ $visit->visit_code }})"
                                                data-btn="Check Out">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                        </button>
                                    @endif

                                    @if ($role->canManageVisitors())
                                        <a href="{{ route('visitors.badge', $visit) }}" class="action-btn" title="Print Badge" target="_blank" aria-label="Print badge for {{ $visit->full_name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M15 8h4M15 12h4M7 16h10"/></svg>
                                        </a>

                                        <a href="{{ route('visitors.pass', $visit) }}" class="action-btn" title="Print Pass" target="_blank" aria-label="Print pass for {{ $visit->full_name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        </a>
                                    @endif

                                    @if ($role->canDeleteVisits())
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                aria-label="Delete visit {{ $visit->visit_code }}"
                                                data-action="{{ route('visitors.destroy', $visit) }}"
                                                data-name="{{ $visit->full_name }} ({{ $visit->visit_code }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="table-empty">No visits match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visits->hasPages())
            <div class="table-footer">
                {{ $visits->links('pagination.app') }}
            </div>
        @endif
    </section>

    @if ($role->canDeleteVisits())
        <x-delete-modal title="Delete Visit" message="Are you sure you want to delete this visit record?" />
    @endif

    {{-- Check-in / check-out confirmation modal --}}
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
        const modal = document.getElementById('confirm-modal');
        const form = document.getElementById('confirm-modal-form');
        const titleEl = document.getElementById('confirm-modal-title');
        const textEl = document.getElementById('confirm-modal-text');
        const nameEl = document.getElementById('confirm-modal-name');
        const btnEl = document.getElementById('confirm-modal-btn');

        document.querySelectorAll('.js-confirm').forEach((btn) => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.action;
                titleEl.textContent = btn.dataset.title;
                textEl.textContent = btn.dataset.text;
                nameEl.textContent = btn.dataset.name;
                btnEl.textContent = btn.dataset.btn;
                modal.hidden = false;
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-confirm]')) modal.hidden = true;
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') modal.hidden = true;
        });
    })();
</script>
@endpush
