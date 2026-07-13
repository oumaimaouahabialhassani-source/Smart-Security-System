<?php

namespace App\Services;

use App\Enums\AccessResult;
use App\Enums\AiRiskLevel;
use App\Enums\CameraStatus;
use App\Enums\DeviceStatus;
use App\Models\AccessEvent;
use App\Models\AiAlert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Visit;

/**
 * Administrator-only conversational assistant.
 *
 * Intent matching is keyword-based and every answer is computed from
 * live database queries, so the assistant never hallucinates: it
 * either knows the answer from the data or says what it can do.
 * Swap answer() for an LLM call later without touching the UI.
 */
class AiChatAssistant
{
    /**
     * Answer one question. Returns the reply plus optional table rows
     * the UI renders under the message.
     *
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    public function answer(string $question): array
    {
        $q = strtolower($question);

        return match (true) {
            str_contains($q, 'critical') => $this->criticalAlerts(),
            str_contains($q, 'camera') && (str_contains($q, 'offline') || str_contains($q, 'down')) => $this->offlineCameras(),
            str_contains($q, 'device') && (str_contains($q, 'offline') || str_contains($q, 'down')) => $this->offlineDevices(),
            str_contains($q, 'unknown face') || str_contains($q, 'unknown faces') => $this->unknownFaces(),
            str_contains($q, 'suspicious') => $this->suspiciousEmployees(),
            str_contains($q, 'visitor') && (str_contains($q, 'inside') || str_contains($q, 'in the building')) => $this->visitorsInside(),
            str_contains($q, 'report') => $this->report(),
            str_contains($q, 'summar') || str_contains($q, 'overview') || str_contains($q, 'today') => $this->summary(),
            str_contains($q, 'help') || str_contains($q, 'what can you') => $this->help(),
            default => $this->fallback(),
        };
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function criticalAlerts(): array
    {
        $alerts = AiAlert::today()->where('risk_level', AiRiskLevel::Critical)->orderByDesc('happened_at')->limit(10)->get();

        return [
            'reply' => $alerts->isEmpty()
                ? 'Good news — no critical AI alerts were raised today.'
                : "I found {$alerts->count()} critical alert(s) today. Here are the details:",
            'rows' => $alerts->map(fn (AiAlert $a) => [
                'Alert' => $a->ai_code,
                'Event' => $a->event_type,
                'Location' => $a->locationLabel(),
                'Time' => $a->happened_at->format('H:i'),
                'Recommendation' => $a->recommendation->label(),
            ])->all(),
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function offlineCameras(): array
    {
        $cameras = Camera::where('status', CameraStatus::Offline)->orderBy('name')->get();

        return [
            'reply' => $cameras->isEmpty()
                ? 'All cameras are currently online.'
                : "{$cameras->count()} camera(s) are offline right now. Consider dispatching a technician:",
            'rows' => $cameras->map(fn (Camera $c) => [
                'Camera' => $c->name,
                'Location' => $c->location,
                'Zone' => $c->zone ?? '—',
                'Last Seen' => $c->last_seen?->diffForHumans() ?? 'Never',
            ])->all(),
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function offlineDevices(): array
    {
        $devices = Device::where('status', DeviceStatus::Offline)->orderBy('name')->get();

        return [
            'reply' => $devices->isEmpty()
                ? 'All IoT devices are currently online.'
                : "{$devices->count()} IoT device(s) are offline:",
            'rows' => $devices->map(fn (Device $d) => [
                'Device' => $d->name,
                'Type' => $d->type->label(),
                'Location' => $d->placement(),
                'Last Seen' => $d->last_seen?->diffForHumans() ?? 'Never',
            ])->all(),
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function unknownFaces(): array
    {
        $events = AccessEvent::where('result', AccessResult::FaceNotRecognized)
            ->whereBetween('happened_at', [today(), today()->endOfDay()])
            ->with(['door', 'camera'])
            ->orderByDesc('happened_at')
            ->get();

        return [
            'reply' => $events->isEmpty()
                ? 'No unknown faces were detected today.'
                : "{$events->count()} unknown face detection(s) today. Review the footage for each:",
            'rows' => $events->map(fn (AccessEvent $e) => [
                'Time' => $e->happened_at->format('H:i:s'),
                'Location' => $e->door?->name ?? 'Facility',
                'Camera' => $e->camera?->name ?? '—',
                'Detail' => $e->detail ?? 'Face not recognized',
            ])->all(),
        ];
    }

    /**
     * People accumulating access denials in the last 24 hours.
     *
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function suspiciousEmployees(): array
    {
        $rows = AccessEvent::whereNot('result', AccessResult::Granted)
            ->where('happened_at', '>=', now()->subDay())
            ->selectRaw('person_name, count(*) as c, max(happened_at) as last_at')
            ->groupBy('person_name')
            ->havingRaw('count(*) >= 2')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        return [
            'reply' => $rows->isEmpty()
                ? 'No one shows a suspicious access pattern in the last 24 hours.'
                : "These people accumulated repeated access denials in the last 24 hours — verify their identity and badge:",
            'rows' => $rows->map(fn ($r) => [
                'Person' => $r->person_name ?? 'Unknown',
                'Denials (24h)' => (string) $r->c,
                'Last Attempt' => \Illuminate\Support\Carbon::parse($r->last_at)->format('H:i'),
            ])->all(),
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function visitorsInside(): array
    {
        $visits = Visit::inside()->with('host')->get();

        return [
            'reply' => $visits->isEmpty()
                ? 'No visitors are inside the building right now.'
                : "{$visits->count()} visitor(s) currently inside:",
            'rows' => $visits->map(fn (Visit $v) => [
                'Visitor' => $v->full_name,
                'Company' => $v->company ?? '—',
                'Host' => $v->host?->name ?? '—',
                'Checked In' => $v->checked_in_at?->format('H:i') ?? '—',
            ])->all(),
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function summary(): array
    {
        $total = AiAlert::today()->count();
        $byLevel = AiAlert::today()->get()->countBy(fn (AiAlert $a) => $a->risk_level->value);
        $open = AiAlert::today()->open()->count();
        $camerasOffline = Camera::where('status', CameraStatus::Offline)->count();
        $devicesOffline = Device::where('status', DeviceStatus::Offline)->count();
        $inside = Visit::inside()->count();

        return [
            'reply' => "Today's security picture: {$total} AI alert(s) — "
                .($byLevel->get('critical', 0)).' critical, '
                .($byLevel->get('high', 0)).' high, '
                .($byLevel->get('medium', 0)).' medium, '
                .($byLevel->get('low', 0)).' low. '
                ."{$open} still open. Infrastructure: {$camerasOffline} camera(s) and {$devicesOffline} IoT device(s) offline. "
                ."{$inside} visitor(s) currently inside the building."
                .($byLevel->get('critical', 0) > 0 ? ' Priority: handle the critical alerts first — ask me "show today\'s critical alerts".' : ' No critical findings — situation nominal.'),
            'rows' => [],
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function report(): array
    {
        $summary = $this->summary();

        return [
            'reply' => $summary['reply'].' I have prepared today\'s full security report — use the "Print Report" button above the chat, or open '.route('ai.report').' to view and print it as PDF.',
            'rows' => [],
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function help(): array
    {
        return [
            'reply' => "I can answer questions about your live security data. Try:\n"
                ."• Show today's critical alerts\n"
                .'• Which cameras are offline?'."\n"
                .'• List unknown faces detected today'."\n"
                .'• Show suspicious employees'."\n"
                .'• Which visitors are inside?'."\n"
                ."• Summarize today's security events\n"
                ."• Generate today's security report",
            'rows' => [],
        ];
    }

    /**
     * @return array{reply: string, rows: array<int, array<string, string>>}
     */
    private function fallback(): array
    {
        return [
            'reply' => "I didn't recognize that request. I currently answer questions about critical alerts, offline cameras and devices, unknown faces, suspicious employees, visitors inside, daily summaries and reports. Type \"help\" to see examples.",
            'rows' => [],
        ];
    }
}
