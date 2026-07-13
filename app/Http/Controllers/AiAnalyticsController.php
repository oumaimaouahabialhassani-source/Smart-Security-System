<?php

namespace App\Http\Controllers;

use App\Enums\AccessResult;
use App\Enums\AiAlertStatus;
use App\Enums\AiRiskLevel;
use App\Enums\CameraStatus;
use App\Enums\DeviceStatus;
use App\Models\AccessEvent;
use App\Models\AiAlert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\User;
use App\Models\Visit;
use App\Services\AiInsightsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiAnalyticsController extends Controller
{
    /**
     * The AI Analytics Dashboard: historical analysis, health
     * statistics and predictive insights. Aggregates are cached for
     * a minute — they cover 30-day windows, so staleness is invisible
     * but page switching stays fast.
     */
    public function index(AiInsightsService $insights): View
    {
        $data = Cache::remember('ai.analytics', 60, fn () => [
            'overview' => $this->overview(),
            'riskZones' => $this->riskZones(),
            'activeCameras' => $this->activeCameras(),
            'alertTypes' => $this->alertTypes(),
            'daily' => $this->dailySeries(14),
            'weekly' => $this->weeklySeries(8),
            'monthly' => $this->monthlySeries(6),
            'peakActivity' => $this->hourly(AccessEvent::class),
            'peakAlerts' => $this->hourly(AiAlert::class),
            'topEmployees' => $this->topPeople('user_id'),
            'topVisitors' => $this->topPeople('visit_id'),
            'cameraHealth' => $this->cameraHealth(),
            'deviceHealth' => $this->deviceHealth(),
            'doorActivity' => $this->doorActivity(),
            'access' => $this->accessStats(),
            'quality' => $this->modelQuality(),
            'forecast' => $insights->forecast(),
            'insights' => $insights->insights(),
        ]);

        return view('ai.analytics', $data + [
            'timeline' => AiAlert::whereIn('risk_level', [AiRiskLevel::Critical, AiRiskLevel::High])
                ->with(['camera', 'user', 'visit'])
                ->orderByDesc('happened_at')->limit(8)->get(),
        ]);
    }

    /**
     * Security overview cards, including the composite security score.
     *
     * @return array<string, int|string>
     */
    private function overview(): array
    {
        $openCritical = AiAlert::open()->where('risk_level', AiRiskLevel::Critical)->count();
        $openHigh = AiAlert::open()->where('risk_level', AiRiskLevel::High)->count();
        $camerasOffline = Camera::where('status', CameraStatus::Offline)->count();
        $devicesOffline = Device::where('status', DeviceStatus::Offline)->count();
        $unauthorized24h = AccessEvent::whereNot('result', AccessResult::Granted)
            ->where('happened_at', '>=', now()->subDay())->count();

        $score = max(5, min(100, 100
            - $openCritical * 10
            - $openHigh * 5
            - $camerasOffline * 3
            - $devicesOffline * 2
            - $unauthorized24h));

        return [
            'score' => $score,
            'scoreTone' => $score >= 75 ? 'stat-success' : ($score >= 50 ? 'stat-warning' : 'stat-danger'),
            'alerts30d' => AiAlert::where('happened_at', '>=', now()->subDays(30))->count(),
            'openCritical' => $openCritical,
            'openHigh' => $openHigh,
            'camerasOffline' => $camerasOffline,
            'devicesOffline' => $devicesOffline,
            'unauthorized24h' => $unauthorized24h,
        ];
    }

    /**
     * Top risk zones (highest average AI risk score) and most
     * dangerous areas (most critical/high alerts), last 30 days.
     *
     * @return array{riskiest: array, dangerous: array}
     */
    private function riskZones(): array
    {
        $riskiest = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull('location')
            ->selectRaw('location as k, round(avg(risk_score)) as score, count(*) as c')
            ->groupBy('k')->havingRaw('count(*) >= 2')
            ->orderByDesc('score')->limit(5)->get()
            ->map(fn ($r) => ['label' => $r->k, 'count' => (int) $r->score, 'percent' => (int) $r->score, 'meta' => $r->c.' events'])
            ->all();

        $rows = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereIn('risk_level', [AiRiskLevel::Critical, AiRiskLevel::High])
            ->whereNotNull('location')
            ->selectRaw('location as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();
        $max = max($rows->max('c') ?? 0, 1);

        return [
            'riskiest' => $riskiest,
            'dangerous' => $rows->map(fn ($r) => ['label' => $r->k, 'count' => $r->c, 'percent' => (int) round($r->c / $max * 100)])->all(),
        ];
    }

    /**
     * Cameras generating the most AI alerts (30 days).
     *
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function activeCameras(): array
    {
        $rows = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull('camera_id')
            ->selectRaw('camera_id as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $names = Camera::whereIn('id', $rows->pluck('k'))->pluck('name', 'id');
        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($r) => [
            'label' => $names[$r->k] ?? '—',
            'count' => $r->c,
            'percent' => (int) round($r->c / $max * 100),
        ])->all();
    }

    /**
     * Most frequent alert types (30 days).
     *
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function alertTypes(): array
    {
        $rows = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->selectRaw('event_type as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(6)->get();
        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($r) => ['label' => $r->k, 'count' => $r->c, 'percent' => (int) round($r->c / $max * 100)])->all();
    }

    /**
     * AI alerts per day (bar chart series).
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function dailySeries(int $days): array
    {
        $byDate = AiAlert::where('happened_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->pluck('happened_at')->countBy(fn ($at) => $at->format('Y-m-d'));

        return collect(range($days - 1, 0))->map(fn (int $ago) => [
            'label' => now()->subDays($ago)->format('d M'),
            'count' => $byDate->get(now()->subDays($ago)->format('Y-m-d'), 0),
        ])->all();
    }

    /**
     * AI alerts per ISO week.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function weeklySeries(int $weeks): array
    {
        $byWeek = AiAlert::where('happened_at', '>=', now()->subWeeks($weeks)->startOfWeek())
            ->pluck('happened_at')->countBy(fn ($at) => $at->format('o-W'));

        return collect(range($weeks - 1, 0))->map(function (int $ago) use ($byWeek) {
            $week = now()->subWeeks($ago);

            return ['label' => 'W'.$week->format('W'), 'count' => $byWeek->get($week->format('o-W'), 0)];
        })->all();
    }

    /**
     * AI alerts per month.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function monthlySeries(int $months): array
    {
        $byMonth = AiAlert::where('happened_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->pluck('happened_at')->countBy(fn ($at) => $at->format('Y-m'));

        return collect(range($months - 1, 0))->map(function (int $ago) use ($byMonth) {
            $month = now()->subMonths($ago);

            return ['label' => $month->format('M'), 'count' => $byMonth->get($month->format('Y-m'), 0)];
        })->all();
    }

    /**
     * Events bucketed into two-hour slots (last 30 days).
     *
     * @param class-string<AccessEvent|AiAlert> $model
     * @return array<int, array{label: string, count: int}>
     */
    private function hourly(string $model): array
    {
        $bySlot = $model::where('happened_at', '>=', now()->subDays(30))
            ->pluck('happened_at')->countBy(fn ($at) => intdiv((int) $at->format('G'), 2));

        return collect(range(0, 11))
            ->map(fn (int $slot) => ['label' => ($slot * 2).'h', 'count' => $bySlot->get($slot, 0)])
            ->all();
    }

    /**
     * People (employees or visitors) triggering the most AI alerts.
     *
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function topPeople(string $column): array
    {
        $rows = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull($column)
            ->selectRaw("{$column} as k, count(*) as c")
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $names = $column === 'user_id'
            ? User::whereIn('id', $rows->pluck('k'))->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name])
            : Visit::whereIn('id', $rows->pluck('k'))->pluck('full_name', 'id');

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($r) => [
            'label' => $names[$r->k] ?? '—',
            'count' => $r->c,
            'percent' => (int) round($r->c / $max * 100),
        ])->all();
    }

    /**
     * @return array{online: int, offline: int, maintenance: int, total: int, uptime: int}
     */
    private function cameraHealth(): array
    {
        $counts = Camera::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $total = max($counts->sum(), 1);

        return [
            'online' => $online = $counts->get(CameraStatus::Online->value, 0),
            'offline' => $counts->get(CameraStatus::Offline->value, 0),
            'maintenance' => $counts->get(CameraStatus::Maintenance->value, 0),
            'total' => $counts->sum(),
            'uptime' => (int) round($online / $total * 100),
        ];
    }

    /**
     * @return array{online: int, offline: int, lowBattery: int, total: int, uptime: int}
     */
    private function deviceHealth(): array
    {
        $total = max(Device::count(), 1);
        $online = Device::where('status', DeviceStatus::Online)->count();

        return [
            'online' => $online,
            'offline' => Device::where('status', DeviceStatus::Offline)->count(),
            'lowBattery' => Device::lowBattery()->count(),
            'total' => Device::count(),
            'uptime' => (int) round($online / $total * 100),
        ];
    }

    /**
     * Busiest doors (30 days).
     *
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function doorActivity(): array
    {
        $rows = AccessEvent::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull('door_id')
            ->selectRaw('door_id as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $names = Door::whereIn('id', $rows->pluck('k'))->pluck('name', 'id');
        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($r) => [
            'label' => $names[$r->k] ?? '—',
            'count' => $r->c,
            'percent' => (int) round($r->c / $max * 100),
        ])->all();
    }

    /**
     * Access attempts, denials, unknown faces and motion (30 days).
     *
     * @return array<string, int>
     */
    private function accessStats(): array
    {
        $since = now()->subDays(30);
        $total = AccessEvent::where('happened_at', '>=', $since)->count();
        $unauthorized = AccessEvent::where('happened_at', '>=', $since)->whereNot('result', AccessResult::Granted)->count();

        return [
            'total' => $total,
            'granted' => $total - $unauthorized,
            'unauthorized' => $unauthorized,
            'grantRate' => $total > 0 ? (int) round(($total - $unauthorized) / $total * 100) : 100,
            'unknownFaces' => AiAlert::where('happened_at', '>=', $since)->where('event_type', 'Unknown Face Detection')->count(),
            'motion' => AiAlert::where('happened_at', '>=', $since)->where('event_type', 'Motion Detection')->count(),
        ];
    }

    /**
     * Model quality metrics from the review outcomes.
     *
     * @return array{accuracy: int, falsePositiveRate: int, truePositiveRate: int, reviewed: int}
     */
    private function modelQuality(): array
    {
        $reviewed = AiAlert::whereIn('status', [AiAlertStatus::Resolved, AiAlertStatus::Actioned, AiAlertStatus::FalsePositive])->count();
        $falsePositives = AiAlert::where('status', AiAlertStatus::FalsePositive)->count();
        $fpr = $reviewed > 0 ? (int) round($falsePositives / $reviewed * 100) : 0;

        return [
            'accuracy' => 100 - $fpr,
            'falsePositiveRate' => $fpr,
            'truePositiveRate' => 100 - $fpr,
            'reviewed' => $reviewed,
        ];
    }
}
