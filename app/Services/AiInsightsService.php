<?php

namespace App\Services;

use App\Enums\AccessResult;
use App\Enums\AiRiskLevel;
use App\Models\AccessEvent;
use App\Models\AiAlert;
use App\Models\Alert;
use App\Models\Camera;
use Illuminate\Support\Collection;

/**
 * Generates natural-language security insights from historical data:
 * month-over-month zone comparisons, unstable cameras, peak incident
 * windows, risk hotspots and repeated-failure patterns. Every insight
 * ships with an automatic recommendation. Pure database analysis — no
 * external AI dependency, and each sentence is backed by a real query.
 */
class AiInsightsService
{
    /**
     * All current insights, most important first.
     *
     * @return Collection<int, array{title: string, detail: string, recommendation: string, tone: string, confidence: int}>
     */
    public function insights(): Collection
    {
        return collect([
            ...$this->zoneTrends(),
            ...$this->unstableCameras(),
            $this->peakWindow(),
            $this->riskiestZone(),
            ...$this->repeatedFailures(),
            $this->overallTrend(),
        ])->filter()->values()->take(8);
    }

    /**
     * Zones with significantly more suspicious activity than the
     * previous 30-day period.
     *
     * @return list<array<string, mixed>>
     */
    private function zoneTrends(): array
    {
        $current = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull('location')
            ->selectRaw('location, count(*) as c')
            ->groupBy('location')->pluck('c', 'location');

        $previous = AiAlert::whereBetween('happened_at', [now()->subDays(60), now()->subDays(30)])
            ->whereNotNull('location')
            ->selectRaw('location, count(*) as c')
            ->groupBy('location')->pluck('c', 'location');

        return $current
            ->map(function (int $count, string $location) use ($previous) {
                $before = $previous->get($location, 0);
                if ($before < 2 || $count < 4) {
                    return null;
                }

                $change = (int) round(($count - $before) / $before * 100);

                return $change >= 20 ? [
                    'title' => 'Rising Activity Zone',
                    'detail' => "{$location} has {$change}% more suspicious activity than the previous month ({$count} vs {$before} events).",
                    'recommendation' => "Increase patrols around {$location} and review its camera coverage.",
                    'tone' => 'badge-danger',
                    'confidence' => min(95, 70 + intdiv($change, 10)),
                ] : null;
            })
            ->filter()->sortByDesc('confidence')->take(2)->values()->all();
    }

    /**
     * Cameras that keep dropping offline.
     *
     * @return list<array<string, mixed>>
     */
    private function unstableCameras(): array
    {
        $rows = Alert::whereIn('type', ['Camera Offline', 'Camera Tampering'])
            ->where('happened_at', '>=', now()->subDays(7))
            ->whereNotNull('camera_id')
            ->selectRaw('camera_id, count(*) as c')
            ->groupBy('camera_id')->havingRaw('count(*) >= 3')
            ->orderByDesc('c')->limit(2)->get();

        $names = Camera::whereIn('id', $rows->pluck('camera_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'title' => 'Unstable Camera',
            'detail' => ($names[$row->camera_id]?->camera_id ?? 'Camera').' ('.($names[$row->camera_id]?->name ?? '—').") has been offline {$row->c} times this week.",
            'recommendation' => 'Dispatch a technician to check its power and network connection.',
            'tone' => 'badge-warning',
            'confidence' => 96,
        ])->all();
    }

    /**
     * The 3-hour window where most incidents happen.
     *
     * @return array<string, mixed>|null
     */
    private function peakWindow(): ?array
    {
        $byHour = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->pluck('happened_at')
            ->countBy(fn ($at) => (int) $at->format('G'));

        if ($byHour->sum() < 10) {
            return null;
        }

        $best = collect(range(0, 23))
            ->map(fn (int $h) => ['start' => $h, 'count' => $byHour->get($h, 0) + $byHour->get(($h + 1) % 24, 0) + $byHour->get(($h + 2) % 24, 0)])
            ->sortByDesc('count')->first();

        $share = (int) round($best['count'] / max($byHour->sum(), 1) * 100);
        $end = ($best['start'] + 3) % 24;

        return [
            'title' => 'Peak Incident Window',
            'detail' => sprintf('Most incidents occur between %02d:00 and %02d:00 — %d%% of the last 30 days\' events.', $best['start'], $end, $share),
            'recommendation' => 'Schedule extra security staff during this window.',
            'tone' => 'badge-warning',
            'confidence' => min(95, 60 + intdiv($share, 2)),
        ];
    }

    /**
     * The location carrying the highest average risk.
     *
     * @return array<string, mixed>|null
     */
    private function riskiestZone(): ?array
    {
        $row = AiAlert::where('happened_at', '>=', now()->subDays(30))
            ->whereNotNull('location')
            ->selectRaw('location, avg(risk_score) as avg_score, count(*) as c')
            ->groupBy('location')->havingRaw('count(*) >= 3')
            ->orderByDesc('avg_score')->first();

        return $row ? [
            'title' => 'Highest Risk Zone',
            'detail' => "{$row->location} has the highest security risk — average AI risk score ".round($row->avg_score)."/100 across {$row->c} events.",
            'recommendation' => "Audit access permissions and sensor coverage for {$row->location}.",
            'tone' => 'badge-danger',
            'confidence' => 90,
        ] : null;
    }

    /**
     * People accumulating access denials this week.
     *
     * @return list<array<string, mixed>>
     */
    private function repeatedFailures(): array
    {
        return AccessEvent::whereNot('result', AccessResult::Granted)
            ->where('happened_at', '>=', now()->subDays(7))
            ->selectRaw('person_name, count(*) as c')
            ->groupBy('person_name')->havingRaw('count(*) >= 4')
            ->orderByDesc('c')->limit(2)->get()
            ->map(fn ($row) => [
                'title' => 'Repeated Failed Access',
                'detail' => 'Repeated failed access attempts detected: '.($row->person_name ?? 'Unknown subject')." was denied {$row->c} times in the last 7 days.",
                'recommendation' => 'Verify this person\'s identity and badge; consider suspending their access.',
                'tone' => 'badge-danger',
                'confidence' => 93,
            ])->all();
    }

    /**
     * Overall direction of alert volume, week over week.
     *
     * @return array<string, mixed>|null
     */
    private function overallTrend(): ?array
    {
        $thisWeek = AiAlert::where('happened_at', '>=', now()->subDays(7))->count();
        $lastWeek = AiAlert::whereBetween('happened_at', [now()->subDays(14), now()->subDays(7)])->count();

        if ($lastWeek < 3) {
            return null;
        }

        $change = (int) round(($thisWeek - $lastWeek) / $lastWeek * 100);

        return [
            'title' => $change >= 0 ? 'Alert Volume Rising' : 'Alert Volume Falling',
            'detail' => 'AI alerts are '.($change >= 0 ? 'up' : 'down').' '.abs($change)."% week-over-week ({$thisWeek} vs {$lastWeek}).",
            'recommendation' => $change >= 20
                ? 'Review this week\'s incidents for a common cause before it escalates.'
                : 'No action needed — keep monitoring.',
            'tone' => $change >= 20 ? 'badge-warning' : 'badge-success',
            'confidence' => 85,
        ];
    }

    /**
     * Simple linear forecast of daily alert counts for the next 7
     * days, fitted on the last 14 days (least squares).
     *
     * @return array{days: array<int, array{label: string, count: int}>, trend: string, risk: AiRiskLevel}
     */
    public function forecast(): array
    {
        $byDate = AiAlert::where('happened_at', '>=', now()->subDays(13)->startOfDay())
            ->pluck('happened_at')
            ->countBy(fn ($at) => $at->format('Y-m-d'));

        $history = collect(range(13, 0))
            ->map(fn (int $ago) => (float) $byDate->get(now()->subDays($ago)->format('Y-m-d'), 0))
            ->values();

        // Least-squares fit y = a + b*x over the 14 observed days.
        $n = $history->count();
        $meanX = ($n - 1) / 2;
        $meanY = $history->avg();
        $num = $den = 0.0;
        foreach ($history as $x => $y) {
            $num += ($x - $meanX) * ($y - $meanY);
            $den += ($x - $meanX) ** 2;
        }
        $b = $den > 0 ? $num / $den : 0.0;
        $a = $meanY - $b * $meanX;

        $days = collect(range(1, 7))->map(fn (int $i) => [
            'label' => now()->addDays($i)->format('D'),
            'count' => max(0, (int) round($a + $b * ($n - 1 + $i))),
        ])->all();

        $avgForecast = collect($days)->avg('count');

        return [
            'days' => $days,
            'trend' => $b > 0.15 ? 'rising' : ($b < -0.15 ? 'falling' : 'stable'),
            'risk' => AiRiskLevel::fromScore(min(100, (int) round($avgForecast * 12))),
        ];
    }
}
