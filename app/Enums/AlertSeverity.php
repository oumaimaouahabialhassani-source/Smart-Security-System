<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Information = 'information';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
            self::Information => 'Information',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Critical, self::High => 'badge-danger',
            self::Medium, self::Low => 'badge-warning',
            self::Information => 'badge-success',
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
            self::Information => 4,
        };
    }
}
