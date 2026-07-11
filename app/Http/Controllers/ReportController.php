<?php

namespace App\Http\Controllers;

use App\Enums\AccessResult;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\BiometricResult;
use App\Enums\CameraStatus;
use App\Enums\SignalStrength;
use App\Enums\UserStatus;
use App\Models\AccessEvent;
use App\Models\AccessPermission;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\BiometricProfile;
use App\Models\BiometricVerification;
use App\Models\Camera;
use App\Models\Device;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * The Reports & Analytics dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorizeReports();
        [$from, $to] = $this->range($request);

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'executive' => $this->executive(),
            'employees' => $this->employees($from, $to),
            'visitors' => $this->visitors($from, $to),
            'cameras' => $this->cameras(),
            'devices' => $this->devices(),
            'biometrics' => $this->biometrics($from, $to),
            'access' => $this->access($from, $to),
            'alerts' => $this->alerts($from, $to),
            'audit' => $this->audit($from, $to),
        ]);
    }

    /**
     * Export one section's key dataset as CSV (opens in Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorizeReports();
        [$from, $to] = $this->range($request);

        $section = $request->query('section', 'executive');

        [$headers, $rows] = match ($section) {
            'executive' => [['Metric', 'Value'], collect($this->executive())->map(fn ($v, $k) => [$k, $v['value']])->values()],
            'visitors' => [
                ['Date', 'Visits'],
                collect($this->visitors($from, $to)['perDay'])->map(fn ($d) => [$d['label'], $d['count']]),
            ],
            'access' => [
                ['Door', 'Events'],
                collect($this->access($from, $to)['topDoors'])->map(fn ($d) => [$d['label'], $d['count']]),
            ],
            'alerts' => [
                ['Severity', 'Count'],
                collect($this->alerts($from, $to)['bySeverity'])->map(fn ($d) => [$d['label'], $d['count']]),
            ],
            'audit' => [
                ['Module', 'Activities'],
                collect($this->audit($from, $to)['byModule'])->map(fn ($d) => [$d['label'], $d['count']]),
            ],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($headers, $rows, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Report period', $from->format('Y-m-d').' → '.$to->format('Y-m-d')]);
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "report-{$section}-".now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The requested date range, defaulting to the last 30 days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    /**
     * Executive KPI cards. Cached for one minute — these are the
     * most-hit and least time-sensitive aggregates.
     *
     * @return array<string, array{value: int|string, meta: string}>
     */
    private function executive(): array
    {
        return Cache::remember('reports.executive', 60, fn () => [
            'Total Users' => ['value' => User::count(), 'meta' => User::where('status', UserStatus::Active)->count().' active'],
            'Biometric Profiles' => ['value' => BiometricProfile::count(), 'meta' => BiometricProfile::whereNotNull('face_enrolled_at')->count().' faces enrolled'],
            'Total Visitors' => ['value' => Visit::count(), 'meta' => Visit::today()->count().' today'],
            'Total Cameras' => ['value' => Camera::count(), 'meta' => Camera::online()->count().' online'],
            'IoT Devices' => ['value' => Device::count(), 'meta' => Device::online()->count().' online'],
            'Access Events' => ['value' => AccessEvent::access()->count(), 'meta' => AccessEvent::access()->today()->count().' today'],
            'Total Alerts' => ['value' => Alert::count(), 'meta' => Alert::open()->count().' pending'],
            'Audit Entries' => ['value' => AuditLog::count(), 'meta' => AuditLog::today()->count().' today'],
            'Currently Inside' => ['value' => Visit::inside()->count(), 'meta' => 'Visitors in the building'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function employees(Carbon $from, Carbon $to): array
    {
        return [
            'byDepartment' => $this->rank(BiometricProfile::selectRaw('department as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'byPosition' => $this->rank(BiometricProfile::selectRaw('position as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'activeRate' => $this->ratio(User::where('status', UserStatus::Active)->count(), User::count()),
            'enrollment' => [
                ['label' => 'Face', 'count' => BiometricProfile::whereNotNull('face_enrolled_at')->count()],
                ['label' => 'Fingerprint', 'count' => BiometricProfile::whereNotNull('fingerprint_enrolled_at')->count()],
                ['label' => 'Iris', 'count' => BiometricProfile::whereNotNull('iris_enrolled_at')->count()],
                ['label' => 'None', 'count' => BiometricProfile::whereNull('face_enrolled_at')->whereNull('fingerprint_enrolled_at')->whereNull('iris_enrolled_at')->count()],
            ],
            'newOverTime' => $this->perDay(User::query(), 'created_at', $from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function visitors(Carbon $from, Carbon $to): array
    {
        $inRange = fn () => Visit::whereBetween('visit_date', [$from, $to]);

        return [
            'total' => $inRange()->count(),
            'checkedIn' => $inRange()->whereNotNull('checked_in_at')->count(),
            'checkedOut' => $inRange()->whereNotNull('checked_out_at')->count(),
            'perDay' => $this->perDay(Visit::query(), 'visit_date', $from, $to),
            'byCompany' => $this->rank($inRange()->whereNotNull('company')->selectRaw('company as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'topHosts' => $this->rank(
                $inRange()->whereNotNull('host_user_id')
                    ->join('users', 'users.id', '=', 'visits.host_user_id')
                    ->selectRaw('users.first_name as f, users.last_name as l, count(*) as c')
                    ->groupBy('f', 'l')->orderByDesc('c')->limit(6)->get()
                    ->map(fn ($r) => (object) ['k' => trim("{$r->f} {$r->l}"), 'c' => $r->c])
            ),
            'peakHours' => $this->byHourSlot(Visit::whereNotNull('checked_in_at')->whereBetween('checked_in_at', [$from, $to])->pluck('checked_in_at')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cameras(): array
    {
        return [
            'onlineRate' => $this->ratio(Camera::online()->count(), Camera::count()),
            'byStatus' => collect(CameraStatus::cases())->map(fn ($s) => ['label' => $s->label(), 'count' => Camera::where('status', $s)->count()])->all(),
            'recording' => $this->ratio(Camera::where('recording_enabled', true)->count(), Camera::count()),
            'health' => Camera::where('status', CameraStatus::Maintenance)->count(),
            'storage' => [
                'used' => (int) round(Camera::where('recording_enabled', true)->count() * 18.5), // ~GB estimate until NVR integration
                'max' => (int) \App\Models\Setting::get('cameras.max_storage_gb', 500),
            ],
            'mostActive' => $this->rank(
                AccessEvent::access()->whereNotNull('access_events.camera_id')
                    ->join('cameras', 'cameras.id', '=', 'access_events.camera_id')
                    ->selectRaw('cameras.name as k, count(*) as c')
                    ->groupBy('k')->orderByDesc('c')->limit(5)->get()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function devices(): array
    {
        return [
            'onlineRate' => $this->ratio(Device::online()->count(), Device::count()),
            'battery' => [
                ['label' => 'Low (≤'.Device::LOW_BATTERY.'%)', 'count' => Device::lowBattery()->count()],
                ['label' => 'Medium', 'count' => Device::whereBetween('battery_level', [Device::LOW_BATTERY + 1, 60])->count()],
                ['label' => 'High', 'count' => Device::where('battery_level', '>', 60)->count()],
                ['label' => 'Mains powered', 'count' => Device::whereNull('battery_level')->count()],
            ],
            'signal' => collect(SignalStrength::cases())->map(fn ($s) => ['label' => $s->label(), 'count' => Device::where('signal_strength', $s)->count()])->all(),
            'activity' => [
                ['label' => 'Seen < 1h', 'count' => Device::where('last_seen', '>=', now()->subHour())->count()],
                ['label' => 'Seen < 24h', 'count' => Device::whereBetween('last_seen', [now()->subDay(), now()->subHour()])->count()],
                ['label' => 'Silent > 24h', 'count' => Device::where(fn ($q) => $q->where('last_seen', '<', now()->subDay())->orWhereNull('last_seen'))->count()],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function biometrics(Carbon $from, Carbon $to): array
    {
        $inRange = fn () => BiometricVerification::whereBetween('happened_at', [$from, $to]);
        $success = $inRange()->where('result', BiometricResult::Success)->count();
        $total = $inRange()->count();

        return [
            'success' => $success,
            'failed' => $inRange()->where('result', BiometricResult::Failed)->count(),
            'successRate' => $this->ratio($success, $total),
            'perDay' => $this->perDay(BiometricVerification::query(), 'happened_at', $from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function access(Carbon $from, Carbon $to): array
    {
        $inRange = fn () => AccessEvent::access()->whereBetween('happened_at', [$from, $to]);
        $granted = $inRange()->where('result', AccessResult::Granted)->count();
        $total = $inRange()->count();

        // Day-of-week × 2-hour-slot heatmap.
        $heatmap = array_fill(0, 7, array_fill(0, 12, 0));
        $inRange()->pluck('happened_at')->each(function ($at) use (&$heatmap) {
            $heatmap[$at->dayOfWeekIso - 1][intdiv((int) $at->format('G'), 2)]++;
        });
        $heatMax = max(1, max(array_map('max', $heatmap)));

        return [
            'granted' => $granted,
            'denied' => $total - $granted,
            'grantRate' => $this->ratio($granted, $total),
            'topDoors' => $this->rank(
                $inRange()->whereNotNull('door_id')
                    ->join('doors', 'doors.id', '=', 'access_events.door_id')
                    ->selectRaw('doors.name as k, count(*) as c')
                    ->groupBy('k')->orderByDesc('c')->limit(6)->get()
            ),
            'heatmap' => $heatmap,
            'heatMax' => $heatMax,
            'temporary' => AccessPermission::where('type', 'temporary')->count(),
            'expired' => AccessPermission::whereNotNull('valid_until')->where('valid_until', '<', today())->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function alerts(Carbon $from, Carbon $to): array
    {
        $inRange = fn () => Alert::whereBetween('happened_at', [$from, $to]);

        $avgMinutes = (int) rescue(fn () => Alert::whereNotNull('resolved_at')
            ->whereBetween('happened_at', [$from, $to])
            ->selectRaw('avg(timestampdiff(minute, happened_at, resolved_at)) as m')
            ->value('m'), 0, false); // timestampdiff is MySQL-only (sqlite in tests)

        return [
            'bySeverity' => collect(AlertSeverity::cases())->map(fn ($s) => [
                'label' => $s->label(),
                'count' => $inRange()->where('severity', $s)->count(),
                'badge' => $s->badge(),
            ])->all(),
            'byType' => $this->rank($inRange()->selectRaw('type as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'resolvedRate' => $this->ratio(
                $inRange()->whereIn('status', [AlertStatus::Resolved, AlertStatus::Closed, AlertStatus::Ignored])->count(),
                $inRange()->count(),
            ),
            'avgResolution' => $avgMinutes >= 60 ? intdiv($avgMinutes, 60).'h '.($avgMinutes % 60).'m' : $avgMinutes.'m',
            'critical' => $inRange()->where('severity', AlertSeverity::Critical)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function audit(Carbon $from, Carbon $to): array
    {
        $inRange = fn () => AuditLog::whereBetween('happened_at', [$from, $to]);

        return [
            'logins' => $inRange()->where('action', 'Login')->count(),
            'failedLogins' => $inRange()->where('action', 'Failed Login')->count(),
            'topUsers' => $this->rank($inRange()->whereNotNull('user_name')->selectRaw('user_name as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'byModule' => $this->rank($inRange()->selectRaw('module as k, count(*) as c')->groupBy('k')->orderByDesc('c')->limit(6)->get()),
            'perDay' => $this->perDay(AuditLog::query(), 'happened_at', $from, $to),
        ];
    }

    /**
     * Counts per day over the range, capped at 31 bars (grouped by
     * week beyond that so the chart stays readable).
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function perDay($query, string $column, Carbon $from, Carbon $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $byWeek = $days > 31;

        // date() aggregation works on MySQL and sqlite alike; week
        // bucketing happens in PHP on the (at most range-sized) result.
        $counts = $query->whereBetween($column, [$from, $to])
            ->selectRaw("date({$column}) as k, count(*) as c")
            ->groupBy('k')
            ->pluck('c', 'k');

        if ($byWeek) {
            $weeks = [];
            foreach ($counts as $day => $c) {
                $key = (int) Carbon::parse($day)->format('oW');
                $weeks[$key] = ($weeks[$key] ?? 0) + (int) $c;
            }

            $out = [];
            $cursor = $from->copy()->startOfWeek();
            while ($cursor->lte($to)) {
                $out[] = ['label' => 'W'.$cursor->format('W'), 'count' => $weeks[(int) $cursor->format('oW')] ?? 0];
                $cursor->addWeek();
            }

            return $out;
        }

        return collect(range(0, $days - 1))
            ->map(function (int $i) use ($from, $counts) {
                $day = $from->copy()->addDays($i);

                return ['label' => $day->format($i % 7 === 0 || $day->isToday() ? 'M j' : 'j'), 'count' => (int) $counts->get($day->format('Y-m-d'), 0)];
            })
            ->all();
    }

    /**
     * Turn a grouped k/c result into ranked rows with percentages.
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function rank($rows): Collection
    {
        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => [
            'label' => (string) ($row->k ?? '—'),
            'count' => (int) $row->c,
            'percent' => (int) round($row->c / $max * 100),
        ]);
    }

    /**
     * @return array{percent: int, part: int, total: int}
     */
    private function ratio(int $part, int $total): array
    {
        return [
            'percent' => $total > 0 ? (int) round($part / $total * 100) : 100,
            'part' => $part,
            'total' => $total,
        ];
    }

    /**
     * Counts bucketed into two-hour slots.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function byHourSlot(Collection $timestamps): array
    {
        $byHour = $timestamps->countBy(fn ($at) => intdiv((int) $at->format('G'), 2));

        return collect(range(0, 11))
            ->map(fn (int $slot) => ['label' => ($slot * 2).'h', 'count' => (int) $byHour->get($slot, 0)])
            ->all();
    }

    private function authorizeReports(): void
    {
        abort_unless(auth()->user()->role->canViewReports(), 403);
    }
}
