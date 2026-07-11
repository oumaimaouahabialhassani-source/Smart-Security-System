<?php

namespace App\Http\Controllers;

use App\Enums\AccessLevel;
use App\Enums\AccessResult;
use App\Enums\CameraStatus;
use App\Enums\DoorStatus;
use App\Enums\EventSeverity;
use App\Models\AccessEvent;
use App\Models\AccessPermission;
use App\Models\Camera;
use App\Models\Door;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessController extends Controller
{
    /**
     * The Access Control dashboard: stats, charts, permissions,
     * temporary access, doors, incidents timeline and live feed.
     */
    public function index(Request $request): View
    {
        $permissions = AccessPermission::query()
            ->with(['user', 'host', 'doors'])
            ->where('type', 'permanent')
            ->search($request->query('search'))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department', $v))
            ->when($request->query('level'), fn ($q, $v) => $q->where('access_level', $v))
            ->when($request->query('building'), fn ($q, $v) => $q->where('building', $v))
            ->when($request->query('door'), fn ($q, $v) => $q->whereHas('doors', fn ($q) => $q->where('doors.id', $v)))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('active', true))
            ->when($request->query('status') === 'disabled', fn ($q) => $q->where('active', false))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $doors = Door::with(['device', 'camera'])->orderBy('building')->orderBy('name')->get();

        return view('access.index', [
            'stats' => $this->stats(),
            'hourly' => $hourly = $this->hourlyAttempts(),
            'maxHourly' => max(max(array_column($hourly, 'count')), 1),
            'grantRate' => $this->grantRate(),
            'weekly' => $weekly = $this->weeklyTrend(),
            'maxWeekly' => max(max(array_column($weekly, 'count')), 1),
            'topDoors' => $this->topList(AccessEvent::access()->whereNotNull('door_id'), 'door_id', fn ($id) => $doors->firstWhere('id', $id)?->name ?? '—'),
            'topDepartments' => $this->departmentBreakdown(),
            'permissions' => $permissions,
            'temporary' => AccessPermission::with(['host', 'doors'])->where('type', 'temporary')->latest()->limit(5)->get(),
            'doors' => $doors,
            'incidents' => AccessEvent::security()->with('door')->orderByDesc('happened_at')->limit(8)->get(),
            'feed' => AccessEvent::access()->with(['user', 'door', 'device'])->orderByDesc('happened_at')->limit(8)->get(),
            'alerts' => $this->alerts($doors),
            'employees' => User::orderBy('first_name')->get(),
            'levels' => AccessLevel::cases(),
            'departments' => AccessPermission::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'buildings' => Door::distinct()->orderBy('building')->pluck('building'),
        ]);
    }

    /**
     * Full access log with filters.
     */
    public function logs(Request $request): View
    {
        $logs = AccessEvent::access()
            ->with(['user', 'visit', 'door', 'device', 'camera'])
            ->search($request->query('search'))
            ->when($request->query('door'), fn ($q, $v) => $q->where('door_id', $v))
            ->when($request->query('result'), fn ($q, $v) => $q->where('result', $v))
            ->when($request->query('device'), fn ($q, $v) => $q->where('device_id', $v))
            ->when($request->query('direction'), fn ($q, $v) => $q->where('direction', $v))
            ->when($request->query('date'), fn ($q, $v) => $q->whereBetween('happened_at', ["{$v} 00:00:00", "{$v} 23:59:59"]))
            ->orderByDesc('happened_at')
            ->paginate(12)
            ->withQueryString();

        return view('access.logs', [
            'logs' => $logs,
            'doors' => Door::orderBy('name')->get(),
            'devices' => \App\Models\Device::orderBy('name')->get(),
            'results' => AccessResult::cases(),
        ]);
    }

    /**
     * Latest access events as JSON, polled by the live activity feed.
     */
    public function feed(): JsonResponse
    {
        $events = AccessEvent::access()
            ->with(['user', 'door', 'device'])
            ->orderByDesc('happened_at')
            ->limit(8)
            ->get()
            ->map(fn (AccessEvent $event) => [
                'id' => $event->id,
                'name' => $event->person_name,
                'initials' => strtoupper(mb_substr($event->person_name, 0, 1)),
                'door' => $event->door?->name ?? '—',
                'time' => $event->happened_at->format('H:i:s'),
                'result' => $event->result?->label(),
                'badge' => $event->result?->badge(),
                'device' => $event->device?->name ?? '—',
            ]);

        return response()->json($events);
    }

    /**
     * Export the filtered access logs as CSV (opens in Excel).
     */
    public function exportLogs(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        $logs = AccessEvent::access()
            ->with(['door', 'device'])
            ->search($request->query('search'))
            ->when($request->query('door'), fn ($q, $v) => $q->where('door_id', $v))
            ->when($request->query('result'), fn ($q, $v) => $q->where('result', $v))
            ->when($request->query('date'), fn ($q, $v) => $q->whereBetween('happened_at', ["{$v} 00:00:00", "{$v} 23:59:59"]))
            ->orderByDesc('happened_at')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Time', 'Person', 'Badge', 'Door', 'Building', 'Direction', 'Result', 'Method', 'Device', 'IP Address', 'Detail']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->happened_at->format('Y-m-d'),
                    $log->happened_at->format('H:i:s'),
                    $log->person_name,
                    $log->badge_id,
                    $log->door?->name,
                    $log->door?->building,
                    $log->direction,
                    $log->result?->label(),
                    $log->method,
                    $log->device?->name,
                    $log->ip_address,
                    $log->detail,
                ]);
            }

            fclose($out);
        }, 'access-logs-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Export the access permissions as CSV (opens in Excel).
     */
    public function exportPermissions(): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        $permissions = AccessPermission::with(['user', 'doors'])->get();

        return response()->streamDownload(function () use ($permissions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Badge', 'Holder', 'Type', 'Department', 'Position', 'Access Level', 'Doors', 'Building', 'Schedule', 'Valid From', 'Valid Until', 'Status']);

            foreach ($permissions as $permission) {
                fputcsv($out, [
                    $permission->badge_id,
                    $permission->holderName(),
                    $permission->type,
                    $permission->department,
                    $permission->position,
                    $permission->access_level->label(),
                    $permission->doors->pluck('name')->implode(' | '),
                    $permission->building,
                    $permission->scheduleLabel(),
                    $permission->valid_from->format('Y-m-d'),
                    $permission->valid_until?->format('Y-m-d'),
                    $permission->active ? 'Active' : 'Disabled',
                ]);
            }

            fclose($out);
        }, 'access-permissions-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Lock a single door.
     */
    public function lockDoor(Door $door): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        $door->update(['status' => DoorStatus::Locked, 'last_activity_at' => now()]);
        $this->recordDoorIncident($door, 'Door locked from the console by '.auth()->user()->name, EventSeverity::Low);

        return back()->with('status', "{$door->name} is now locked.");
    }

    /**
     * Unlock a single door.
     */
    public function unlockDoor(Door $door): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        $door->update(['status' => DoorStatus::Closed, 'last_activity_at' => now()]);
        $this->recordDoorIncident($door, 'Door unlocked from the console by '.auth()->user()->name, EventSeverity::Low);

        return back()->with('status', "{$door->name} is now unlocked.");
    }

    /**
     * Emergency lockdown: lock every door. Administrators only.
     */
    public function lockAll(): RedirectResponse
    {
        abort_unless(auth()->user()->role->canAdministerAccess(), 403);

        $count = Door::whereNot('status', DoorStatus::Offline)->update(['status' => DoorStatus::Locked, 'last_activity_at' => now()]);

        AccessEvent::create([
            'kind' => 'security',
            'person_name' => auth()->user()->name,
            'severity' => EventSeverity::Critical,
            'detail' => 'LOCKDOWN — all doors locked from the console',
            'ip_address' => request()->ip(),
            'happened_at' => now(),
        ]);

        \App\Models\Alert::raise(
            'Intrusion Detected',
            \App\Enums\AlertSeverity::Critical,
            'Emergency lockdown engaged by '.auth()->user()->name." — {$count} doors locked",
            ['user_id' => auth()->id()]
        );

        return back()->with('status', "Lockdown engaged — {$count} doors locked.");
    }

    /**
     * Write a door incident into the security timeline.
     */
    private function recordDoorIncident(Door $door, string $detail, EventSeverity $severity): void
    {
        AccessEvent::create([
            'kind' => 'security',
            'person_name' => auth()->user()->name,
            'door_id' => $door->id,
            'severity' => $severity,
            'detail' => $detail,
            'ip_address' => request()->ip(),
            'happened_at' => now(),
        ]);
    }

    /**
     * Stat cards with percentage change vs yesterday.
     *
     * @return array<int, array{label: string, value: int, meta: string, delta: int|null}>
     */
    private function stats(): array
    {
        $entriesToday = AccessEvent::access()->today()->where('direction', 'entry')->where('result', AccessResult::Granted)->count();
        $entriesYesterday = $this->yesterdayCount('entry', AccessResult::Granted);

        $exitsToday = AccessEvent::access()->today()->where('direction', 'exit')->count();
        $exitsYesterday = AccessEvent::access()->whereBetween('happened_at', [today()->subDay(), today()->subDay()->endOfDay()])->where('direction', 'exit')->count();

        $deniedToday = AccessEvent::access()->today()->whereNot('result', AccessResult::Granted)->count();
        $deniedYesterday = AccessEvent::access()->whereBetween('happened_at', [today()->subDay(), today()->subDay()->endOfDay()])->whereNot('result', AccessResult::Granted)->count();

        $inside = max($entriesToday - $exitsToday, 0);

        return [
            ['label' => "Today's Entries", 'value' => $entriesToday, 'meta' => 'Granted entries', 'delta' => $this->delta($entriesToday, $entriesYesterday)],
            ['label' => "Today's Exits", 'value' => $exitsToday, 'meta' => 'Recorded exits', 'delta' => $this->delta($exitsToday, $exitsYesterday)],
            ['label' => 'Denied Attempts', 'value' => $deniedToday, 'meta' => 'All denial reasons', 'delta' => $this->delta($deniedToday, $deniedYesterday)],
            ['label' => 'Currently Inside', 'value' => $inside, 'meta' => 'Entries minus exits today', 'delta' => null],
        ];
    }

    private function yesterdayCount(string $direction, AccessResult $result): int
    {
        return AccessEvent::access()
            ->whereBetween('happened_at', [today()->subDay(), today()->subDay()->endOfDay()])
            ->where('direction', $direction)
            ->where('result', $result)
            ->count();
    }

    /**
     * Percentage change vs yesterday, or null when yesterday was empty.
     */
    private function delta(int $today, int $yesterday): ?int
    {
        return $yesterday > 0 ? (int) round(($today - $yesterday) / $yesterday * 100) : null;
    }

    /**
     * Today's attempts bucketed into two-hour slots, for the bar chart.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function hourlyAttempts(): array
    {
        $byHour = AccessEvent::access()->today()
            ->pluck('happened_at')
            ->countBy(fn ($at) => intdiv((int) $at->format('G'), 2));

        return collect(range(0, 11))
            ->map(fn (int $slot) => [
                'day' => ($slot * 2).'h',
                'count' => $byHour->get($slot, 0),
            ])
            ->all();
    }

    /**
     * Granted vs denied over the last 7 days, for the donut chart.
     *
     * @return array{percent: int, granted: int, denied: int, total: int}
     */
    private function grantRate(): array
    {
        $since = now()->subDays(6)->startOfDay();
        $total = AccessEvent::access()->where('happened_at', '>=', $since)->count();
        $granted = AccessEvent::access()->where('happened_at', '>=', $since)->where('result', AccessResult::Granted)->count();

        return [
            'percent' => $total > 0 ? (int) round($granted / $total * 100) : 100,
            'granted' => $granted,
            'denied' => $total - $granted,
            'total' => $total,
        ];
    }

    /**
     * Access events per day over the last seven days.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function weeklyTrend(): array
    {
        $since = now()->subDays(6)->startOfDay();

        $countsByDate = AccessEvent::access()->where('happened_at', '>=', $since)
            ->pluck('happened_at')
            ->countBy(fn ($at) => $at->format('Y-m-d'));

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($countsByDate) {
                $day = now()->subDays($daysAgo);

                return ['day' => $day->format('D'), 'count' => $countsByDate->get($day->format('Y-m-d'), 0)];
            })
            ->all();
    }

    /**
     * Top-5 ranking with percentages for the "most used" lists.
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function topList($query, string $column, callable $labeler): Collection
    {
        $rows = $query->selectRaw("{$column} as k, count(*) as c")
            ->where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => [
            'label' => $labeler($row->k),
            'count' => $row->c,
            'percent' => (int) round($row->c / $max * 100),
        ]);
    }

    /**
     * Access volume by permission-holder department (last 7 days).
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function departmentBreakdown(): Collection
    {
        $rows = AccessEvent::query()
            ->where('access_events.kind', 'access')
            ->where('access_events.happened_at', '>=', now()->subDays(6)->startOfDay())
            ->whereNotNull('access_events.badge_id')
            ->join('access_permissions', 'access_permissions.badge_id', '=', 'access_events.badge_id')
            ->selectRaw('access_permissions.department as k, count(*) as c')
            ->whereNotNull('access_permissions.department')
            ->groupBy('k')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => [
            'label' => $row->k,
            'count' => $row->c,
            'percent' => (int) round($row->c / $max * 100),
        ]);
    }

    /**
     * Security notifications: offline hardware, expired badges,
     * repeated failures and unauthorized attempts.
     *
     * @param Collection<int, Door> $doors
     * @return Collection<int, array{severity: string, label: string, detail: string}>
     */
    private function alerts(Collection $doors): Collection
    {
        $alerts = collect();

        $doors->where('status', DoorStatus::Offline)->each(fn (Door $door) => $alerts->push([
            'severity' => 'danger',
            'label' => 'Door Offline',
            'detail' => "{$door->name} — {$door->building}, {$door->floor}",
        ]));

        Camera::where('status', CameraStatus::Offline)->limit(3)->get()->each(fn (Camera $camera) => $alerts->push([
            'severity' => 'warning',
            'label' => 'Camera Offline',
            'detail' => "{$camera->name} — {$camera->location}",
        ]));

        AccessEvent::access()->today()->where('result', AccessResult::ExpiredBadge)->with('door')->limit(3)->get()->each(fn (AccessEvent $event) => $alerts->push([
            'severity' => 'warning',
            'label' => 'Expired Badge Used',
            'detail' => "{$event->person_name} at ".($event->door?->name ?? 'unknown door').' — '.$event->happened_at->format('H:i'),
        ]));

        AccessEvent::access()->today()
            ->whereNot('result', AccessResult::Granted)
            ->selectRaw('person_name, count(*) as c')
            ->groupBy('person_name')
            ->havingRaw('count(*) >= 3')
            ->get()
            ->each(fn ($row) => $alerts->push([
                'severity' => 'danger',
                'label' => 'Multiple Failed Attempts',
                'detail' => "{$row->person_name} — {$row->c} denials today",
            ]));

        AccessEvent::access()->today()->where('result', AccessResult::Unauthorized)->with('door')->limit(3)->get()->each(fn (AccessEvent $event) => $alerts->push([
            'severity' => 'danger',
            'label' => 'Unauthorized Access Attempt',
            'detail' => "{$event->person_name} at ".($event->door?->name ?? 'unknown door').' — '.$event->happened_at->format('H:i'),
        ]));

        return $alerts
            ->sortBy(fn (array $alert) => $alert['severity'] === 'danger' ? 0 : 1)
            ->take(8)
            ->values();
    }
}
