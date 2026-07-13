<?php

namespace App\Enums;

enum AiAlertStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Actioned = 'actioned';
    case Resolved = 'resolved';
    case FalsePositive = 'false_positive';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Reviewing',
            self::Actioned => 'Actioned',
            self::Resolved => 'Resolved',
            self::FalsePositive => 'False Positive',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::New => 'badge-danger',
            self::Reviewing, self::Actioned => 'badge-warning',
            self::Resolved => 'badge-success',
            self::FalsePositive => 'badge-muted',
        };
    }

    /**
     * Still needs attention from the security team.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Reviewing], true);
    }
}
