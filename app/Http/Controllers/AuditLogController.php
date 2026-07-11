<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * The audit trail: stats, charts, filters and the log table.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role->canViewAuditLogs(), 403);

        $logs = $this->filtered($request)
            ->with('user')
            ->orderByDesc('happened_at')
            ->paginate(12)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'stats' => $this->stats(),
            'daily' => $daily = $this->daily(),
            'maxDaily' => max(max(array_column($daily, 'count')), 1),
            'byModule' => $this->topList('module'),
            'topUsers' => $this->topList('user_name'),
            'successRate' => $this->successRate(),
            'modules' => AuditLog::distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('first_name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Export the filtered logs as CSV (opens in Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role->canViewAuditLogs(), 403);

        $logs = $this->filtered($request)->orderByDesc('happened_at')->limit(10000)->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Log ID', 'Date', 'Time', 'User', 'Role', 'Module', 'Action', 'Status', 'Description', 'IP Address', 'Browser', 'OS', 'Device', 'Method', 'URL']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->happened_at->format('Y-m-d'),
                    $log->happened_at->format('H:i:s'),
                    $log->user_name ?? 'System',
                    $log->user_role,
                    $log->module,
                    $log->action,
                    $log->status,
                    $log->description,
                    $log->ip_address,
                    $log->browser,
                    $log->operating_system,
                    $log->device_type,
                    $log->http_method,
                    $log->url,
                ]);
            }

            fclose($out);
        }, 'audit-logs-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The shared filter pipeline for the table and the export.
     */
    private function filtered(Request $request)
    {
        return AuditLog::query()
            ->search($request->query('search'))
            ->when($request->query('user'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('module'), fn ($q, $v) => $q->where('module', $v))
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('role'), fn ($q, $v) => $q->where('user_role', $v))
            ->when($request->query('ip'), fn ($q, $v) => $q->where('ip_address', 'like', "%{$v}%"))
            ->when($request->query('from'), fn ($q, $v) => $q->where('happened_at', '>=', "{$v} 00:00:00"))
            ->when($request->query('to'), fn ($q, $v) => $q->where('happened_at', '<=', "{$v} 23:59:59"));
    }

    /**
     * Headline stat cards.
     *
     * @return array<int, array{label: string, value: int|string, meta: string}>
     */
    private function stats(): array
    {
        $mostActive = AuditLog::today()
            ->whereNotNull('user_name')
            ->selectRaw('user_name as k, count(*) as c')
            ->groupBy('k')->orderByDesc('c')->first();

        return [
            ['label' => 'Total Logs', 'value' => AuditLog::count(), 'meta' => 'All time'],
            ['label' => "Today's Logs", 'value' => AuditLog::today()->count(), 'meta' => now()->format('l, M j')],
            ['label' => 'Successful Activities', 'value' => AuditLog::today()->where('status', 'success')->count(), 'meta' => 'Today'],
            ['label' => 'Failed Activities', 'value' => AuditLog::today()->where('status', 'failed')->count(), 'meta' => 'Today'],
            ['label' => 'Logins Today', 'value' => AuditLog::today()->where('action', 'Login')->count(), 'meta' => AuditLog::today()->where('action', 'Failed Login')->count().' failed attempts'],
            ['label' => 'Most Active User', 'value' => $mostActive->k ?? '—', 'meta' => $mostActive ? $mostActive->c.' actions today' : 'No activity yet'],
        ];
    }

    /**
     * Activities per day over the last seven days.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function daily(): array
    {
        $since = now()->subDays(6)->startOfDay();
        $byDate = AuditLog::where('happened_at', '>=', $since)
            ->selectRaw('date(happened_at) as d, count(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($byDate) {
                $day = now()->subDays($daysAgo);

                return ['day' => $day->format('D'), 'count' => (int) $byDate->get($day->format('Y-m-d'), 0)];
            })
            ->all();
    }

    /**
     * Top-5 ranking for a column (last 7 days).
     *
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    private function topList(string $column): Collection
    {
        $rows = AuditLog::where('happened_at', '>=', now()->subDays(6)->startOfDay())
            ->whereNotNull($column)
            ->selectRaw("{$column} as k, count(*) as c")
            ->groupBy('k')->orderByDesc('c')->limit(5)->get();

        $max = max($rows->max('c') ?? 0, 1);

        return $rows->map(fn ($row) => ['label' => $row->k, 'count' => $row->c, 'percent' => (int) round($row->c / $max * 100)]);
    }

    /**
     * Success vs failed ratio for the donut (last 7 days).
     *
     * @return array{percent: int, success: int, failed: int, total: int}
     */
    private function successRate(): array
    {
        $since = now()->subDays(6)->startOfDay();
        $total = AuditLog::where('happened_at', '>=', $since)->count();
        $success = AuditLog::where('happened_at', '>=', $since)->where('status', 'success')->count();

        return [
            'percent' => $total > 0 ? (int) round($success / $total * 100) : 100,
            'success' => $success,
            'failed' => $total - $success,
            'total' => $total,
        ];
    }
}
