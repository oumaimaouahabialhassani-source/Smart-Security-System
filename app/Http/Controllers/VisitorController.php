<?php

namespace App\Http\Controllers;

use App\Enums\VisitAccessLevel;
use App\Enums\VisitDocumentType;
use App\Enums\VisitStatus;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\AccessEvent;
use App\Models\Door;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorController extends Controller
{
    /**
     * List visits with summary stats, alerts, search and filters.
     */
    public function index(Request $request): View
    {
        $visits = Visit::query()
            ->with('host')
            ->search($request->query('search'))
            ->when($request->query('host'), fn ($q, $v) => $q->where('host_user_id', $v))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department', $v))
            ->when($request->query('company'), fn ($q, $v) => $q->where('company', 'like', "%{$v}%"))
            ->when($request->query('date'), fn ($q, $v) => $q->whereDate('visit_date', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        return view('visitors.index', [
            'visits' => $visits,
            'stats' => $this->stats(),
            'alerts' => $this->alerts(),
            'statuses' => VisitStatus::cases(),
            'hosts' => User::orderBy('first_name')->get(),
            'departments' => $this->departments(),
        ]);
    }

    /**
     * Show the form for registering a new visitor.
     */
    public function create(): View
    {
        abort_unless(auth()->user()->role->canManageVisitors(), 403);

        return view('visitors.create', $this->formOptions());
    }

    /**
     * Register a new visit.
     */
    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $data = $this->preparedData($request);
        $data['registered_by'] = $request->user()->id;
        $data['status'] = VisitStatus::Expected;

        $visit = Visit::create($data);

        return redirect()->route('visitors.index')
            ->with('status', "Visitor {$visit->full_name} has been registered ({$visit->visit_code}).");
    }

    /**
     * Display a visit with the visitor's full history.
     */
    public function show(Visit $visitor): View
    {
        return view('visitors.show', [
            'visit' => $visitor->load(['host', 'registrar']),
            'history' => $visitor->previousVisits()->with(['host', 'registrar'])->limit(10)->get(),
        ]);
    }

    /**
     * Show the form for editing a visit.
     */
    public function edit(Visit $visitor): View
    {
        abort_unless(auth()->user()->role->canManageVisitors(), 403);

        return view('visitors.edit', ['visit' => $visitor] + $this->formOptions());
    }

    /**
     * Update the given visit.
     */
    public function update(UpdateVisitRequest $request, Visit $visitor): RedirectResponse
    {
        $visitor->update($this->preparedData($request, $visitor));

        return redirect()->route('visitors.index')
            ->with('status', "Visit {$visitor->visit_code} ({$visitor->full_name}) has been updated.");
    }

    /**
     * Delete the given visit. Administrators only.
     */
    public function destroy(Visit $visitor): RedirectResponse
    {
        abort_unless(auth()->user()->role->canDeleteVisits(), 403);

        if ($visitor->photo) {
            Storage::disk('public')->delete($visitor->photo);
        }

        $label = "{$visitor->visit_code} ({$visitor->full_name})";
        $visitor->delete();

        return redirect()->route('visitors.index')
            ->with('status', "Visit {$label} has been deleted.");
    }

    /**
     * Check the visitor in: stamp the time, issue a badge, flag as inside.
     */
    public function checkIn(Visit $visitor): RedirectResponse
    {
        abort_unless(auth()->user()->role->canProcessVisits(), 403);

        if ($visitor->status !== VisitStatus::Expected) {
            return back()->with('error', "Only expected visits can be checked in — {$visitor->visit_code} is {$visitor->status->label()}.");
        }

        if ($visitor->blacklisted) {
            $this->recordAccessEvent($visitor, 'entry', \App\Enums\AccessResult::Unauthorized, 'Blacklisted visitor refused at reception');

            \App\Models\Alert::raise(
                'Unauthorized Access',
                \App\Enums\AlertSeverity::Critical,
                "Blacklisted visitor {$visitor->full_name} attempted to check in at reception",
                ['visit_id' => $visitor->id]
            );

            return back()->with('error', "{$visitor->full_name} is blacklisted. Check-in refused — contact the security supervisor.");
        }

        $visitor->update([
            'checked_in_at' => now(),
            'status' => VisitStatus::Inside,
            'badge_number' => $visitor->badge_number ?: 'BDG-'.strtoupper(Str::random(6)),
        ]);

        $this->recordAccessEvent($visitor, 'entry', \App\Enums\AccessResult::Granted, 'Visitor check-in at reception');

        return back()->with('status', "{$visitor->full_name} checked in — badge {$visitor->badge_number} is now active.");
    }

    /**
     * Check the visitor out: stamp the time and disable the badge.
     */
    public function checkOut(Visit $visitor): RedirectResponse
    {
        abort_unless(auth()->user()->role->canProcessVisits(), 403);

        if ($visitor->status !== VisitStatus::Inside) {
            return back()->with('error', "Only visitors inside the building can be checked out — {$visitor->visit_code} is {$visitor->status->label()}.");
        }

        $visitor->update([
            'checked_out_at' => now(),
            'status' => VisitStatus::CheckedOut,
        ]);

        $this->recordAccessEvent($visitor, 'exit', \App\Enums\AccessResult::Granted, 'Visitor check-out at reception');

        return back()->with('status', "{$visitor->full_name} checked out — visit duration {$visitor->durationLabel()}. Badge {$visitor->badge_number} is disabled.");
    }

    /**
     * Record the check-in/out in the Access Control logs.
     */
    private function recordAccessEvent(Visit $visit, string $direction, \App\Enums\AccessResult $result, string $detail): void
    {
        AccessEvent::create([
            'kind' => 'access',
            'visit_id' => $visit->id,
            'person_name' => $visit->full_name,
            'badge_id' => $visit->badge_number,
            'door_id' => Door::where('name', 'like', '%Reception%')->value('id') ?? Door::query()->value('id'),
            'direction' => $direction,
            'result' => $result,
            'method' => 'badge',
            'ip_address' => request()->ip(),
            'detail' => $detail,
            'happened_at' => now(),
        ]);
    }

    /**
     * Printable visitor badge (card format).
     */
    public function badge(Visit $visitor): View
    {
        abort_unless(auth()->user()->role->canManageVisitors(), 403);

        return view('visitors.badge', ['visit' => $visitor->load('host'), 'format' => 'badge']);
    }

    /**
     * Printable visitor pass (full-page format).
     */
    public function pass(Visit $visitor): View
    {
        abort_unless(auth()->user()->role->canManageVisitors(), 403);

        return view('visitors.badge', ['visit' => $visitor->load(['host', 'registrar']), 'format' => 'pass']);
    }

    /**
     * Export the filtered visit list as a CSV (opens in Excel).
     * Restricted: rows contain national IDs and other visitor PII.
     */
    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageVisitors(), 403);

        $visits = Visit::query()
            ->with('host')
            ->search($request->query('search'))
            ->when($request->query('host'), fn ($q, $v) => $q->where('host_user_id', $v))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department', $v))
            ->when($request->query('company'), fn ($q, $v) => $q->where('company', 'like', "%{$v}%"))
            ->when($request->query('date'), fn ($q, $v) => $q->whereDate('visit_date', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('visit_date')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($visits) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Visit ID', 'Full Name', 'National ID', 'Company', 'Person Visited',
                'Department', 'Purpose', 'Visit Date', 'Check-In', 'Check-Out',
                'Duration', 'Status', 'Badge', 'Access Level',
            ]);

            foreach ($visits as $visit) {
                fputcsv($out, [
                    $visit->visit_code,
                    $visit->full_name,
                    $visit->national_id,
                    $visit->company,
                    $visit->host?->name,
                    $visit->department,
                    $visit->purpose,
                    $visit->visit_date->format('Y-m-d'),
                    $visit->checked_in_at?->format('H:i'),
                    $visit->checked_out_at?->format('H:i'),
                    $visit->durationLabel(),
                    $visit->status->label(),
                    $visit->badge_number,
                    $visit->access_level->label(),
                ]);
            }

            fclose($out);
        }, 'visitors-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Headline statistics for the stat cards.
     *
     * @return array<int, array{label: string, value: string|int, meta: string}>
     */
    private function stats(): array
    {
        $today = Visit::today()->count();
        $inside = Visit::inside()->count();
        $week = Visit::whereBetween('visit_date', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $month = Visit::whereBetween('visit_date', [now()->startOfMonth(), now()->endOfMonth()])->count();

        $topDepartment = Visit::selectRaw('department, count(*) as visits')
            ->groupBy('department')
            ->orderByDesc('visits')
            ->first();

        // timestampdiff() is MySQL-only; rescue() keeps the sqlite test runs working.
        $avgMinutes = (int) rescue(fn () => Visit::whereNotNull('checked_in_at')
            ->whereNotNull('checked_out_at')
            ->selectRaw('avg(timestampdiff(minute, checked_in_at, checked_out_at)) as avg_minutes')
            ->value('avg_minutes'), 0, false);

        return [
            ['label' => 'Visitors Today', 'value' => $today, 'meta' => now()->format('l, M j')],
            ['label' => 'Currently Inside', 'value' => $inside, 'meta' => $inside > 0 ? 'Badges active' : 'Building clear'],
            ['label' => 'Visits This Week', 'value' => $week, 'meta' => 'Mon — Sun'],
            ['label' => 'Visits This Month', 'value' => $month, 'meta' => now()->format('F Y')],
            ['label' => 'Most Visited Department', 'value' => $topDepartment->department ?? '—', 'meta' => $topDepartment ? $topDepartment->visits.' visits' : 'No visits yet'],
            ['label' => 'Average Visit Duration', 'value' => $avgMinutes >= 60 ? intdiv($avgMinutes, 60).'h '.($avgMinutes % 60).'m' : $avgMinutes.'m', 'meta' => 'Completed visits'],
        ];
    }

    /**
     * Live visitor alerts: overstays, forgotten check-outs and
     * blacklisted visitors with a pending or active visit.
     *
     * @return Collection<int, array{severity: string, label: string, visit: Visit}>
     */
    private function alerts(): Collection
    {
        $alerts = collect();

        Visit::inside()->get()->each(function (Visit $visit) use ($alerts) {
            if ($visit->forgotCheckOut()) {
                $alerts->push([
                    'severity' => 'danger',
                    'label' => 'Forgot to Check Out',
                    'detail' => 'Checked in '.$visit->checked_in_at->diffForHumans().' and never left.',
                    'visit' => $visit,
                ]);
            } elseif ($visit->isOverstay()) {
                $alerts->push([
                    'severity' => 'warning',
                    'label' => 'Visit Duration Exceeded',
                    'detail' => 'Expected to leave '.$visit->expectedEndAt()->diffForHumans().'.',
                    'visit' => $visit,
                ]);
            }
        });

        Visit::where('blacklisted', true)
            ->whereIn('status', [VisitStatus::Expected, VisitStatus::Inside])
            ->get()
            ->each(fn (Visit $visit) => $alerts->push([
                'severity' => 'danger',
                'label' => 'Blacklisted Visitor',
                'detail' => $visit->status === VisitStatus::Inside ? 'Currently inside the building.' : 'Has a pending visit today.',
                'visit' => $visit,
            ]));

        return $alerts
            ->sortBy(fn (array $alert) => $alert['severity'] === 'danger' ? 0 : 1)
            ->values();
    }

    /**
     * Normalize checkbox and file input before persisting.
     *
     * @return array<string, mixed>
     */
    private function preparedData(StoreVisitRequest $request, ?Visit $visit = null): array
    {
        $data = $request->validated();
        $data['bag_inspected'] = $request->boolean('bag_inspected');
        $data['special_permission'] = $request->boolean('special_permission');
        $data['blacklisted'] = $request->boolean('blacklisted');
        unset($data['status']); // status only changes through check-in/out actions

        if ($request->hasFile('photo')) {
            if ($visit?->photo) {
                Storage::disk('public')->delete($visit->photo);
            }
            $data['photo'] = $request->file('photo')->store('visitors', 'public');
        }

        return $data;
    }

    /**
     * Department options: the fixed list merged with values already in use.
     *
     * @return Collection<int, string>
     */
    private function departments(): Collection
    {
        return collect(\App\Support\Departments::ALL)
            ->merge(Visit::distinct()->pluck('department'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Shared select options for the create/edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'hosts' => User::orderBy('first_name')->get(),
            'departments' => $this->departments(),
            'documentTypes' => VisitDocumentType::cases(),
            'accessLevels' => VisitAccessLevel::cases(),
        ];
    }
}
