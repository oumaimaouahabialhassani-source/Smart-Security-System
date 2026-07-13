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

    @php($role = auth()->user()->role)

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
            <span class="nav-icon" aria-hidden="true">▦</span> Dashboard
        </a>
        @can('viewAny', App\Models\User::class)
            <a href="{{ route('users.index') }}" @class(['nav-link', 'active' => request()->routeIs('users.*')])>
                <span class="nav-icon" aria-hidden="true">◉</span> Users
            </a>
        @endcan
        @if ($role->canViewVisitors())
            <a href="{{ route('visitors.index') }}" @class(['nav-link', 'active' => request()->routeIs('visitors.*')])>
                <span class="nav-icon" aria-hidden="true">◈</span> Visitors
            </a>
        @endif
        <a href="{{ route('cameras.index') }}" @class(['nav-link', 'active' => request()->routeIs('cameras.*') && ! request()->routeIs('cameras.live')])>
            <span class="nav-icon" aria-hidden="true">◎</span> Cameras
        </a>
        <a href="{{ route('cameras.live') }}" @class(['nav-link', 'active' => request()->routeIs('cameras.live')])>
            <span class="nav-icon" aria-hidden="true">▶</span> Live Monitoring
        </a>
        @if ($role->canViewDevices())
            <a href="{{ route('devices.index') }}" @class(['nav-link', 'active' => request()->routeIs('devices.*')])>
                <span class="nav-icon" aria-hidden="true">⬡</span> IoT Devices
            </a>
        @endif
        @if ($role->canViewBiometrics())
            <a href="{{ route('biometrics.index') }}" @class(['nav-link', 'active' => request()->routeIs('biometrics.*')])>
                <span class="nav-icon" aria-hidden="true">❋</span> Biometrics
            </a>
        @endif
        @if ($role->canViewAccess())
            <a href="{{ route('access.index') }}" @class(['nav-link', 'active' => request()->routeIs('access.*')])>
                <span class="nav-icon" aria-hidden="true">≡</span> Access Control
            </a>
        @endif
        <a href="{{ route('alerts.index') }}" @class(['nav-link', 'active' => request()->routeIs('alerts.*')])>
            <span class="nav-icon" aria-hidden="true">⚠</span> Alerts
        </a>
        @if ($role->canUseAiBot())
            <a href="{{ route('ai.dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('ai.*') && ! request()->routeIs('ai.analytics')])>
                <span class="nav-icon" aria-hidden="true">✦</span> AI Security Bot
            </a>
            <a href="{{ route('ai.analytics') }}" @class(['nav-link', 'active' => request()->routeIs('ai.analytics')])>
                <span class="nav-icon" aria-hidden="true">◆</span> AI Analytics
            </a>
        @endif
        @if ($role->canViewReports())
            <a href="{{ route('reports.index') }}" @class(['nav-link', 'active' => request()->routeIs('reports.*')])>
                <span class="nav-icon" aria-hidden="true">▤</span> Reports
            </a>
        @endif
        @if ($role->canManageSettings())
            <a href="{{ route('settings.index') }}" @class(['nav-link', 'active' => request()->routeIs('settings.*')])>
                <span class="nav-icon" aria-hidden="true">⚙</span> Settings
            </a>
        @endif
        <a href="{{ route('profile.edit') }}" @class(['nav-link', 'active' => request()->routeIs('profile.*')])>
            <span class="nav-icon" aria-hidden="true">◍</span> Profile
        </a>
        <a href="{{ route('notifications.index') }}" @class(['nav-link', 'active' => request()->routeIs('notifications.index')])>
            <span class="nav-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px; height:15px; vertical-align:-2px">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.7 21a2 2 0 01-3.4 0"/>
                </svg>
            </span> Notifications
        </a>
        @if ($role->canViewAuditLogs())
            <a href="{{ route('audit.index') }}" @class(['nav-link', 'active' => request()->routeIs('audit.*')])>
                <span class="nav-icon" aria-hidden="true">🗒</span> Activity Logs
            </a>
        @endif
        <a href="{{ route('help.index') }}" @class(['nav-link', 'active' => request()->routeIs('help.*')])>
            <span class="nav-icon" aria-hidden="true">✚</span> Help &amp; Support
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
        @csrf
        <button type="submit" class="nav-link logout-btn">
            <span class="nav-icon" aria-hidden="true">⏻</span> Logout
        </button>
    </form>
</aside>
