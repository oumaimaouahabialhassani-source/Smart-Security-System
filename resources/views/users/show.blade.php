@extends('layouts.app')

@section('title', $user->name . ' — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">User Details</h1>
            <p class="page-subtitle">Profile, activity and permissions for {{ $user->name }}.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back to Users</a>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">Edit User</a>
        </div>
    </div>

    {{-- Profile card --}}
    <section class="panel profile-card">
        <div class="profile-identity">
            <x-user-avatar :user="$user" size="xl" />
            <div>
                <h2 class="profile-name">{{ $user->name }}</h2>
                <p class="profile-role">{{ $user->role->label() }}</p>
                <x-status-badge :status="$user->status" />
            </div>
        </div>

        <dl class="profile-grid">
            <div class="profile-item"><dt>First Name</dt><dd>{{ $user->first_name }}</dd></div>
            <div class="profile-item"><dt>Last Name</dt><dd>{{ $user->last_name }}</dd></div>
            <div class="profile-item"><dt>Full Name</dt><dd>{{ $user->name }}</dd></div>
            <div class="profile-item"><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            <div class="profile-item"><dt>Phone</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Role</dt><dd>{{ $user->role->label() }}</dd></div>
            <div class="profile-item"><dt>Status</dt><dd>{{ $user->status->label() }}</dd></div>
            <div class="profile-item"><dt>Created At</dt><dd>{{ $user->created_at->format('M j, Y — H:i') }}</dd></div>
            <div class="profile-item"><dt>Updated At</dt><dd>{{ $user->updated_at->format('M j, Y — H:i') }}</dd></div>
            <div class="profile-item"><dt>Last Login</dt><dd>{{ $user->last_login?->format('M j, Y — H:i') ?? 'Never' }}</dd></div>
        </dl>
    </section>

    {{-- Tabs --}}
    <section class="panel">
        <div class="tabs" role="tablist" aria-label="User details tabs">
            <button type="button" class="tab active" role="tab" aria-selected="true" data-tab="activity">Activity</button>
            <button type="button" class="tab" role="tab" aria-selected="false" data-tab="permissions">Permissions</button>
        </div>

        {{-- Activity tab: placeholder timeline until Event Logs module lands. --}}
        <div class="tab-panel" id="tab-activity" role="tabpanel">
            <ol class="timeline">
                @foreach ([
                    ['event' => 'Logged In', 'detail' => 'Signed in from the web console', 'time' => $user->last_login?->diffForHumans() ?? 'Pending first login'],
                    ['event' => 'Changed Password', 'detail' => 'Password updated successfully', 'time' => 'Example event'],
                    ['event' => 'Created Device', 'detail' => 'Registered a new IoT device', 'time' => 'Example event'],
                    ['event' => 'Updated Camera', 'detail' => 'Modified camera "Entrance-01" settings', 'time' => 'Example event'],
                    ['event' => 'Logged Out', 'detail' => 'Session ended', 'time' => 'Example event'],
                ] as $entry)
                    <li class="timeline-item">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-body">
                            <span class="timeline-event">{{ $entry['event'] }}</span>
                            <span class="timeline-detail">{{ $entry['detail'] }}</span>
                            <span class="timeline-time">{{ $entry['time'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="muted tab-note">Real activity will appear here once the Event Logs module is connected.</p>
        </div>

        {{-- Permissions tab: preview of the future permission system. --}}
        <div class="tab-panel" id="tab-permissions" role="tabpanel" hidden>
            <div class="permissions-grid">
                @foreach ([
                    'Can Manage Users' => 'Create, edit and delete system users',
                    'Can Manage Cameras' => 'Configure and control security cameras',
                    'Can Manage Devices' => 'Register and manage IoT devices',
                    'Can View Dashboard' => 'Access the security overview dashboard',
                    'Can Manage Alerts' => 'Acknowledge and resolve security alerts',
                    'Can Manage Reports' => 'Generate and export system reports',
                    'Can Configure Settings' => 'Change global system configuration',
                ] as $permission => $description)
                    <label class="permission-card">
                        <input type="checkbox" disabled>
                        <span class="permission-info">
                            <span class="permission-name">{{ $permission }}</span>
                            <span class="permission-desc">{{ $description }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="muted tab-note">Permission management is coming soon — this preview shows the planned structure.</p>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach((t) => {
                t.classList.toggle('active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            document.querySelectorAll('.tab-panel').forEach((panel) => {
                panel.hidden = panel.id !== 'tab-' + tab.dataset.tab;
            });
        });
    });
</script>
@endpush
