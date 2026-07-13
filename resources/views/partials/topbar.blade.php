<header class="topbar">
    {{-- Searches the current module: submits ?search=… to the page you are on. --}}
    <form class="search-box" method="GET" action="{{ url()->current() }}" role="search">
        <input type="search" name="search" value="{{ request('search') }}"
               placeholder="Search this page — employees, visitors, logs…" aria-label="Search this page">
    </form>

    <div class="topbar-actions">
        <div class="notif-wrap">
            <button type="button" class="icon-btn" id="topbar-bell"
                    aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.7 21a2 2 0 01-3.4 0"/>
                </svg>
                <span class="badge-count" id="topbar-bell-count" hidden></span>
            </button>

            <div class="notif-dropdown" id="notif-dropdown" hidden>
                <div class="notif-head">
                    <strong>Notifications</strong>
                    <button type="button" class="notif-readall" id="notif-readall">Mark all as read</button>
                </div>
                <div class="notif-items" id="notif-items">
                    <p class="notif-empty">Loading…</p>
                </div>
                <a href="{{ route('alerts.index') }}" class="notif-footer">View all alerts →</a>
            </div>
        </div>

        <a href="{{ route('profile.edit') }}" class="user-chip" title="My profile">
            <x-user-avatar :user="auth()->user()" />
            <span class="user-name">{{ auth()->user()->name }}</span>
        </a>
    </div>
</header>

<script>
(function () {
    const bell = document.getElementById('topbar-bell');
    const panel = document.getElementById('notif-dropdown');
    const items = document.getElementById('notif-items');
    const count = document.getElementById('topbar-bell-count');
    if (!bell || !panel) return;

    const csrf = '{{ csrf_token() }}';
    const esc = (value) => String(value ?? '').replace(/[&<>"']/g,
        (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function setCount(unread) {
        count.textContent = unread > 99 ? '99+' : unread;
        count.hidden = unread === 0;
        bell.setAttribute('aria-label', 'Notifications — ' + unread + ' unread');
    }

    function render(data) {
        setCount(data.unread);

        items.innerHTML = data.items.length
            ? data.items.map((n) => `
                <a href="${esc(n.url || '{{ route('alerts.index') }}')}" class="notif-item${n.read ? '' : ' unread'}" data-id="${esc(n.id)}" data-read="${n.read ? 1 : 0}">
                    <span class="badge ${esc(n.badge)}">${esc(n.severity)}</span>
                    <span class="notif-body"><strong>${esc(n.title)}</strong><span>${esc(n.detail)}</span></span>
                    <span class="notif-time">${esc(n.time)}</span>
                </a>`).join('')
            : '<p class="notif-empty">No notifications yet.</p>';
    }

    // Small toast in the bottom-right corner, reusing the flash styling.
    function toast(message, ok = true) {
        const el = document.createElement('div');
        el.className = 'flash ' + (ok ? 'flash-success' : 'flash-error') + ' toast';
        el.setAttribute('role', 'status');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function refresh() {
        fetch('{{ route('notifications.feed') }}', { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : null))
            .then((d) => d && render(d))
            .catch(() => {});
    }

    bell.addEventListener('click', () => {
        const opening = panel.hidden;
        panel.hidden = !opening;
        bell.setAttribute('aria-expanded', String(opening));
        if (opening) refresh();
    });

    document.addEventListener('click', (e) => {
        if (!panel.hidden && !e.target.closest('.notif-wrap')) {
            panel.hidden = true;
            bell.setAttribute('aria-expanded', 'false');
        }
    });

    // Clicking a notification marks only that one as read (keepalive:
    // the request survives the navigation to the related alert page).
    items.addEventListener('click', (e) => {
        const item = e.target.closest('.notif-item');
        if (item && item.dataset.id && item.dataset.read !== '1') {
            item.classList.remove('unread');
            item.dataset.read = '1';
            setCount(Math.max(0, (parseInt(count.textContent, 10) || 0) - 1));
            fetch('/notifications/' + item.dataset.id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                keepalive: true,
            });
        }
    });

    document.getElementById('notif-readall')?.addEventListener('click', async () => {
        // Optimistic UI: clear the badge and unread styling immediately.
        setCount(0);
        items.querySelectorAll('.notif-item.unread').forEach((el) => {
            el.classList.remove('unread');
            el.dataset.read = '1';
        });

        try {
            const res = await fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            if (!res.ok) throw new Error(res.status);
            toast('All notifications marked as read.');
        } catch (_) {
            toast('Could not mark notifications as read — please try again.', false);
        }

        refresh(); // resync with the server either way
    });

    refresh();
    // Poll only while the tab is visible; refresh immediately when the
    // user comes back so the bell never looks stale.
    setInterval(() => { if (!document.hidden) refresh(); }, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
</script>
