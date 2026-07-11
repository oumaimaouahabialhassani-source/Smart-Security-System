<aside class="sidebar">
    <div class="sidebar-brand">
        @if ($brandLogo = \App\Models\Setting::get('appearance.logo'))
            <img src="{{ $brandLogo }}" alt="" class="brand-logo" aria-hidden="true">
        @else
            <span class="brand-shield" aria-hidden="true">
                <x-shield-logo />
            </span>
        @endif
        <span class="brand-name">{{ config('app.name') }}</span>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
            <span class="nav-icon" aria-hidden="true">▦</span> Dashboard
        </a>
        @can('viewAny', App\Models\User::class)
            <a href="{{ route('users.index') }}" @class(['nav-link', 'active' => request()->routeIs('users.*')])>
                <span class="nav-icon" aria-hidden="true">◉</span> Users
            </a>
        @endcan
        <a href="{{ route('visitors.index') }}" @class(['nav-link', 'active' => request()->routeIs('visitors.*')])>
            <span class="nav-icon" aria-hidden="true">◈</span> Visitors
        </a>
        <a href="{{ route('cameras.index') }}" @class(['nav-link', 'active' => request()->routeIs('cameras.*')])>
            <span class="nav-icon" aria-hidden="true">◎</span> Cameras
        </a>
        <a href="{{ route('devices.index') }}" @class(['nav-link', 'active' => request()->routeIs('devices.*')])>
            <span class="nav-icon" aria-hidden="true">⬡</span> IoT Devices
        </a>
        <a href="{{ route('biometrics.index') }}" @class(['nav-link', 'active' => request()->routeIs('biometrics.*')])>
            <span class="nav-icon" aria-hidden="true">❋</span> Biometrics
        </a>
        <a href="{{ route('access.index') }}" @class(['nav-link', 'active' => request()->routeIs('access.*')])>
            <span class="nav-icon" aria-hidden="true">≡</span> Access Control
        </a>
        <a href="{{ route('alerts.index') }}" @class(['nav-link', 'active' => request()->routeIs('alerts.*')])>
            <span class="nav-icon" aria-hidden="true">⚠</span> Alerts
        </a>
        @if (auth()->user()->role->canViewReports())
            <a href="{{ route('reports.index') }}" @class(['nav-link', 'active' => request()->routeIs('reports.*')])>
                <span class="nav-icon" aria-hidden="true">▤</span> Reports
            </a>
        @endif
        @if (auth()->user()->role->canManageSettings())
            <a href="{{ route('settings.index') }}" @class(['nav-link', 'active' => request()->routeIs('settings.*')])>
                <span class="nav-icon" aria-hidden="true">⚙</span> Settings
            </a>
        @endif
        @if (auth()->user()->role->canViewAuditLogs())
            <a href="{{ route('audit.index') }}" @class(['nav-link', 'active' => request()->routeIs('audit.*')])>
                <span class="nav-icon" aria-hidden="true">🗒</span> Audit Logs
            </a>
        @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
        @csrf
        <button type="submit" class="nav-link logout-btn">
            <span class="nav-icon" aria-hidden="true">⏻</span> Logout
        </button>
    </form>
</aside>
