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
            <h2 class="panel-title">Access Events — This Week</h2>
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

    {{-- Recent activity --}}
    <section class="panel">
        <h2 class="panel-title">Recent Access Events</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Door</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accessEvents as $event)
                        <tr>
                            <td>{{ $event['time'] }}</td>
                            <td>{{ $event['name'] }}</td>
                            <td>{{ $event['type'] }}</td>
                            <td>{{ $event['door'] }}</td>
                            <td>
                                <span class="status {{ $event['status'] === 'Granted' ? 'status-granted' : 'status-denied' }}">
                                    {{ $event['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

@endsection
