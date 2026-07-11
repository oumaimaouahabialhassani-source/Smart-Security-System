@extends('layouts.app')

@section('title', $visit->full_name . ' — ' . config('app.name'))

@php($role = auth()->user()->role)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Visit Details</h1>
            <p class="page-subtitle">Visit {{ $visit->visit_code }} — {{ $visit->full_name }}</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('visitors.index') }}" class="btn btn-secondary">← Back to Visitors</a>
            @if ($role->canManageVisitors())
                <a href="{{ route('visitors.badge', $visit) }}" class="btn btn-secondary" target="_blank">Print Badge</a>
                <a href="{{ route('visitors.pass', $visit) }}" class="btn btn-secondary" target="_blank">Print Pass</a>
                <a href="{{ route('visitors.edit', $visit) }}" class="btn btn-primary">Edit Visit</a>
            @endif
        </div>
    </div>

    @if ($visit->blacklisted)
        <div class="flash flash-error" role="alert">
            ⚠ This visitor is <strong>blacklisted</strong> — check-in is refused. Contact the security supervisor.
        </div>
    @elseif ($visit->forgotCheckOut())
        <div class="flash flash-error" role="alert">
            ⚠ This visitor checked in {{ $visit->checked_in_at->diffForHumans() }} and never checked out.
        </div>
    @elseif ($visit->isOverstay())
        <div class="flash flash-error" role="alert">
            ⚠ Allowed visit duration exceeded — the visitor was expected to leave {{ $visit->expectedEndAt()->diffForHumans() }}.
        </div>
    @endif

    {{-- Identity card --}}
    <section class="panel profile-card">
        <div class="profile-identity">
            <x-visitor-avatar :visit="$visit" size="xl" />
            <div>
                <h2 class="profile-name">{{ $visit->full_name }}</h2>
                <p class="profile-role">{{ $visit->company ?? 'Individual visitor' }}</p>
                <x-status-badge :status="$visit->status" />
            </div>
        </div>

        <dl class="profile-grid">
            <div class="profile-item"><dt>Visit ID</dt><dd class="mono">{{ $visit->visit_code }}</dd></div>
            <div class="profile-item"><dt>{{ $visit->document_type->label() }}</dt><dd class="mono">{{ $visit->national_id }}</dd></div>
            <div class="profile-item"><dt>Phone</dt><dd>{{ $visit->phone }}</dd></div>
            <div class="profile-item"><dt>Email</dt><dd>{{ $visit->email ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Gender</dt><dd>{{ $visit->gender ? ucfirst($visit->gender) : '—' }}</dd></div>
            <div class="profile-item"><dt>Date of Birth</dt><dd>{{ $visit->date_of_birth?->format('M j, Y') ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Nationality</dt><dd>{{ $visit->nationality ?? '—' }}</dd></div>
            <div class="profile-item"><dt>Vehicle Plate</dt><dd>{{ $visit->vehicle_plate ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="panels-grid">
        {{-- Visit information --}}
        <div class="panel">
            <h2 class="panel-title">Visit Information</h2>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Person Visited</dt><dd>{{ $visit->host?->name ?? '—' }}</dd></div>
                <div class="profile-item"><dt>Department</dt><dd>{{ $visit->department }}</dd></div>
                <div class="profile-item"><dt>Purpose</dt><dd>{{ $visit->purpose }}</dd></div>
                <div class="profile-item"><dt>Visit Date</dt><dd>{{ $visit->visit_date->format('l, M j, Y') }}</dd></div>
                <div class="profile-item"><dt>Expected Check-In</dt><dd>{{ $visit->expected_check_in ? substr($visit->expected_check_in, 0, 5) : '—' }}</dd></div>
                <div class="profile-item"><dt>Expected Duration</dt><dd>{{ $visit->expected_duration_minutes }} minutes</dd></div>
                <div class="profile-item"><dt>Companions</dt><dd>{{ $visit->companions }}</dd></div>
                <div class="profile-item"><dt>Check-In</dt><dd>{{ $visit->checked_in_at?->format('M j — H:i') ?? 'Not yet' }}</dd></div>
                <div class="profile-item"><dt>Check-Out</dt><dd>{{ $visit->checked_out_at?->format('M j — H:i') ?? 'Not yet' }}</dd></div>
                <div class="profile-item"><dt>Duration</dt><dd>{{ $visit->durationLabel() ?? '—' }}</dd></div>
            </dl>

            @if ($role->canProcessVisits())
                <div class="form-actions">
                    @if ($visit->status === App\Enums\VisitStatus::Expected && ! $visit->blacklisted)
                        <form method="POST" action="{{ route('visitors.check-in', $visit) }}" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-primary" data-loading-text="Checking in…">Check-In Now</button>
                        </form>
                    @elseif ($visit->status === App\Enums\VisitStatus::Inside)
                        <form method="POST" action="{{ route('visitors.check-out', $visit) }}" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-danger" data-loading-text="Checking out…">Check-Out Now</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        {{-- Security information --}}
        <div class="panel">
            <h2 class="panel-title">Security Information</h2>
            <dl class="profile-grid">
                <div class="profile-item"><dt>Document Type</dt><dd>{{ $visit->document_type->label() }}</dd></div>
                <div class="profile-item"><dt>Badge Number</dt><dd class="mono">{{ $visit->badge_number ?? 'Issued at check-in' }}</dd></div>
                <div class="profile-item">
                    <dt>Badge State</dt>
                    <dd>
                        @if ($visit->status === App\Enums\VisitStatus::Inside)
                            <span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Active</span>
                        @elseif ($visit->badge_number)
                            <span class="badge badge-muted"><span class="badge-indicator" aria-hidden="true"></span>Disabled</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="profile-item"><dt>Access Level</dt><dd>{{ $visit->access_level->label() }}</dd></div>
                <div class="profile-item"><dt>Bag Inspection</dt><dd>{{ $visit->bag_inspected ? 'Yes — inspected' : 'No' }}</dd></div>
                <div class="profile-item"><dt>Special Permission</dt><dd>{{ $visit->special_permission ? 'Required' : 'Not required' }}</dd></div>
                <div class="profile-item"><dt>Blacklisted</dt><dd>{{ $visit->blacklisted ? '⚠ Yes' : 'No' }}</dd></div>
                <div class="profile-item"><dt>Registered By</dt><dd>{{ $visit->registrar?->name ?? '—' }}</dd></div>
                <div class="profile-item"><dt>Registered At</dt><dd>{{ $visit->created_at->format('M j, Y — H:i') }}</dd></div>
            </dl>

            @if ($visit->security_notes)
                <div class="info-note" role="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    {{ $visit->security_notes }}
                </div>
            @endif
        </div>
    </section>

    {{-- Visit history --}}
    <section class="panel panel-flush">
        <h2 class="panel-title panel-title-pad">Previous Visits by {{ $visit->full_name }}</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Date</th>
                        <th>Person Visited</th>
                        <th>Department</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Duration</th>
                        <th>Reception Employee</th>
                        <th>Security Notes</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $previous)
                        <tr>
                            <td class="mono"><a href="{{ route('visitors.show', $previous) }}">{{ $previous->visit_code }}</a></td>
                            <td>{{ $previous->visit_date->format('M j, Y') }}</td>
                            <td>{{ $previous->host?->name ?? '—' }}</td>
                            <td>{{ $previous->department }}</td>
                            <td>{{ $previous->checked_in_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $previous->checked_out_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $previous->durationLabel() ?? '—' }}</td>
                            <td>{{ $previous->registrar?->name ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($previous->security_notes, 40) ?: '—' }}</td>
                            <td><x-status-badge :status="$previous->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="table-empty">First visit — no previous history for this national ID.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection
