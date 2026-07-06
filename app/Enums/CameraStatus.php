<?php

namespace App\Enums;

enum CameraStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Maintenance => 'Maintenance',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Online => 'badge-success',
            self::Offline => 'badge-danger',
            self::Maintenance => 'badge-warning',
        };
    }
}
