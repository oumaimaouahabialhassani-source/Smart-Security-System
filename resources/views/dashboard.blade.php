@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name'))

@section('content')

    <h1 class="page-title">Security Overview</h1>
    <p class="page-subtitle">Live status of your facility — {{ now()->format('l, F j, Y') }}</p>

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

    {{-- Charts row --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Access Events — Last 7 Days</h2>
            <div class="bar-chart" role="img" aria-label="Bar chart of access events per day">
                @foreach ($weeklyAccess as $day)
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
            <h2 class="panel-title">Camera Status</h2>
            <div class="donut-wrap">
                <div class="donut" style="--online-percent: {{ $cameras['percent'] }}%" role="img"
                     aria-label="{{ $cameras['online'] }} of {{ $cameras['total'] }} cameras online">
                    <span class="donut-center">{{ $cameras['percent'] }}%</span>
                </div>
                <ul class="donut-legend">
                    <li><span class="dot dot-online" aria-hidden="true"></span> Online — {{ $cameras['online'] }}</li>
                    <li><span class="dot dot-offline" aria-hidden="true"></span> Offline — {{ $cameras['total'] - $cameras['online'] }}</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Active alerts --}}
    <section class="panel">
        <h2 class="panel-title">
            Active Alerts
            @if ($alertTotal > count($alerts))
                <span class="muted">(showing {{ count($alerts) }} of {{ $alertTotal }})</span>
            @endif
        </h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Alert</th>
                        <th>Item</th>
                        <th>Location</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        <tr>
                            <td>{{ $alert['label'] }}</td>
                            <td><a href="{{ $alert['url'] }}">{{ $alert['name'] }}</a></td>
                            <td>{{ $alert['location'] }}</td>
                            <td>
                                <span class="badge {{ $alert['severity'] === 'danger' ? 'badge-danger' : 'badge-warning' }}">
                                    <span class="badge-indicator" aria-hidden="true"></span>{{ $alert['severity'] === 'danger' ? 'Critical' : 'Warning' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-empty">No active alerts — all cameras and devices are healthy.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Recent access events --}}
    <section class="panel">
        <h2 class="panel-title">Recent Access Events</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Door</th>
                        <th>Direction</th>
                        <th>Method</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accessEvents as $event)
                        <tr>
                            <td title="{{ $event->happened_at->format('Y-m-d H:i:s') }}">{{ $event->happened_at->diffForHumans() }}</td>
                            <td>{{ $event->person_name }}</td>
                            <td>{{ $event->door?->name ?? '—' }}</td>
                            <td>{{ ucfirst($event->direction ?? '—') }}</td>
                            <td>{{ ucfirst($event->method ?? '—') }}</td>
                            <td><x-status-badge :status="$event->result" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No access events yet — check a visitor in or run a biometric verification.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <a href="{{ route('access.logs') }}" class="btn btn-ghost">View all access logs →</a>
        </div>
    </section>

@endsection
