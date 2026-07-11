<?php

namespace App\Enums;

enum BiometricStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Active => 'badge-success',
            self::Suspended => 'badge-danger',
        };
    }
}
