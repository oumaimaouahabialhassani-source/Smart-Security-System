<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-shield" aria-hidden="true">
            <x-shield-logo />
        </span>
        <span class="brand-name">{{ config('app.name') }}</span>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('dashboard')])>
            <span class="nav-icon" aria-hidden="true">▦</span> Dashboard
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">◉</span> Employees
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">◈</span> Visitors
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">◎</span> Cameras
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">≡</span> Access Logs
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">▤</span> Reports
        </a>
        <a href="#" class="nav-link">
            <span class="nav-icon" aria-hidden="true">⚙</span> Settings
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
        @csrf
        <button type="submit" class="nav-link logout-btn">
            <span class="nav-icon" aria-hidden="true">⏻</span> Logout
        </button>
    </form>
</aside>
