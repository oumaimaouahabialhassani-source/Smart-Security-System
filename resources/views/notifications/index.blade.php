@extends('layouts.app')

@section('title', 'Notifications — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Notifications</h1>
            <p class="page-subtitle">Everything the system has sent you — {{ $unread }} unread.</p>
        </div>
        @if ($unread > 0)
            <button type="button" class="btn btn-secondary" id="mark-all-read">Mark all as read</button>
        @endif
    </div>

    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Severity</th>
                        <th>Received</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        <tr>
                            <td>
                                {{ $notification->data['title'] ?? 'Security Alert' }}
                                <div class="cell-sub">{{ $notification->data['detail'] ?? '' }}</div>
                            </td>
                            <td><span class="badge {{ $notification->data['badge'] ?? 'badge-muted' }}">{{ $notification->data['severity_label'] ?? '—' }}</span></td>
                            <td><span title="{{ $notification->created_at->format('Y-m-d H:i') }}">{{ $notification->created_at->diffForHumans() }}</span></td>
                            <td>
                                @if ($notification->read_at)
                                    <span class="badge badge-muted">Read</span>
                                @else
                                    <span class="badge badge-warning"><span class="badge-indicator" aria-hidden="true"></span>Unread</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-empty">No notifications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($notifications->hasPages())
            <div class="table-footer">
                {{ $notifications->links('pagination.app') }}
            </div>
        @endif
    </section>

@endsection

@push('scripts')
<script>
    document.getElementById('mark-all-read')?.addEventListener('click', async () => {
        await fetch('{{ route('notifications.read-all') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
        });
        window.location.reload();
    });
</script>
@endpush
