<?php

namespace App\Services;

use App\Enums\AccessResult;
use App\Enums\AiRiskLevel;
use App\Enums\BiometricResult;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AccessEvent;
use App\Models\AiAlert;
use App\Models\Alert;
use App\Models\BiometricVerification;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Notifications\AiBotAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * The AI Security Bot's continuous monitoring loop.
 *
 * Each sweep scans everything the platform recorded since the last
 * sweep (access events, raised alerts, visit movements, biometric
 * verifications), pushes every event through the AiRiskAnalyzer and
 * persists an AiAlert per noteworthy finding. Routine low-risk
 * traffic (a granted badge swipe during office hours) is analyzed
 * but not stored, so the alert table stays signal, not noise.
 *
 * Sweeps are triggered by the dashboard's live feed poll and by the
 * manual "Run AI Scan" action, so no queue worker or cron is needed
 * in development; the sweep is idempotent (source unique key) and
 * cheap to run repeatedly.
 */
class AiEventMonitor
{
    private const LAST_SWEEP_KEY = 'ai_bot.last_sweep';

    public function __construct(private AiRiskAnalyzer $analyzer)
    {
    }

    /**
     * Scan events recorded since the last sweep. Returns the number
     * of AI alerts created.
     */
    public function sweep(): int
    {
        $since = Setting::get(self::LAST_SWEEP_KEY)
            ? Carbon::parse(Setting::get(self::LAST_SWEEP_KEY))
            : now()->subDay();

        // Never rescan more than 7 days (first run on an old database).
        $since = $since->max(now()->subDays(7));

        $created = 0;
        $created += $this->scanAccessEvents($since);
        $created += $this->scanSystemAlerts($since);
        $created += $this->scanVisits($since);
        $created += $this->scanBiometrics($since);

        Setting::set(self::LAST_SWEEP_KEY, now()->toDateTimeString());

        return $created;
    }

    /**
     * Door and badge traffic: check ins/outs, denials, unknown faces.
     */
    private function scanAccessEvents(Carbon $since): int
    {
        $created = 0;

        AccessEvent::with(['door', 'camera', 'device', 'user', 'visit'])
            ->where('happened_at', '>', $since)
            ->orderBy('happened_at')
            ->limit(500)
            ->get()
            ->each(function (AccessEvent $event) use (&$created) {
                $eventType = $this->classifyAccessEvent($event);
                if ($eventType === null) {
                    return;
                }

                $repeats = $event->result === AccessResult::Granted ? 0
                    : AccessEvent::where('person_name', $event->person_name)
                        ->whereNot('result', AccessResult::Granted)
                        ->where('happened_at', '>=', $event->happened_at->copy()->subDay())
                        ->where('happened_at', '<=', $event->happened_at)
                        ->count();

                $verdict = $this->analyzer->analyze($eventType, [
                    'happened_at' => $event->happened_at,
                    'location' => $event->door?->name,
                    'repeat_count' => $repeats,
                ]);

                $created += (int) $this->record($eventType, $verdict, [
                    'description' => trim(($event->person_name ?? 'Unknown subject').' — '.($event->detail ?: $event->result?->label()).' at '.($event->door?->name ?? 'facility').'.'),
                    'camera_id' => $event->camera_id,
                    'device_id' => $event->device_id,
                    'door_id' => $event->door_id,
                    'user_id' => $event->user_id,
                    'visit_id' => $event->visit_id,
                    'location' => $event->door?->name,
                    'happened_at' => $event->happened_at,
                    'source_type' => AccessEvent::class,
                    'source_id' => $event->id,
                ]);
            });

        return $created;
    }

