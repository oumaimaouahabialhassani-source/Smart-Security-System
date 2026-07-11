<?php

namespace App\Enums;

enum DoorStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Locked => 'Locked',
            self::Offline => 'Offline',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Open => 'badge-warning',
            self::Closed => 'badge-success',
            self::Locked => 'badge-muted',
            self::Offline => 'badge-danger',
        };
    }
}
