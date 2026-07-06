<?php

namespace App\Enums;

enum SignalStrength: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Weak = 'weak';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Excellent',
            self::Good => 'Good',
            self::Weak => 'Weak',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Excellent => 'badge-success',
            self::Good => 'badge-warning',
            self::Weak => 'badge-danger',
        };
    }
}