    /**
     * Map an access event onto the bot's event taxonomy. Returns null
     * for events not worth analyzing.
     */
    private function classifyAccessEvent(AccessEvent $event): ?string
    {
        if ($event->result === AccessResult::Granted) {
            $hour = $event->happened_at->hour;

            // Routine office-hours movement is monitored live but not alerted.
            if ($hour >= 5 && $hour < 22) {
                return null;
            }

            return 'After-Hours Activity';
        }

        return match ($event->result) {
            AccessResult::FaceNotRecognized => 'Unknown Face Detection',
            AccessResult::FingerprintFailed => 'Face Recognition Failed',
            default => 'Unauthorized Door Access',
        };
    }

    /**
     * Hardware and platform alerts raised by the rest of the system.
     */
    private function scanSystemAlerts(Carbon $since): int
    {
        $map = [
            'Camera Offline' => 'Camera Offline',
            'Camera Tampering' => 'Camera Offline',
            'IoT Device Offline' => 'IoT Device Offline',
            'Motion Detected' => 'Motion Detection',
            'Unknown Face Detected' => 'Unknown Face Detection',
            'Multiple Failed Login Attempts' => 'Multiple Failed Login Attempts',
            'Door Forced Open' => 'Unauthorized Door Access',
            'Door Left Open' => 'Door Open',
            'Unauthorized Access' => 'Unauthorized Door Access',
        ];

        $created = 0;

        Alert::with(['camera', 'device', 'door'])
            ->where('happened_at', '>', $since)
            ->whereIn('type', array_keys($map))
            ->orderBy('happened_at')
            ->limit(500)
            ->get()
            ->each(function (Alert $alert) use (&$created, $map) {
                $eventType = $map[$alert->type];

                $verdict = $this->analyzer->analyze($eventType, [
                    'happened_at' => $alert->happened_at,
                    'location' => $alert->locationLabel(),
                    'coverage_gap' => in_array($eventType, ['Camera Offline', 'IoT Device Offline'], true)
                        && Alert::open()->whereNot('id', $alert->id)->exists(),
                ]);

                $created += (int) $this->record($eventType, $verdict, [
                    'description' => $alert->description,
                    'camera_id' => $alert->camera_id,
                    'device_id' => $alert->device_id,
                    'door_id' => $alert->door_id,
                    'user_id' => $alert->user_id,
                    'visit_id' => $alert->visit_id,
                    'location' => $alert->locationLabel(),
                    'building' => $alert->building,
                    'floor' => $alert->floor,
                    'happened_at' => $alert->happened_at,
                    'source_type' => Alert::class,
                    'source_id' => $alert->id,
                ]);
            });

        return $created;
    }

    /**
     * Visitor movements: registrations, exits, blacklisted arrivals.
     */
    private function scanVisits(Carbon $since): int
    {
        $created = 0;

        Visit::where(fn ($q) => $q->where('checked_in_at', '>', $since)->orWhere('checked_out_at', '>', $since))
            ->limit(200)
            ->get()
            ->each(function (Visit $visit) use (&$created, $since) {
                if ($visit->checked_in_at?->gt($since)) {
                    $eventType = $visit->blacklisted ? 'Blacklisted Visitor' : 'Visitor Registration';

                    $verdict = $this->analyzer->analyze($eventType, [
                        'happened_at' => $visit->checked_in_at,
                        'blacklisted' => (bool) $visit->blacklisted,
                    ]);

                    $created += (int) $this->record($eventType, $verdict, [
                        'description' => "{$visit->full_name} ({$visit->company}) checked in — purpose: {$visit->purpose}.",
                        'visit_id' => $visit->id,
                        'happened_at' => $visit->checked_in_at,
                        'source_type' => Visit::class,
                        'source_id' => $visit->id,
                    ]);
                }

                if ($visit->checked_out_at?->gt($since)) {
                    $verdict = $this->analyzer->analyze('Visitor Exit', [
                        'happened_at' => $visit->checked_out_at,
                    ]);

                    $created += (int) $this->record('Visitor Exit', $verdict, [
                        'description' => "{$visit->full_name} checked out after {$visit->checked_in_at?->diffForHumans($visit->checked_out_at, true)}.",
                        'visit_id' => $visit->id,
                        'happened_at' => $visit->checked_out_at,
                        'source_type' => Visit::class,
                        'source_id' => $visit->id,
                    ]);
                }
            });

        return $created;
    }

