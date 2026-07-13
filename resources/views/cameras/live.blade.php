@extends('layouts.app')

@section('title', 'Live Monitoring — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Live Monitoring <span class="live-dot" aria-hidden="true"></span></h1>
            <p class="page-subtitle">All cameras streaming in real time with AI detection overlays. Statuses refresh automatically.</p>
        </div>
        <a href="{{ route('cameras.index') }}" class="btn btn-ghost">Cameras Management</a>
    </div>

    {{-- Summary stat cards --}}
    <section class="stats-grid stats-grid-4">
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                </span>
                <span class="stat-value">{{ $stats['total'] }}</span>
            </div>
            <div class="stat-label">Total Cameras</div>
            <div class="stat-meta">On the monitoring wall</div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span class="stat-value" data-live="online">{{ $stats['online'] }}</span>
            </div>
            <div class="stat-label">Online</div>
            <div class="stat-meta">Streaming now</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-top">
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </span>
                <span class="stat-value" data-live="offline">{{ $stats['offline'] }}</span>
            </div>
            <div class="stat-label">Offline</div>
            <div class="stat-meta">No signal</div>
        </div>
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-icon stat-icon-rec" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                </span>
                <span class="stat-value" data-live="recording">{{ $stats['recording'] }}</span>
            </div>
            <div class="stat-label">Recording</div>
            <div class="stat-meta">Recording enabled</div>
        </div>
    </section>

    {{-- Filters --}}
    <form method="GET" action="{{ route('cameras.live') }}" class="panel filters-bar" role="search">
        <div class="filter-field">
            <label for="building">Building</label>
            <select id="building" name="building">
                <option value="">All</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building }}" @selected(request('building') === $building)>{{ $building }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="floor">Floor</label>
            <select id="floor" name="floor">
                <option value="">All</option>
                @foreach ($floors as $floor)
                    <option value="{{ $floor }}" @selected(request('floor') === $floor)>{{ $floor }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-field">
            <label for="camera">Camera</label>
            <select id="camera" name="camera">
                <option value="">All</option>
                @foreach ($allCameras as $id => $name)
                    <option value="{{ $id }}" @selected(request('camera') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['building', 'floor', 'camera']))
                <a href="{{ route('cameras.live') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Camera wall --}}
    <section class="monitor-grid">
        @forelse ($cameras as $camera)
            <div class="panel panel-flush monitor-tile" data-camera-id="{{ $camera->id }}">
                {{--
                    Stream driver: when a playable stream URL exists the JS
                    below swaps this placeholder for a <video> element.
                    data-stream-url is where a future HLS/WebRTC gateway URL
                    (proxied from the camera's RTSP feed) plugs in.
                --}}
                <div @class(['monitor-stream', 'monitor-stream-offline' => $camera->status->value !== 'online'])
                     data-stream-url="{{ $camera->rtsp_url }}">
                    <div class="monitor-topbar">
                        <span class="badge" data-role="status"><span class="badge-indicator" aria-hidden="true"></span><span data-role="status-text">{{ $camera->status->label() }}</span></span>
                        <span class="badge badge-rec" data-role="rec" @if (! $camera->recording_enabled || $camera->status->value !== 'online') hidden @endif>
                            <span class="badge-indicator" aria-hidden="true"></span>REC
                        </span>
                    </div>

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:48px; height:48px; opacity:.4"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>

                    <div class="monitor-detect-box" data-role="detect-box" hidden></div>
                    <span class="badge monitor-detect" data-role="detect" hidden></span>
                    <span class="monitor-timestamp" data-role="clock">--:--:--</span>
                </div>

                <div class="stream-bar">
                    <span class="badge badge-muted mono">{{ $camera->camera_id }}</span>
                    <span class="muted" title="{{ $camera->placement() }}">{{ $camera->location }} · {{ $camera->building }} · {{ $camera->floor }}</span>
                    <span class="muted mono" style="margin-left:auto">{{ $camera->resolution ?? '1080p' }} · <span data-role="fps">{{ $camera->fps ?? 25 }}</span> FPS</span>
                    <button type="button" class="action-btn js-fullscreen" title="Full screen" aria-label="Full screen {{ $camera->name }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <section class="panel">
                <p class="table-empty">No cameras match your filters.</p>
            </section>
        @endforelse
    </section>

@endsection

@push('scripts')
<script>
    (() => {
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        // Timestamp overlay on every tile, ticking every second.
        const tick = () => {
            const now = new Date().toLocaleString('sv-SE').replace('T', ' ');
            document.querySelectorAll('[data-role="clock"]').forEach((el) => { el.textContent = now; });
        };
        tick();
        setInterval(tick, 1000);

        // Full-screen per tile.
        document.querySelectorAll('.js-fullscreen').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tile = btn.closest('.monitor-tile');
                if (document.fullscreenElement) document.exitFullscreen();
                else tile.requestFullscreen?.();
            });
        });

        // Stream driver: attach a real player when a tile has a playable
        // URL (HLS .m3u8 or direct MP4/WebM). RTSP URLs stay on the
        // simulated placeholder until a streaming gateway converts them.
        document.querySelectorAll('.monitor-stream[data-stream-url]').forEach((box) => {
            const url = box.dataset.streamUrl || '';
            if (/\.(m3u8|mp4|webm)(\?|$)/i.test(url)) {
                const video = document.createElement('video');
                video.src = url;
                video.muted = true;
                video.autoplay = true;
                video.playsInline = true;
                video.style.cssText = 'position:absolute; inset:0; width:100%; height:100%; object-fit:cover;';
                box.prepend(video);
            }
        });

        // Live status + AI detection overlays, refreshed every 10 s.
        const refresh = async () => {
            try {
                const res = await fetch('{{ route('cameras.live-feed') }}', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                let online = 0, offline = 0, recording = 0;

                data.cameras.forEach((cam) => {
                    if (cam.status === 'online') online++; else if (cam.status === 'offline') offline++;
                    if (cam.recording) recording++;

                    const tile = document.querySelector(`[data-camera-id="${cam.id}"]`);
                    if (!tile) return;

                    const stream = tile.querySelector('.monitor-stream');
                    stream.classList.toggle('monitor-stream-offline', cam.status !== 'online');

                    const status = tile.querySelector('[data-role="status"]');
                    status.className = 'badge ' + esc(cam.badge);
                    tile.querySelector('[data-role="status-text"]').textContent = cam.statusLabel;
                    tile.querySelector('[data-role="rec"]').hidden = !cam.recording;
                    tile.querySelector('[data-role="fps"]').textContent = cam.fps;

                    const detect = tile.querySelector('[data-role="detect"]');
                    const detectBox = tile.querySelector('[data-role="detect-box"]');
                    if (cam.detection) {
                        detect.hidden = false;
                        detect.className = 'badge ' + esc(cam.detection.tone);
                        detect.style.cssText = 'position:absolute; bottom:8px; left:10px; z-index:2;';
                        detect.innerHTML = '<span class="badge-indicator" aria-hidden="true"></span>' + esc(cam.detection.label);
                        detectBox.hidden = false;
                        detectBox.classList.toggle('monitor-detect-danger', cam.detection.tone === 'badge-danger');
                    } else {
                        detect.hidden = true;
                        detectBox.hidden = true;
                    }
                });

                const set = (key, value) => { const el = document.querySelector(`[data-live="${key}"]`); if (el) el.textContent = value; };
                set('online', online); set('offline', offline); set('recording', recording);
            } catch (_) { /* keep last known state while polling */ }
        };

        refresh();
        setInterval(() => { if (!document.hidden) refresh(); }, 10000);
    })();
</script>
@endpush
