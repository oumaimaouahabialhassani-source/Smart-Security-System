<header class="topbar">
    <div class="search-box">
        <input type="search" placeholder="Search employees, visitors, logs…" aria-label="Search">
    </div>

    <div class="topbar-actions">
        <button type="button" class="icon-btn" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.7 21a2 2 0 01-3.4 0"/>
            </svg>
            <span class="badge-dot" aria-hidden="true"></span>
        </button>

        <div class="user-chip">
            <span class="avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="user-name">{{ auth()->user()->name }}</span>
        </div>
    </div>
</header>
