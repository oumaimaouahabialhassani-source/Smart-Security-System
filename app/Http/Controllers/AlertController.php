<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\CameraStatus;
use App\Enums\DeviceStatus;
use App\Enums\DoorStatus;
use App\Enums\UserRole;
use App\Models\AccessEvent;
use App\Models\Alert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertController extends Controller
{
    /**
     * The Alerts & Notifications center.
     */
    public function index(Request $request): View
    {
        $alerts = Alert::query()
            ->with(['device', 'camera', 'door', 'user', 'visit', 'assignee'])
            ->search($request->query('search'))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('from'), fn ($q, $v) => $q->where('happened_at', '>=', "{$v} 00:00:00"))
            ->when($request->query('to'), fn ($q, $v) => $q->where('happened_at', '<=', "{$v} 23:59:59"))
            ->when($request->query('building'), fn ($q, $v) => $q->where('building', $v))
            ->when($request->query('assigned'), fn ($q, $v) => $q->where('assigned_to', $v))
            ->orderByDesc('happened_at')
            ->paginate(10)
            ->withQueryString();

        return view('alerts.index', [
            'alerts' => $alerts,
            'stats' => $this->stats(),
            'hourly' => $hourly = $this->hourly(),
            'maxHourly' => max(max(array_column($hourly, 'count')), 1),
            'daily' => $daily = $this->daily(),
            'maxDaily' => max(max(array_column($daily, 'count')), 1),
            'severityDist' => $this->severityDistribution(),
            'resolvedRate' => $this->resolvedRate(),
            'topTypes' => $this->topTypes(),
            'topDevices' => $this->topDevices(),
            'insights' => $this->aiInsights(),
            'timeline' => AccessEvent::security()->with('door')->orderByDesc('happened_at')->limit(6)->get(),
            'health' => $this->systemHealth(),
            'map' => $this->mapData(),
            'officers' => User::whereIn('role', [UserRole::Administrator, UserRole::SecurityOfficer])->orderBy('first_name')->get(),
            'types' => Alert::TYPES,
            'severities' => AlertSeverity::cases(),
            'statuses' => AlertStatus::cases(),
            'buildings' => Alert::whereNotNull('building')->distinct()->orderBy('building')->pluck('building'),
            'preferences' => auth()->user()->notification_preferences ?? [],
        ]);
    }

    /**
     * Latest notifications as JSON, polled by the live feed and the
     * top-bar bell.
     */
    public function feed(): JsonResponse
    {
        $notifications = collect()
            ->concat(Alert::with(['door', 'camera', 'device'])->orderByDesc('happened_at')->limit(6)->get()->map(fn (Alert $alert) => [
                'id' => 'alert-'.$alert->id,
                'icon' => '⚠',
                'text' => $alert->type.' — '.$alert->locationLabel(),
                'time' => $alert->happened_at->format('H:i:s'),
                'at' => $alert->happened_at->timestamp,
                'badge' => $alert->severity->badge(),
                'label' => $alert->severity->label(),
            ]))
            ->concat(AccessEvent::access()->with('door')->orderByDesc('happened_at')->limit(6)->get()->map(fn (AccessEvent $event) => [
                'id' => 'event-'.$event->id,
                'icon' => '≡',
                'text' => $event->person_name.' — '.($event->door?->name ?? 'door').' ('.$event->result?->label().')',
                'time' => $event->happened_at->format('H:i:s'),
                'at' => $event->happened_at->timestamp,
                'badge' => $event->result?->badge() ?? 'badge-muted',
                'label' => $event->result?->label() ?? '—',
            ]))
            ->sortByDesc('at')
            ->take(8)
            ->values();

        return response()->json([
            'openCount' => Alert::open()->count(),
            'items' => $notifications,
        ]);
    }

    /**
     * Update an alert: acknowledge, assign, annotate, resolve, close.
     */
    public function update(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::enum(AlertStatus::class)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = AlertStatus::from($data['status']);

        $alert->update([
            'status' => $status,
            'assigned_to' => $data['assigned_to'] ?? $alert->assigned_to,
            'notes' => $data['notes'] ?? $alert->notes,
            'resolved_at' => in_array($status, [AlertStatus::Resolved, AlertStatus::Closed], true)
                ? ($alert->resolved_at ?? now())
                : null,
        ]);

        return back()->with('status', "Alert {$alert->alert_code} updated — {$status->label()}".($alert->assignee ? ", assigned to {$alert->assignee->name}" : '').'.');
    }

    /**
     * Quick action: resolve directly from the table.
     */
    public function resolve(Alert $alert): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $alert->update(['status' => AlertStatus::Resolved, 'resolved_at' => now()]);

        return back()->with('status', "Alert {$alert->alert_code} marked as resolved.");
    }

    /**
     * Quick action: acknowledge every new alert.
     */
    public function acknowledgeAll(): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $count = Alert::where('status', AlertStatus::New)->update(['status' => AlertStatus::Pending]);

        return back()->with('status', "{$count} new alerts acknowledged.");
    }

    /**
     * Delete an alert. Administrators only.
     */
    public function destroy(Alert $alert): RedirectResponse
    {
        abort_unless(auth()->user()->role === UserRole::Administrator, 403);

        $code = $alert->alert_code;
        $alert->delete();

        return back()->with('status', "Alert {$code} has been deleted.");
    }

    /**
     * Save the current user's notification preferences.
     */
    public function savePreferences(Request $request): RedirectResponse
    {
        $keys = [
            'email', 'sms', 'push', 'desktop', 'sound',
            'critical_only', 'real_time', 'daily_summary', 'weekly_report',
        ];

        $preferences = collect($keys)->mapWithKeys(fn ($key) => [$key => $request->boolean($key)])->all();

        auth()->user()->forceFill(['notification_preferences' => $preferences])->save();

        return back()->with('status', 'Notification preferences saved.');
    }

    /**
     * Export the filtered alerts as CSV (opens in Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $alerts = Alert::query()
            ->with(['device', 'camera', 'door', 'user', 'visit', 'assignee'])
            ->search($request->query('search'))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('happened_at')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($alerts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Alert ID', 'Date', 'Time', 'Type', 'Severity', 'Status', 'Location', 'Device', 'Person', 'Description', 'Assigned To', 'Resolved At']);

            foreach ($alerts as $alert) {
                fputcsv($out, [
                    $alert->alert_code,
                    $alert->happened_at->format('Y-m-d'),
                    $alert->happened_at->format('H:i:s'),
                    $alert->type,
                    $alert->severity->label(),
                    $alert->status->label(),
                    $alert->locationLabel(),
                    $alert->device?->name,
                    $alert->user?->name ?? $alert->visit?->full_name,
                    $alert->description,
                    $alert->assignee?->name,
                    $alert->resolved_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, 'alerts-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Stat cards with change vs yesterday.
     *
     * @return array<int, array{label: string, value: int, meta: string, delta: int|null}>
     */
    private function stats(): array
    {
        $todayTotal = Alert::today()->count();
        $yesterdayTotal = Alert::whereBetween('happened_at', [today()->subDay(), today()->subDay()->endOfDay()])->count();

        return [
            ['label' => 'Total Alerts Today', 'value' => $todayTotal, 'meta' => now()->format('l, M j'), 'delta' => $yesterdayTotal > 0 ? (int) round(($todayTotal - $yesterdayTotal) / $yesterdayTotal * 100) : null],
            ['label' => 'Critical Alerts', 'value' => Alert::today()->where('severity', AlertSeverity::Critical)->count(), 'meta' => 'Today', 'delta' => null],
            ['label' => 'Warning Alerts', 'value' => Alert::today()->whereIn('severity', [AlertSeverity::High, AlertSeverity::Medium])->count(), 'meta' => 'High + medium today', 'delta' => null],
            ['label' => 'Information', 'value' => Alert::today()->whereIn('severity', [AlertSeverity::Low, AlertSeverity::Information])->count(), 'meta' => 'Low + info today', 'delta' => null],
            ['label' => 'Resolved Alerts', 'value' => Alert::whereIn('status', [AlertStatus::Resolved, AlertStatus::Closed])->count(), 'meta' => 'All time', 'delta' => null],
            ['label' => 'Pending Alerts', 'value' => Alert::open()->count(), 'meta' => 'New + investigating', 'delta' => null],
        ];
    }

    /**
     * Today's alerts bucketed into two-hour slots.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function hourly(): array
    {
        $byHour = Alert::today()->pluck('happened_at')->countBy(fn ($at) => intdiv((int) $at->format('G'), 2));

        return collect(range(0, 11))
            ->map(fn (int $slot) => ['day' => ($slot * 2).'h', 'count' => $byHour->get($slot, 0)])
            ->all();
    }

    /**
     * Alerts per day over the last seven days.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function daily(): array
    {
        $since = now()->subDays(6)->startOfDay();
        $byDate = Alert::where('happened_at', '>=', $since)->pluck('happened_at')->countBy(fn ($at) => $at->format('Y-m-d'));

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($byDate) {
                $day = now()->subDays($daysAgo);

                return ['day' => $day->format('D'), 'count' => $byDate->get($day->format('Y-m-d'), 0)];
            })
            ->all();
    }

    /**
     * Alert counts per severity (last 7 days), for the ranked bars.
     *
     * @return Collection<int, array{label: string, count: int, percent: int, badge: string}>
     */
    private function severityDistribution(): Collection
    {
        $since = now()->subDays(6)->startOfDay();
        $counts = Alert::where('happened_at', '>=', $since)->get()->countBy(fn (Alert $a) => $a->severity->value);
        $max = max($counts->max() ?? 0, 1);

        return collect(AlertSeverity::cases())->map(fn (AlertSeverity $severity) => [
            'label' => $severity->label(),
            'count' => $counts->get($severity->value, 0),
            'percent' => (int) round($counts->get($severity->value, 0) / $max * 100),
            'badge' => $severity->badge(),
        ]);
    }

    /**
     * Resolved vs still-open ratio, for the donut.
     *
     * @return array{percent: int, resolved: int, open: int, total: int}
     */
    private function resolvedRate(): array
    {
        $total = Alert::count();
        $resolved = Alert::whereIn('status', [AlertStatus::Resolved, AlertStatus::Closed, AlertStatus::Ignored])->count();

        return [
            'percent' => $total > 0 ? (int) round($resolved / $total * 100) : 100,
            'resolved' => $resolved,
            'open' => $total - $resolved,
            'total' => $total,
        ];
    }

    /**
     * Most frequent alert types (last 7 days).
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function topTypes(): Collection
    {
        $rows = Alert::where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('type as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => ['label' => $row->k, 'count' => $row->c, 'percent' => (int) round($row->c / $max * 100)]);
    }

    /**
     * Devices raising the most alerts (last 7 days).
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function topDevices(): Collection
    {
        $rows = Alert::where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->whereNotNull('device_id')
            ->selectRaw('device_id as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $max = max($rows->max('c') ?? 0, 1);
        $names = Device::whereIn('id', $rows->pluck('k'))->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'label' => $names[$row->k] ?? '—',
            'count' => $row->c,
            'percent' => (int) round($row->c / $max * 100),
        ]);
    }

    /**
     * AI-style insights derived from real recent data. Confidence
     * scores are placeholders until a real analytics engine lands.
     *
     * @return Collection<int, array{title: string, detail: string, confidence: int, action: string}>
     */
    private function aiInsights(): Collection
    {
        $insights = collect();
        $since = now()->subDay();

        $repeated = AccessEvent::access()->where('happened_at', '>=', $since)
            ->whereNot('result', \App\Enums\AccessResult::Granted)
            ->selectRaw('person_name, count(*) as c')
            ->groupBy('person_name')
            ->havingRaw('count(*) >= 3')
            ->orderByDesc('c')
            ->first();
        if ($repeated !== null) {
            $insights->push([
                'title' => 'Repeated Failed Access',
                'detail' => $repeated->person_name.' accumulated '.$repeated->c.' denials in 24h.',
                'confidence' => 93,
                'action' => 'Review the badge holder and consider suspending the badge.',
            ]);
        }

        $unknownFaces = AccessEvent::access()->where('happened_at', '>=', $since)
            ->where('result', \App\Enums\AccessResult::FaceNotRecognized)->count();
        if ($unknownFaces > 0) {
            $insights->push([
                'title' => 'Unrecognized Face',
                'detail' => $unknownFaces.' face verification failure(s) at biometric readers in 24h.',
                'confidence' => 88,
                'action' => 'Check the camera snapshots against the visitor log.',
            ]);
        }

        $offline = Device::where('status', DeviceStatus::Offline)->count() + Camera::where('status', CameraStatus::Offline)->count();
        if ($offline > 0) {
            $insights->push([
                'title' => 'Coverage Gap',
                'detail' => $offline.' camera(s)/device(s) offline create unmonitored zones.',
                'confidence' => 96,
                'action' => 'Dispatch a technician; increase patrols in the affected zones.',
            ]);
        }

        $afterHours = AccessEvent::access()->where('happened_at', '>=', $since)
            ->where(fn ($q) => $q->whereTime('happened_at', '>=', '22:00:00')->orWhereTime('happened_at', '<', '05:00:00'))
            ->count();
        if ($afterHours > 0) {
            $insights->push([
                'title' => 'Abnormal Movement',
                'detail' => $afterHours.' access event(s) between 22:00 and 05:00.',
                'confidence' => 74,
                'action' => 'Verify the night-shift roster covers these entries.',
            ]);
        }

        return $insights->take(4);
    }

    /**
     * Pre-computed marker positions and colors for the facility map.
     *
     * @return array{floors: array<string, int>, doors: Collection, cameras: Collection}
     */
    private function mapData(): array
    {
        $floors = ['Ground Floor' => 210, 'Floor 1' => 110, 'Floor 2' => 10];

        $doors = Door::get()
            ->groupBy(fn (Door $door) => array_key_exists($door->floor, $floors) ? $door->floor : 'Ground Floor')
            ->flatMap(fn ($group, $floorName) => $group->values()->map(fn (Door $door, int $i) => [
                'x' => 120 + $i * 95,
                'y' => $floors[$floorName],
                'name' => $door->name,
                'status' => $door->status->label(),
                'offline' => $door->status === DoorStatus::Offline,
                'tone' => match ($door->status) {
                    DoorStatus::Offline => 'var(--red)',
                    DoorStatus::Open => 'var(--orange)',
                    default => 'var(--green)',
                },
            ]))
            ->values();

        $cameras = Camera::limit(12)->get()->values()->map(fn (Camera $camera, int $i) => [
            'cx' => 70 + ($i % 4) * 160,
            'cy' => 38 + intdiv($i, 4) * 100,
            'name' => $camera->name,
            'status' => $camera->status->label(),
            'offline' => $camera->status === CameraStatus::Offline,
            'tone' => match ($camera->status) {
                CameraStatus::Offline => 'var(--red)',
                CameraStatus::Maintenance => 'var(--orange)',
                default => 'var(--green)',
            },
        ]);

        return ['floors' => $floors, 'doors' => $doors, 'cameras' => $cameras];
    }

    /**
     * System health widgets.
     *
     * @return array<string, array{value: string, ok: bool}>
     */
    private function systemHealth(): array
    {
        $camerasOnline = Camera::where('status', CameraStatus::Online)->count();
        $camerasTotal = Camera::count();
        $doorsConnected = Door::whereNot('status', DoorStatus::Offline)->count();
        $devicesOnline = Device::where('status', DeviceStatus::Online)->count();

        return [
            'Cameras Online' => ['value' => "{$camerasOnline} / {$camerasTotal}", 'ok' => $camerasOnline === $camerasTotal],
            'Doors Connected' => ['value' => "{$doorsConnected} / ".Door::count(), 'ok' => $doorsConnected === Door::count()],
            'IoT Devices Online' => ['value' => "{$devicesOnline} / ".Device::count(), 'ok' => $devicesOnline === Device::count()],
            'Sensors Active' => ['value' => (string) Device::online()->whereIn('type', [\App\Enums\DeviceType::MotionSensor, \App\Enums\DeviceType::DoorSensor, \App\Enums\DeviceType::SmokeDetector, \App\Enums\DeviceType::GasSensor])->count(), 'ok' => true],
            'Server Status' => ['value' => 'Online', 'ok' => true],
            'Network Status' => ['value' => 'Stable', 'ok' => true],
            'AI Engine' => ['value' => 'Simulated', 'ok' => true],
        ];
    }
}
