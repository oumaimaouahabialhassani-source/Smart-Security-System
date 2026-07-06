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
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <x-user-avatar :user="$user" />
                                    <span class="user-cell-name">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '—' }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td><x-status-badge :status="$user->status" /></td>
                            <td>
                                @if ($user->last_login)
                                    <span title="{{ $user->last_login->format('Y-m-d H:i') }}">{{ $user->last_login->diffForHumans() }}</span>
                                @else
                                    <span class="muted">Never</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('users.show', $user) }}" class="action-btn" title="View" aria-label="View {{ $user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <a href="{{ route('users.edit', $user) }}" class="action-btn" title="Edit" aria-label="Edit {{ $user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></svg>
                                    </a>
                                    <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                            aria-label="Delete {{ $user->name }}"
                                            data-action="{{ route('users.destroy', $user) }}"
                                            data-name="{{ $user->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No users match your search or filters.</td>
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

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') modal.hidden = true;
    });
</script>
@endpush
