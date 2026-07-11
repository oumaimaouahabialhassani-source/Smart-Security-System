<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Expected = 'expected';
    case Inside = 'inside';
    case CheckedOut = 'checked_out';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Expected => 'Expected',
            self::Inside => 'Inside Building',
            self::CheckedOut => 'Checked Out',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Expected => 'badge-warning',
            self::Inside => 'badge-success',
            self::CheckedOut => 'badge-muted',
            self::Completed => 'badge-muted',
            self::Rejected => 'badge-danger',
        };
    }
}
