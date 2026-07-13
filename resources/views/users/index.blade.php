@extends('layouts.app')

@section('title', 'Users Management — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Users Management</h1>
            <p class="page-subtitle">Manage all system users, their roles, permissions, and account status.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add User
        </a>
    </div>

    {{-- Search & filters --}}
    <form method="GET" action="{{ route('users.index') }}" class="panel filters-bar" role="search">
        <div class="filter-field filter-grow">
            <label for="search">Search</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="Name or email…">
        </div>

        <div class="filter-field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'role', 'status']))
                <a href="{{ route('users.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    {{-- Users table --}}
    <section class="panel panel-flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Permissions</th>
                        <th>Last Login</th>
                        <th>Created By</th>
                        <th>Updated By</th>
                        <th>Activity</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php($actor = auth()->user())
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <x-user-avatar :user="$user" />
                                    <div>
                                        <span class="user-cell-name">{{ $user->name }}</span>
                                        <div class="cell-sub">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $user->role->label() }}
                                @if (! $user->is($actor) && $actor->can('changeRole', $user))
                                    @if ($user->role === App\Enums\UserRole::Viewer)
                                        <div><button type="button" class="btn btn-ghost js-role" style="padding:4px 10px; font-size:12px"
                                                data-action="{{ route('users.role', $user) }}"
                                                data-role="{{ App\Enums\UserRole::SuperAdmin->value }}"
                                                data-name="{{ $user->name }}"
                                                data-label="Promote to Super Admin">Promote to Super Admin</button></div>
                                    @elseif (! App\Policies\UserPolicy::isLastActiveAdministrator($user))
                                        <div><button type="button" class="btn btn-ghost js-role" style="padding:4px 10px; font-size:12px"
                                                data-action="{{ route('users.role', $user) }}"
                                                data-role="{{ App\Enums\UserRole::Viewer->value }}"
                                                data-name="{{ $user->name }}"
                                                data-label="Demote to Viewer">Demote to Viewer</button></div>
                                    @endif
                                @endif
                            </td>
                            <td><x-status-badge :status="$user->status" /></td>
                            <td>
                                @if ($user->role === App\Enums\UserRole::SuperAdmin)
                                    <span class="badge badge-danger">Full Access</span>
                                @else
                                    <span class="badge badge-muted">Read Only</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->last_login)
                                    <span title="{{ $user->last_login->format('Y-m-d H:i') }}">{{ $user->last_login->diffForHumans() }}</span>
                                @else
                                    <span class="muted">Never</span>
                                @endif
                            </td>
                            <td>{{ $user->creator?->name ?? '—' }}</td>
                            <td>{{ $user->updater?->name ?? '—' }}</td>
                            <td>
                                @if ($actor->role->canViewAuditLogs())
                                    <a href="{{ route('audit.index', ['search' => $user->name]) }}" class="btn btn-ghost">View</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('users.show', $user) }}" class="action-btn" title="View" aria-label="View {{ $user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" class="action-btn" title="Edit" aria-label="Edit {{ $user->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                        </a>
                                    @endcan
                                    @if (! $user->is($actor) && ! App\Policies\UserPolicy::isLastActiveAdministrator($user) && $actor->can('delete', $user))
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                aria-label="Delete {{ $user->name }}"
                                                data-action="{{ route('users.destroy', $user) }}"
                                                data-name="{{ $user->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No users match your search or filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="table-footer">
                {{ $users->links('pagination.app') }}
            </div>
        @endif
    </section>

    {{-- Role change confirmation modal --}}
    <div class="modal-backdrop" id="role-modal" hidden>
        <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="role-modal-title">
            <h3 class="modal-title" id="role-modal-title">Change Role</h3>
            <p class="modal-text" id="role-modal-text"></p>
            <p class="modal-target" id="role-modal-name"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-role>Cancel</button>
                <form method="POST" id="role-modal-form" data-loading>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="role" id="role-modal-role">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="modal-backdrop" id="delete-modal" hidden>
        <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-modal-title">
            <h3 class="modal-title" id="delete-modal-title">Delete User</h3>
            <p class="modal-text">Are you sure you want to delete this user?</p>
            <p class="modal-target" id="delete-modal-name"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-modal>Cancel</button>
                <form method="POST" id="delete-modal-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    const modal = document.getElementById('delete-modal');
    const form = document.getElementById('delete-modal-form');
    const nameEl = document.getElementById('delete-modal-name');

    document.querySelectorAll('.js-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.action;
            nameEl.textContent = btn.dataset.name;
            modal.hidden = false;
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.closest('[data-close-modal]')) modal.hidden = true;
    });

    // Promote/Demote confirmation
    const roleModal = document.getElementById('role-modal');
    const roleForm = document.getElementById('role-modal-form');

    document.querySelectorAll('.js-role').forEach((btn) => {
        btn.addEventListener('click', () => {
            roleForm.action = btn.dataset.action;
            document.getElementById('role-modal-role').value = btn.dataset.role;
            document.getElementById('role-modal-title').textContent = btn.dataset.label;
            document.getElementById('role-modal-text').textContent =
                btn.dataset.role === 'super_admin'
                    ? 'This user will get FULL, unrestricted access to the system. Continue?'
                    : 'This user will lose all management access and become read-only. Continue?';
            document.getElementById('role-modal-name').textContent = btn.dataset.name;
            roleModal.hidden = false;
        });
    });

    roleModal.addEventListener('click', (e) => {
        if (e.target === roleModal || e.target.closest('[data-close-role]')) roleModal.hidden = true;
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { modal.hidden = true; roleModal.hidden = true; }
    });
</script>
@endpush
