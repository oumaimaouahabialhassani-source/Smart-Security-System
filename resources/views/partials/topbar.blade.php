<header class="topbar">
    <div class="search-box">
        <input type="search" placeholder="Search employees, visitors, logs…" aria-label="Search">
    </div>

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

    function render(data) {
        count.textContent = data.unread > 99 ? '99+' : data.unread;
        count.hidden = data.unread === 0;
        bell.setAttribute('aria-label', 'Notifications — ' + data.unread + ' unread');

        items.innerHTML = data.items.length
            ? data.items.map((n) => `
                <a href="{{ route('alerts.index') }}" class="notif-item${n.read ? '' : ' unread'}" data-id="${esc(n.id)}">
                    <span class="badge ${esc(n.badge)}">${esc(n.severity)}</span>
                    <span class="notif-body"><strong>${esc(n.title)}</strong><span>${esc(n.detail)}</span></span>
                    <span class="notif-time">${esc(n.time)}</span>
                </a>`).join('')
            : '<p class="notif-empty">No notifications yet.</p>';
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

    items.addEventListener('click', (e) => {
        const item = e.target.closest('.notif-item');
        if (item && item.dataset.id) {
            fetch('/notifications/' + item.dataset.id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                keepalive: true,
            });
        }
    });

    document.getElementById('notif-readall').addEventListener('click', () => {
        fetch('{{ route('notifications.read-all') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).then(refresh);
    });

    refresh();
    setInterval(refresh, 20000);
})();
</script>
