<?php

namespace App\Enums;

enum AiRiskLevel: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Critical, self::High => 'badge-danger',
            self::Medium => 'badge-warning',
            self::Low => 'badge-success',
        };
    }

    /**
     * Sort weight: most urgent first.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }

    /**
     * Resolve a level from the analyzer's 0-100 risk score.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::Critical,
            $score >= 60 => self::High,
            $score >= 35 => self::Medium,
            default => self::Low,
        };
    }
}
