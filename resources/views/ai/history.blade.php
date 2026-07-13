@extends('layouts.app')

@section('title', 'AI Alert History — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">AI Alert History</h1>
            <p class="page-subtitle">Full archive of everything the AI Security Bot has detected — {{ number_format($total) }} record(s).</p>
        </div>
        <div class="row-actions">
            @if (auth()->user()->role->canManageAlerts())
            <a href="{{ route('ai.export', request()->query()) }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Excel
            </a>
            @endif
            <a href="{{ route('ai.report', array_filter(['date' => request('from')])) }}" class="btn btn-secondary" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Export PDF
            </a>
            <a href="{{ route('ai.dashboard') }}" class="btn btn-ghost">Back to AI Dashboard</a>
        </div>
    </div>

    {{-- Search & filters --}}
    <form method="GET" action="{{ route('ai.history') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Alert ID, event type, description or analysis…">
        </div>

        <div class="filter-field">
            <label for="risk">Risk Level</label>
            <select id="risk" name="risk">
                <option value="">All</option>
                @foreach ($riskLevels as $level)
                    <option value="{{ $level->value }}" @selected(request('risk') === $level->value)>{{ $level->label() }}</option>
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
            <label for="event">Event Type</label>
            <select id="event" name="event">
                <option value="">All</option>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type }}" @selected(request('event') === $type)>{{ $type }}</option>
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
            @if (request()->hasAny(['search', 'risk', 'status', 'event', 'from', 'to']))
                <a href="{{ route('ai.history') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- History table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alert ID</th>
                        <th>Date &amp; Time</th>
                        <th>Event Type</th>
                        <th>Description</th>
                        <th>Risk</th>
                        <th>Recommendation</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td class="mono">{{ $alert->ai_code }}</td>
                            <td>
                                {{ $alert->happened_at->format('Y-m-d') }}
                                <div class="cell-sub mono">{{ $alert->happened_at->format('H:i:s') }}</div>
                            </td>
                            <td>{{ $alert->event_type }}</td>
                            <td>
                                <span title="{{ $alert->description }}">{{ \Illuminate\Support\Str::limit($alert->description, 60) }}</span>
                                <div class="cell-sub" title="{{ $alert->analysis }}">{{ \Illuminate\Support\Str::limit($alert->analysis, 70) }}</div>
                            </td>
                            <td><span class="badge {{ $alert->risk_level->badge() }}"><span class="badge-indicator" aria-hidden="true"></span>{{ $alert->risk_level->label() }}</span></td>
                            <td>{{ $alert->recommendation->label() }}</td>
                            <td><span class="badge {{ $alert->status->badge() }}">{{ $alert->status->label() }}</span></td>
                            <td>{{ $alert->reviewer?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-empty">No AI alerts match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alerts->hasPages())
            <div class="table-footer">
                {{ $alerts->links('pagination.app') }}
            </div>
        @endif
    </section>

@endsection
