<?php

namespace App\Enums;

enum EventSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Low => 'badge-muted',
            self::Medium => 'badge-warning',
            self::High => 'badge-danger',
            self::Critical => 'badge-danger',
        };
    }
}
