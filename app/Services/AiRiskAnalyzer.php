<?php

namespace App\Services;

use App\Enums\AiRecommendation;
use App\Enums\AiRiskLevel;
use Illuminate\Support\Carbon;

/**
 * Rule-based risk scoring engine behind the AI Security Bot.
 *
 * Every monitored event receives a 0-100 risk score built from a
 * baseline per event type plus contextual modifiers (time of day,
 * repetition, blacklist, sensitive zones). The score maps onto the
 * four AiRiskLevel bands and drives the recommended action, and each
 * triggered factor is kept so the alert can explain its own verdict.
 */
class AiRiskAnalyzer
{
    /**
     * Baseline risk score per monitored event type.
     *
     * @var array<string, int>
     */
    private const BASELINES = [
        'Employee Check In' => 5,
        'Employee Check Out' => 5,
        'Visitor Registration' => 15,
        'Visitor Exit' => 10,
        'Door Open' => 15,
        'Door Closed' => 5,
        'Unauthorized Door Access' => 70,
        'Camera Offline' => 55,
        'IoT Device Offline' => 45,
        'Multiple Failed Login Attempts' => 65,
        'Face Recognition Success' => 5,
        'Face Recognition Failed' => 45,
        'Unknown Face Detection' => 70,
        'Motion Detection' => 35,
        'Blacklisted Visitor' => 85,
        'After-Hours Activity' => 50,
    ];

    /**
     * Zones where any anomaly is escalated.
     *
     * @var list<string>
     */
    private const SENSITIVE_ZONES = ['Server Room', 'Laboratory', 'Data Center', 'Vault'];

    /**
     * Analyze one event and return the bot's verdict.
     *
     * Supported context keys: happened_at (Carbon), location (string),
     * repeat_count (int, occurrences of the same failure in 24h),
     * blacklisted (bool), person (string), detail (string),
     * coverage_gap (bool, camera/device loss overlaps other incidents).
     *
     * @param array<string, mixed> $context
     * @return array{risk_level: AiRiskLevel, risk_score: int, analysis: string, recommendation: AiRecommendation}
     */
    public function analyze(string $eventType, array $context = []): array
    {
        $score = self::BASELINES[$eventType] ?? 30;
        $factors = ["Baseline risk for \"{$eventType}\" events."];

        $happenedAt = $context['happened_at'] ?? now();
        $happenedAt = $happenedAt instanceof Carbon ? $happenedAt : Carbon::parse($happenedAt);

        // Anything between 22:00 and 05:00 is outside normal operating hours.
        if ($happenedAt->hour >= 22 || $happenedAt->hour < 5) {
            $score += 20;
            $factors[] = 'Occurred after hours ('.$happenedAt->format('H:i').'), outside the 05:00-22:00 operating window.';
        }

        if (($repeat = (int) ($context['repeat_count'] ?? 0)) >= 3) {
            $score += min(25, 5 * $repeat);
            $factors[] = "Repeated pattern: {$repeat} similar events within 24 hours.";
        }

        if (! empty($context['blacklisted'])) {
            $score += 30;
            $factors[] = 'Subject is on the blacklist.';
        }

        $location = (string) ($context['location'] ?? '');
        foreach (self::SENSITIVE_ZONES as $zone) {
            if ($location !== '' && str_contains(strtolower($location), strtolower($zone))) {
                $score += 15;
                $factors[] = "Involves a sensitive zone ({$zone}).";
                break;
            }
        }

        if (! empty($context['coverage_gap'])) {
            $score += 10;
            $factors[] = 'Creates a monitoring coverage gap while other incidents are open.';
        }

        $score = max(0, min(100, $score));
        $level = AiRiskLevel::fromScore($score);

        return [
            'risk_level' => $level,
            'risk_score' => $score,
            'analysis' => implode(' ', $factors)." Overall risk score {$score}/100 → {$level->label()}.",
            'recommendation' => $this->recommend($eventType, $level),
        ];
    }

    /**
     * Pick the action the security team should take.
     */
    private function recommend(string $eventType, AiRiskLevel $level): AiRecommendation
    {
        if ($level === AiRiskLevel::Low) {
            return AiRecommendation::Ignore;
        }

        $byType = match ($eventType) {
            'Unauthorized Door Access', 'Blacklisted Visitor' => AiRecommendation::LockDoor,
            'Unknown Face Detection', 'Motion Detection' => AiRecommendation::ReviewFootage,
            'Face Recognition Failed', 'Employee Check In', 'Employee Check Out', 'After-Hours Activity' => AiRecommendation::VerifyIdentity,
            'Camera Offline', 'IoT Device Offline' => AiRecommendation::DispatchTechnician,
            'Multiple Failed Login Attempts' => AiRecommendation::ContactAdministrator,
            default => AiRecommendation::NotifySecurityTeam,
        };

        // Critical events always page the administrator, except physical
        // breaches where locking down comes first.
        if ($level === AiRiskLevel::Critical && $byType !== AiRecommendation::LockDoor) {
            return AiRecommendation::ContactAdministrator;
        }

        return $byType;
    }
}
