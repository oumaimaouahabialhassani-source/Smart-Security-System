<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Dashboard</title>
    @vite(['resources/css/dashboard.css'])
</head>
<body>

<div class="layout">

    {{-- ============ Sidebar ============ --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-shield" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </span>
            <span class="brand-name">{{ config('app.name') }}</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-link active">
                <span class="nav-icon">▦</span> Dashboard
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">◉</span> Employees
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">◈</span> Visitors
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">◎</span> Cameras
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">≡</span> Access Logs
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">▤</span> Reports
            </a>
            <a href="#" class="nav-link">
                <span class="nav-icon">⚙</span> Settings
            </a>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
            @csrf
            <button type="submit" class="nav-link logout-btn">
                <span class="nav-icon">⏻</span> Logout
            </button>
        </form>
    </aside>

    {{-- ============ Main column ============ --}}
    <div class="main">

        {{-- Top navbar --}}
        <header class="topbar">
            <div class="search-box">
                <input type="search" placeholder="Search employees, visitors, logs…" aria-label="Search">
            </div>

            <div class="topbar-actions">
                <button type="button" class="icon-btn" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.7 21a2 2 0 01-3.4 0"/>
                    </svg>
                    <span class="badge-dot"></span>
                </button>

                <div class="user-chip">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="user-name">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        {{-- Main content --}}
        <main class="content">

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
                        <div class="donut" role="img" aria-label="46 of 48 cameras online">
                            <span class="donut-center">96%</span>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot dot-online"></span> Online — 46</li>
                            <li><span class="dot dot-offline"></span> Offline — 2</li>
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

        </main>
    </div>
</div>

</body>
</html>
