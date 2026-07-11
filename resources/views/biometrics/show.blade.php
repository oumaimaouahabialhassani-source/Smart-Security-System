@extends('layouts.app')

@section('title', $profile->user->name . ' — Biometrics — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Biometric Profile</h1>
            <p class="page-subtitle">{{ $profile->employee_code }} — {{ $profile->user->name }}</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('biometrics.index') }}" class="btn btn-secondary">← Back to Biometrics</a>
            @if ($role->canManageBiometrics())
                <a href="{{ route('biometrics.edit', $profile) }}" class="btn btn-primary">Edit Profile</a>
            @endif
        </div>
    </div>

    {{-- Identity card --}}
    <section class="panel profile-card">
        <div class="profile-identity">
            <x-user-avatar :user="$profile->user" size="xl" />
            <div>
                <h2 class="profile-name">{{ $profile->user->name }}</h2>
                <p class="profile-role">{{ $profile->position }} — {{ $profile->department }}</p>
                <x-status-badge :status="$profile->status" />
            </div>
        </div>

        <dl class="profile-grid">
            <div class="profile-item"><dt>Employee ID</dt><dd class="mono">{{ $profile->employee_code }}</dd></div>
            <div class="profile-item"><dt>Email</dt><dd>{{ $profile->user->email }}</dd></div>
            <div class="profile-item"><dt>Phone</dt><dd>{{ $profile->user->phone ?? '—' }}</dd></div>
            <div class="profile-item"><dt>System Role</dt><dd>{{ $profile->user->role->label() }}</dd></div>
            <div class="profile-item"><dt>Assigned Device</dt><dd>{{ $profile->assignedDevice?->name ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Enrolled Since</dt><dd>{{ $profile->created_at->format('M j, Y') }}</dd></div>
        </dl>
    </section>

    {{-- Enrollment status --}}
    <section class="panels-grid">
        <div class="panel">
            <h2 class="panel-title">Enrollment Status ({{ $profile->enrolledCount() }}/3 modalities)</h2>
            <dl class="profile-grid">
                <div class="profile-item">
                    <dt>◉ Face Recognition</dt>
                    <dd>
                        @if ($profile->face_enrolled_at)
                            <span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                            <p class="muted enroll-detail">{{ $profile->face_enrolled_at->format('M j, Y — H:i') }} · quality {{ $profile->face_quality }}%</p>
                        @else
                            <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                        @endif
                    </dd>
                </div>
                <div class="profile-item">
                    <dt>❋ Fingerprint</dt>
                    <dd>
                        @if ($profile->fingerprint_enrolled_at)
                            <span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                            <p class="muted enroll-detail">{{ $profile->fingerprint_finger }} · {{ $profile->fingerprint_enrolled_at->format('M j, Y') }} · quality {{ $profile->fingerprint_quality }}%</p>
                        @else
                            <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                        @endif
                    </dd>
                </div>
                <div class="profile-item">
                    <dt>◎ Iris Scan</dt>
                    <dd>
                        @if ($profile->iris_enrolled_at)
                            <span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Registered</span>
                            <p class="muted enroll-detail">{{ $profile->iris_enrolled_at->format('M j, Y — H:i') }}</p>
                        @else
                            <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Not Registered</span>
                            <p class="muted enroll-detail">Iris capture requires dedicated hardware.</p>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="panel">
            <h2 class="panel-title">Verification Summary</h2>
            <div class="mini-stats">
                <div class="mini-stat"><span class="mini-stat-value">{{ $history->count() }}</span><span class="mini-stat-label">Recent attempts</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $history->where('result', App\Enums\BiometricResult::Success)->count() }}</span><span class="mini-stat-label">Success</span></div>
                <div class="mini-stat"><span class="mini-stat-value">{{ $history->where('result', App\Enums\BiometricResult::Failed)->count() }}</span><span class="mini-stat-label">Failed</span></div>
            </div>
            <p class="muted tab-note">Showing the {{ $history->count() }} most recent attempts for this profile.</p>

            @if ($profile->security_notes)
                <div class="info-note" role="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    {{ $profile->security_notes }}
                </div>
            @endif
        </div>
    </section>

    {{-- Verification history --}}
    <section class="panel panel-flush">
        <h2 class="panel-title panel-title-pad">Verification History</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Method</th>
                        <th>Device</th>
                        <th>Duration</th>
                        <th>Result</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $log)
                        <tr>
                            <td>{{ $log->happened_at->format('M j, Y') }}</td>
                            <td>{{ $log->happened_at->format('H:i:s') }}</td>
                            <td><span aria-hidden="true">{{ $log->method->icon() }}</span> {{ $log->method->label() }}</td>
                            <td>{{ $log->device?->name ?? '—' }}</td>
                            <td>{{ $log->duration_ms }} ms</td>
                            <td><x-status-badge :status="$log->result" /></td>
                            <td>{{ $log->detail ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">No verification attempts recorded for this profile yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection
