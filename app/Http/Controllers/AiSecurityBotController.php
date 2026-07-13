<?php

namespace App\Http\Controllers;

use App\Enums\AiAlertStatus;
use App\Enums\AiRiskLevel;
use App\Enums\CameraStatus;
use App\Enums\DeviceStatus;
use App\Enums\UserRole;
use App\Http\Requests\UpdateAiAlertRequest;
use App\Models\AccessEvent;
use App\Models\AiAlert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\AiChatAssistant;
use App\Services\AiEventMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiSecurityBotController extends Controller
{
    /**
     * The AI Security Bot dashboard.
     */
    public function dashboard(): View
    {
        return view('ai.dashboard', [
            'stats' => $this->stats(),
            'riskDist' => $this->riskDistribution(),
            'daily' => $this->daily(),
            'recent' => AiAlert::with(['camera', 'device', 'door', 'user', 'visit'])->orderByDesc('happened_at')->limit(8)->get(),
            'topEvents' => $this->topEventTypes(),
            'lastSweep' => Setting::get('ai_bot.last_sweep'),
        ]);
    }

    /**
     * Live monitoring feed as JSON, polled by the dashboard. Each poll
     * also triggers a monitoring sweep (throttled to one per 30s), so
     * the bot keeps analyzing while anyone is watching.
     */
    public function feed(AiEventMonitor $monitor): JsonResponse
    {
        $lastSweep = Setting::get('ai_bot.last_sweep');

        if (! $lastSweep || Carbon::parse($lastSweep)->lt(now()->subSeconds(30))) {
            rescue(fn () => $monitor->sweep(), null, false);
        }

        $events = AccessEvent::with(['door', 'camera'])
            ->orderByDesc('happened_at')->limit(8)->get()
            ->map(fn (AccessEvent $event) => [
                'id' => 'event-'.$event->id,
                'icon' => '≡',
                'text' => ($event->person_name ?? 'Unknown').' — '.($event->door?->name ?? 'facility').' ('.($event->result?->label() ?? '—').')',
                'time' => $event->happened_at->format('H:i:s'),
                'at' => $event->happened_at->timestamp,
                'badge' => $event->result?->badge() ?? 'badge-muted',
                'label' => $event->result?->label() ?? '—',
            ])
            ->concat(AiAlert::with('door')->orderByDesc('happened_at')->limit(8)->get()->map(fn (AiAlert $alert) => [
                'id' => 'ai-'.$alert->id,
                'icon' => '✦',
                'text' => $alert->event_type.' — '.$alert->locationLabel(),
                'time' => $alert->happened_at->format('H:i:s'),
                'at' => $alert->happened_at->timestamp,
                'badge' => $alert->risk_level->badge(),
                'label' => $alert->risk_level->label(),
            ]))
            ->sortByDesc('at')
            ->take(10)
            ->values();

        return response()->json([
            'openCount' => AiAlert::open()->count(),
            'todayCount' => AiAlert::today()->count(),
            'criticalToday' => AiAlert::today()->where('risk_level', AiRiskLevel::Critical)->count(),
            'lastSweep' => Setting::get('ai_bot.last_sweep'),
            'items' => $events,
        ]);
    }

    /**
     * Manual "Run AI Scan" action.
     */
    public function scan(AiEventMonitor $monitor): RedirectResponse
    {
        // Sweeps write alerts and send notifications — a manage
        // action, not monitoring; operators watch, admins trigger.
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $created = $monitor->sweep();

        return back()->with('status', "AI scan complete — {$created} new alert(s) generated.");
    }

    /**
     * The AI alert management page.
     */
    public function alerts(Request $request): View
    {
        return view('ai.alerts', [
            'alerts' => $this->filtered($request)->paginate(10)->withQueryString(),
            'stats' => $this->stats(),
            'riskLevels' => AiRiskLevel::cases(),
            'statuses' => AiAlertStatus::cases(),
            'eventTypes' => AiAlert::EVENT_TYPES,
            'cameras' => Camera::orderBy('name')->pluck('name', 'id'),
            'employees' => User::orderBy('first_name')->get()->pluck('name', 'id'),
        ]);
    }

    /**
     * Update an AI alert: review, action, resolve, dismiss.
     */
    public function update(UpdateAiAlertRequest $request, AiAlert $aiAlert): RedirectResponse
    {
        $status = AiAlertStatus::from($request->validated('status'));

        $aiAlert->update([
            'status' => $status,
            'notes' => $request->validated('notes') ?? $aiAlert->notes,
            'reviewed_by' => auth()->id(),
            'resolved_at' => in_array($status, [AiAlertStatus::Resolved, AiAlertStatus::FalsePositive], true)
                ? ($aiAlert->resolved_at ?? now())
                : null,
        ]);

        return back()->with('status', "AI alert {$aiAlert->ai_code} updated — {$status->label()}.");
    }

    /**
     * Quick action: resolve directly from the table.
     */
    public function resolve(AiAlert $aiAlert): RedirectResponse
    {
        $this->authorize('update', $aiAlert);

        $aiAlert->update(['status' => AiAlertStatus::Resolved, 'reviewed_by' => auth()->id(), 'resolved_at' => now()]);

        return back()->with('status', "AI alert {$aiAlert->ai_code} marked as resolved.");
    }

    /**
     * Delete an AI alert. Administrators only.
     */
    public function destroy(AiAlert $aiAlert): RedirectResponse
    {
        $this->authorize('delete', $aiAlert);

        $code = $aiAlert->ai_code;
        $aiAlert->delete();

        return back()->with('status', "AI alert {$code} has been deleted.");
    }

    /**
     * The alert history page: full archive with search and pagination.
     */
    public function history(Request $request): View
    {
        return view('ai.history', [
            'alerts' => $this->filtered($request)->paginate(15)->withQueryString(),
            'riskLevels' => AiRiskLevel::cases(),
            'statuses' => AiAlertStatus::cases(),
            'eventTypes' => AiAlert::EVENT_TYPES,
            'total' => AiAlert::count(),
        ]);
    }

    /**
     * Export the filtered history as CSV (opens in Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        // Exports carry PII — reserved to the roles that manage
        // alerts, matching the policy on every other module's export.
        abort_unless(auth()->user()->role->canManageAlerts(), 403);

        $alerts = $this->filtered($request)->limit(5000)->get();

        return response()->streamDownload(function () use ($alerts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Alert ID', 'Date', 'Time', 'Event Type', 'Risk Level', 'Risk Score', 'Status', 'Location', 'Camera', 'Person', 'Description', 'AI Analysis', 'Recommendation', 'Reviewed By']);

            foreach ($alerts as $alert) {
                fputcsv($out, [
                    $alert->ai_code,
                    $alert->happened_at->format('Y-m-d'),
                    $alert->happened_at->format('H:i:s'),
                    $alert->event_type,
                    $alert->risk_level->label(),
                    $alert->risk_score,
                    $alert->status->label(),
                    $alert->locationLabel(),
                    $alert->camera?->name,
                    $alert->personLabel(),
                    $alert->description,
                    $alert->analysis,
                    $alert->recommendation->label(),
                    $alert->reviewer?->name,
                ]);
            }

            fclose($out);
        }, 'ai-alerts-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Printable daily security report ("Export PDF": the page offers
     * the browser's print-to-PDF, so no PDF package is required).
     */
    public function report(Request $request): View
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : today();
        $alerts = AiAlert::with(['camera', 'user', 'visit', 'reviewer'])
            ->whereBetween('happened_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('happened_at')
            ->get();

        return view('ai.report', [
            'date' => $date,
            'alerts' => $alerts,
            'byLevel' => $alerts->countBy(fn (AiAlert $a) => $a->risk_level->value),
            'camerasOffline' => Camera::where('status', CameraStatus::Offline)->count(),
            'devicesOffline' => Device::where('status', DeviceStatus::Offline)->count(),
            'visitorsInside' => Visit::inside()->count(),
        ]);
    }

    /**
     * The AI Chat Assistant page. Administrators only.
     */
    public function chat(): View
    {
        abort_unless(auth()->user()->role->canUseAiAssistant(), 403);

        return view('ai.chat');
    }

    /**
     * Answer one chat message as JSON. Administrators only.
     */
    public function chatMessage(Request $request, AiChatAssistant $assistant): JsonResponse
    {
        abort_unless(auth()->user()->role->canUseAiAssistant(), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($assistant->answer($data['message']));
    }

    /**
     * Shared filter pipeline for the alerts, history and export pages.
     */
    private function filtered(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return AiAlert::query()
            ->with(['camera', 'device', 'door', 'user', 'visit', 'reviewer'])
            ->search($request->query('search'))
            ->when($request->query('risk'), fn ($q, $v) => $q->where('risk_level', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('event'), fn ($q, $v) => $q->where('event_type', $v))
            ->when($request->query('camera'), fn ($q, $v) => $q->where('camera_id', $v))
            ->when($request->query('employee'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('from'), fn ($q, $v) => $q->where('happened_at', '>=', "{$v} 00:00:00"))
            ->when($request->query('to'), fn ($q, $v) => $q->where('happened_at', '<=', "{$v} 23:59:59"))
            ->orderByDesc('happened_at');
    }

    /**
     * Stat cards for the dashboard and alerts pages.
     *
     * @return array<string, int|string>
     */
    private function stats(): array
    {
        $byLevel = AiAlert::query()->get(['risk_level'])->countBy(fn (AiAlert $a) => $a->risk_level->value);

        // "Accuracy": share of reviewed alerts that were genuine
        // (not dismissed as false positives). Placeholder metric until
        // a real model reports its own confusion matrix.
        $reviewed = AiAlert::whereIn('status', [AiAlertStatus::Resolved, AiAlertStatus::Actioned, AiAlertStatus::FalsePositive])->count();
        $falsePositives = AiAlert::where('status', AiAlertStatus::FalsePositive)->count();

        return [
            'total' => AiAlert::count(),
            'critical' => $byLevel->get('critical', 0),
            'high' => $byLevel->get('high', 0),
            'medium' => $byLevel->get('medium', 0),
            'low' => $byLevel->get('low', 0),
            'today' => AiAlert::today()->count(),
            'open' => AiAlert::open()->count(),
            'accuracy' => $reviewed > 0 ? (int) round(($reviewed - $falsePositives) / $reviewed * 100) : 100,
        ];
    }

    /**
     * Alert counts per risk level (last 7 days), for the ranked bars.
     *
     * @return array<int, array{label: string, count: int, percent: int, badge: string}>
     */
    private function riskDistribution(): array
    {
        $counts = AiAlert::where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['risk_level'])
            ->countBy(fn (AiAlert $a) => $a->risk_level->value);
        $max = max($counts->max() ?? 0, 1);

        return collect(AiRiskLevel::cases())->map(fn (AiRiskLevel $level) => [
            'label' => $level->label(),
            'count' => $counts->get($level->value, 0),
            'percent' => (int) round($counts->get($level->value, 0) / $max * 100),
            'badge' => $level->badge(),
        ])->all();
    }

    /**
     * AI alerts per day over the last seven days.
     *
     * @return array<int, array{label: string, count: int}>
     */
    private function daily(): array
    {
        $since = now()->subDays(6)->startOfDay();
        $byDate = AiAlert::where('happened_at', '>=', $since)->pluck('happened_at')->countBy(fn ($at) => $at->format('Y-m-d'));

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($byDate) {
                $day = now()->subDays($daysAgo);

                return ['label' => $day->format('D'), 'count' => $byDate->get($day->format('Y-m-d'), 0)];
            })
            ->all();
    }

    /**
     * Most frequent event types (last 7 days).
     *
     * @return array<int, array{label: string, count: int, percent: int}>
     */
    private function topEventTypes(): array
    {
        $rows = AiAlert::where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('event_type as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => [
            'label' => $row->k,
            'count' => $row->c,
            'percent' => (int) round($row->c / $max * 100),
        ])->all();
    }
}
