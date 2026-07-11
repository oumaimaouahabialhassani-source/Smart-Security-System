@extends('layouts.app')

@section('title', 'Biometric Activity Logs — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Biometric Activity Logs</h1>
            <p class="page-subtitle">Every authentication attempt recorded by the biometric readers.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('biometrics.index') }}" class="btn btn-secondary">← Back to Biometrics</a>
            @if (auth()->user()->role->canManageBiometrics())
                <a href="{{ route('biometrics.logs.export', request()->query()) }}" class="btn btn-secondary" title="Export the filtered logs (opens in Excel)">Export CSV</a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="window.print()" title="Print (or save as PDF)">Print</button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('biometrics.logs') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Subject name or detail…">
        </div>
        <div class="filter-field">
            <label for="method">Method</label>
            <select id="method" name="method">
                <option value="">All methods</option>
                @foreach ($methods as $method)
                    <option value="{{ $method->value }}" @selected(request('method') === $method->value)>{{ $method->label() }}</option>
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
            @if (request()->hasAny(['search', 'method', 'result', 'device', 'date']))
                <a href="{{ route('biometrics.logs') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Logs table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Action</th>
                        <th>Method</th>
                        <th>Device</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Result</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    @if ($log->profile?->user)
                                        <x-user-avatar :user="$log->profile->user" />
                                    @else
                                        <span class="avatar avatar-md" aria-hidden="true">?</span>
                                    @endif
                                    <span class="user-cell-name">
                                        @if ($log->profile)
                                            <a href="{{ route('biometrics.show', $log->profile) }}">{{ $log->subject_name }}</a>
                                        @else
                                            {{ $log->subject_name }}
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td>{{ $log->detail ?? 'Identity verification' }}</td>
                            <td><span aria-hidden="true">{{ $log->method->icon() }}</span> {{ $log->method->label() }}</td>
                            <td>{{ $log->device?->name ?? '—' }}</td>
                            <td>{{ $log->happened_at->format('M j, Y') }}</td>
                            <td>{{ $log->happened_at->format('H:i:s') }}</td>
                            <td>{{ $log->duration_ms }} ms</td>
                            <td><x-status-badge :status="$log->result" /></td>
                            <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-empty">No log entries match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="table-footer">
                {{ $logs->links('pagination.app') }}
            </div>
        @endif
    </section>

@endsection