    /**
     * Biometric verifications: failed faces/fingerprints at readers.
     */
    private function scanBiometrics(Carbon $since): int
    {
        $created = 0;

        BiometricVerification::with('profile.user')
            ->where('happened_at', '>', $since)
            ->whereIn('result', [BiometricResult::Failed, BiometricResult::Warning])
            ->limit(200)
            ->get()
            ->each(function (BiometricVerification $verification) use (&$created) {
                $eventType = $verification->result === BiometricResult::Failed
                    ? 'Face Recognition Failed'
                    : 'Motion Detection';

                $repeats = BiometricVerification::where('biometric_profile_id', $verification->biometric_profile_id)
                    ->where('result', BiometricResult::Failed)
                    ->where('happened_at', '>=', $verification->happened_at->copy()->subDay())
                    ->where('happened_at', '<=', $verification->happened_at)
                    ->count();

                $verdict = $this->analyzer->analyze($eventType, [
                    'happened_at' => $verification->happened_at,
                    'repeat_count' => $repeats,
                ]);

                $created += (int) $this->record($eventType, $verdict, [
                    'description' => trim(($verification->subject_name ?? 'Unknown subject').' — '.($verification->detail ?: 'biometric verification issue').'.'),
                    'user_id' => $verification->profile?->user_id,
                    'happened_at' => $verification->happened_at,
                    'source_type' => BiometricVerification::class,
                    'source_id' => $verification->id,
                ]);
            });

        return $created;
    }

    /**
     * Persist one finding (idempotently) and fan out notifications.
     *
     * @param array{risk_level: AiRiskLevel, risk_score: int, analysis: string, recommendation: \App\Enums\AiRecommendation} $verdict
     * @param array<string, mixed> $attributes
     */
    private function record(string $eventType, array $verdict, array $attributes): bool
    {
        // The unique (source_type, source_id, event_type) key makes
        // re-sweeping the same window a no-op.
        if (isset($attributes['source_type'], $attributes['source_id'])
            && AiAlert::where('source_type', $attributes['source_type'])
                ->where('source_id', $attributes['source_id'])
                ->where('event_type', $eventType)
                ->exists()) {
            return false;
        }

        $alert = AiAlert::create($attributes + [
            'event_type' => $eventType,
            'risk_level' => $verdict['risk_level'],
            'risk_score' => $verdict['risk_score'],
            'analysis' => $verdict['analysis'],
            'recommendation' => $verdict['recommendation'],
        ]);

        $this->notify($alert);

        return true;
    }

    /**
     * Send the alert through every configured channel. High and
     * critical findings notify the security staff; SMS and WhatsApp
     * are placeholders (logged) until a provider is wired in.
     */
    public function notify(AiAlert $alert): void
    {
        if (! in_array($alert->risk_level, [AiRiskLevel::High, AiRiskLevel::Critical], true)) {
            return;
        }

        $recipients = User::where('status', UserStatus::Active)
            ->whereIn('role', UserRole::monitoringRoles())
            ->get();

        // rescue(): a notification failure must never break the sweep.
        rescue(fn () => Notification::send($recipients, new AiBotAlert($alert)), null, false);

        // SMS / WhatsApp placeholders: swap for Vonage/Twilio channels later.
        Log::info('[AI Security Bot] SMS placeholder', ['alert' => $alert->ai_code, 'event' => $alert->event_type, 'risk' => $alert->risk_level->value]);
        Log::info('[AI Security Bot] WhatsApp placeholder', ['alert' => $alert->ai_code, 'event' => $alert->event_type, 'risk' => $alert->risk_level->value]);

        $alert->forceFill(['notified_channels' => ['dashboard', 'email', 'sms:placeholder', 'whatsapp:placeholder']])->saveQuietly();
    }
}
