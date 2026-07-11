<?php

namespace App\Enums;

enum AlertStatus: string
{
    case New = 'new';
    case Pending = 'pending';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Pending => 'Pending',
            self::Investigating => 'Investigating',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Ignored => 'Ignored',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::New => 'badge-danger',
            self::Pending, self::Investigating => 'badge-warning',
            self::Resolved => 'badge-success',
            self::Closed, self::Ignored => 'badge-muted',
        };
    }

    /**
     * Still needs attention from the security team.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Pending, self::Investigating], true);
    }
}
